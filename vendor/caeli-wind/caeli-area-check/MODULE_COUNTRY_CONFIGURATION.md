# Länder-Beschränkung für Caeli Area Check

## Überblick
Das Caeli Area Check Modul unterstützt jetzt eine konfigurierbare Länder-Beschränkung für die Geocoding-Suche. Dies ermöglicht es, die Suche auf bestimmte Länder zu beschränken.

## Konfiguration im Backend

### Moduleinstellungen
1. Im Contao Backend unter "Module" das entsprechende Area Check Map Modul bearbeiten
2. Im Bereich "Konfiguration" das neue Feld "Erlaubte Länder" verwenden
3. ISO 3166-1 Alpha-2 Ländercodes eingeben (z.B. "de", "gb", "at")

### Beispiel-Konfigurationen

**Nur Deutschland:**
```
de
```

**Deutschland und Österreich:**
```
de
at
```

**England/UK:**
```
gb
```

**Deutschland, Österreich und Schweiz:**
```
de
at
ch
```

## Unterstützte Ländercodes

| Land | Code | Notizen |
|------|------|---------|
| Deutschland | de | Standard-Fallback |
| Österreich | at | |
| Schweiz | ch | |
| England/UK | gb | |
| Frankreich | fr | |
| Niederlande | nl | |
| Belgien | be | |
| Italien | it | |
| Spanien | es | |
| Polen | pl | |

## Technische Details

### Fallback-Verhalten
- Wenn keine Länder konfiguriert sind, wird automatisch Deutschland (`de`) verwendet
- Die Konfiguration wird als Array an die Google Maps Geocoding API weitergegeben
- Bei einem Land wird ein String, bei mehreren Ländern ein Array verwendet

### JavaScript-Konfiguration
Die Länder-Beschränkung wird automatisch in das JavaScript übertragen:
```javascript
window.CaeliAreaCheckConfig = {
    allowedCountries: ['de'] // oder ['de', 'at', 'ch']
};
```

### Debugging
Aktiviere das Browser-Debugging um die Länder-Beschränkung zu überprüfen:
```javascript
console.log('[DEBUG] Geocoding country restriction:', geocodeRequest.componentRestrictions.country);
```

## Anwendungsfälle

### Seite A: Nur Deutschland
- Moduleinstellung: `de`
- Verwendung für deutsche Windparks

### Seite B: Nur England  
- Moduleinstellung: `gb`
- Verwendung für britische Windparks

### Seite C: DACH-Region
- Moduleinstellung: `de`, `at`, `ch`
- Verwendung für deutschsprachigen Raum

## Fehlerbehebung

### "InvalidValueError: not a string"
Dieser Fehler tritt auf wenn:
- Die Länder-Konfiguration fehlerhaft ist
- Debugging wurde hinzugefügt um die tatsächlichen Werte zu überprüfen

### Keine Suchergebnisse
Überprüfe:
- Korrekte ISO-Codes verwendet
- Länder-Beschränkung nicht zu restriktiv
- Google Maps API unterstützt das Land 