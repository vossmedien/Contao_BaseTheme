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

Das Modul verwendet die öffentliche HubSpot Forms API, um Formulardaten direkt an HubSpot zu senden:

1. In jede Seite wird ein JavaScript eingebettet, das nach aktivierten Formularen sucht
2. Wenn ein Formular abgesendet wird, werden die Daten parallel an HubSpot übermittelt
3. Die Feldnamen werden entsprechend der Konfiguration im Backend gemappt

## Debug-Informationen

Wenn ein Formular mit aktivierter HubSpot-Integration abgesendet wird, werden in der Browser-Konsole (F12) detaillierte Informationen angezeigt:

- Erkannte Formulare
- Gelesene Formularfelder und ihre Zuordnung
- Übermittelte Daten
- Antwort von HubSpot

## Voraussetzungen

- Contao 5.5 oder höher
- Portal-ID und Formular-ID von HubSpot
- JavaScript muss im Browser aktiviert sein

## Kontakt

Bei Fragen oder Problemen kontaktieren Sie uns unter:

- E-Mail: christian.voss@caeli-wind.de
- Website: https://www.caeli-wind.de
