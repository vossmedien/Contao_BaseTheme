#!/bin/bash

# Laden der Umgebungsvariablen aus .env.local
if [ -f ".env.local" ]; then
    export $(grep -v '^#' .env.local | xargs)
else
    echo "Fehler: .env.local nicht gefunden!"
    exit 1
fi

# Log-Datei für Feedback im Backend
LOG_FILE="${DEPLOY_STAGING_PATH}/rollback_log.txt"
# Log-Datei komplett löschen und neu erstellen
> ${LOG_FILE}

# Funktion für das Schreiben in die Log-Datei ohne führende Leerzeichen
log() {
    echo "$@" >> ${LOG_FILE}
}

# Log-Zeilen ohne führende Leerzeichen schreiben
log "===== Rollback gestartet: $(date) ====="

# Fehlerbehandlung aktivieren
set -e

# Prüfen, ob Backups existieren
if [ ! -f "${DEPLOY_STAGING_PATH}/x_live_files.tar.gz" ]; then
    log "FEHLER: Datei-Backup nicht gefunden: ${DEPLOY_STAGING_PATH}/x_live_files.tar.gz"
    log "Ein Rollback ist nicht möglich!"
    exit 1
fi

if [ ! -f "${DEPLOY_STAGING_PATH}/x_live_db.sql" ]; then
    log "FEHLER: Datenbank-Backup nicht gefunden: ${DEPLOY_STAGING_PATH}/x_live_db.sql"
    log "Ein Rollback ist nicht möglich!"
    exit 1
fi

# Information über das verwendete Backup ins Log schreiben
if [ -f "${DEPLOY_STAGING_PATH}/rollback_info.txt" ]; then
    log "===== Verwendetes Backup ====="
    cat "${DEPLOY_STAGING_PATH}/rollback_info.txt" >> ${LOG_FILE}
    log "============================="
else
    log "===== Verwendetes Backup ====="
    log "Verwendetes Backup: Standardbackup aus ${DEPLOY_STAGING_PATH}"
    log "============================="
fi

# Prüfen, ob eine Backup-Info Datei existiert und diese im Log anzeigen
if [ -f "${DEPLOY_STAGING_PATH}/backup_info.txt" ]; then
    log "===== Backup-Info ====="
    cat "${DEPLOY_STAGING_PATH}/backup_info.txt" >> ${LOG_FILE}
    log "======================"
fi

# Wiederherstellen der Dateien
log "Stelle Dateien wieder her..."
log "Entpacke ${DEPLOY_STAGING_PATH}/x_live_files.tar.gz nach /"
tar xfz ${DEPLOY_STAGING_PATH}/x_live_files.tar.gz -C / > /dev/null 2>&1
log "Dateien wurden wiederhergestellt. $(date)"

# Wiederherstellen der Datenbank
log "Stelle Datenbank wieder her..."
log "Importiere ${DEPLOY_STAGING_PATH}/x_live_db.sql in ${DEPLOY_LIVE_DB_NAME}"
mysql -u ${DEPLOY_LIVE_DB_USER} -p"${DEPLOY_LIVE_DB_PASSWORD}" -h ${DEPLOY_LIVE_DB_HOST} ${DEPLOY_LIVE_DB_NAME} < ${DEPLOY_STAGING_PATH}/x_live_db.sql 2> /dev/null
log "Datenbank wurde wiederhergestellt. $(date)"

# Cache leeren
log "Leere Cache..."
rm -rf ${DEPLOY_LIVE_PATH}/var/cache > /dev/null 2>&1
log "Cache wurde geleert."

# Lösche temporäre Info-Dateien
if [ -f "${DEPLOY_STAGING_PATH}/rollback_info.txt" ]; then
    rm -f "${DEPLOY_STAGING_PATH}/rollback_info.txt"
fi

if [ -f "${DEPLOY_STAGING_PATH}/backup_info.txt" ]; then
    rm -f "${DEPLOY_STAGING_PATH}/backup_info.txt"
fi

# Rollback erfolgreich
log "===== ROLLBACK ERFOLGREICH ABGESCHLOSSEN ====="
log "Rollback wurde am $(date) erfolgreich ausgeführt."
log "Alle Dateien und Datenbanken wurden auf den gewählten Stand zurückgesetzt."

log "===== Rollback abgeschlossen: $(date) ====="
log "rollback"
