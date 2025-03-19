class StripeElementHandler {
    constructor(stripe) {
        this.stripe = stripe;
        this.elements = null;
    }

    async initialize(amount, currency, errorCallback) {
        try {
            // Validiere die Eingabe
            if (!amount || isNaN(parseFloat(amount))) {
                throw new Error('Ungültiger Betrag für Stripe Elements: ' + amount);
            }
            
            // Stelle sicher, dass Währung normalisiert ist
            const normalizedCurrency = (currency || 'eur').toLowerCase();
            if (!['eur', 'usd', 'gbp'].includes(normalizedCurrency)) {
                console.warn('Ungewöhnliche Währung für Stripe Elements: ' + normalizedCurrency);
            }
            
            // Erstelle die Stripe Elements Instanz
            const options = this.createElementOptions(amount, normalizedCurrency);
            console.log('Initialisiere Stripe Elements mit Optionen:', options);
            
            this.elements = this.stripe.elements(options);
            
            // Erstelle das Payment Element
            const paymentElement = this.elements.create('payment');
            
            // Element in den DOM einfügen
            this.mountElement(paymentElement);
            
            // Event-Listener hinzufügen
            this.initializeListeners(paymentElement, errorCallback);
            
            console.log('Stripe Elements erfolgreich initialisiert');
            
            // Gib die Elements Instanz zurück
            return this.elements;
        } catch (error) {
            console.error('Fehler bei der Initialisierung von Stripe Elements:', error);
            throw error;
        }
    }

    createElementOptions(amount, currency) {
        return {
            mode: 'payment',
            amount: Math.round(parseFloat(amount)), // Die Beträge sollten bereits in Cent sein
            currency: currency.toLowerCase(),
            locale: 'de',
            appearance: this.getAppearanceOptions(),
            automatic_payment_methods: {
                enabled: true
            }
        };
    }

    getAppearanceOptions() {
        const getVar = (name, fallback) => {
            return getComputedStyle(document.documentElement)
                .getPropertyValue(name)
                .trim() || fallback;
        };

        return {
            theme: 'stripe',
            labels: 'floating',
            variables: {
                colorPrimary: getVar('--bs-primary', '#007bff'),
                colorText: getVar('--bs-body-color', '#212529'),
                colorBackground: getVar('--bs-body-bg', '#ffffff'),
                colorDanger: getVar('--bs-danger', '#dc3545'),
                colorTextSecondary: getVar('--bs-secondary', '#6c757d'),
                colorTextPlaceholder: getVar('--bs-gray-500', '#adb5bd'),
                fontFamily: getVar('--bs-body-font-family', '-apple-system, system-ui, sans-serif'),
            },
            rules: {
                '.Input': {
                    border: getVar('--bs-input-border', '1px solid var(--bs-border-color)'),
                    borderRadius: getVar('--bs-input-border-radius', 'var(--bs-border-radius)'),
                    boxShadow: getVar('--bs-input-shadow', '0px 1px 1px rgba(0, 0, 0, 0.03), 0px 3px 6px rgba(0, 0, 0, 0.02)'),
                    color: getVar('--bs-input-color', 'var(--bs-body-color)'),
                }
            }
        };
    }

    mountElement(paymentElement) {
        const container = document.getElementById('payment-element');
        if (!container) {
            console.error('Payment Element Container nicht gefunden!');
            throw new Error('DOM-Element #payment-element nicht gefunden. Bitte überprüfen Sie Ihr HTML.');
        }
        container.innerHTML = '';
        paymentElement.mount('#payment-element');
    }

    initializeListeners(paymentElement, errorCallback) {
        // Fehler-Element suchen
        const displayError = document.getElementById('payment-errors');
        if (!displayError) {
            console.warn('Payment Error Container (#payment-errors) nicht gefunden!');
        }
        
        // "change" Event-Listener für Validierung
        paymentElement.on('change', (event) => {
            // Wenn kein Fehler-Container gefunden wurde, nur loggen
            if (!displayError) {
                if (event.error) {
                    console.error('Stripe Element Fehler:', event.error.code, event.error.message);
                }
                return;
            }
            
            if (event.error) {
                const errorMessage = typeof errorCallback === 'function' ? 
                    errorCallback(event.error.code) : 
                    event.error.message;
                    
                console.log('Stripe Element Fehler:', errorMessage);
                displayError.textContent = errorMessage;
                displayError.style.display = 'block';
            } else {
                displayError.textContent = '';
                displayError.style.display = 'none';
            }
        });
        
        // "ready" Event-Listener für Initialisierungsbestätigung
        paymentElement.on('ready', () => {
            console.log('Stripe Element ist bereit');
        });
    }
}