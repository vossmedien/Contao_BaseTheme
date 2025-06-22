# Caeli Area PDF Bundle

Contao **Unsichtbares** Frontend-Modul zur direkten PDF-Generierung von Windenergie-Ersteinschätzungen.

## Installation

```bash
composer require caeli-wind/caeli-area-pdf
```

## Konfiguration

Das Modul benötigt folgende Umgebungsvariablen in der `.env.local` Datei:

```env
# Caeli Wind Infrastruktur API
CAELI_INFRA_API_URL=https://infra.caeli-wind.de/api/
CAELI_INFRA_API_USERNAME=website@caeli-wind.de
CAELI_INFRA_API_PASSWORD=your_api_password_here

# Google Maps API (für Kartenintegration)
GOOGLE_MAPS_API_KEY=your_google_maps_api_key_here
```

## Funktionen

- ✅ **PDF-Generierung** mit professionellem Layout
- ✅ **Kartenintegration** mit Google Static Maps
- ✅ **API-Integration** mit Caeli Wind Infrastruktur
- ✅ **Bewertungssystem** für Flächen
- ✅ **Umgebungsvariablen-Support** für sichere Konfiguration

## Sicherheit

⚠️ **Wichtig**: Verwenden Sie niemals API-Schlüssel oder Passwörter direkt im Code. Alle sensiblen Daten werden über Umgebungsvariablen konfiguriert.

## Abhängigkeiten

- PHP >= 8.1
- Contao >= 5.0
- TCPDF für PDF-Generierung
- Google Static Maps API

## Verwendung

Das Modul ist ein **"unsichtbares" Frontend-Modul** - es zeigt normalerweise keinen Inhalt an und wird nur bei Bedarf aktiv.

### 🔧 Verhalten:

- **Ohne Parameter**: Modul bleibt komplett unsichtbar (kein HTML-Output)
- **Mit parkid**: PDF wird direkt generiert und an den Browser ausgegeben

### URL-Parameter

Das Modul wird durch den Parameter `parkid` aktiviert:
```
https://example.com/page-with-pdf-module?parkid=your-park-uuid
```

### Backend-Integration

- **Backend**: Zeigt nur Informationen über das Modul an
- **Frontend ohne Parameter**: Unsichtbar
- **Frontend mit parkid**: Direkte PDF-Ausgabe (und `exit`)

## Fallbacks

Falls Umgebungsvariablen nicht gesetzt sind, verwendet das Modul Standard-Fallback-Werte (nur für Entwicklung empfohlen). 