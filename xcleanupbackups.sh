#!/bin/bash

# Laden der Umgebungsvariablen aus .env.local
if [ -f ".env.local" ]; then
    export $(grep -v '^#' .env.local | xargs)
else
    echo "Fehler: .env.local nicht gefunden!"
    exit 1
fi

# Log-Datei für Feedback im Backend
LOG_FILE="${DEPLOY_STAGING_PATH}/cleanup_log.txt"
> ${LOG_FILE}

echo "===== Backup-Bereinigung gestartet: $(date) =====" >> ${LOG_FILE}

# Anzahl der zu behaltenden Backups
KEEP_BACKUPS=4

# Backup-Verzeichnis prüfen
if [ ! -d "${DEPLOY_BACKUP_PATH}" ]; then
    echo "FEHLER: Backup-Verzeichnis nicht gefunden: ${DEPLOY_BACKUP_PATH}" >> ${LOG_FILE}
    exit 1
fi

echo "Durchsuche Backup-Verzeichnis: ${DEPLOY_BACKUP_PATH}" >> ${LOG_FILE}

# Dateien nach Typ filtern und nach Datum sortieren
FILES_BACKUPS=$(ls -t ${DEPLOY_BACKUP_PATH}/*_live_files.tar.gz 2>/dev/null)
DB_BACKUPS=$(ls -t ${DEPLOY_BACKUP_PATH}/*_live_db.sql 2>/dev/null)

# Zähler für gelöschte Dateien
DELETED_FILES=0
DELETED_DBS=0

# Behalte die neuesten KEEP_BACKUPS Datei-Backups
COUNT=0
for file in ${FILES_BACKUPS}; do
    COUNT=$((COUNT+1))
    if [ ${COUNT} -gt ${KEEP_BACKUPS} ]; then
        echo "Lösche altes Datei-Backup: $(basename ${file})" >> ${LOG_FILE}
        rm -f "${file}"
        DELETED_FILES=$((DELETED_FILES+1))
    else
        echo "Behalte Datei-Backup: $(basename ${file})" >> ${LOG_FILE}
    fi
done

# Behalte die neuesten KEEP_BACKUPS Datenbank-Backups
COUNT=0
for file in ${DB_BACKUPS}; do
    COUNT=$((COUNT+1))
    if [ ${COUNT} -gt ${KEEP_BACKUPS} ]; then
        echo "Lösche altes Datenbank-Backup: $(basename ${file})" >> ${LOG_FILE}
        rm -f "${file}"
        DELETED_DBS=$((DELETED_DBS+1))
    else
        echo "Behalte Datenbank-Backup: $(basename ${file})" >> ${LOG_FILE}
    fi
done

echo "===== Backup-Bereinigung abgeschlossen: $(date) =====" >> ${LOG_FILE}
echo "Insgesamt wurden ${DELETED_FILES} Datei-Backups und ${DELETED_DBS} Datenbank-Backups gelöscht." >> ${LOG_FILE}

echo "cleanup_success" 