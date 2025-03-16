/**
 * Basisklasse für Stripe-Komponenten
 * 
 * Enthält gemeinsame Funktionalitäten für alle Stripe-bezogenen Komponenten.
 */
class StripeBase {
    /**
     * Konstruktor für die Basisklasse
     * @param {Object} config - Konfigurationsobjekt
     */
    constructor(config) {
        console.log('StripeBase wird initialisiert...');

        // Prüfen ob Stripe global verfügbar ist
        if (typeof Stripe === 'undefined') {
            console.error('FEHLER: Stripe ist nicht geladen! Bitte Stripe.js Script in der Seite prüfen.');
            alert('Fehler beim Laden von Stripe. Bitte versuchen Sie es später erneut oder kontaktieren Sie den Support.');
            return;
        }

        // Stripe-Schlüssel ist erforderlich
        if (!config || !config.stripeKey) {
            console.error('FEHLER: Stripe-Schlüssel fehlt in der Konfiguration!');
            return;
        }

        try {
            this.stripe = Stripe(config.stripeKey);
            this.successUrl = this.ensureAbsoluteUrl(config.successUrl || window.location.href);
            this.cancelUrl = this.ensureAbsoluteUrl(config.cancelUrl || window.location.href);
            this.createUserAccount = config.createUserAccount || false;
            this.currency = (config.currency || 'eur').toLowerCase();
            this.texts = config.texts || this.getDefaultTexts();
            this.debug = config.debug || false;

            console.log('StripeBase erfolgreich initialisiert mit:', {
                stripeKey: config.stripeKey.substring(0, 5) + '...',
                successUrl: this.successUrl,
                createUserAccount: this.createUserAccount,
                currency: this.currency,
                debug: this.debug
            });
        } catch (error) {
            console.error('FEHLER bei der Initialisierung der StripeBase:', error);
        }
    }

    /**
     * Liefert Standardtexte für die UI
     * @returns {Object} - Objekt mit Standardtexten
     */
    getDefaultTexts() {
        return {
            errorRequired: 'Bitte füllen Sie alle Pflichtfelder aus.',
            errorGeneral: 'Ein Fehler ist aufgetreten',
            errorProductNotFound: 'Produkt nicht gefunden',
            validationPassword: 'Das Passwort muss mindestens 8 Zeichen lang sein und mindestens eine Zahl, einen Groß- und einen Kleinbuchstaben enthalten.',
            validationEmail: 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            validationUsername: 'Der Benutzername muss zwischen 3 und 20 Zeichen lang sein und darf nur Buchstaben, Zahlen, Unterstrich und Bindestrich enthalten.',
            loadingText: 'Zahlung wird vorbereitet...',
            stripeErrors: {
                incomplete_number: 'Die Kartennummer ist unvollständig.',
                invalid_number: 'Die Kartennummer ist ungültig.',
                incomplete_expiry: 'Das Ablaufdatum ist unvollständig.',
                invalid_expiry: 'Das Ablaufdatum ist ungültig.',
                incomplete_cvc: 'Der Sicherheitscode ist unvollständig.',
                invalid_cvc: 'Der Sicherheitscode ist ungültig.',
                default: 'Ein Fehler ist aufgetreten.'
            }
        };
    }

    /**
     * Stellt sicher, dass eine URL absolut ist
     * @param {string} url - URL, die überprüft werden soll
     * @returns {string} - Absolute URL
     */
    ensureAbsoluteUrl(url) {
        if (!url) return window.location.href;
        
        // Wenn die URL bereits absolut ist (mit http:// oder https://)
        if (url.match(/^https?:\/\//i)) {
            return url;
        }
        
        // Wenn es eine relative URL mit führendem Slash ist
        if (url.startsWith('/')) {
            return window.location.origin + url;
        }
        
        // Ansonsten relativ zum aktuellen Pfad
        const basePath = window.location.href.substring(0, window.location.href.lastIndexOf('/') + 1);
        return basePath + url;
    }

    /**
     * Formatiert einen Preis mit Währungssymbol
     * @param {number} amount - Betrag in der kleinsten Währungseinheit (z.B. Cent)
     * @param {string} currency - Währungscode
     * @returns {string} - Formatierter Preiswert
     */
    formatCurrency(amount, currency) {
        const curr = currency || this.currency || 'eur';
        return this.formatPrice(amount, curr);
    }

    /**
     * Formatiert einen Preis basierend auf der Währung
     * @param {number} amount - Betrag in der kleinsten Währungseinheit
     * @param {string} currency - Währungscode
     * @returns {string} - Formatierter Preiswert
     */
    formatPrice(amount, currency) {
        // Umrechnung von Cent in Euro (oder äquivalent)
        const value = parseFloat(amount) / 100;
        return new Intl.NumberFormat('de-DE', { style: 'currency', currency: currency.toUpperCase() }).format(value);
    }

    /**
     * Generiert einen unique Download-Token
     * @returns {string} - Zufälliger Token
     */
    generateDownloadToken() {
        return Array.from(window.crypto.getRandomValues(new Uint8Array(16)))
            .map(b => b.toString(16).padStart(2, '0'))
            .join('');
    }

    /**
     * Generiert eine eindeutige Session-ID
     * @returns {string} - Eindeutige Session ID
     */
    generateSessionId() {
        return 'sess_' + Date.now() + '_' + Math.random().toString(36).substring(2, 15);
    }
}

// Export für ES6 Module
export default StripeBase; 