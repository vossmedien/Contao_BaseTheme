#!/bin/bash

# Setze Standardwerte, falls Variablen vom Controller kommen
# DEPLOY_LIVE_* Variablen werden nicht mehr benötigt, Ziel-DB wird aus .env.local des TARGET_PATH gelesen
#: ${DEPLOY_LIVE_PATH:=""} # TARGET_PATH wird verwendet
#: ${DEPLOY_STAGING_PATH:=""} # Nicht direkt für DB-Zugriff benötigt
#: ${DEPLOY_LIVE_DB_USER:=""}
#: ${DEPLOY_LIVE_DB_PASSWORD:=""}
#: ${DEPLOY_LIVE_DB_HOST:=""}
#: ${DEPLOY_LIVE_DB_NAME:=""}

# Laden der Umgebungsvariablen aus .env.local (ENTFERNT - Variablen kommen vom Controller)
# if [ -f ".env.local" ]; then
#     # Sichereres Laden: Nur Zeilen mit '=' exportieren, Kommentare und Leerzeilen ignorieren
#     set -a # Automatically export all variables subsequently defined
#     while IFS= read -r line || [ -n "$line" ]; do
#         # Ignoriere Kommentare und leere Zeilen
#         [[ "$line" =~ ^# ]] || [[ -z "$line" ]] && continue
#         # Exportiere nur, wenn ein '=' vorhanden ist
#         if [[ "$line" == *"="* ]]; then
#             export "$line"
#         fi
#     done < ".env.local"
#     set +a # Stop automatically exporting
# else
#     echo "Fehler: .env.local nicht gefunden!"
#     exit 1
# fi

# Stelle sicher, dass wichtige Pfade/Variablen vom Controller übergeben wurden
if [ -z "${SOURCE_PATH}" ] || [ -z "${TARGET_PATH}" ]; then
    echo "FEHLER: SOURCE_PATH oder TARGET_PATH ist nicht gesetzt! (DEPLOY_CURRENT_PATH, DEPLOY_XXX_PATH)" >&2
    exit 1
fi
# TARGET_DB_NAME wird nicht mehr direkt benötigt
# if [ -z "${TARGET_DB_NAME}" ]; then
#     echo "FEHLER: TARGET_DB_NAME ist nicht gesetzt! (Prüfe DEPLOY_XXX_DB_NAME in .env.local)" >&2
#     exit 1
# fi
# Prüfe auch die vom Controller übergebenen Rollback-Quellpfade
if [ -z "${ROLLBACK_FILE_SOURCE}" ] || [ -z "${ROLLBACK_DB_SOURCE}" ]; then
    echo "FEHLER: ROLLBACK_FILE_SOURCE oder ROLLBACK_DB_SOURCE nicht vom Controller übergeben!" >&2
    exit 1
fi

# Log-Datei für Feedback im Backend (im Source/Current-Verzeichnis)
LOG_FILE="${SOURCE_PATH}/rollback_log.txt"
# Log-Datei komplett löschen und neu erstellen
> ${LOG_FILE}

# Funktion für das Schreiben in die Log-Datei ohne führende Leerzeichen
log() {
    echo "$@" >> ${LOG_FILE}
}

# Funktion zum Parsen der DATABASE_URL aus einer .env-Datei (für das Zielsystem)
# Argument 1: Pfad zur .env-Datei
# Setzt und exportiert: TARGET_DB_USER, TARGET_DB_PASSWORD, TARGET_DB_HOST, TARGET_DB_NAME, TARGET_DB_PORT
parse_target_database_url() {
    local env_file=$1
    local db_url=""
    local prefix="TARGET_" # Prefix für die exportierten Variablen

    if [ ! -f "${env_file}" ]; then
        log "FEHLER: Zieldatei .env.local nicht gefunden: ${env_file}"
        exit 1
    fi

    # Suche nach DATABASE_URL in der .env Datei
    db_url=$(grep -E '^DATABASE_URL=' "${env_file}" | head -n 1 | sed -e 's/^DATABASE_URL=//' -e 's/\"//g' -e "s/'//g")

    if [ -z "${db_url}" ]; then
        log "FEHLER: DATABASE_URL nicht in ${env_file} gefunden oder ist leer."
        exit 1
    fi

    # Parse DATABASE_URL (Format: mysql://user:password@host:port/dbname?...)
    local clean_url=$(echo "${db_url}" | sed -e 's|^[^:]*://||' -e 's|\?.*$||')
    local user_pass=$(echo "${clean_url}" | cut -d'@' -f1)
    local host_port_db=$(echo "${clean_url}" | cut -d'@' -f2)
    local host_port=$(echo "${host_port_db}" | cut -d'/' -f1)

    export ${prefix}DB_USER=$(echo "${user_pass}" | cut -d':' -f1)
    export ${prefix}DB_PASSWORD=$(echo "${user_pass}" | cut -d':' -f2-)
    export ${prefix}DB_NAME=$(echo "${host_port_db}" | cut -d'/' -f2)

    if [[ "${host_port}" == *":"* ]]; then
        export ${prefix}DB_HOST=$(echo "${host_port}" | cut -d':' -f1)
        export ${prefix}DB_PORT=$(echo "${host_port}" | cut -d':' -f2)
    else
        export ${prefix}DB_HOST="${host_port}"
        export ${prefix}DB_PORT="3306" # Standard MySQL Port
    fi

    # Validierung
    if [ -z "$(eval echo \$${prefix}DB_USER)" ] || [ -z "$(eval echo \$${prefix}DB_HOST)" ] || [ -z "$(eval echo \$${prefix}DB_NAME)" ]; then
        log "FEHLER: Unvollständige Zieldatenbank-Credentials nach dem Parsen von ${env_file}."
        log "Geparst: USER=$(eval echo \$${prefix}DB_USER), HOST=$(eval echo \$${prefix}DB_HOST), NAME=$(eval echo \$${prefix}DB_NAME)"
        exit 1
    fi

    log "Zieldatenbank-Credentials aus ${env_file} erfolgreich geparst."
    log "Geparst: USER=$(eval echo \$${prefix}DB_USER), HOST=$(eval echo \$${prefix}DB_HOST), PORT=$(eval echo \$${prefix}DB_PORT), NAME=$(eval echo \$${prefix}DB_NAME)"
}

# Log-Zeilen ohne führende Leerzeichen schreiben
log "===== Rollback gestartet: $(date) ====="

# Parse die Zieldatenbank-URL (Ziel ist die Live-Umgebung beim Rollback)
TARGET_ENV_FILE="${TARGET_PATH}/.env.local"
log "Lese Zieldatenbank-Konfiguration aus ${TARGET_ENV_FILE}..."
parse_target_database_url "${TARGET_ENV_FILE}"
log "Zielsystem (Datenbank): ${TARGET_DB_NAME} auf ${TARGET_DB_HOST}:${TARGET_DB_PORT}"

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
# Verwende die übergebene Quell-Datei für das Rollback
log "Entpacke ${ROLLBACK_FILE_SOURCE} nach /"
tar xfz ${ROLLBACK_FILE_SOURCE} -C / > /dev/null 2>&1
log "Dateien wurden wiederhergestellt. $(date)"

# Wiederherstellen der Datenbank
log "Stelle Datenbank wieder her..."
# Verwende die übergebene Quell-DB für das Rollback und die geparsten Ziel-Credentials
log "Importiere ${ROLLBACK_DB_SOURCE} in ${TARGET_DB_NAME}"
mysql -u ${TARGET_DB_USER} -p"${TARGET_DB_PASSWORD}" -h ${TARGET_DB_HOST} --port=${TARGET_DB_PORT} ${TARGET_DB_NAME} < ${ROLLBACK_DB_SOURCE} 2> /dev/null
log "Datenbank wurde wiederhergestellt. $(date)"

# Cache leeren
log "Leere Cache im Zielpfad ${TARGET_PATH}..."
rm -rf ${TARGET_PATH}/var/cache > /dev/null 2>&1
log "Cache wurde geleert."

# Lösche temporäre Info-Dateien (falls vorhanden und vom Controller übergeben?)
# Die Pfade hier beziehen sich auf DEPLOY_STAGING_PATH, was möglicherweise nicht korrekt ist.
# Das Löschen sollte ggf. im Controller passieren oder die Pfade müssen korrekt übergeben werden.
# Beispiel: Lösche Info-Datei am Quellort des Rollbacks?
# if [ -n "${ROLLBACK_INFO_FILE_PATH}" ] && [ -f "${ROLLBACK_INFO_FILE_PATH}" ]; then
#     log "Lösche Rollback-Info-Datei: ${ROLLBACK_INFO_FILE_PATH}"
#     rm -f "${ROLLBACK_INFO_FILE_PATH}"
# fi
# if [ -n "${BACKUP_INFO_FILE_PATH}" ] && [ -f "${BACKUP_INFO_FILE_PATH}" ]; then
#      log "Lösche Backup-Info-Datei: ${BACKUP_INFO_FILE_PATH}"
#      rm -f "${BACKUP_INFO_FILE_PATH}"
# fi
# Entferne alten Code zum Löschen der Info-Dateien basierend auf DEPLOY_STAGING_PATH
# if [ -f "${DEPLOY_STAGING_PATH}/rollback_info.txt" ]; then
#     rm -f "${DEPLOY_STAGING_PATH}/rollback_info.txt"
# fi
# 
# if [ -f "${DEPLOY_STAGING_PATH}/backup_info.txt" ]; then
#     rm -f "${DEPLOY_STAGING_PATH}/backup_info.txt"
# fi

# Rollback erfolgreich
log "===== ROLLBACK ERFOLGREICH ABGESCHLOSSEN ====="
log "Rollback wurde am $(date) erfolgreich ausgeführt."
log "Alle Dateien und Datenbanken wurden auf den gewählten Stand zurückgesetzt."

log "===== Rollback abgeschlossen: $(date) ====="
log "rollback"
