![Alt text](docs/logo.png?raw=true "logo")


# Welcome to Caeli PIN-Login
This bundle is still under construction.

# Caeli PIN-Login Bundle

Dieses Bundle ermöglicht es, einzelne Seiten in Contao mit einem PIN-Code zu schützen.

## Features

- Seitenspezifischer PIN-Schutz: Jede Seite kann einen eigenen PIN haben
- Timeout-Funktion: Automatisches Ablaufen der PIN-Autorisierung nach einer konfigurierbaren Zeit
- Optionale E-Mail-Erfassung: Sammeln von E-Mail-Adressen bei der PIN-Eingabe
- Fehlerbehandlung: Klare Fehlermeldungen bei falscher PIN-Eingabe
- Entwicklermodus: Debug-Informationen im Entwicklungsmodus

## Installation

```bash
composer require caeli-wind/caeli-pin-login
```

## Verwendung

### 1. PIN-Login-Seite erstellen

Erstellen Sie eine neue Seite in der Seitenstruktur, die das PIN-Login-Formular enthalten soll.
Fügen Sie auf dieser Seite ein neues Modul vom Typ "PIN-Login Formular" hinzu.

### 2. Seiten mit PIN schützen

1. Öffnen Sie die Seiteneigenschaften der zu schützenden Seite
2. Aktivieren Sie unter "PIN-Schutz Einstellungen" die Option "Seite mit PIN schützen"
3. Geben Sie einen PIN-Wert ein
4. Wählen Sie die PIN-Login-Seite aus
5. Optional: Passen Sie das Timeout an (Standard: 1800 Sekunden = 30 Minuten)

### 3. Konfiguration des PIN-Login-Formulars

Das PIN-Login-Formular kann wie folgt konfiguriert werden:

- **E-Mail-Adresse erforderlich**: Fordert bei der PIN-Eingabe auch eine E-Mail-Adresse an
- **Zusätzliches Datenfeld anzeigen**: Zeigt ein weiteres Eingabefeld für zusätzliche Daten an

## Technische Details

- Seitenspezifische Autorisierung: Im Gegensatz zu früheren Implementierungen wird jede Seite separat autorisiert
- Timeout-Funktion: Die Autorisierung läuft nach der konfigurierten Zeit ab
- Fehlerbehandlung: Klare Fehlermeldungen bei fehlender E-Mail oder falschem PIN

## Migration von älteren Implementierungen

Wenn Sie von einer älteren PIN-Login-Implementierung migrieren, beachten Sie folgende Änderungen:

1. Die PIN-Autorisierung gilt jetzt pro Seite und nicht global
2. Es gibt eine Timeout-Funktion für die Autorisierung
3. Fehlermeldungen werden jetzt klar angezeigt

## Lizenz

MIT
