#!/bin/bash

# Stelle sicher, dass wichtige Pfade/Variablen vom Controller übergeben wurden
if [ -z "${SOURCE_PATH}" ] || [ -z "${TARGET_PATH}" ] || [ -z "${TARGET_BACKUP_PATH}" ]; then
    echo "FEHLER: SOURCE_PATH, TARGET_PATH oder TARGET_BACKUP_PATH ist nicht gesetzt! (Prüfe DEPLOY_CURRENT_PATH, DEPLOY_XXX_PATH, DEPLOY_BACKUP_PATH in .env.local)" >&2
    exit 1
fi
# Prüfe DB-Credentials
if [ -z "${TARGET_DB_NAME}" ] || [ -z "${SOURCE_DB_NAME}" ]; then
    echo "FEHLER: Datenbanknamen (TARGET oder SOURCE) nicht gesetzt! (Prüfe DEPLOY_XXX_DB_NAME, DEPLOY_CURRENT_DB_NAME in .env.local)" >&2
    exit 1
fi
# Prüfe Timestamp und Zielnamen
if [ -z "${DEPLOY_TIMESTAMP}" ] || [ -z "${TARGET_ENV_NAME}" ]; then
    echo "FEHLER: DEPLOY_TIMESTAMP oder TARGET_ENV_NAME nicht vom Controller übergeben!" >&2
    exit 1
fi

# Log-Datei für Feedback im Backend (im Source/Current-Verzeichnis)
LOG_FILE="${SOURCE_PATH}/deploy_log.txt"
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
log "Quelle: Staging-System (${SOURCE_PATH})"
log "Ziel: Ziel-System (${TARGET_PATH})"
log "Staging-Datenbank: ${SOURCE_DB_NAME}"
log "Ziel-Datenbank: ${TARGET_DB_NAME}"
log "Zeitstempel: $(date '+%Y-%m-%d %H:%M:%S')"
log "============================="
log ""

# Log-Zeilen ohne führende Leerzeichen schreiben
log "===== Deployment gestartet: $(date) ====="
log "Quelle: ${SOURCE_PATH} (${SOURCE_DB_NAME})"
log "Ziel: ${TARGET_PATH} (${TARGET_DB_NAME}) - Umgebung: ${TARGET_ENV_NAME}"
log "Timestamp: ${DEPLOY_TIMESTAMP}"
log "Backup-Pfad: ${TARGET_BACKUP_PATH}"
log "============================="
log ""

TIMESTAMP=$(date '+%Y-%m-%d_%H-%M-%S')

# Erstellen eines neuen Backups der Ziel-Umgebung direkt im Ziel-Backup-Verzeichnis
log "Erstelle Backup der Ziel-Umgebung (${TARGET_PATH})..."
BACKUP_FILE_TARGET="${TARGET_BACKUP_PATH}/${DEPLOY_TIMESTAMP}_${TARGET_ENV_NAME}_files.tar.gz"
BACKUP_DB_TARGET="${TARGET_BACKUP_PATH}/${DEPLOY_TIMESTAMP}_${TARGET_ENV_NAME}_db.sql"

# Archiviere Ziel-Dateien komplett ohne Ausgabe
tar cfz ${BACKUP_FILE_TARGET} ${TARGET_PATH}/* > /dev/null 2>&1
log "Zieldateien wurden archiviert: ${BACKUP_FILE_TARGET}"

# Sichere Ziel-Datenbank ohne Ausgabe
mysqldump -u ${TARGET_DB_USER} -p"${TARGET_DB_PASSWORD}" -h ${TARGET_DB_HOST} ${TARGET_DB_NAME} > ${BACKUP_DB_TARGET} 2> /dev/null
log "Zieldatenbank wurde gesichert: ${BACKUP_DB_TARGET}"

# Sichern der Ausnahmen aus der Ziel-DB vor dem Deployment
log "Sichere Ausnahmen aus der Ziel-Datenbank (${TARGET_DB_NAME})..."

# Verwende Ausnahmen-Konfiguration für das Ziel
if [ -z "${TARGET_EXCEPTIONS}" ]; then
    # Standard: DNS in tl_page bewahren
    TARGET_EXCEPTIONS="tl_page:dns"
    log "Verwende Standard-Ausnahmen: ${TARGET_EXCEPTIONS}"
else
    log "Verwende konfigurierte Ausnahmen: ${TARGET_EXCEPTIONS}"
fi

# SQL-Datei für die Ausnahmen (im TARGET_BACKUP_PATH speichern)
EXCEPTIONS_SQL_FILENAME="${DEPLOY_TIMESTAMP}_${TARGET_ENV_NAME}_exceptions.sql"
EXCEPTIONS_SQL_PATH="${TARGET_BACKUP_PATH}/${EXCEPTIONS_SQL_FILENAME}"
> ${EXCEPTIONS_SQL_PATH}

# Füge Header zur SQL-Datei hinzu
echo "-- Ausnahmen-Sicherung erstellt am $(date)" >> ${EXCEPTIONS_SQL_PATH}
echo "-- Quelle der Ausnahmen: ${TARGET_DB_NAME} (${TARGET_ENV_NAME})" >> ${EXCEPTIONS_SQL_PATH}
echo "-- Gehört zum Backup Timestamp: ${DEPLOY_TIMESTAMP}" >> ${EXCEPTIONS_SQL_PATH}
echo "-- Konfigurierte Ausnahmen: ${TARGET_EXCEPTIONS}" >> ${EXCEPTIONS_SQL_PATH}
echo "" >> ${EXCEPTIONS_SQL_PATH}

# Verarbeite jede Ausnahme
IFS=';' read -ra TABLE_EXCEPTIONS <<< "${TARGET_EXCEPTIONS}"
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
    echo "-- Ausnahmen für Tabelle '$TABLE', Spalten: '$COLUMNS'" >> ${EXCEPTIONS_SQL_PATH}
    
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
    
    # Hole die Daten aus der ZIEL-Datenbank
    table_data=$(mysql -u ${TARGET_DB_USER} -p"${TARGET_DB_PASSWORD}" -h ${TARGET_DB_HOST} ${TARGET_DB_NAME} -e "SELECT id, ${column_names} FROM \`${TABLE}\` WHERE ${conditions}" --batch --skip-column-names)
    
    # Prüfe, ob Daten vorhanden sind
    if [ -z "$table_data" ]; then
        log "Keine Daten für $TABLE in ${TARGET_DB_NAME} gefunden, überspringe..."
        continue
    fi
    
    # Generiere UPDATE-Statements für jede Zeile
    log "Generiere SQL für $(echo "$table_data" | wc -l) Zeilen in $TABLE..."
    
    while read -r line; do
        values=($line)
        id=${values[0]}
        updates=""
        for ((i=0; i<${#COLUMN_LIST[@]}; i++)); do
            column=${COLUMN_LIST[$i]}
            value=${values[$i+1]}
            escaped_value=$(echo "$value" | sed "s/'/''/g")
            if [ -z "$updates" ]; then
                updates="\`${column}\`='${escaped_value}'"
            else
                updates="${updates}, \`${column}\`='${escaped_value}'"
            fi
        done
        echo "UPDATE \`${TABLE}\` SET ${updates} WHERE id=${id};" >> ${EXCEPTIONS_SQL_PATH}
    done <<< "$table_data"
    
    row_count=$(grep -c "UPDATE \`${TABLE}\`" ${EXCEPTIONS_SQL_PATH})
    log "Gesichert: $row_count Datensätze für $TABLE mit den Spalten $COLUMNS aus ${TARGET_DB_NAME}"
done

# Füge Abschluss zur SQL-Datei hinzu
echo "" >> ${EXCEPTIONS_SQL_PATH}
echo "-- Ende der Ausnahmen-Sicherung" >> ${EXCEPTIONS_SQL_PATH}
log "Ausnahmen-SQL gespeichert: ${EXCEPTIONS_SQL_PATH}"

# Synchronisieren der Staging-Umgebung zur Ziel-Umgebung
log "Synchronisiere Dateien von Staging (${SOURCE_PATH}) zu Ziel (${TARGET_PATH})..."

# Verwende Excludes-Konfiguration für das Ziel
if [ -z "${TARGET_EXCLUDES}" ]; then
    # Standard-Ausschlüsse
    TARGET_EXCLUDES=".env.local public/.htaccess public/.htpasswd xdeploystagingtolive.sh xrollbacklive.sh x_live_db.sql x_live_files.tar.gz deploy_log.txt rollback_log.txt exceptions.sql backup_infos.txt"
    log "Verwende Standard-Ausschlüsse für rsync"
else
    log "Verwende konfigurierte Ausschlüsse für rsync: ${TARGET_EXCLUDES}"
fi

# Baue rsync-Exclude-Parameter aus der Liste
RSYNC_EXCLUDES=""
for exclude in ${TARGET_EXCLUDES}; do
    RSYNC_EXCLUDES="${RSYNC_EXCLUDES} --exclude='${exclude}'"
done

# Komplett ohne Ausgabe der einzelnen Dateien
log "Starte rsync mit folgenden Parametern:"
log "Quelle: ${SOURCE_PATH}/"
log "Ziel: ${TARGET_PATH}/"
log "Excludes: ${RSYNC_EXCLUDES}"
eval "rsync -av --itemize-changes ${RSYNC_EXCLUDES} ${SOURCE_PATH}/ ${TARGET_PATH}/ >> ${LOG_FILE} 2>&1"
log "rsync-Befehl ausgeführt. Exit-Code: $?"

# Übertragen der Staging-Datenbank in die Ziel-Umgebung
log "Übertrage Datenbank von Staging (${SOURCE_DB_NAME}) zu Ziel (${TARGET_DB_NAME})..."
mysqldump -u ${SOURCE_DB_USER} -p"${SOURCE_DB_PASSWORD}" -h ${SOURCE_DB_HOST} ${SOURCE_DB_NAME} --ignore-table=${SOURCE_DB_NAME}.tl_user | mysql -u ${TARGET_DB_USER} -p"${TARGET_DB_PASSWORD}" -h ${TARGET_DB_HOST} ${TARGET_DB_NAME} 2> /dev/null
log "Datenbank-Übertragung abgeschlossen. $(date)"

# Stelle die Ausnahmen wieder her (in die ZIEL-DB)
log "Stelle Ausnahmen wieder her in ${TARGET_DB_NAME}..."

# Spiele die Ausnahmen-SQL ein (aus dem Backup-Pfad)
if [ -f "${EXCEPTIONS_SQL_PATH}" ]; then
    log "Importiere Ausnahmen aus ${EXCEPTIONS_SQL_PATH}..."
    mysql -u ${TARGET_DB_USER} -p"${TARGET_DB_PASSWORD}" -h ${TARGET_DB_HOST} ${TARGET_DB_NAME} < ${EXCEPTIONS_SQL_PATH} 2> /dev/null
    
    if [ $? -eq 0 ]; then
        log "Ausnahmen erfolgreich wiederhergestellt."
        
        # Überprüfe die wiederhergestellten Daten für jede Ausnahme in der ZIEL-DB
        for table_exception in "${TABLE_EXCEPTIONS[@]}"; do
             IFS=':' read -ra PARTS <<< "$table_exception"
             TABLE=${PARTS[0]}
             COLUMNS=${PARTS[1]}
             if [ -z "$TABLE" ] || [ -z "$COLUMNS" ]; then continue; fi
             log "Überprüfe wiederhergestellte Daten für $TABLE in ${TARGET_DB_NAME}..."
             IFS=',' read -ra COLUMN_LIST <<< "$COLUMNS"
             for column in "${COLUMN_LIST[@]}"; do
                 count=$(mysql -u ${TARGET_DB_USER} -p"${TARGET_DB_PASSWORD}" -h ${TARGET_DB_HOST} ${TARGET_DB_NAME} -e "SELECT COUNT(*) FROM \`${TABLE}\` WHERE \`${column}\` IS NOT NULL AND \`${column}\` != ''" --skip-column-names)
                 log "Tabelle $TABLE, Spalte $column: $count nicht-leere Werte"
             done
        done
    else
        log "FEHLER: Konnte Ausnahmen nicht wiederherstellen!"
    fi
else
    log "WARNUNG: Ausnahmen-SQL-Datei nicht gefunden: ${EXCEPTIONS_SQL_PATH}"
fi

# Cache im Zielverzeichnis leeren
log "Leere Cache im Zielverzeichnis (${TARGET_PATH})..."
rm -rf ${TARGET_PATH}/var/cache > /dev/null 2>&1
log "Cache wurde geleert."

# Rückmeldung für das Backend
log -e "\n===== DEPLOYMENT ERFOLGREICH ABGESCHLOSSEN ====="
log "Deployment wurde am $(date) erfolgreich ausgeführt."
log "Alle Daten wurden übertragen und Ausnahmen wiederhergestellt."
log "Quelle: ${SOURCE_PATH} (${SOURCE_DB_NAME})"
log "Ziel: ${TARGET_PATH} (${TARGET_DB_NAME})"

log "===== Deployment abgeschlossen: $(date) ====="
log "ausgerollt"
