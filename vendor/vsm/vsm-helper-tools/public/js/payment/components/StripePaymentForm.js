/**
 * StripePaymentForm-Klasse für Stripe-Zahlungsformulare
 * 
 * Verarbeitet Zahlungsformulare und Kundendaten für Stripe-Zahlungen.
 * Erbt von StripeBase und nutzt UIHandler und FormValidator.
 */
import StripeBase from './StripeBase.js';
import UIHandler from './UIHandler.js';
import FormValidator from './FormValidator.js';

class StripePaymentForm extends StripeBase {
    /**
     * Konstruktor für das StripePaymentForm
     * @param {Object} config - Konfigurationsobjekt
     */
    constructor(config = {}) {
        super(config);
        
        // Standardtexte für Zahlungsformulare
        const defaultTexts = {
            ...this.getDefaultTexts(),
            submitButton: 'Jetzt bezahlen',
            loadingButton: 'Wird verarbeitet...',
            errorRequired: 'Bitte füllen Sie alle Pflichtfelder aus.',
            errorInvoiceAddress: 'Bitte geben Sie Ihre vollständige Adresse für die Rechnungserstellung an.',
            successMessage: 'Vielen Dank für Ihre Zahlung!',
        };
        
        this.texts = { ...defaultTexts, ...config.texts };
        this.successUrl = config.successUrl || window.location.href;
        this.cancelUrl = config.cancelUrl || window.location.href;
        this.formSelector = config.formSelector || '#payment-form';
        
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
        
        // Speichert die aktuelle Produktinformation
        this.currentProduct = null;
        
        if (this.debug) {
            console.log('StripePaymentForm initialisiert mit Konfiguration:', config);
        }
        
        // Initialisierung starten
        this.init();
    }
    
    /**
     * Initialisiert das Zahlungsformular
     */
    init() {
        if (this.debug) {
            console.log('Initialisiere Zahlungsformular');
        }
        
        // Event-Listener für Zahlungsbuttons hinzufügen
        this.setupPaymentButtons();
        
        // Event-Listener für Formular-Submission hinzufügen
        this.setupFormSubmission();
    }
    
    /**
     * Richtet Event-Listener für Zahlungsbuttons ein
     */
    setupPaymentButtons() {
        const paymentButtons = document.querySelectorAll('[data-action="stripe-payment"]');
        
        if (this.debug) {
            console.log(`${paymentButtons.length} Zahlungsbuttons gefunden`);
        }
        
        paymentButtons.forEach(button => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                this.handlePaymentButtonClick(button);
            });
        });
    }
    
    /**
     * Verarbeitet Klicks auf Zahlungsbuttons
     * @param {HTMLElement} button - Der geklickte Button
     */
    handlePaymentButtonClick(button) {
        if (this.debug) {
            console.log('Zahlungsbutton geklickt:', button);
        }
        
        // Produktdaten aus Button-Attributen extrahieren
        const productData = this.extractProductData(button);
        
        if (!productData.title) {
            console.error('Produkttitel fehlt');
            alert('Fehler: Produkttitel fehlt. Bitte laden Sie die Seite neu und versuchen Sie es erneut.');
            return;
        }
        
        // Produktdaten speichern
        this.currentProduct = productData;
        
        // Kundenformular öffnen
        this.openCustomerForm(productData);
    }
    
    /**
     * Extrahiert Produktdaten aus einem Button
     * @param {HTMLElement} button - Der Button mit Produktdaten
     * @returns {Object} - Die extrahierten Produktdaten
     */
    extractProductData(button) {
        const productData = {
            title: button.dataset.title || '',
            price: button.dataset.price || 0,
            currency: button.dataset.currency || 'EUR',
            description: button.dataset.description || '',
            elementId: button.dataset.elementId || '',
            create_invoice: button.dataset.createInvoice === 'true',
            image: button.dataset.image || '',
            quantity: parseInt(button.dataset.quantity || '1', 10)
        };
        
        // Zusätzliche Daten für Downloads
        if (button.dataset.isDownload === 'true') {
            productData.is_download = true;
            productData.download_url = button.dataset.downloadUrl || '';
            productData.download_expires = button.dataset.downloadExpires || '';
            productData.download_limit = button.dataset.downloadLimit || '';
        }
        
        if (this.debug) {
            console.log('Extrahierte Produktdaten:', productData);
        }
        
        return productData;
    }
    
    /**
     * Öffnet das Kundenformular für die Dateneingabe
     * @param {Object} productData - Die Produktdaten
     */
    openCustomerForm(productData) {
        // Das richtige Modal finden
        const customerDataModal = document.getElementById('customerDataModal') || 
                                  document.getElementById('modal-customer-data') ||
                                  document.querySelector('[data-modal="customer-data"]');
        
        if (!customerDataModal) {
            console.error('Modal für Kundendaten nicht gefunden');
            alert('Ein Fehler ist aufgetreten. Das Formular für Ihre Daten konnte nicht gefunden werden.');
            return;
        }
        
        // Produktdaten im Modal speichern
        customerDataModal.setAttribute('data-product', JSON.stringify(productData));
        
        // Modal öffnen (Bootstrap)
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modalInstance = new bootstrap.Modal(customerDataModal);
            modalInstance.show();
        } else {
            // Fallback für nicht-Bootstrap-Umgebungen
            customerDataModal.style.display = 'block';
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
            // Produktdaten aus dem Modal holen
            const modal = form.closest('.modal') || document.getElementById('customerDataModal');
            let productData = this.currentProduct;
            
            if (!productData && modal && modal.getAttribute('data-product')) {
                try {
                    productData = JSON.parse(modal.getAttribute('data-product'));
                } catch (error) {
                    console.error('Fehler beim Parsen der Produktdaten:', error);
                    throw new Error('Produktdaten konnten nicht gelesen werden');
                }
            }
            
            if (!productData) {
                throw new Error('Keine Produktdaten gefunden');
            }
            
            // Kundendaten aus dem Formular sammeln
            const customerData = this.collectCustomerData(form);
            
            // Checkout-Daten zusammenführen
            const checkoutData = {
                ...productData,
                customer: customerData
            };
            
            // Stripe Checkout starten
            await this.startStripeCheckout(checkoutData);
            
        } catch (error) {
            console.error('Fehler bei der Formularverarbeitung:', error);
            this.uiHandler.showFormError(error.message || this.texts.error, form);
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
     * Startet den Stripe Checkout-Prozess
     * @param {Object} checkoutData - Die kombinierten Produkt- und Kundendaten
     */
    async startStripeCheckout(checkoutData) {
        try {
            this.uiHandler.showLoadingIndicator();
            
            if (!checkoutData || !checkoutData.customer) {
                throw new Error('Unvollständige Checkout-Daten');
            }
            
            // Kundendaten extrahieren
            const customerData = checkoutData.customer;
            
            // Eindeutige Session-ID generieren
            const sessionId = this.generateSessionId();
            
            // Prüfen, ob Adressdaten für Rechnungserstellung vorhanden sind
            const createInvoice = customerData.create_invoice === 'on' || customerData.create_invoice === true;
            if (createInvoice && (customerData.street === '' || customerData.postal === '' || customerData.city === '')) {
                throw new Error(this.texts.errorInvoiceAddress);
            }
            
            // Preis in Cent umrechnen für Stripe
            const priceCents = this.formatCurrency(checkoutData.price);
            
            // URLs überprüfen und absolute URLs sicherstellen
            const successUrl = this.ensureAbsoluteUrl(this.successUrl);
            const cancelUrl = this.ensureAbsoluteUrl(this.cancelUrl);
            
            // Checkout-Session erstellen
            const response = await fetch('/api/stripe/create-checkout-session', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    sessionId: sessionId,
                    customer: customerData,
                    product: {
                        title: checkoutData.title,
                        price: priceCents,
                        currency: checkoutData.currency || 'EUR',
                        description: checkoutData.description || '',
                        image: checkoutData.image || '',
                        quantity: checkoutData.quantity || 1,
                        is_download: checkoutData.is_download || false,
                        download_url: checkoutData.download_url || '',
                        download_expires: checkoutData.download_expires || '',
                        download_limit: checkoutData.download_limit || ''
                    },
                    success_url: successUrl,
                    cancel_url: cancelUrl
                }),
            });
            
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Fehler beim Erstellen der Checkout-Session');
            }
            
            const session = await response.json();
            
            if (!session || !session.id) {
                throw new Error('Ungültige Checkout-Session-Antwort');
            }
            
            // Zu Stripe Checkout weiterleiten
            this.stripe.redirectToCheckout({
                sessionId: session.id
            }).then((result) => {
                if (result.error) {
                    throw new Error(result.error.message);
                }
            });
            
        } catch (error) {
            console.error('Fehler beim Verarbeiten des Checkouts:', error);
            throw error;
        } finally {
            this.uiHandler.hideLoadingIndicator();
        }
    }
    
    /**
     * Generiert eine eindeutige Session-ID
     * @returns {string} - Die generierte Session-ID
     */
    generateSessionId() {
        return 'stripe_' + Math.random().toString(36).substring(2, 15);
    }
}

// Export für ES6 Module
export default StripePaymentForm; 