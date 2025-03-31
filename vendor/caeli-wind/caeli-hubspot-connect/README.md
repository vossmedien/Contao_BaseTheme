![Alt text](docs/logo.png?raw=true "logo")


# Caeli HubSpot Connect

Ein Contao-Modul zur Integration von Formularen mit HubSpot für Contao 5.5.

## Funktionalitäten

- Direkte Anbindung von Contao-Formularen an HubSpot ohne API-Key
- Einfache Konfiguration von Portal-ID und Formular-ID im Backend
- Direkte Zuordnung von Contao-Formularfeldern zu HubSpot-Formularfeldern
- Automatische Übermittlung der Formulardaten an HubSpot

## Installation

```bash
composer require caeli-wind/caeli-hubspot-connect
```

Nach der Installation den Contao-Cache leeren:
1. "Systemwartung" im Backend
2. "Produktions-Cache leeren" auswählen

## Verwendung

1. In der Contao-Installation zum Formular-Manager navigieren
2. Formular erstellen oder bearbeiten
3. Im Bereich "HubSpot-Integration" die Option "Formular an HubSpot anbinden" aktivieren
4. Portal-ID und Formular-ID eingeben
5. Formularfelder erstellen oder bearbeiten und im Feld "HubSpot-Feldname" den internen Feldnamen eingeben (z.B. "email" für das E-Mail-Feld)

## HubSpot-Feldnamen

Hier sind einige häufig verwendete HubSpot-Feldnamen:
- `email` - E-Mail-Adresse
- `firstname` - Vorname
- `lastname` - Nachname
- `phone` - Telefonnummer
- `company` - Firma
- `jobtitle` - Position
- `website` - Website
- `address` - Adresse
- `city` - Stadt
- `state` - Bundesland
- `zip` - Postleitzahl
- `country` - Land
- `message` - Nachricht

Sie können auch benutzerdefinierte Feldnamen aus Ihrem HubSpot-Formular verwenden.

## Funktionsweise

Das Modul verwendet den `processFormData`-Hook von Contao und die öffentliche HubSpot Forms API:

1. Wenn ein Formular abgesendet wird, prüft das Modul, ob die HubSpot-Integration aktiviert ist
2. Die Formulardaten werden serverseitig an HubSpot übermittelt
3. Die Feldnamen werden entsprechend der Konfiguration im Backend gemappt
4. Der Nutzer merkt von der Integration nichts und erhält die normale Formularbestätigung

## Sicherheit und Datenschutz

Da die Übermittlung an HubSpot serverseitig stattfindet:
- Werden keine zusätzlichen Cookies gesetzt
- Ist kein JavaScript im Browser notwendig
- Werden nur die Daten übermittelt, die auch im Formular eingegeben wurden

## Voraussetzungen

- Contao 5.5 oder höher
- Portal-ID und Formular-ID von HubSpot

## Kontakt

Bei Fragen oder Problemen kontaktieren Sie uns unter:

- E-Mail: christian.voss@caeli-wind.de
- Website: https://www.caeli-wind.de
