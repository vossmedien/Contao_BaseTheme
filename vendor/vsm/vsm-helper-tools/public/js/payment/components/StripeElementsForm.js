/**
 * StripeElementsForm-Klasse für Stripe Elements Integration
 * 
 * Implementiert Stripe Elements für Zahlungsformulare mit direkter Kartenerfassung.
 * Erbt von StripeBase und nutzt UIHandler und FormValidator.
 */
import StripeBase from './StripeBase.js';
import UIHandler from './UIHandler.js';
import FormValidator from './FormValidator.js';

class StripeElementsForm extends StripeBase {
    /**
     * Konstruktor für das StripeElementsForm
     * @param {Object} config - Konfigurationsobjekt
     */
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
        this.paymentIntentId = null;
        
        if (this.debug) {
            console.log('StripeElementsForm initialisiert mit Konfiguration:', config);
        }
        
        // Initialisierung starten
        this.init();
    }
    
    /**
     * Initialisiert das Elements-Formular
     */
    init() {
        if (this.debug) {
            console.log('Initialisiere Stripe Elements Formular');
        }
        
        // Prüfen, ob Stripe verfügbar ist
        if (!this.stripe) {
            console.error('Stripe ist nicht initialisiert. Bitte stellen Sie sicher, dass die Stripe.js Bibliothek geladen ist.');
            return;
        }
        
        // Stripe Elements initialisieren
        this.initializeElements();
        
        // Event-Listener für Formular-Submission hinzufügen
        this.setupFormSubmission();
    }
    
    /**
     * Initialisiert Stripe Elements
     */
    initializeElements() {
        // Stripe Elements erstellen
        this.elements = this.stripe.elements();
        
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
        
        // Kartenelement in das DOM einfügen
        const cardElementContainer = document.querySelector(this.cardElementSelector);
        if (!cardElementContainer) {
            console.error(`Kartenelement-Container mit Selektor ${this.cardElementSelector} nicht gefunden`);
            return;
        }
        
        this.cardElement.mount(cardElementContainer);
        
        // Event-Listener für Kartenänderungen
        this.cardElement.on('change', (event) => {
            this.handleCardChange(event);
        });
        
        if (this.debug) {
            console.log('Stripe Elements initialisiert');
        }
    }
    
    /**
     * Verarbeitet Änderungen am Kartenelement
     * @param {Object} event - Das Stripe Elements Event
     */
    handleCardChange(event) {
        const displayError = document.querySelector(this.cardErrorSelector);
        
        if (!displayError) {
            if (this.debug) {
                console.warn(`Fehleranzeige mit Selektor ${this.cardErrorSelector} nicht gefunden`);
            }
            return;
        }
        
        if (event.error) {
            displayError.textContent = event.error.message;
            displayError.style.display = 'block';
        } else {
            displayError.textContent = '';
            displayError.style.display = 'none';
        }
        
        // Zusätzliche Informationen für Benutzer anzeigen
        if (event.complete) {
            displayError.textContent = this.texts.cardComplete;
            displayError.style.color = 'green';
            displayError.style.display = 'block';
        } else if (!event.error) {
            displayError.textContent = this.texts.cardIncomplete;
            displayError.style.color = '#aab7c4';
            displayError.style.display = 'block';
        }
    }
    
    /**
     * Richtet Event-Listener für Formular-Submission ein
     */
    setupFormSubmission() {
        const form = document.querySelector(this.formSelector);
        
        if (!form) {
            if (this.debug) {
                console.warn(`Zahlungsformular mit Selektor ${this.formSelector} nicht gefunden`);
            }
            return;
        }
        
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            this.handleFormSubmission(form);
        });
        
        if (this.debug) {
            console.log('Formular-Submission-Handler eingerichtet');
        }
    }
    
    /**
     * Verarbeitet die Formular-Submission
     * @param {HTMLFormElement} form - Das abgesendete Formular
     */
    async handleFormSubmission(form) {
        if (this.debug) {
            console.log('Formular abgesendet');
        }
        
        // Formular validieren
        if (!this.formValidator.validateForm(form)) {
            return;
        }
        
        // Formular in Ladestand versetzen
        this.uiHandler.setFormLoading(form, true);
        
        try {
            // Kundendaten aus dem Formular sammeln
            const customerData = this.collectCustomerData(form);
            
            // Produktdaten aus dem Formular oder Attributen holen
            const productData = this.getProductDataFromForm(form);
            
            if (!productData || !productData.title || !productData.price) {
                throw new Error('Unvollständige Produktdaten');
            }
            
            // Payment Intent erstellen
            await this.createPaymentIntent(productData, customerData);
            
            // Zahlung bestätigen
            await this.confirmCardPayment(customerData);
            
        } catch (error) {
            console.error('Fehler bei der Formularverarbeitung:', error);
            this.showPaymentError(error.message || this.texts.error);
        } finally {
            // Formular aus Ladestand nehmen
            this.uiHandler.setFormLoading(form, false);
        }
    }
    
    /**
     * Sammelt Kundendaten aus dem Formular
     * @param {HTMLFormElement} form - Das Formular mit Kundendaten
     * @returns {Object} - Die gesammelten Kundendaten
     */
    collectCustomerData(form) {
        const formData = new FormData(form);
        const customerData = {};
        
        // FormData in ein Objekt umwandeln
        for (const [key, value] of formData.entries()) {
            customerData[key] = value;
        }
        
        if (this.debug) {
            console.log('Gesammelte Kundendaten:', customerData);
        }
        
        return customerData;
    }
    
    /**
     * Holt Produktdaten aus dem Formular oder Attributen
     * @param {HTMLFormElement} form - Das Zahlungsformular
     * @returns {Object} - Die Produktdaten
     */
    getProductDataFromForm(form) {
        // Versuche, Produktdaten aus Formular-Attributen zu holen
        const productData = {
            title: form.dataset.productTitle || '',
            price: form.dataset.productPrice || 0,
            currency: form.dataset.productCurrency || 'EUR',
            description: form.dataset.productDescription || '',
            id: form.dataset.productId || this.generateToken()
        };
        
        // Preis in Cent umrechnen für Stripe
        productData.priceCents = this.formatCurrency(productData.price);
        
        if (this.debug) {
            console.log('Produktdaten aus Formular:', productData);
        }
        
        return productData;
    }
    
    /**
     * Erstellt einen Payment Intent bei Stripe
     * @param {Object} productData - Die Produktdaten
     * @param {Object} customerData - Die Kundendaten
     */
    async createPaymentIntent(productData, customerData) {
        try {
            this.uiHandler.showLoadingIndicator();
            
            const response = await fetch('/api/stripe/create-payment-intent', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    amount: productData.priceCents,
                    currency: productData.currency || 'EUR',
                    description: productData.description || productData.title,
                    customer_email: customerData.email || '',
                    metadata: {
                        product_id: productData.id,
                        customer_name: customerData.name || '',
                        customer_email: customerData.email || ''
                    }
                }),
            });
            
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Fehler beim Erstellen des Payment Intent');
            }
            
            const result = await response.json();
            
            if (!result || !result.clientSecret) {
                throw new Error('Ungültige Payment Intent-Antwort');
            }
            
            this.paymentIntentId = result.id;
            this.clientSecret = result.clientSecret;
            
            if (this.debug) {
                console.log('Payment Intent erstellt:', result.id);
            }
            
        } catch (error) {
            console.error('Fehler beim Erstellen des Payment Intent:', error);
            throw error;
        } finally {
            this.uiHandler.hideLoadingIndicator();
        }
    }
    
    /**
     * Bestätigt die Kartenzahlung mit Stripe
     * @param {Object} customerData - Die Kundendaten
     */
    async confirmCardPayment(customerData) {
        try {
            this.uiHandler.showLoadingIndicator();
            
            if (!this.clientSecret) {
                throw new Error('Kein Client Secret vorhanden');
            }
            
            const result = await this.stripe.confirmCardPayment(this.clientSecret, {
                payment_method: {
                    card: this.cardElement,
                    billing_details: {
                        name: customerData.name || '',
                        email: customerData.email || '',
                        address: {
                            line1: customerData.street || '',
                            postal_code: customerData.postal || '',
                            city: customerData.city || '',
                            country: customerData.country || 'DE'
                        }
                    }
                }
            });
            
            if (result.error) {
                throw new Error(result.error.message);
            }
            
            if (result.paymentIntent && result.paymentIntent.status === 'succeeded') {
                // Zahlung erfolgreich
                this.handlePaymentSuccess(result.paymentIntent);
            } else {
                throw new Error('Zahlung nicht erfolgreich');
            }
            
        } catch (error) {
            console.error('Fehler bei der Kartenzahlung:', error);
            throw error;
        } finally {
            this.uiHandler.hideLoadingIndicator();
        }
    }
    
    /**
     * Verarbeitet erfolgreiche Zahlungen
     * @param {Object} paymentIntent - Das erfolgreiche Payment Intent
     */
    handlePaymentSuccess(paymentIntent) {
        if (this.debug) {
            console.log('Zahlung erfolgreich:', paymentIntent);
        }
        
        // Erfolgsseite anzeigen oder weiterleiten
        const successUrl = this.ensureAbsoluteUrl(this.successUrl);
        const urlWithParams = new URL(successUrl);
        
        // Parameter hinzufügen
        urlWithParams.searchParams.append('payment_intent_id', paymentIntent.id);
        urlWithParams.searchParams.append('payment_status', paymentIntent.status);
        
        // Zur Erfolgsseite weiterleiten
        window.location.href = urlWithParams.toString();
    }
    
    /**
     * Zeigt einen Zahlungsfehler an
     * @param {string} errorMessage - Die Fehlermeldung
     */
    showPaymentError(errorMessage) {
        const displayError = document.querySelector(this.cardErrorSelector);
        
        if (displayError) {
            displayError.textContent = errorMessage;
            displayError.style.display = 'block';
            displayError.style.color = '#fa755a';
            
            // Zum Fehler scrollen
            displayError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
            // Fallback: Alert anzeigen
            alert('Fehler: ' + errorMessage);
        }
    }
}

// Export für ES6 Module
export default StripeElementsForm; 