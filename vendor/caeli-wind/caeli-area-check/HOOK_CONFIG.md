# Formular-Hook Konfiguration

Die Caeli Area Check Extension enthält einen Formular-Hook, der nach dem Absenden von Kontaktformularen die entsprechenden Flächencheck-Einträge in der Datenbank mit den Kontaktdaten aktualisiert.

## Funktionsweise

Nach dem Absenden eines konfigurierten Formulars werden folgende Daten aus dem Formular in den Flächencheck-Eintrag übertragen:
- Nachname (lastname)
- Vorname (firstname)  
- Telefon (phone)
- E-Mail (email)

Der korrekte Eintrag wird über die `checkid` aus dem URL-Parameter identifiziert.

## Konfiguration

### 1. Formular-IDs konfigurieren

Die Formular-IDs müssen in der Datei `config/caeli_area_check.yaml` konfiguriert werden:

```yaml
caeli_area_check:
    form_ids:
        - 'form_kontakt_1'      # Erste Formular-ID
        - 'form_kontakt_2'      # Zweite Formular-ID  
        - 'form_kontakt_3'      # Dritte Formular-ID
        - 'form_kontakt_4'      # Vierte Formular-ID
```

### 2. Formular-IDs herausfinden

Um die korrekte Formular-ID zu ermitteln:

1. Gehe in das Contao Backend
2. Navigiere zu `Layout` → `Formulargenerator` 
3. Öffne das entsprechende Formular
4. Schaue dir das Feld `Formular-ID` (form ID) an
5. Verwende diese ID in der Konfigurationsdatei

### 3. Feldnamen anpassen (optional)

Falls die Formularfelder andere Namen haben, können diese angepasst werden:

```yaml
caeli_area_check:
    field_mapping:
        lastname_field: 'nachname'    # Falls das Feld 'nachname' heißt
        firstname_field: 'vorname'    # Falls das Feld 'vorname' heißt  
        phone_field: 'telefon'        # Falls das Feld 'telefon' heißt
        email_field: 'email'          # Standard ist 'email'
```

## Voraussetzungen

1. **URL-Parameter**: Die Seite mit dem Formular muss den Parameter `checkid` in der URL haben
   - Beispiel: `https://example.com/kontakt?checkid=ABC123`

2. **Formularfelder**: Das Formular muss die entsprechenden Felder enthalten:
   - `lastname` (oder konfigurierter Name für Nachname)
   - `firstname` (oder konfigurierter Name für Vorname)
   - `phone` (oder konfigurierter Name für Telefon)
   - `email` (oder konfigurierter Name für E-Mail)

3. **Datenbank-Eintrag**: Es muss bereits ein Eintrag in der `tl_flaechencheck` Tabelle existieren

## Logging

Der Hook protokolliert seine Aktivitäten im Contao-Log. Bei Problemen schaue in:
- Backend → System → System-Log
- Log-Level: Debug, Info, Warning, Error

## Troubleshooting

### Formular wird nicht erkannt
- Prüfe ob die Formular-ID korrekt in der Konfiguration steht
- Vergleiche mit der ID im Backend (Formulargenerator)

### Keine Daten werden übertragen
- Prüfe ob der URL-Parameter `checkid` vorhanden ist
- Prüfe ob die Feldnamen im Formular korrekt sind
- Schaue ins System-Log für Fehlermeldungen

### Falscher Eintrag wird aktualisiert
- Der Hook sucht automatisch nach der korrekten checkid
- Bei numerischen IDs wird nach `id` gesucht
- Bei Text-IDs wird nach `park_id` gesucht 