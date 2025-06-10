# Environment Setup für Caeli Area Check Bundle

## Erforderliche Environment Variables

Dieses Bundle benötigt folgende Umgebungsvariablen für den korrekten Betrieb:

### API-Zugang für Caeli Infra
```bash
# Erforderlich für die Kommunikation mit der Caeli Infrastructure API
CAELI_INFRA_API_URL=https://infra.caeli-wind.de/api/
CAELI_INFRA_API_USERNAME=your-username@caeli-wind.de
CAELI_INFRA_API_PASSWORD=your-secure-password
```

### Google Maps Integration  
```bash
# Erforderlich für die Kartenanzeige und Geocoding
GOOGLE_MAPS_API_KEY=your-google-maps-api-key
GOOGLE_MAPS_MAP_ID=your-google-maps-map-id
```

## Setup in Contao

### 1. .env Datei erstellen/ergänzen
Erstelle oder erweitere die `.env` Datei im Hauptverzeichnis deiner Contao-Installation:

```bash
# Caeli API
CAELI_INFRA_API_URL=https://infra.caeli-wind.de/api/
CAELI_INFRA_API_USERNAME=deine@email.de
CAELI_INFRA_API_PASSWORD=dein-sicheres-passwort

# Google Maps
GOOGLE_MAPS_API_KEY=dein-google-maps-api-key
GOOGLE_MAPS_MAP_ID=dein-google-maps-map-id
```

### 2. Produktionsumgebung
Für Produktionsumgebungen sollten die Variablen direkt im System gesetzt werden:

```bash
export CAELI_INFRA_API_URL="https://infra.caeli-wind.de/api/"
export CAELI_INFRA_API_USERNAME="deine@email.de"
export CAELI_INFRA_API_PASSWORD="dein-sicheres-passwort"
export GOOGLE_MAPS_API_KEY="dein-google-maps-api-key"
export GOOGLE_MAPS_MAP_ID="dein-google-maps-map-id"
```

## Sicherheitshinweise

⚠️ **Wichtig**: 
- Niemals API-Schlüssel oder Passwörter direkt im Code hinterlegen
- Die `.env` Datei NICHT in die Versionskontrolle einschließen
- Für Produktionsumgebungen sichere Passwort-Manager verwenden

## Troubleshooting

### "Environment variable is required" Fehler
Wenn du Fehler wie "CAELI_INFRA_API_URL environment variable is required" erhältst:
1. Prüfe ob die `.env.local` oder `.env` Datei existiert und korrekt ist
2. Stelle sicher, dass alle erforderlichen Variablen gesetzt sind
3. Prüfe die Schreibweise der Variablennamen (Groß-/Kleinschreibung beachten)
4. Stelle sicher, dass keine Leerzeichen um die = stehen
5. Verwende keine Anführungszeichen um die Werte (außer sie sind Teil des Wertes)

**Beispiel korrekte .env.local:**
```bash
CAELI_INFRA_API_URL=https://infra.caeli-wind.de/api/
CAELI_INFRA_API_USERNAME=deine@email.de
CAELI_INFRA_API_PASSWORD=dein-passwort
```

**Falsch:**
```bash
CAELI_INFRA_API_URL = "https://infra.caeli-wind.de/api/"  # Leerzeichen und Anführungszeichen
```

### Google Maps funktioniert nicht
1. Prüfe ob der API-Key gültig ist
2. Stelle sicher, dass die Maps JavaScript API aktiviert ist
3. Prüfe die Domain-Einschränkungen des API-Keys 