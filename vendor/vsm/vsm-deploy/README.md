# VSM Deploy Bundle

Das VSM Deploy Bundle ist eine Contao 5-kompatible Deployment-Lösung, die als lokales VSM-Package erstellt wurde.

## Funktionen

- **Deployment**: Überträgt die aktuelle Contao-Version auf Zielsysteme
- **Rollback**: Stellt vorherige Versionen aus Backups wieder her
- **Cleanup**: Bereinigt alte Backup-Dateien (behalte die letzten 5)
- **Multi-Environment**: Unterstützt mehrere Zielumgebungen (LIVE, DEV, STAGING, etc.)
- **Backup-Management**: Automatische Backups mit Metadaten

## Installation

Das Bundle ist als lokales VSM-Package konfiguriert und wird automatisch über `composer install` geladen.

## Konfiguration

Konfiguration erfolgt über Umgebungsvariablen in der `.env.local` Datei:

### Basis-Konfiguration
```
DEPLOY_CURRENT_PATH=/pfad/zum/aktuellen/system
DEPLOY_BACKUP_PATH=/pfad/zum/backup/verzeichnis
DATABASE_URL=mysql://user:password@host:port/database
```

### Umgebungs-spezifische Konfiguration
```
DEPLOY_LIVE_PATH=/pfad/zum/live/system
DEPLOY_DEV_PATH=/pfad/zum/dev/system
DEPLOY_STAGING_PATH=/pfad/zum/staging/system
```

### Optionale Konfiguration
```
DEPLOY_EXCEPTIONS="datei1,datei2,verzeichnis1/*"
DEPLOY_EXCLUDES="tmp/*,cache/*"
DEPLOY_IGNORE_TABLES="tl_log,tl_undo"
```

## Verwendung

1. Öffne das Contao Backend
2. Navigiere zu **VSM > Deployment**
3. Wähle die gewünschte Aktion:
   - **ausrollen**: Deployed auf die ausgewählte Umgebung
   - **rollback**: Stellt ein ausgewähltes Backup wieder her
4. Für die Backup-Bereinigung nutze den **"Backups bereinigen"** Button unter der Backup-Liste

## Abhängigkeiten

Das Bundle nutzt die bestehenden Deployment-Skripte:
- `xdeploystagingtolive.sh`
- `xrollbacklive.sh`
- `xcleanupbackups.sh`

## Ersetzt

Dieses Bundle ersetzt das `@caeli-wind/caeli-deploy` Package und bietet dieselbe Funktionalität in einer VSM-kompatiblen Struktur.