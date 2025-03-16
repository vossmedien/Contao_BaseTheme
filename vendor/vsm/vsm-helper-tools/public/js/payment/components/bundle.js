/**
 * Stripe Components Bundle
 * 
 * Bündelt alle Stripe-Komponenten und macht sie als globale Objekte verfügbar.
 * Diese Datei ersetzt die Verwendung von ES6-Modulen, wenn diese nicht unterstützt werden.
 */

// Self-executing function to isolate scope
(function() {
    
    /**
     * StripeBase - Basisklasse für Stripe-Implementierungen
     */
    class StripeBase {
        constructor(config = {}) {
            this.config = config;
            this.debug = config.debug || false;
            this.stripeKey = config.stripeKey || config.publicKey;
            this.stripe = null;
            this.clientSecret = null;
            
            if (this.debug) {
                console.log('StripeBase initialisiert mit Konfiguration:', config);
            }
        }
        
        /**
         * Liefert Standardtexte für Fehlermeldungen
         */
        getDefaultTexts() {
            return {
                error: 'Ein Fehler ist aufgetreten',
                errorRequired: 'Bitte füllen Sie alle Pflichtfelder aus',
                errorPayment: 'Bei der Zahlung ist ein Fehler aufgetreten',
                loading: 'Wird geladen...',
                success: 'Die Zahlung war erfolgreich'
            };
        }
        
        /**
         * Initialisiert die Stripe-Instanz
         */
        initializeStripe() {
            if (!window.Stripe) {
                console.error('Stripe.js ist nicht geladen');
                return false;
            }
            
            if (!this.stripeKey) {
                console.error('Kein Stripe-API-Schlüssel konfiguriert');
                return false;
            }
            
            try {
                this.stripe = window.Stripe(this.stripeKey);
                return true;
            } catch (error) {
                console.error('Fehler bei der Initialisierung von Stripe:', error);
                return false;
            }
        }
        
        /**
         * Formatiert einen Preis mit Währungssymbol
         */
        formatPrice(amount, currency = 'EUR') {
            return new Intl.NumberFormat('de-DE', {
                style: 'currency',
                currency: currency.toUpperCase()
            }).format(amount);
        }
        
        /**
         * Zeigt eine Fehlermeldung bei der Zahlung an
         */
        showPaymentError(message, selector = '#payment-error') {
            const errorElement = document.querySelector(selector);
            if (errorElement) {
                errorElement.textContent = message;
                errorElement.style.display = 'block';
            } else if (this.debug) {
                console.error('Zahlungsfehler:', message);
            }
        }
    }
    
    /**
     * UIHandler - Behandelt UI-Interaktionen
     */
    class UIHandler {
        constructor(config = {}) {
            this.texts = config.texts || {};
            this.debug = config.debug || false;
        }
        
        /**
         * Zeigt einen Ladeindikator
         */
        showLoadingIndicator(selector = '#loading-indicator') {
            const loadingElement = document.querySelector(selector);
            if (loadingElement) {
                loadingElement.style.display = 'block';
            }
        }
        
        /**
         * Versteckt einen Ladeindikator
         */
        hideLoadingIndicator(selector = '#loading-indicator') {
            const loadingElement = document.querySelector(selector);
            if (loadingElement) {
                loadingElement.style.display = 'none';
            }
        }
        
        /**
         * Setzt den Ladestand eines Formulars
         */
        setFormLoading(form, isLoading) {
            const submitButton = form.querySelector('button[type="submit"]');
            const loadingSpinner = form.querySelector('[data-form-spinner]');
            
            if (submitButton) {
                submitButton.disabled = isLoading;
            }
            
            if (loadingSpinner) {
                if (isLoading) {
                    loadingSpinner.classList.remove('d-none');
                } else {
                    loadingSpinner.classList.add('d-none');
                }
            }
        }
        
        /**
         * Zeigt eine Fehlermeldung in einem Formular an
         */
        showFormError(errorMessage, form) {
            if (!form) {
                form = document.querySelector('form');
                if (!form) return;
            }
            
            // Fehlercontainer finden oder erstellen
            let errorContainer = form.querySelector('[data-form-error]');
            
            if (!errorContainer) {
                errorContainer = document.createElement('div');
                errorContainer.setAttribute('data-form-error', '');
                errorContainer.className = 'alert alert-danger';
                form.prepend(errorContainer);
            }
            
            errorContainer.textContent = errorMessage;
            errorContainer.classList.remove('d-none');
            
            // Zum Fehlercontainer scrollen
            errorContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
    
    /**
     * FormValidator - Validiert Formulare
     */
    class FormValidator {
        constructor(config = {}) {
            this.texts = config.texts || {};
            this.debug = config.debug || false;
            this.uiHandler = config.uiHandler || new UIHandler(config);
        }
        
        /**
         * Validiert ein Formular
         */
        validateForm(form) {
            if (!form) return false;
            
            // HTML5-Validierung verwenden
            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                
                // Erstes ungültiges Feld fokussieren
                const invalidField = form.querySelector(':invalid');
                if (invalidField) {
                    invalidField.focus();
                }
                
                // Fehlermeldung anzeigen
                const errorMessage = this.texts.errorRequired || 'Bitte füllen Sie alle Pflichtfelder aus.';
                this.uiHandler.showFormError(errorMessage, form);
                
                return false;
            }
            
            return true;
        }
        
        /**
         * Validiert eine E-Mail-Adresse
         */
        validateEmail(email) {
            const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
            return re.test(String(email).toLowerCase());
        }
    }
    
    /**
     * StripeElementsForm - Implementiert Stripe Elements
     */
    class StripeElementsForm extends StripeBase {
        constructor(config = {}) {
            super(config);
            
            // Standardtexte für Elements-Formulare
            const defaultTexts = {
                ...this.getDefaultTexts(),
                submitButton: 'Jetzt bezahlen',
                loadingButton: 'Wird verarbeitet...',
                cardError: 'Ihre Kreditkartendaten sind ungültig.',
                cardComplete: 'Kreditkartendaten vollständig',
                cardIncomplete: 'Bitte geben Sie Ihre Kreditkartendaten ein',
                successMessage: 'Vielen Dank für Ihre Zahlung!',
            };
            
            this.texts = { ...defaultTexts, ...config.texts };
            this.successUrl = config.successUrl || window.location.href;
            this.cancelUrl = config.cancelUrl || window.location.href;
            this.formSelector = config.formSelector || '#payment-form';
            this.cardElementSelector = config.cardElementSelector || '#card-element';
            this.cardErrorSelector = config.cardErrorSelector || '#card-errors';
            
            // UI-Handler und FormValidator initialisieren
            this.uiHandler = config.uiHandler || new UIHandler({ 
                texts: this.texts,
                debug: this.debug
            });
            
            this.formValidator = config.formValidator || new FormValidator({
                texts: this.texts,
                debug: this.debug,
                uiHandler: this.uiHandler
            });
            
            // Stripe Elements Objekte
            this.elements = null;
            this.cardElement = null;
        }
        
        /**
         * Initialisiert das Formular
         */
        init() {
            if (!this.initializeStripe()) {
                return;
            }
            
            this.setupStripeElements();
            this.setupFormSubmission();
            
            if (this.debug) {
                console.log('StripeElementsForm erfolgreich initialisiert');
            }
        }
        
        /**
         * Initialisiert Stripe Elements
         */
        setupStripeElements() {
            this.elements = this.stripe.elements();
            
            const cardElement = document.querySelector(this.cardElementSelector);
            if (!cardElement) {
                if (this.debug) {
                    console.warn(`Card Element mit Selektor ${this.cardElementSelector} nicht gefunden`);
                }
                return;
            }
            
            // Stil für das Kartenelement
            const style = {
                base: {
                    color: '#32325d',
                    fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
                    fontSmoothing: 'antialiased',
                    fontSize: '16px',
                    '::placeholder': {
                        color: '#aab7c4'
                    }
                },
                invalid: {
                    color: '#fa755a',
                    iconColor: '#fa755a'
                }
            };
            
            // Kartenelement erstellen
            this.cardElement = this.elements.create('card', { style: style });
            this.cardElement.mount(cardElement);
            
            // Event-Listener für Kartenänderungen
            const displayError = document.querySelector(this.cardErrorSelector);
            if (displayError) {
                this.cardElement.on('change', function(event) {
                    if (event.error) {
                        displayError.textContent = event.error.message;
                        displayError.style.display = 'block';
                    } else {
                        displayError.textContent = '';
                        displayError.style.display = 'none';
                    }
                });
            }
            
            if (this.debug) {
                console.log('Stripe Elements erfolgreich eingerichtet');
            }
        }
    }
    
    // Als globale Variablen bereitstellen
    window.StripeBase = StripeBase;
    window.UIHandler = UIHandler;
    window.FormValidator = FormValidator;
    window.StripeElementsForm = StripeElementsForm;
    
    // Debug-Info ausgeben
    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
        console.log('Stripe-Komponenten als globale Variablen verfügbar:');
        console.log('- StripeBase');
        console.log('- UIHandler');
        console.log('- FormValidator');
        console.log('- StripeElementsForm');
    }
})(); 