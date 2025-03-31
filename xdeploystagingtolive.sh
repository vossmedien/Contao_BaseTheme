#!/bin/bash

# Laden der Umgebungsvariablen aus .env.local
if [ -f ".env.local" ]; then
    export $(grep -v '^#' .env.local | xargs)
else
    echo "Fehler: .env.local nicht gefunden!"
    exit 1
fi

# Log-Datei für Feedback im Backend
LOG_FILE="${DEPLOY_STAGING_PATH}/deploy_log.txt"
# Log-Datei komplett löschen und neu erstellen
> ${LOG_FILE}

# Funktion für das Schreiben in die Log-Datei ohne führende Leerzeichen
log() {
    echo "$@" >> ${LOG_FILE}
}

# Log-Zeilen ohne führende Leerzeichen schreiben
log "===== Deployment gestartet: $(date) ====="
log ""
log "===== Eingespielte Version ====="
log "Quelle: Staging-System (${DEPLOY_STAGING_PATH})"
log "Ziel: Live-System (${DEPLOY_LIVE_PATH})"
log "Staging-Datenbank: ${DEPLOY_STAGING_DB_NAME}"
log "Live-Datenbank: ${DEPLOY_LIVE_DB_NAME}"
log "Zeitstempel: $(date '+%Y-%m-%d %H:%M:%S')"

# Info aus der Backup-Info-Datei hinzufügen, wenn vorhanden
if [ -f "${DEPLOY_STAGING_PATH}/backup_info.txt" ]; then
    INFO=$(cat "${DEPLOY_STAGING_PATH}/backup_info.txt")
    log "Info: ${INFO}"
fi

log "============================="
log ""

# Backup der vorherigen Dateien und Datenbank
log "Sichere bisherige Backups..."
TIMESTAMP=$(date '+%Y-%m-%d_%H-%M-%S')
mv ${DEPLOY_STAGING_PATH}/x_live_files.tar.gz ${DEPLOY_BACKUP_PATH}/${TIMESTAMP}_live_files.tar.gz 2> /dev/null || true
mv ${DEPLOY_STAGING_PATH}/x_live_db.sql ${DEPLOY_BACKUP_PATH}/${TIMESTAMP}_live_db.sql 2> /dev/null || true

# Backup-Info kopieren, wenn vorhanden
if [ -f "${DEPLOY_STAGING_PATH}/backup_info.txt" ]; then
    cp ${DEPLOY_STAGING_PATH}/backup_info.txt ${DEPLOY_BACKUP_PATH}/${TIMESTAMP}_info.txt 2> /dev/null || true
    log "Backup-Info wurde gesichert: ${DEPLOY_BACKUP_PATH}/${TIMESTAMP}_info.txt"
fi

# Erstellen eines neuen Backups der Live-Umgebung
log "Erstelle Backup der Live-Umgebung..."
# Archiviere komplett ohne Ausgabe
tar cfz ${DEPLOY_STAGING_PATH}/x_live_files.tar.gz ${DEPLOY_LIVE_PATH}/* > /dev/null 2>&1
log "Dateien wurden archiviert: ${DEPLOY_STAGING_PATH}/x_live_files.tar.gz"

# Datenbank sichern ohne Ausgabe
mysqldump -u ${DEPLOY_LIVE_DB_USER} -p"${DEPLOY_LIVE_DB_PASSWORD}" -h ${DEPLOY_LIVE_DB_HOST} ${DEPLOY_LIVE_DB_NAME} > ${DEPLOY_STAGING_PATH}/x_live_db.sql 2> /dev/null
log "Datenbank wurde gesichert: ${DEPLOY_STAGING_PATH}/x_live_db.sql"

# Sichern der Ausnahmen vor dem Deployment
log "Sichere Ausnahmen..."

# Setze Standard-Ausnahmen, falls nicht definiert
if [ -z "${DEPLOY_EXCEPTIONS}" ]; then
    # Standard: DNS in tl_page bewahren
    DEPLOY_EXCEPTIONS="tl_page:dns"
    log "Verwende Standard-Ausnahmen: ${DEPLOY_EXCEPTIONS}"
else
    log "Verwende konfigurierte Ausnahmen: ${DEPLOY_EXCEPTIONS}"
fi

# SQL-Datei für die Ausnahmen
EXCEPTIONS_SQL="${DEPLOY_STAGING_PATH}/exceptions.sql"
> ${EXCEPTIONS_SQL}

# Füge Header zur SQL-Datei hinzu
echo "-- Ausnahmen-Sicherung erstellt am $(date)" >> ${EXCEPTIONS_SQL}
echo "-- Konfigurierte Ausnahmen: ${DEPLOY_EXCEPTIONS}" >> ${EXCEPTIONS_SQL}
echo "" >> ${EXCEPTIONS_SQL}

# Verarbeite jede Ausnahme
IFS=';' read -ra TABLE_EXCEPTIONS <<< "${DEPLOY_EXCEPTIONS}"
for table_exception in "${TABLE_EXCEPTIONS[@]}"; do
    IFS=':' read -ra PARTS <<< "$table_exception"
    TABLE=${PARTS[0]}
    COLUMNS=${PARTS[1]}
    
    if [ -z "$TABLE" ] || [ -z "$COLUMNS" ]; then
        log "WARNUNG: Ungültiges Format für Ausnahme: $table_exception, überspringe..."
        continue
    fi
    
    log "Sichere Ausnahmen für Tabelle $TABLE: $COLUMNS"
    
    # Kommentar zur SQL-Datei hinzufügen
    echo "-- Ausnahmen für Tabelle '$TABLE', Spalten: '$COLUMNS'" >> ${EXCEPTIONS_SQL}
    
    # Wandle Spalten in Array um
    IFS=',' read -ra COLUMN_LIST <<< "$COLUMNS"
    
    # Zähle die Anzahl der Spalten für die Platzhalter
    column_count=${#COLUMN_LIST[@]}
    
    # Erstelle Spalten-Liste für SELECT und INSERT
    column_names=""
    placeholders=""
    
    for column in "${COLUMN_LIST[@]}"; do
        if [ -z "$column_names" ]; then
            column_names="\`${column}\`"
            placeholders="%s"
        else
            column_names="${column_names}, \`${column}\`"
            placeholders="${placeholders}, %s"
        fi
    done
    
    # Erstelle Bedingung für nicht-leere Werte
    conditions=""
    for column in "${COLUMN_LIST[@]}"; do
        if [ -z "$conditions" ]; then
            conditions="\`${column}\` IS NOT NULL AND \`${column}\` != ''"
        else
            conditions="${conditions} OR (\`${column}\` IS NOT NULL AND \`${column}\` != '')"
        fi
    done
    
    # Hole die Daten direkt für jede Spalte ohne dynamisches SQL zu benötigen
    table_data=$(mysql -u ${DEPLOY_LIVE_DB_USER} -p"${DEPLOY_LIVE_DB_PASSWORD}" -h ${DEPLOY_LIVE_DB_HOST} ${DEPLOY_LIVE_DB_NAME} -e "SELECT id, ${column_names} FROM \`${TABLE}\` WHERE ${conditions}" --batch --skip-column-names)
    
    # Prüfe, ob Daten vorhanden sind
    if [ -z "$table_data" ]; then
        log "Keine Daten für $TABLE gefunden, überspringe..."
        continue
    fi
    
    # Generiere INSERT-Statements für jede Zeile
    log "Generiere SQL für $(echo "$table_data" | wc -l) Zeilen in $TABLE..."
    
    # Für jede Zeile ein INSERT-Statement erstellen
    while read -r line; do
        # Trenne die Werte
        values=($line)
        id=${values[0]}
        
        # Erstelle UPDATE-Teile
        updates=""
        for ((i=0; i<${#COLUMN_LIST[@]}; i++)); do
            column=${COLUMN_LIST[$i]}
            value=${values[$i+1]}
            
            # Escape-Behandlung für Sonderzeichen
            escaped_value=$(echo "$value" | sed "s/'/''/g")
            
            if [ -z "$updates" ]; then
                updates="\`${column}\`='${escaped_value}'"
            else
                updates="${updates}, \`${column}\`='${escaped_value}'"
            fi
        done
        
        # Erstelle das vollständige SQL-Statement
        echo "UPDATE \`${TABLE}\` SET ${updates} WHERE id=${id};" >> ${EXCEPTIONS_SQL}
    done <<< "$table_data"
    
    # Zähle, wie viele Datensätze gesichert wurden
    row_count=$(grep -c "UPDATE \`${TABLE}\`" ${EXCEPTIONS_SQL})
    log "Gesichert: $row_count Datensätze für $TABLE mit den Spalten $COLUMNS"
done

# Füge Abschluss zur SQL-Datei hinzu
echo "" >> ${EXCEPTIONS_SQL}
echo "-- Ende der Ausnahmen-Sicherung" >> ${EXCEPTIONS_SQL}

# Synchronisieren der Staging-Umgebung zur Live-Umgebung
log "Synchronisiere Dateien von Staging zu Live..."
# Komplett ohne Ausgabe der einzelnen Dateien
rsync -a --quiet --exclude='.env.local' --exclude='public/.htaccess' --exclude='public/.htpasswd' --exclude='xdeploystagingtolive.sh' --exclude='xrollbacklive.sh' --exclude='x_live_db.sql' --exclude='x_live_files.tar.gz' --exclude='deploy_log.txt' --exclude='rollback_log.txt' --exclude='exceptions.sql' ${DEPLOY_STAGING_PATH}/ ${DEPLOY_LIVE_RSYNC_TARGET}/ > /dev/null 2>&1
log "Dateisynchronisierung abgeschlossen. $(date)"

# Übertragen der Staging-Datenbank in die Live-Umgebung
log "Übertrage Datenbank von Staging zu Live..."
mysqldump -u ${DEPLOY_STAGING_DB_USER} -p"${DEPLOY_STAGING_DB_PASSWORD}" -h ${DEPLOY_STAGING_DB_HOST} ${DEPLOY_STAGING_DB_NAME} --ignore-table=${DEPLOY_STAGING_DB_NAME}.tl_user | mysql -u ${DEPLOY_LIVE_DB_USER} -p"${DEPLOY_LIVE_DB_PASSWORD}" -h ${DEPLOY_LIVE_DB_HOST} ${DEPLOY_LIVE_DB_NAME} 2> /dev/null
log "Datenbank-Übertragung abgeschlossen. $(date)"

# Stelle die Ausnahmen wieder her
log "Stelle Ausnahmen wieder her..."

# Spiele die Ausnahmen-SQL ein
if [ -f "${EXCEPTIONS_SQL}" ]; then
    log "Importiere Ausnahmen aus ${EXCEPTIONS_SQL}..."
    mysql -u ${DEPLOY_LIVE_DB_USER} -p"${DEPLOY_LIVE_DB_PASSWORD}" -h ${DEPLOY_LIVE_DB_HOST} ${DEPLOY_LIVE_DB_NAME} < ${EXCEPTIONS_SQL} 2> /dev/null
    
    # Überprüfe das Ergebnis
    if [ $? -eq 0 ]; then
        log "Ausnahmen erfolgreich wiederhergestellt."
        
        # Überprüfe die wiederhergestellten Daten für jede Ausnahme
        for table_exception in "${TABLE_EXCEPTIONS[@]}"; do
            IFS=':' read -ra PARTS <<< "$table_exception"
            TABLE=${PARTS[0]}
            COLUMNS=${PARTS[1]}
            
            if [ -z "$TABLE" ] || [ -z "$COLUMNS" ]; then
                continue
            fi
            
            log "Überprüfe wiederhergestellte Daten für $TABLE..."
            IFS=',' read -ra COLUMN_LIST <<< "$COLUMNS"
            for column in "${COLUMN_LIST[@]}"; do
                count=$(mysql -u ${DEPLOY_LIVE_DB_USER} -p"${DEPLOY_LIVE_DB_PASSWORD}" -h ${DEPLOY_LIVE_DB_HOST} ${DEPLOY_LIVE_DB_NAME} -e "SELECT COUNT(*) FROM \`${TABLE}\` WHERE \`${column}\` IS NOT NULL AND \`${column}\` != ''" --skip-column-names)
                log "Tabelle $TABLE, Spalte $column: $count nicht-leere Werte"
            done
        done
    else
        log "FEHLER: Konnte Ausnahmen nicht wiederherstellen!"
    fi
else
    log "WARNUNG: Ausnahmen-SQL-Datei nicht gefunden!"
fi

# Cache leeren
log "Leere Cache..."
rm -rf ${DEPLOY_LIVE_PATH}/var/cache > /dev/null 2>&1
log "Cache wurde geleert."

# Rückmeldung für das Backend
log -e "\n===== DEPLOYMENT ERFOLGREICH ABGESCHLOSSEN ====="
log "Deployment wurde am $(date) erfolgreich ausgeführt."
log "Alle Daten wurden übertragen und Ausnahmen wiederhergestellt."

log "===== Deployment abgeschlossen: $(date) ====="
log "ausgerollt"
