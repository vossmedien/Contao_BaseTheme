# Stripe Checkout mit Redirect-Flow

Diese Dokumentation beschreibt die verbesserte Stripe Checkout Implementierung mit Redirect-Flow für Contao 5. 
Diese Methode ist weniger fehleranfällig als die Webhook-basierte Verarbeitung und eignet sich besonders gut für 
einfache Produkt-Verkäufe und Download-Produkte.

## Überblick

Die Implementierung nutzt den folgenden Ablauf:

1. Kunde wählt ein Produkt aus und klickt auf "Kaufen"
2. Kunde gibt seine persönlichen Daten ein
3. Stripe Checkout wird geöffnet, wo der Kunde seine Zahlungsinformationen eingibt
4. Bei erfolgreicher Zahlung wird der Kunde zur Erfolgsseite weitergeleitet (Redirect)
5. Auf der Erfolgsseite werden alle notwendigen Aktionen ausgeführt (Benutzererstellung, E-Mail-Versand, etc.)

Vorteile gegenüber der Webhook-basierten Verarbeitung:

- Weniger Fehleranfälligkeit, da keine Abhängigkeit von Webhooks
- Einfachere Implementierung und Wartung
- Kein Timeout-Problem bei der Verarbeitung
- Sofortiges Feedback an den Benutzer

## Installation

Die Tabellen für Stripe Checkout werden automatisch bei der Installation der Erweiterung erstellt.
Falls dies nicht der Fall ist, können Sie die Tabellen manuell mit dem folgenden SQL-Schema erstellen:

```sql
-- Siehe vendor/vsm/vsm-helper-tools/config/schema.sql
```

## Konfiguration

### Stripe API Keys

Sie müssen Ihre Stripe API Keys in der `config/config.yaml` oder als Umgebungsvariablen konfigurieren:

```yaml
# config/config.yaml
vsm_helper_tools:
    stripe:
        public_key: 'pk_test_xxx'
        secret_key: 'sk_test_xxx'
```

Oder als Umgebungsvariablen in der `.env`-Datei:

```
STRIPE_PUBLIC_KEY=pk_test_xxx
STRIPE_SECRET_KEY=sk_test_xxx
```

### Erfolgseite

Erstellen Sie eine Seite in Contao, die nach erfolgreicher Zahlung angezeigt werden soll. Konfigurieren Sie diese Seite
mit dem Template `fe_payment_success.html5`. 

Diese Seite zeigt eine Bestellbestätigung und, falls zutreffend, einen Download-Link für digitale Produkte an.

## Verwendung

### Produktkonfiguration

Konfigurieren Sie Ihre Produkte wie gewohnt im RSCE-Element "Produkt-Payment". 

Wichtige Einstellungen:

- **Stripe Zahlungen aktivieren**: Muss aktiviert sein
- **Stripe Public Key**: Ihr Stripe Public Key (oder leer lassen für globalen Key)
- **Währung**: EUR, USD oder GBP
- **Erfolgsseite**: Die Seite, die nach erfolgreicher Zahlung angezeigt wird
- **E-Mail-Template**: Das zu verwendende E-Mail-Template für Bestellbestätigungen

Für Download-Produkte:

- **Datei-Verkauf**: Aktivieren Sie diese Option
- **Download-Datei**: Wählen Sie die zu verkaufende Datei
- **Ablauf in Tagen**: Nach wie vielen Tagen der Download abläuft
- **Download-Limit**: Maximale Anzahl an Downloads

### Benutzerregistrierung

Um automatisch Benutzerkonten für Käufer zu erstellen:

- **Benutzer anlegen**: Aktivieren Sie diese Option
- **Mitgliedergruppe**: Wählen Sie die Gruppe für neue Benutzer

### Formularkonfiguration

Sie können auswählen, welche Felder im Checkout-Formular angezeigt werden:

- **Formularfelder**: Wählen Sie die Felder, die im Formular angezeigt werden sollen (Name, E-Mail, etc.)
- **Datenschutzhinweis anzeigen**: Bei Aktivierung wird ein Datenschutz-Checkbox angezeigt
- **Datenschutz-Seite**: Die Seite mit der Datenschutzerklärung

## Bestellprozess

1. Der Kunde wählt ein Produkt aus und klickt auf den Kaufen-Button
2. Ein Modal-Dialog öffnet sich, in dem der Kunde seine persönlichen Daten eingeben kann
3. Nach dem Absenden des Formulars wird der Kunde zu Stripe Checkout weitergeleitet
4. Der Kunde gibt seine Zahlungsinformationen ein
5. Bei erfolgreicher Zahlung wird der Kunde zur konfigurierten Erfolgsseite weitergeleitet
6. Auf der Erfolgsseite werden verschiedene Aktionen ausgeführt:
   - Die Bestellung wird als bezahlt markiert
   - Der Benutzer erhält eine Bestellbestätigung per E-Mail
   - Falls konfiguriert, wird ein Benutzerkonto erstellt
   - Bei Download-Produkten wird ein Download-Link generiert und angezeigt

## Fehlerbehandlung

Wenn ein Fehler auftritt, wird der Benutzer zur Startseite weitergeleitet. Im Debug-Modus werden zusätzliche 
Informationen im Browser und im Contao-Log angezeigt.

## Anpassungen

### E-Mail-Templates

Sie können eigene E-Mail-Templates für Bestellbestätigungen erstellen. Legen Sie dazu eine neue Datei im 
Verzeichnis `templates/emails/` an, z.B. `email_custom_confirmation.html5`.

Verfügbare Variablen in E-Mail-Templates:

- `{{order_id}}` - Bestellnummer
- `{{product_name}}` - Produktname
- `{{product_price}}` - Produktpreis
- `{{customer_name}}` - Kundenname
- `{{customer_email}}` - Kunden-E-Mail
- `{{download_link}}` - Download-Link (falls verfügbar)
- `{{download_expires}}` - Tage bis zum Ablauf des Downloads
- `{{download_limit}}` - Maximale Anzahl an Downloads

### Erfolgsseite

Sie können die Erfolgsseite anpassen, indem Sie das Template `fe_payment_success.html5` überschreiben.

## Technische Details

Die Implementierung nutzt die folgenden Komponenten:

- **StripeCheckoutHandler.js**: Clientseitige JavaScript-Klasse für den Checkout-Prozess
- **StripeCheckoutController.php**: Serverseitige Controller-Klasse für die API-Endpoints
- **fe_payment_success.html5**: Template für die Erfolgsseite

### API-Endpoints

- `/stripe/create-checkout-session`: Erstellt eine Stripe Checkout-Session
- `/stripe/checkout/success`: Verarbeitet erfolgreiche Zahlungen
- `/stripe/download/{token}`: Stellt den Download für gekaufte Dateien bereit

### Datenbank-Tabellen

- `tl_stripe_orders`: Speichert Bestellungen
- `tl_stripe_downloads`: Speichert Download-Links
- `tl_stripe_session_data`: Speichert temporäre Session-Daten

## Fehlerbehebung

Wenn Sie Probleme mit der Stripe-Integration haben, prüfen Sie Folgendes:

1. Sind die Stripe API Keys korrekt konfiguriert?
2. Ist die Erfolgseite korrekt eingerichtet?
3. Ist das E-Mail-Template korrekt formatiert?
4. Prüfen Sie die Contao-Logs auf Fehler 