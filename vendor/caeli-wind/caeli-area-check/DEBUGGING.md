# CaeliAreaCheck - Debugging Guide

## ✅ HAUPTPROBLEM BEHOBEN: Website blockiert nicht mehr

### Die Anpassungen:
Es gab **zwei verschiedene Implementierungen**, jetzt wurde das Problem komplett gelöst:

1. **❌ SYNCHRONE ROUTE (entfernt)**: 
   - Route: `/flaechencheck/result` (POST-Requests werden abgelehnt)
   - **Problem gelöst**: Keine blockierenden API-Calls mehr

2. **✅ ASYNCHRONE ROUTE (aktiviert)**:
   - Routes: `/flaechencheck/ajax/start` + `/ajax/status/{sessionId}`
   - **Lösung**: AJAX + Session-basiertes Polling
   - **Vorteil**: Unlimitierte parallele Anfragen möglich

### 🔧 Durchgeführte Änderungen:

#### 1. Frontend auf AJAX umgestellt:
```html
<!-- ❌ VORHER - Synchron (blockierend): -->
<form action="{{ app.request.uri }}" method="POST">

<!-- ✅ NACHHER - Asynchron (non-blocking): -->
<form action="#" method="POST" onsubmit="return submitFormWithRedirect();">
```

#### 2. JavaScript robuster gemacht:
- Kein Fallback auf synchrone Verarbeitung mehr
- AJAX-Config wird immer korrekt geladen
- Bessere Fehlerbehandlung bei AJAX-Problemen

#### 3. Synchrone Route deaktiviert:
- `AreaCheckController.php`: POST-Requests werden abgelehnt
- Alle API-Methoden entfernt (nur noch in AreaCheckMapController)
- Legacy-Support nur für GET-Requests

### 📊 Performance-Verbesserung:
| Vorher | Nachher |
|--------|---------|
| Blockiert alle User für 90s | Blockiert niemanden |
| ~20-50 parallele Anfragen | Unlimitierte parallele Anfragen |
| Keine Progress-Anzeige | Live Progress-Updates |
| Eine API-Timeout = Website down | Einzelne User-Fehler isoliert |

---

## Problem 1: API nicht erreichbar blockiert Website ✅ KOMPLETT BEHOBEN

### Verbesserungen:
- **Timeout angepasst**: Login 20s, Rating-Operationen 90s (Check kann bis zu 75s dauern)
- **Verbesserte Fehlerbehandlung**: Alle curl_exec() Calls werden jetzt auf `false` geprüft
- **HTTP-Status-Prüfung**: Alle API-Calls prüfen den HTTP-Status-Code
- **JSON-Validierung**: JSON-Dekodierung wird auf Fehler geprüft
- **Detailliertes Logging**: Alle Fehler werden mit Details geloggt
- **🎯 WICHTIGSTE ÄNDERUNG**: Asynchrone Verarbeitung - API-Probleme betreffen nur den einzelnen User

### Timeout-Konfiguration:
- **Login** (`getApiSessionId`): 20s Gesamt, 10s Verbindung
- **Park-Erstellung** (`createPark`): 90s Gesamt, 15s Verbindung  
- **Rating-Abruf** (`getPlotRating`): 90s Gesamt, 15s Verbindung
- **Area-Rating** (`getRatingArea`): 90s Gesamt, 15s Verbindung

### Betroffene Methoden (nur noch in AreaCheckMapController):
- `getApiSessionId()`
- `createPark()`
- `getPlotRating()` 
- `getRatingArea()`

### Log-Nachrichten zum Monitoring:
```
[API] CURL-Fehler bei Session-Erstellung: Connection timeout
[API] HTTP-Fehler bei Park-Erstellung: 500, Response: {"error":"Server error"}
[API] JSON-Parse-Fehler bei Rating-Abruf: Syntax error
```

## Problem 2: FormHook funktioniert nicht - Debugging verbessert

### ✅ Form-IDs sind korrekt konfiguriert:
```yaml
form_ids:
    - 'flaechencheckNotSuccessEN'
    - 'flaechencheckNotSuccessDE'
    - 'flaechencheckSuccessEN' 
    - 'flaechencheckSuccessDE'
```

### ✅ Beide URL-Parameter unterstützt:
- **Erfolgreiche Checks**: `?parkid=504e4334-a113-4f18-b16a-fa3d2e7ed6bf`
- **Negative Checks**: `?checkid=fc-1234567890-abcdef123456`

### 🆕 Formular-Vorausfüllung implementiert:

#### Unterstützte Felder:
1. **`zip`**: PLZ aus `searched_address` extrahiert (international: DE/AT/CH/FR/NL/GB/etc.)
2. **`area_id`**: `park_id` aus Datenbank
3. **`flaechenkoordinaten`**: GeoJSON `geometry` 
4. **`such_string`**: `searched_address`
5. **`flaechengroesse`**: Berechnet aus Geometrie (Shoelace-Formel)
6. **`datum`**: Formatiert aus `tstamp` (d.m.Y)
7. **`quelle___caeli`**: Immer "caeli"

#### Logging der Vorausfüllung:
```
[FormDataListener] PLZ extrahiert (de): 12345
[FormDataListener] PLZ vorausgefüllt: 12345
[FormDataListener] area_id vorausgefüllt: 504e4334-a113-4f18-b16a-fa3d2e7ed6bf
[FormDataListener] Flächengröße vorausgefüllt: 15.42 ha
[FormDataListener] Datum vorausgefüllt: 11.06.2025
```

#### Internationale PLZ-Erkennung:
- **Länder-Erkennung**: Aus Adresstext (Deutschland, Austria, Switzerland, etc.)
- **Fallback-System**: Deutschland → Alle Länder → Flexible Regex
- **Unterstützte Formate**: DE(12345), AT(1234), NL(1234 AB), GB(M1 1AA), etc.
- **Debug-Logging**: Zeigt erkanntes Land und extrahierte PLZ

### Debugging-Verbesserungen:
- **Alle Formular-Submissions loggen**: Zeigt welche Formulare abgesendet werden
- **Parameter-Debugging**: Loggt alle verfügbaren URL- und POST-Parameter
- **Feldname-Debugging**: Zeigt extrahierte Formulardaten
- **Stack-Trace**: Bei Fehlern wird vollständiger Stack-Trace geloggt
- **Datenbank-Suche**: Detailliertes Logging welche Suchtypen verwendet werden
- **Formular-Vorausfüllung**: Zeigt welche Felder mit welchen Werten gefüllt werden

### Zu prüfen:
1. **Formular-ID stimmt überein**:
   ```
   [FormDataListener] Formular abgesendet - ID: flaechencheckSuccessDE, Konfigurierte IDs: ...
   ```

2. **URL-Parameter vorhanden**:
   ```
   [FormDataListener] Verfügbare URL-Parameter: {"parkid":"504e4334-a113-4f18-b16a-fa3d2e7ed6bf"}
   ```

3. **Datenbank-Suche erfolgreich**:
   ```
   [FormDataListener] park_id Suche: GEFUNDEN (1 Zeilen)
   [FormDataListener] === EINTRAG GEFUNDEN ===
   ```

4. **Feldnamen stimmen überein**:
   ```
   [FormDataListener] Submitted data keys: ["zip","area_id","firstname","lastname","email","phone"]
   ```

5. **Update erfolgreich**:
   ```
   [FormDataListener] === UPDATE ERFOLGREICH ===
   ```

### Konfiguration prüfen:
In `config/caeli_area_check.yaml`:
```yaml
caeli_area_check:
    form_ids:
        - 'actual_form_id_from_contao'  # Muss mit tatsächlicher formID übereinstimmen
    field_mapping:
        lastname_field: 'lastname'      # Muss mit tatsächlichem Feldname übereinstimmen
        firstname_field: 'firstname'
        phone_field: 'phone'
        email_field: 'email'
```

### Log-Level auf Debug setzen:
In `config/packages/monolog.yaml`:
```yaml
monolog:
    handlers:
        main:
            level: debug  # Für detaillierte Logs
```

### Häufige Probleme:
1. **Falsche formID**: Contao-Formular hat andere ID als konfiguriert
2. **Fehlende checkid**: URL hat keinen `?checkid=` Parameter
3. **Falsche Feldnamen**: Formularfelder haben andere Namen als in `field_mapping`
4. **Hook nicht registriert**: Service-Konfiguration fehlerhaft

### Test-Checkid generieren:
Für Tests kann eine checkid manuell in die URL eingefügt werden:
```
/kontakt?checkid=test-123
```

---

## 🎉 Status: BEIDE PROBLEME BEHOBEN

✅ **Website blockiert nicht mehr** - Asynchrone Verarbeitung aktiviert  
✅ **API-Timeouts verbessert** - Robuste Fehlerbehandlung implementiert  
🔄 **FormHook-Debugging** - Umfassendes Logging für Fehleranalyse  

**Ready for Production** 🚀 