#!/bin/bash


# Stelle sicher, dass wichtige Pfade vom Controller übergeben wurden
if [ -z "${SOURCE_PATH}" ] || [ -z "${TARGET_BACKUP_PATH}" ]; then
    echo "FEHLER: SOURCE_PATH oder TARGET_BACKUP_PATH ist nicht gesetzt! (Prüfe DEPLOY_CURRENT_PATH, DEPLOY_BACKUP_PATH in .env.local)" >&2
    exit 1
fi

# Log-Datei für Feedback im Backend (im Source/Current-Verzeichnis)
LOG_FILE="${SOURCE_PATH}/cleanup_log.txt"
> ${LOG_FILE}

echo "===== Backup-Bereinigung gestartet: $(date) =====" >> ${LOG_FILE}

# Anzahl der zu behaltenden Backups
KEEP_BACKUPS=5

# Backup-Verzeichnis prüfen
if [ ! -d "${TARGET_BACKUP_PATH}" ]; then
    echo "FEHLER: Backup-Verzeichnis nicht gefunden: ${TARGET_BACKUP_PATH}" >> ${LOG_FILE}
    exit 1
fi

echo "Durchsuche Backup-Verzeichnis: ${TARGET_BACKUP_PATH}" >> ${LOG_FILE}

# --- NEUE LOGIK: Verarbeite Backup-Sets basierend auf Zeitstempel ---

# Ermittle alle *_files.tar.gz Backups, sortiert nach Zeit (neueste zuerst)
# Korrigiertes Muster: *_*_files.tar.gz, um Umgebungen (LIVE, DEV, etc.) einzuschließen
# Leite Fehler nicht mehr um, um Probleme zu sehen
# Wir nutzen bewusst *_files.tar.gz als Anker, da diese für ein vollständiges Backup existieren sollten.
BACKUP_FILES_LIST=$(ls -t ${TARGET_BACKUP_PATH}/*_*_files.tar.gz 2>/dev/null || true) # Fehler unterdrücken, aber leeren String bei Fehler erlauben

# Prüfen, ob überhaupt Dateien gefunden wurden
if [ -z "$BACKUP_FILES_LIST" ]; then
    echo "Keine *_files.tar.gz Backups im Verzeichnis gefunden. Keine Bereinigung durchgeführt." >> ${LOG_FILE}
    echo "===== Backup-Bereinigung abgeschlossen: $(date) =====" >> ${LOG_FILE}
    echo "cleanup_success"
    exit 0
fi

# Zähler für behaltene Sets und gelöschte Dateien
COUNT_SETS=0
DELETED_FILES_COUNT=0

echo "--- Prüfung der Backup-Sets ---" >> ${LOG_FILE}

# Iteriere durch die nach Zeit sortierte Liste der Datei-Backups
echo "$BACKUP_FILES_LIST" | while IFS= read -r file_backup_path; do
    # Extrahiere den Basis-Präfix (Pfad + YYYY-MM-DD_HH-MM-SS_ENV) aus dem .tar.gz Pfad
    # Beispiel: /path/to/backups/2025-05-02_13derbeste1337
    # derdasd-16-03_LIVE
    prefix_path=$(echo "$file_backup_path" | sed -E 's/(.*)_files\.tar\.gz$/\1/')
    prefix_basename=$(basename "$prefix_path") # Nur für die Log-Ausgabe YYYY-MM-DD_HH-MM-SS_ENV

    COUNT_SETS=$((COUNT_SETS + 1))

    if [ ${COUNT_SETS} -gt ${KEEP_BACKUPS} ]; then
        # Dieses Set ist älter als die zu behaltenden -> löschen
        echo "Lösche altes Backup-Set mit Präfix: ${prefix_basename}" >> ${LOG_FILE}

        # Lösche alle drei zugehörigen Dateien (tar.gz, db.sql, exceptions.sql)
        # Verwende -f, um Fehler zu unterdrücken, falls eine Datei unerwartet fehlt
        deleted_count_this_set=0
        if rm -f "${prefix_path}_files.tar.gz"; then deleted_count_this_set=$((deleted_count_this_set + 1)); fi
        if rm -f "${prefix_path}_db.sql"; then deleted_count_this_set=$((deleted_count_this_set + 1)); fi
        if rm -f "${prefix_path}_exceptions.sql"; then deleted_count_this_set=$((deleted_count_this_set + 1)); fi

        DELETED_FILES_COUNT=$((DELETED_FILES_COUNT + deleted_count_this_set))
        echo "  -> ${deleted_count_this_set} Datei(en) für dieses Set gelöscht." >> ${LOG_FILE}

    else
        # Dieses Set gehört zu den neuesten -> behalten
        echo "Behalte Backup-Set mit Präfix: ${prefix_basename}" >> ${LOG_FILE}
    fi
done

# --- ENDE NEUE LOGIK ---

# Die alte Logik zur getrennten Bearbeitung wird nicht mehr benötigt

echo "===== Backup-Bereinigung abgeschlossen: $(date) =====" >> ${LOG_FILE}
# Angepasste Log-Meldung für gelöschte Dateien
echo "Insgesamt wurden ${DELETED_FILES_COUNT} Backup-Dateien (aus älteren Sets) gelöscht." >> ${LOG_FILE}

echo "cleanup_success"
# Der Rest des Skripts nach diesem Punkt wird nicht mehr erreicht, da die alte Logik entfernt wurde.  #!/bin/bash


# Stelle sicher, dass wichtige Pfade vom Controller übergeben wurden
if [ -z "${SOURCE_PATH}" ] || [ -z "${TARGET_BACKUP_PATH}" ]; then
    echo "FEHLER: SOURCE_PATH oder TARGET_BACKUP_PATH ist nicht gesetzt! (Prüfe DEPLOY_CURRENT_PATH, DEPLOY_BACKUP_PATH in .env.local)" >&2
    exit 1
fi

# Log-Datei für Feedback im Backend (im Source/Current-Verzeichnis)
LOG_FILE="${SOURCE_PATH}/cleanup_log.txt"
> ${LOG_FILE}

echo "===== Backup-Bereinigung gestartet: $(date) =====" >> ${LOG_FILE}

# Anzahl der zu behaltenden Backups
KEEP_BACKUPS=5

# Backup-Verzeichnis prüfen
if [ ! -d "${TARGET_BACKUP_PATH}" ]; then
    echo "FEHLER: Backup-Verzeichnis nicht gefunden: ${TARGET_BACKUP_PATH}" >> ${LOG_FILE}
    exit 1
fi

echo "Durchsuche Backup-Verzeichnis: ${TARGET_BACKUP_PATH}" >> ${LOG_FILE}

# --- NEUE LOGIK: Verarbeite Backup-Sets basierend auf Zeitstempel ---

# Ermittle alle *_files.tar.gz Backups, sortiert nach Zeit (neueste zuerst)
# Korrigiertes Muster: *_*_files.tar.gz, um Umgebungen (LIVE, DEV, etc.) einzuschließen
# Leite Fehler nicht mehr um, um Probleme zu sehen
# Wir nutzen bewusst *_files.tar.gz als Anker, da diese für ein vollständiges Backup existieren sollten.
BACKUP_FILES_LIST=$(ls -t ${TARGET_BACKUP_PATH}/*_*_files.tar.gz 2>/dev/null || true) # Fehler unterdrücken, aber leeren String bei Fehler erlauben

# Prüfen, ob überhaupt Dateien gefunden wurden
if [ -z "$BACKUP_FILES_LIST" ]; then
    echo "Keine *_files.tar.gz Backups im Verzeichnis gefunden. Keine Bereinigung durchgeführt." >> ${LOG_FILE}
    echo "===== Backup-Bereinigung abgeschlossen: $(date) =====" >> ${LOG_FILE}
    echo "cleanup_success"
    exit 0
fi

# Zähler für behaltene Sets und gelöschte Dateien
COUNT_SETS=0
DELETED_FILES_COUNT=0

echo "--- Prüfung der Backup-Sets ---" >> ${LOG_FILE}

# Iteriere durch die nach Zeit sortierte Liste der Datei-Backups
echo "$BACKUP_FILES_LIST" | while IFS= read -r file_backup_path; do
    # Extrahiere den Basis-Präfix (Pfad + YYYY-MM-DD_HH-MM-SS_ENV) aus dem .tar.gz Pfad
    # Beispiel: /path/to/backups/2025-05-02_13derbeste1337
    # derdasd-16-03_LIVE
    prefix_path=$(echo "$file_backup_path" | sed -E 's/(.*)_files\.tar\.gz$/\1/')
    prefix_basename=$(basename "$prefix_path") # Nur für die Log-Ausgabe YYYY-MM-DD_HH-MM-SS_ENV

    COUNT_SETS=$((COUNT_SETS + 1))

    if [ ${COUNT_SETS} -gt ${KEEP_BACKUPS} ]; then
        # Dieses Set ist älter als die zu behaltenden -> löschen
        echo "Lösche altes Backup-Set mit Präfix: ${prefix_basename}" >> ${LOG_FILE}

        # Lösche alle drei zugehörigen Dateien (tar.gz, db.sql, exceptions.sql)
        # Verwende -f, um Fehler zu unterdrücken, falls eine Datei unerwartet fehlt
        deleted_count_this_set=0
        if rm -f "${prefix_path}_files.tar.gz"; then deleted_count_this_set=$((deleted_count_this_set + 1)); fi
        if rm -f "${prefix_path}_db.sql"; then deleted_count_this_set=$((deleted_count_this_set + 1)); fi
        if rm -f "${prefix_path}_exceptions.sql"; then deleted_count_this_set=$((deleted_count_this_set + 1)); fi

        DELETED_FILES_COUNT=$((DELETED_FILES_COUNT + deleted_count_this_set))
        echo "  -> ${deleted_count_this_set} Datei(en) für dieses Set gelöscht." >> ${LOG_FILE}

    else
        # Dieses Set gehört zu den neuesten -> behalten
        echo "Behalte Backup-Set mit Präfix: ${prefix_basename}" >> ${LOG_FILE}
    fi
done

# --- ENDE NEUE LOGIK ---

# Die alte Logik zur getrennten Bearbeitung wird nicht mehr benötigt

echo "===== Backup-Bereinigung abgeschlossen: $(date) =====" >> ${LOG_FILE}
# Angepasste Log-Meldung für gelöschte Dateien
echo "Insgesamt wurden ${DELETED_FILES_COUNT} Backup-Dateien (aus älteren Sets) gelöscht." >> ${LOG_FILE}

echo "cleanup_success"
# Der Rest des Skripts nach diesem Punkt wird nicht mehr erreicht, da die alte Logik entfernt wurde.