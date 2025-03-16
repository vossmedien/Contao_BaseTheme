/**
 * Stripe Checkout Handler
 *
 * Diese Klasse stellt einen vereinfachten Flow für Stripe Checkout bereit.
 * Sie verwendet Stripe Checkout mit Redirect statt der komplexeren Webhooks,
 * um Zahlungen zu verarbeiten.
 */
class StripeCheckoutHandler {
    constructor(config) {
        console.log('StripeCheckoutHandler wird initialisiert...');

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

            // UI und Validierungs-Handler initialisieren
            this.uiHandler = null;
            this.validationHandler = null;

            console.log('StripeCheckoutHandler erfolgreich initialisiert mit:', {
                stripeKey: config.stripeKey.substring(0, 5) + '...',
                successUrl: this.successUrl,
                createUserAccount: this.createUserAccount,
                currency: this.currency,
                debug: this.debug
            });

            // Event-Listener direkt initialisieren, nicht auf DOMContentLoaded warten
            this.initEventListeners();
        } catch (error) {
            console.error('FEHLER bei der Initialisierung des StripeCheckoutHandler:', error);
        }
    }

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
     * Initialisiert Event-Listener für alle Checkout-Buttons
     */
    initEventListeners() {
        console.log('Initialisiere Event-Listener...');

        // Event-Listener für alle Checkout-Buttons
        const checkoutButtons = document.querySelectorAll('[data-checkout]');
        console.log(`${checkoutButtons.length} Checkout-Buttons gefunden.`);

        checkoutButtons.forEach((button, index) => {
            console.log(`Registriere Event-Listener für Button ${index}:`,
                button.dataset.productTitle,
                button.dataset.price,
                button.dataset.currency);

            // Alte Listener entfernen, falls vorhanden
            button.removeEventListener('click', this.handleCheckoutClick);

            // Neuen Listener hinzufügen
            button.addEventListener('click', (event) => {
                console.log(`Button ${index} wurde geklickt`);
                this.handleCheckoutClick(event);
            });
        });

        // Preis-Displays initialisieren
        this.initializePriceDisplays();

        console.log('Event-Listener erfolgreich initialisiert.');
    }

    /**
     * Behandelt Klicks auf Checkout-Buttons
     * @param {Event} event
     */
    handleCheckoutClick(event) {
        event.preventDefault();

        // Überprüfen, ob ein Modal bereits angezeigt wird
        if (document.querySelector('.modal.show')) {
            console.log('Ein Modal ist bereits geöffnet. Verhindere Öffnen eines neuen Modals.');
            return;
        }

        const button = event.currentTarget;
        let productData = {};

        try {
            // NEUES FORMAT: Versuche, Produktdaten aus dem data-product Attribut zu extrahieren (JSON)
            try {
                productData = JSON.parse(button.getAttribute('data-product') || '{}');
            } catch (e) {
                console.log('Kein gültiges data-product Attribut gefunden, versuche einzelne Datenattribute...');
            }

            // ALTES FORMAT: Fallback auf einzelne data-* Attribute, wenn kein data-product vorhanden oder ungültig
            if (!productData || Object.keys(productData).length === 0) {
                console.log('Verwende einzelne data-Attribute für Produktdaten');

                // Detailliertes Logging aller verfügbaren data-Attribute
                console.log('Button data-Attribute:', {
                    productId: button.dataset.productId,
                    title: button.dataset.productTitle,
                    price: button.dataset.price,
                    notificationId: button.dataset.notificationId,
                    elementId: button.dataset.elementId,
                    fileSale: button.dataset.fileSale,
                    filePath: button.dataset.filePath,
                    // E-Mail-Einstellungen
                    senderEmail: button.dataset.senderEmail,
                    adminEmail: button.dataset.adminEmail,
                    adminTemplate: button.dataset.adminTemplate,
                    userTemplate: button.dataset.userTemplate
                });

                // Extrahiere Daten aus einzelnen Attributen mit Fallback-Werten
                productData = {
                    id: button.dataset.productId || 'default-product',
                    title: button.dataset.productTitle || 'Produkt',
                    price: button.dataset.price ? parseFloat(button.dataset.price.replace(',', '.')) : 0,
                    tax_rate: button.dataset.taxRate ? parseFloat(button.dataset.taxRate) : 19, // Steuersatz mit Fallback auf 19%
                    create_invoice: button.dataset.createInvoice === 'true', // Rechnungserstellung aktivieren/deaktivieren
                    notification_id: button.dataset.notificationId,
                    element_id: button.dataset.elementId,
                    // Mitgliedschaftsdauer und Gruppen
                    subscription_duration: button.dataset.subscriptionDuration ? parseInt(button.dataset.subscriptionDuration) : 0,
                    duration: button.dataset.duration ? parseInt(button.dataset.duration) : 0,
                    member_group: button.dataset.memberGroup || null,
                    create_user: button.dataset.createUser === 'true' || false,
                    // E-Mail-Einstellungen hinzufügen
                    sender_email: button.dataset.senderEmail,
                    admin_email: button.dataset.adminEmail,
                    admin_template: button.dataset.adminTemplate,
                    user_template: button.dataset.userTemplate
                };

                // Detailliertes Log für die Mitgliedschaftsdaten
                console.log('Mitgliedschaftsdaten aus Button extrahiert:', {
                    subscription_duration: productData.subscription_duration,
                    duration: productData.duration,
                    member_group: productData.member_group,
                    create_user: productData.create_user
                });

                // Sicherstellen, dass der Preis eine gültige Zahl ist
                if (isNaN(productData.price)) {
                    console.warn('Preis konnte nicht als Zahl interpretiert werden:', button.dataset.price);
                    productData.price = 0;
                }

                // Zusätzliche Datei-Attribute für Downloads
                if (button.dataset.fileSale === 'true') {
                    productData.file_sale = true;

                    // UUID für die Datei
                    if (button.dataset.fileUuid) {
                        productData.file_uuid = button.dataset.fileUuid;
                        console.log('Datei-UUID erkannt (vermutlich Base64-kodiert):', button.dataset.fileUuid);
                    }
                    // Fallback für ältere Implementierung
                    else if (button.dataset.filePath) {
                        console.warn('Veraltetes Attribut data-file-path wird verwendet. Bitte auf data-file-uuid umstellen!');
                        productData.file_path = button.dataset.filePath;
                    }

                    productData.download_expires = button.dataset.downloadExpires;
                    productData.download_limit = button.dataset.downloadLimit;
                }
            }

            // Validiere, ob die wichtigsten Produktdaten vorhanden sind
            let validationErrors = [];

            // Weniger strenge Validierung - nur Titel wird wirklich benötigt
            if (!productData.title) validationErrors.push("Produkttitel fehlt");

            if (validationErrors.length > 0) {
                console.error('Produktdaten-Validierungsfehler:', validationErrors, productData);
                alert('Fehler: ' + validationErrors.join(', ') + '. Bitte laden Sie die Seite neu und versuchen Sie es erneut.');
                return;
            }

            console.log('Produktdaten gefunden:', productData);

            // Das richtige Modal öffnen
            const customerDataModal = document.getElementById('customerDataModal');

            if (!customerDataModal) {
                console.error('Modal für Kundendaten nicht gefunden. ID: customerDataModal');

                // Versuche alternative IDs oder Selektoren
                const alternativeModal = document.getElementById('modal-customer-data') ||
                                          document.querySelector('[data-modal="customer-data"]');

                if (alternativeModal) {
                    console.log('Alternatives Modal gefunden:', alternativeModal.id);

                    // Speichere Produktdaten am Modal-Element für die spätere Verwendung
                    alternativeModal.setAttribute('data-product', JSON.stringify(productData));

                    // Modal anzeigen
                    const modalInstance = new bootstrap.Modal(alternativeModal);
                    modalInstance.show();

                    return;
                }

                alert('Ein Fehler ist aufgetreten. Das Formular für Ihre Daten konnte nicht gefunden werden. Bitte versuchen Sie es später erneut.');
                return;
            }

            // Produktdaten im Modal speichern
            customerDataModal.setAttribute('data-product', JSON.stringify(productData));

            // Modal öffnen
            const modalInstance = new bootstrap.Modal(customerDataModal);
            modalInstance.show();

            // Handler initialisieren, wenn noch nicht geschehen
            if (customerDataModal.getAttribute('data-handler-registered') !== 'true') {
                console.log('Initialisiere Handler für das Formular');

                // Formular im Modal finden
                const form = customerDataModal.querySelector('form[data-customer-form]') ||
                            customerDataModal.querySelector('form#customerDataForm') ||
                            customerDataModal.querySelector('form');

                if (!form) {
                    console.error('Kein Formular im Modal gefunden. Bitte stellen Sie sicher, dass das Formular mit data-customer-form markiert ist.');
                    alert('Technischer Fehler: Formular nicht gefunden. Bitte laden Sie die Seite neu.');
                    return;
                }

                console.log('Formular gefunden:', form.id || '(ohne ID)', form.getAttribute('data-customer-form') ? 'mit data-customer-form' : 'ohne data-customer-form');

                // UI-Handler initialisieren
                if (typeof UIHandler !== 'undefined') {
                    this.uiHandler = new UIHandler(form);

                    // Produktinfo und Preis im Formular aktualisieren
                    if (productData && productData.price) {
                        this.uiHandler.updateModal(productData, this.currency);
                        this.uiHandler.initializePriceDisplays();
                    }

                    console.log('UI-Handler erfolgreich initialisiert');
                } else {
                    console.warn('UIHandler-Klasse nicht gefunden - UI-Funktionen deaktiviert');
                }

                // Validierungs-Handler initialisieren
                if (typeof ValidationHandler !== 'undefined') {
                    this.validationHandler = new ValidationHandler(form, this.texts);
                    this.validationHandler.initialize();
                    console.log('Validierungs-Handler erfolgreich initialisiert');
                } else {
                    console.warn('ValidationHandler-Klasse nicht gefunden - Validierungsfunktionen deaktiviert');
                }

                // Formular-Submit-Handler einrichten
                form.addEventListener('submit', (e) => {
                    e.preventDefault();

                    // Validierung durchführen, falls verfügbar
                    if (this.validationHandler && this.validationHandler.hasErrors) {
                        console.log('Formular enthält Fehler, Absenden wird verhindert');
                        return;
                    }

                    // Produktdaten aus dem Modal-Attribut holen
                    const productJson = customerDataModal.getAttribute('data-product');
                    if (!productJson) {
                        console.error('Keine Produktdaten gefunden');
                        return;
                    }

                    // Kundendaten aus dem Formular sammeln
                    const customerData = {};
                    const formData = new FormData(form);
                    formData.forEach((value, key) => {
                        customerData[key] = value;
                    });

                    // Produktdaten mit Kundendaten zusammenführen
                    const productData = JSON.parse(productJson);
                    const checkoutData = {
                        ...productData,
                        customer: customerData
                    };

                    // Ladeanimation anzeigen wenn vorhanden
                    this.showLoading();

                    // Stripe Checkout starten
                    this.startStripeCheckout(checkoutData)
                        .then(session => {
                            // Weiterleitung zur Stripe Checkout-Seite
                            if (session && session.url) {
                                // Entferne den Ladeindikator vor der Weiterleitung
                                this.hideLoadingIndicator();
                                const loader = document.getElementById('stripe-checkout-loader');
                                if (loader) {
                                    loader.remove();
                                }
                                // Zur Stripe-Seite weiterleiten
                                window.location.href = session.url;
                            } else {
                                this.hideLoading();
                                // Entferne den globalen Ladeindikator
                                const loader = document.getElementById('stripe-checkout-loader');
                                if (loader) {
                                    loader.remove();
                                }
                                console.error('Keine gültige Checkout-URL erhalten');

                                // Falls UI-Handler vorhanden, Fehlermeldung anzeigen
                                if (this.uiHandler) {
                                    this.uiHandler.showError(this.texts.errorCheckout || 'Ein Fehler ist beim Starten des Checkouts aufgetreten');
                                }
                            }
                        })
                        .catch(error => {
                            this.hideLoading();
                            // Entferne den globalen Ladeindikator
                            const loader = document.getElementById('stripe-checkout-loader');
                            if (loader) {
                                loader.remove();
                            }
                            console.error('Fehler beim Starten des Checkouts:', error);

                            // Falls UI-Handler vorhanden, Fehlermeldung anzeigen
                            if (this.uiHandler) {
                                this.uiHandler.showError(error.message || this.texts.errorCheckout || 'Ein Fehler ist beim Starten des Checkouts aufgetreten');
                            }
                        });
                });

                // Modal als initialisiert markieren
                customerDataModal.setAttribute('data-handler-registered', 'true');
                console.log('Event-Handler für das Formular registriert');
            }

        } catch (error) {
            console.error('Fehler beim Verarbeiten des Checkout-Klicks', error);
            alert('Es ist ein Fehler aufgetreten. Bitte versuchen Sie es erneut.');
        }
    }

    /**
     * Führt den Checkout-Prozess durch
     * @param {Object} productData
     * @param {Object} customerData
     */
    processCheckout(productData, customerData = {}) {
        try {
            // Eindeutige Session-ID generieren
            const sessionId = 'stripe_' + Math.random().toString(36).substring(2, 15);

            // Sicherstellen, dass Adressdaten angegeben wurden, wenn Rechnungserstellung gewünscht ist
            if (productData.create_invoice && (customerData.street === '' || customerData.postal === '' || customerData.city === '')) {
                this.showMessage('Bitte geben Sie Ihre vollständige Adresse für die Rechnungserstellung an.', 'error');
                return;
            }

            console.log('Produktdaten:', productData);
            console.log('Adressdaten:', customerData);
            console.log('Rechnungserstellung:', productData.create_invoice);
            console.log('Preis (Original):', productData.price);
            console.log('Preis-Typ:', typeof productData.price);

            // Markup des Produkts für E-Mail-Templates speichern (falls vorhanden)
            let productMarkup = '';
            let buttonMarkup = '';
            
            if (productData.elementId) {
                // Produktelement finden
                const productElement = document.querySelector(`[data-element-id="${productData.elementId}"]`);
                if (productElement) {
                    // Das nächste .card-body Element erfassen
                    const productCardBody = productElement.closest('.card').querySelector('.card-body');
                    if (productCardBody) {
                        productMarkup = productCardBody.innerHTML;
                    }
                    
                    // Den Button-Bereich erfassen
                    const productButton = productElement.closest('.card').querySelector('.card-footer');
                    if (productButton) {
                        buttonMarkup = productButton.innerHTML;
                    }
                }
            }

            // Preis in Cent umrechnen für Stripe
            let priceCents = 0;

            // Verschiedene Preisformate unterstützen (Dezimalpunkt, Komma, mit/ohne Währungssymbol)
            if (typeof productData.price === 'string') {
                // String-Preis bereinigen und in Zahl umwandeln
                const cleanPrice = productData.price.replace(/[€$£]/g, '').trim().replace(',', '.');
                priceCents = Math.round(parseFloat(cleanPrice) * 100);
            } else if (typeof productData.price === 'number') {
                // Wenn bereits eine Zahl, einfach mit 100 multiplizieren
                priceCents = Math.round(productData.price * 100);
            }

            // Wenn der Preis ungültig ist, verwenden wir 0
            if (isNaN(priceCents)) {
                console.warn('Ungültiger Preis wurde auf 0 gesetzt:', productData.price);
                priceCents = 0;
            }

            console.log('Berechneter Preis in Cent:', priceCents);

            // URLs überprüfen und absolute URLs sicherstellen
            const successUrl = this.ensureAbsoluteUrl(this.successUrl);
            const cancelUrl = this.ensureAbsoluteUrl(this.cancelUrl);

            console.log('Verwendete URLs:', {
                success: successUrl,
                cancel: cancelUrl
            });

            // Checkout-Daten in der richtigen Struktur vorbereiten
            const checkoutData = {
                sessionId: sessionId,
                productData: {
                    id: productData.id || 'default-product',
                    title: productData.title || 'Produkt',
                    price: priceCents, // Preis in Cent für Stripe
                    currency: this.currency,
                    tax_rate: productData.tax_rate || 19, // Standardmäßig 19% Mehrwertsteuer
                    tax_included: true, // Der angegebene Preis enthält bereits die Steuer (Brutto)
                    create_invoice: productData.create_invoice, // Rechnungserstellung berücksichtigen
                    create_user: productData.create_user || this.createUserAccount || false, // Benutzer erstellen
                    member_group: productData.member_group || null, // Mitgliedergruppe
                    notification_id: productData.notification_id,
                    duration: productData.duration || 0, // Mitgliedsdauer in Monaten
                    subscription_duration: productData.subscription_duration || 0, // Alternative Mitgliedschaftsdauer
                    // E-Mail-Templates und Adressen hinzufügen
                    sender_email: productData.sender_email,
                    admin_email: productData.admin_email,
                    admin_template: productData.admin_template,
                    user_template: productData.user_template
                },
                personalData: customerData || {},
                successUrl: successUrl,
                cancelUrl: cancelUrl,
                createUser: this.createUserAccount || productData.create_user || false,
                productMarkup: productMarkup,
                buttonMarkup: buttonMarkup
            };

            // Elementdaten hinzufügen
            if (productData.element_id) {
                checkoutData.elementId = productData.element_id;
            }

            // Bei Dateikauf die entsprechenden Daten hinzufügen
            if (productData.file_sale) {
                checkoutData.productData.file_sale = true;

                // UUID für Dateiidentifikation verwenden statt Dateipfad (sicherer)
                if (productData.file_uuid) {
                    checkoutData.productData.file_uuid = productData.file_uuid;
                    console.log('Datei-UUID für Download verwendet:', productData.file_uuid);
                }
                // Fallback für ältere Implementierung
                else if (productData.file_path) {
                    // Alte Methode - nur zu Kompatibilitätszwecken beibehalten
                    console.warn('Veralteter Dateipfad erkannt! Bitte auf UUID umstellen!');
                    checkoutData.productData.file_path = productData.file_path;
                }

                // Download-Einstellungen
                checkoutData.productData.download_expires = productData.download_expires;
                checkoutData.productData.download_limit = productData.download_limit;
            }

            console.log('Sende Checkout-Anfrage mit folgenden Daten:', checkoutData);

            // Hinzufügen eines Ladevorgangs, um zu verhindern, dass das Modal geschlossen wird
            this.showLoadingIndicator();

            // POST-Request zum Erstellen einer Checkout-Session
            fetch('/stripe/create-checkout-session', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(checkoutData)
            })
            .then(response => {
                // Ladevorgang beenden
                this.hideLoadingIndicator();

                if (!response.ok) {
                    return response.json().then(errorData => {
                        console.error('Server-Fehler mit Details:', errorData);
                        throw new Error(errorData.error || 'Serverfehler: ' + response.status);
                    }).catch(e => {
                        if (e instanceof SyntaxError) {
                            // Wenn kein JSON zurückkommt
                            throw new Error('Serverfehler: ' + response.status);
                        }
                        throw e;
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }

                if (!data.id && !data.session_url) {
                    throw new Error('Keine gültige Session-Daten erhalten');
                }

                const redirectUrl = data.session_url || data.url;

                if (redirectUrl) {
                    console.log('Leite zum Stripe Checkout weiter:', redirectUrl);
                    // Ladeindikator entfernen vor der Weiterleitung
                    this.hideLoadingIndicator();
                    const loader = document.getElementById('stripe-checkout-loader');
                    if (loader) {
                        loader.remove();
                    }
                    window.location.href = redirectUrl;
                } else if (data.id) {
                    // Fallback: Wenn eine Session-ID, aber keine URL zurückkommt
                    console.log('Stripe Session ID erhalten, verwende redirectToCheckout API:', data.id);

                    // Prüfen, ob Stripe geladen ist
                    if (typeof Stripe === 'undefined') {
                        throw new Error('Stripe ist nicht geladen');
                    }

                    // Ladeindikator entfernen vor der Weiterleitung
                    this.hideLoadingIndicator();
                    const loader = document.getElementById('stripe-checkout-loader');
                    if (loader) {
                        loader.remove();
                    }

                    // Hole Stripe-Key aus der Konfiguration
                    const stripeConfig = document.getElementById('stripe-config');
                    const config = JSON.parse(stripeConfig.textContent);
                    const stripe = Stripe(config.stripeKey || config.publicKey);

                    // Umleitung über Stripe API
                    stripe.redirectToCheckout({
                        sessionId: data.id
                    }).then(result => {
                        if (result.error) {
                            throw new Error(result.error.message);
                        }
                    });
                }
            })
            .catch(error => {
                // Ladeindikator im Fehlerfall entfernen
                this.hideLoadingIndicator();
                const loader = document.getElementById('stripe-checkout-loader');
                if (loader) {
                    loader.remove();
                }
                console.error('Fehler beim Erstellen der Checkout-Session:', error);
                alert(this.texts.error || 'Ein Fehler ist aufgetreten: ' + error.message);
            });
        } catch (error) {
            console.error('Fehler beim Verarbeiten des Checkouts:', error);
            alert(this.texts.error || 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.');
        }
    }

    /**
     * Startet den Stripe Checkout-Prozess mit den gesammelten Daten
     * @param {Object} checkoutData Kombination aus Produkt- und Kundendaten
     * @returns {Promise} Promise, das das Ergebnis des Checkout-Vorgangs enthält
     */
    async startStripeCheckout(checkoutData) {
        try {
            if (!checkoutData || !checkoutData.customer) {
                throw new Error('Unvollständige Checkout-Daten');
            }

            // Kundendaten extrahieren
            const customerData = checkoutData.customer;

            // Eindeutige Session-ID generieren
            const sessionId = 'stripe_' + Math.random().toString(36).substring(2, 15);

            // Sicherstellen, dass Adressdaten angegeben wurden, wenn Rechnungserstellung gewünscht ist
            const createInvoice = customerData.create_invoice === 'on' || customerData.create_invoice === true;
            if (createInvoice && (customerData.street === '' || customerData.postal === '' || customerData.city === '')) {
                if (this.uiHandler) {
                    this.uiHandler.showError(this.texts.errorInvoiceAddress || 'Bitte geben Sie Ihre vollständige Adresse für die Rechnungserstellung an.');
                }
                throw new Error('Unvollständige Adresse für Rechnungserstellung');
            }

            console.log('Checkout-Daten:', checkoutData);

            // Preis in Cent umrechnen für Stripe
            let priceCents = 0;

            // Verschiedene Preisformate unterstützen (Dezimalpunkt, Komma, mit/ohne Währungssymbol)
            if (typeof checkoutData.price === 'string') {
                // String-Preis bereinigen und in Zahl umwandeln
                const cleanPrice = checkoutData.price.replace(/[€$£]/g, '').trim().replace(',', '.');
                priceCents = Math.round(parseFloat(cleanPrice) * 100);
            } else if (typeof checkoutData.price === 'number') {
                // Wenn bereits eine Zahl, einfach mit 100 multiplizieren
                priceCents = Math.round(checkoutData.price * 100);
            }

            // Wenn der Preis ungültig ist, verwenden wir 0
            if (isNaN(priceCents)) {
                console.warn('Ungültiger Preis wurde auf 0 gesetzt:', checkoutData.price);
                priceCents = 0;
            }

            console.log('Berechneter Preis in Cent:', priceCents);

            // URLs überprüfen und absolute URLs sicherstellen
            const successUrl = this.ensureAbsoluteUrl(this.successUrl);
            const cancelUrl = this.ensureAbsoluteUrl(this.cancelUrl);

            // Checkout-Daten in der richtigen Struktur vorbereiten
            const stripeData = {
                sessionId: sessionId,
                productData: {
                    id: checkoutData.id || 'default-product',
                    title: checkoutData.title || 'Produkt',
                    price: priceCents, // Preis in Cent für Stripe
                    currency: this.currency,
                    tax_rate: checkoutData.tax_rate || 19, // Standardmäßig 19% Mehrwertsteuer
                    tax_included: true, // Der angegebene Preis enthält bereits die Steuer (Brutto)
                    create_invoice: createInvoice,
                    notification_id: checkoutData.notification_id,
                    duration: checkoutData.duration || 0,
                    sender_email: checkoutData.sender_email,
                    admin_email: checkoutData.admin_email,
                    admin_template: checkoutData.admin_template,
                    user_template: checkoutData.user_template
                },
                personalData: customerData || {},
                successUrl: successUrl,
                cancelUrl: cancelUrl,
                createUser: this.createUserAccount || false
            };

            // Elementdaten hinzufügen
            if (checkoutData.element_id) {
                stripeData.elementId = checkoutData.element_id;
            }

            // Bei Dateikauf die entsprechenden Daten hinzufügen
            if (checkoutData.file_sale) {
                stripeData.productData.file_sale = true;

                // UUID für Dateiidentifikation verwenden statt Dateipfad
                if (checkoutData.file_uuid) {
                    stripeData.productData.file_uuid = checkoutData.file_uuid;
                    console.log('Datei-UUID für Download verwendet:', checkoutData.file_uuid);
                }
                // Fallback für ältere Implementierung
                else if (checkoutData.file_path) {
                    console.warn('Veralteter Dateipfad erkannt! Bitte auf UUID umstellen!');
                    stripeData.productData.file_path = checkoutData.file_path;
                }

                // Download-Einstellungen
                stripeData.productData.download_expires = checkoutData.download_expires || 7;
                stripeData.productData.download_limit = checkoutData.download_limit || 3;
            }

            console.log('Sende Checkout-Anfrage mit Daten:', stripeData);

            // Hinzufügen eines Ladevorgangs, um zu verhindern, dass das Modal geschlossen wird
            this.showLoadingIndicator();

            console.log('Sende Anfrage an Server...');
            
            const response = await fetch('/stripe/create-checkout-session', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(stripeData)
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({ error: 'HTTP-Fehler: ' + response.status }));
                throw new Error(errorData.error || 'Serverfehler: ' + response.status);
            }
            
            const data = await response.json();
            
            if (data.error) {
                throw new Error(data.error);
            }

            if (!data.id && !data.session_url) {
                throw new Error('Keine gültige Session-Daten erhalten');
            }
            
            // Schließe das Modal und entferne den Ladeindikator bevor zur Stripe-Checkout-Seite weitergeleitet wird
            if (this.uiHandler) {
                this.uiHandler.closeModal();
            }
            
            // Ladeindikator explizit entfernen
            this.hideLoadingIndicator();
            
            // Entferne auch den globalen Ladeindikator
            const loader = document.getElementById('stripe-checkout-loader');
            if (loader) {
                loader.remove();
            }

            // Session-URL zurückgeben
            return {
                url: data.session_url || data.url,
                id: data.id
            };
        } catch (error) {
            console.error('Fehler beim Checkout-Prozess:', error);
            
            // Im Fehlerfall auch den Ladeindikator entfernen
            this.hideLoadingIndicator();
            
            // Entferne auch den globalen Ladeindikator
            const loader = document.getElementById('stripe-checkout-loader');
            if (loader) {
                loader.remove();
            }
            
            throw error;
        }
    }

    /**
     * Validiert ein Formular auf Pflichtfelder
     * @param {HTMLFormElement} form Das zu validierende Formular
     * @returns {boolean}
     */
    validateForm(form) {
        console.log('Validiere Formular');
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
                console.warn('Pflichtfeld nicht ausgefüllt:', field.name || field.id);
            } else {
                field.classList.remove('is-invalid');
            }
        });

        if (!isValid) {
            this.showFormError(this.texts.errorRequired);
        }

        return isValid;
    }

    /**
     * Zeigt einen Fehler im Formular an
     * @param {string} errorMessage - Die Fehlermeldung, die angezeigt werden soll
     */
    showFormError(errorMessage) {
        console.log('Formularfehler:', errorMessage);

        // Alle Formulare durchgehen und Fehler anzeigen
        document.querySelectorAll('.stripe-customer-form').forEach(form => {
            const errorElement = form.querySelector('.form-error');
            if (errorElement) {
                errorElement.textContent = errorMessage;
                errorElement.style.display = 'block';

                // Zum Fehler scrollen
                errorElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                // Fallback: Alert anzeigen, wenn kein Fehlerelement gefunden wurde
                alert('Fehler: ' + errorMessage);
            }
        });
    }

    /**
     * Setzt das Formular in den Lade-Zustand
     */
    setFormLoading(form, isLoading) {
        const submitButton = form.querySelector('button[type="submit"]');
        const spinner = form.querySelector('[data-form-spinner]');

        if (submitButton) {
            submitButton.disabled = isLoading;
        }

        if (spinner) {
            if (isLoading) {
                spinner.classList.remove('d-none');
            } else {
                spinner.classList.add('d-none');
            }
        }
    }

    /**
     * Initialisiert alle Preis-Anzeigen auf der Seite
     */
    initializePriceDisplays() {
        const priceElements = document.querySelectorAll('[data-price-display]');
        console.log(`${priceElements.length} Preis-Anzeigen gefunden.`);

        priceElements.forEach(element => {
            const amount = parseFloat(element.dataset.amount);
            const currency = element.dataset.currency || this.currency;

            if (!isNaN(amount)) {
                element.textContent = this.formatCurrency(amount, currency);
            }
        });
    }

    /**
     * Formatiert einen Betrag als Währung
     */
    formatCurrency(amount, currency) {
        return new Intl.NumberFormat('de-DE', {
            style: 'currency',
            currency: currency.toUpperCase()
        }).format(amount);
    }

    /**
     * Formatiert einen Preis (Alias für formatCurrency)
     */
    formatPrice(amount, currency) {
        return this.formatCurrency(amount, currency);
    }

    /**
     * Generiert ein Token für Downloads
     */
    generateDownloadToken() {
        return 'dl_' + Math.random().toString(36).substring(2, 15) +
               Math.random().toString(36).substring(2, 15);
    }

    /**
     * Generiert eine einzigartige Session-ID
     */
    generateSessionId() {
        return 'session_' + Date.now() + '_' +
               Math.random().toString(36).substring(2, 10);
    }

    /**
     * Direkte Weiterleitung zum Checkout ohne Modal
     * Kann verwendet werden, wenn keine zusätzlichen Kundendaten benötigt werden
     */
    async redirectToCheckout(productInfo) {
        console.log('Direkter Checkout ohne Modal');
        try {
            // Einzigartige Session-ID für diesen Kauf generieren
            const sessionId = this.generateSessionId();

            // Erfolgsurl mit Session-Parametern
            const fullSuccessUrl = this.successUrl + (this.successUrl.includes('?') ? '&' : '?') +
                                  'session_id={CHECKOUT_SESSION_ID}&payment_id=' + sessionId;

            console.log('Sende direkte Checkout-Anfrage an Server');

            // Vollständige Produktdaten zusammenstellen
            const checkoutData = {
                productData: {
                    id: productInfo.productId,
                    title: productInfo.title,
                    price: productInfo.price * 100, // In Cent umrechnen
                    currency: this.currency,
                    notification_id: productInfo.notificationId
                },
                sessionId: sessionId,
                elementId: productInfo.elementId,
                successUrl: fullSuccessUrl,
                cancelUrl: this.cancelUrl
            };

            // Zusätzliche Daten für Dateiverkäufe hinzufügen
            if (productInfo.fileSale) {
                checkoutData.fileSale = true;
                checkoutData.filePath = productInfo.filePath;
                checkoutData.downloadExpires = productInfo.downloadExpires || '7';
                checkoutData.downloadLimit = productInfo.downloadLimit || '3';
            }

            // E-Mail-Template-Informationen hinzufügen
            if (productInfo.emailTemplate) {
                checkoutData.emailTemplate = productInfo.emailTemplate;
            }

            // Status-Anzeige (optional)
            const statusElement = document.getElementById('checkout-status');
            if (statusElement) {
                statusElement.textContent = 'Verbindung zu Stripe wird hergestellt...';
                statusElement.style.display = 'block';
            }

            // Stripe Checkout-Session erstellen
            const response = await fetch('/stripe/create-checkout-session', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(checkoutData)
            });

            // Antwort verarbeiten
            if (!response.ok) {
                // Fehlerbehandlung bei HTTP-Fehler
                const errorText = await response.text();
                console.error('Server-Fehler bei direktem Checkout:', response.status, errorText);

                // Ladeindikator entfernen
                this.hideLoadingIndicator();
                const loader = document.getElementById('stripe-checkout-loader');
                if (loader) {
                    loader.remove();
                }

                let errorMessage;
                try {
                    const errorData = JSON.parse(errorText);
                    errorMessage = errorData.error || 'Fehler bei der Verbindung zum Zahlungsserver.';
                } catch (e) {
                    errorMessage = 'Fehler bei der Verbindung zum Zahlungsserver. Status: ' + response.status;
                }

                if (statusElement) {
                    statusElement.classList.add('error');
                    statusElement.textContent = errorMessage;
                } else {
                    alert(errorMessage);
                }
                return;
            }

            // Daten aus der Antwort extrahieren
            const data = await response.json();

            if (statusElement) {
                statusElement.textContent = 'Leite weiter zu Stripe...';
            }

            // Prüfen, ob eine Session-URL zurückkommt
            if (data.session_url || data.url) {
                // Direktes Weiterleiten zum Stripe Checkout
                console.log('Leite weiter zum Stripe Checkout...');
                
                // Ladeindikator entfernen
                this.hideLoadingIndicator();
                const loader = document.getElementById('stripe-checkout-loader');
                if (loader) {
                    loader.remove();
                }
                
                window.location.href = data.session_url || data.url;
            } else if (data.id) {
                // Fallback für ältere API-Version ohne direkte URL
                console.log('Verwende redirectToCheckout mit Session-ID');
                
                // Ladeindikator entfernen
                this.hideLoadingIndicator();
                const loader = document.getElementById('stripe-checkout-loader');
                if (loader) {
                    loader.remove();
                }
                
                await this.stripe.redirectToCheckout({
                    sessionId: data.id
                });
            } else {
                // Fehlerfall: Weder URL noch ID erhalten
                console.error('Keine Session-URL oder ID erhalten:', data);
                
                // Ladeindikator entfernen
                this.hideLoadingIndicator();
                const loader = document.getElementById('stripe-checkout-loader');
                if (loader) {
                    loader.remove();
                }
                
                const errorMessage = 'Fehler bei der Kommunikation mit dem Zahlungsdienst.';
                if (statusElement) {
                    statusElement.classList.add('error');
                    statusElement.textContent = errorMessage;
                } else {
                    alert(errorMessage);
                }
            }
        } catch (error) {
            console.error('Fehler bei redirectToCheckout:', error);
            
            // Ladeindikator entfernen
            this.hideLoadingIndicator();
            const loader = document.getElementById('stripe-checkout-loader');
            if (loader) {
                loader.remove();
            }
            
            const errorMessage = error.message || 'Fehler bei der Verbindung zum Zahlungsdienst.';
            const statusElement = document.getElementById('checkout-status');
            if (statusElement) {
                statusElement.classList.add('error');
                statusElement.textContent = errorMessage;
            } else {
                alert(errorMessage);
            }
        }
    }

    // Neue Methoden für Loading-Anzeige
    showLoadingIndicator() {
        // Prüfen, ob bereits ein Ladeindikator existiert
        let loader = document.getElementById('stripe-checkout-loader');
        if (!loader) {
            // Ladeindikator erstellen
            loader = document.createElement('div');
            loader.id = 'stripe-checkout-loader';
            loader.style.position = 'fixed';
            loader.style.top = '0';
            loader.style.left = '0';
            loader.style.width = '100%';
            loader.style.height = '100%';
            loader.style.backgroundColor = 'rgba(0,0,0,0.5)';
            loader.style.zIndex = '9999';
            loader.style.display = 'flex';
            loader.style.alignItems = 'center';
            loader.style.justifyContent = 'center';

            const spinnerEl = document.createElement('div');
            spinnerEl.style.width = '50px';
            spinnerEl.style.height = '50px';
            spinnerEl.style.border = '5px solid #f3f3f3';
            spinnerEl.style.borderTop = '5px solid #3498db';
            spinnerEl.style.borderRadius = '50%';
            spinnerEl.style.animation = 'spin 2s linear infinite';

            // Füge die Animation hinzu
            const styleEl = document.createElement('style');
            styleEl.innerHTML = '@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }';
            document.head.appendChild(styleEl);

            loader.appendChild(spinnerEl);
            document.body.appendChild(loader);
        } else {
            loader.style.display = 'flex';
        }
    }

    hideLoadingIndicator() {
        const loader = document.getElementById('stripe-checkout-loader');
        if (loader) {
            loader.style.display = 'none';
        }
    }

    /**
     * Stellt sicher, dass eine URL absolut ist
     * @param {string} url Die zu prüfende URL
     * @returns {string} Die absolute URL
     */
    ensureAbsoluteUrl(url) {
        // Leere URL
        if (!url) {
            return window.location.origin + '/';
        }

        try {
            // Versuche, ein URL-Objekt zu erstellen
            const urlObj = new URL(url, window.location.origin);
            return urlObj.href;
        } catch (e) {
            console.warn('Fehler beim Parsen der URL, verwende Standard-URL:', e);
            return window.location.origin + (url.startsWith('/') ? url : '/' + url);
        }
    }

    /**
     * Zeigt einen Ladeindikator an
     */
    showLoading() {
        // UI-Handler verwenden, wenn verfügbar
        if (this.uiHandler) {
            this.uiHandler.toggleLoadingState(true);
            return;
        }

        // Fallback: Legacy-Methode verwenden
        this.showLoadingIndicator();
    }

    /**
     * Versteckt den Ladeindikator
     */
    hideLoading() {
        // UI-Handler verwenden, wenn verfügbar
        if (this.uiHandler) {
            this.uiHandler.toggleLoadingState(false);
            return;
        }

        // Fallback: Legacy-Methode verwenden
        this.hideLoadingIndicator();
    }
}

// Direktes Initialisieren - nicht auf DOMContentLoaded warten
console.log('Stripe Checkout Handler Script geladen');

// Globale Instanz bereitstellen
window.stripeCheckout = null;

// Initialisierungs-Flag, um Doppelausführungen zu vermeiden
let isInitialized = false;

/**
 * Initialisiert den Stripe Checkout Handler
 */
function initializeStripeCheckout() {
    // Prüfen, ob bereits initialisiert
    if (isInitialized) {
        console.log('Stripe Checkout Handler bereits initialisiert - überspringe');
        return;
    }

    console.log('Prüfe Stripe-Konfiguration...');
    const configElement = document.getElementById('stripe-config');

    if (configElement) {
        try {
            console.log('Stripe-Konfiguration gefunden, initialisiere Handler');
            const config = JSON.parse(configElement.textContent);
            window.stripeCheckout = new StripeCheckoutHandler(config);
            isInitialized = true; // Als initialisiert markieren
        } catch (error) {
            console.error('Fehler beim Initialisieren des Stripe Checkout Handlers:', error);
        }
    } else {
        console.warn('Stripe-Konfiguration nicht gefunden, versuche erneut in 500ms');
        setTimeout(initializeStripeCheckout, 500);
    }
}

// Nur einmal beim Laden der Seite initialisieren - das reicht vollkommen aus
if (document.readyState === 'loading') {
    // Wenn Dokument noch lädt, auf DOMContentLoaded warten
    document.addEventListener('DOMContentLoaded', initializeStripeCheckout);
} else {
    // Wenn Dokument bereits geladen ist, sofort initialisieren
    initializeStripeCheckout();
}

/**
 * Funktion zum Debuggen des Stripe-Zahlungsprozesses
 * Diese Funktion überprüft und protokolliert Informationen zur Fehlerbehebung
 */
function debugStripePaymentProcess() {
    console.group('=== STRIPE PAYMENT DEBUG ===');

    // 1. Grundlegende Verfügbarkeit prüfen
    console.log('Stripe Checkout Handler verfügbar:', typeof StripeCheckoutHandler !== 'undefined');

    // 2. Modal-Komponenten prüfen
    const customerModal = document.getElementById('customerDataModal');
    console.log('Kunden-Modal gefunden:', !!customerModal);

    if (customerModal) {
        console.log('Modal-ID:', customerModal.id);
        console.log('Modal ist Bootstrap-Modal:', typeof bootstrap !== 'undefined' && typeof bootstrap.Modal !== 'undefined');

        // Formular im Modal prüfen
        const form = customerModal.querySelector('form');
        console.log('Formular im Modal gefunden:', !!form);

        if (form) {
            // Formularfelder prüfen
            const requiredFields = ['firstname', 'lastname', 'email', 'street', 'postal', 'city'];
            const missingFields = [];

            requiredFields.forEach(field => {
                if (!form.querySelector(`[name="${field}"]`)) {
                    missingFields.push(field);
                }
            });

            if (missingFields.length > 0) {
                console.warn('Fehlende Formularfelder:', missingFields);
            } else {
                console.log('Alle erforderlichen Formularfelder vorhanden');
            }

            // Submit-Button prüfen
            const submitButton = form.querySelector('button[type="submit"]');
            console.log('Submit-Button gefunden:', !!submitButton);
        }
    }

    // 3. Checkout-Buttons prüfen
    const buttons = document.querySelectorAll('[data-checkout]');
    console.log('Anzahl der Checkout-Buttons:', buttons.length);

    if (buttons.length > 0) {
        // Überprüfe die Attribute der Buttons
        buttons.forEach((btn, index) => {
            console.log(`Button ${index + 1} Daten:`, {
                id: btn.dataset.productId || '(nicht gesetzt)',
                title: btn.dataset.productTitle || '(nicht gesetzt)',
                price: btn.dataset.price || '(nicht gesetzt)',
                notificationId: btn.dataset.notificationId || '(nicht gesetzt)'
            });
        });
    } else {
        console.warn('Keine Checkout-Buttons gefunden! Überprüfen Sie die data-checkout Attribute in Ihrem Template.');
    }

    // 4. Stripe-Konfiguration prüfen
    const stripeConfig = document.getElementById('stripe-config');
    console.log('Stripe-Konfiguration gefunden:', !!stripeConfig);

    if (stripeConfig) {
        try {
            const config = JSON.parse(stripeConfig.textContent);
            console.log('Stripe-Konfiguration gültig:', !!config);
            console.log('Public Key vorhanden:', !!config.publicKey || !!config.stripeKey);

            // Weitere Konfigurationsdetails
            if (config) {
                console.log('Success URL:', config.successUrl || '(nicht gesetzt)');
                console.log('Cancel URL:', config.cancelUrl || '(nicht gesetzt)');
                console.log('Währung:', config.currency || 'EUR');
            }
        } catch (e) {
            console.error('Fehler beim Parsen der Stripe-Konfiguration:', e);
        }
    }

    // 5. Hinweise zur Problembehebung
    console.log('\nUm E-Mail-Probleme zu beheben:');
    console.log('1. Prüfen Sie, ob die notification_id im Produktobjekt korrekt gesetzt ist');
    console.log('2. Prüfen Sie, ob der Notification Center in Contao korrekt konfiguriert ist');
    console.log('3. Überprüfen Sie die Server-Logs auf Fehler beim E-Mail-Versand:');
    console.log('   - var/log/prod.log oder var/log/system.log');
    console.log('   - PHP-Fehler im Apache/Nginx-Log');
    console.log('4. Überprüfen Sie, ob die Redirect-URLs korrekt konfiguriert sind');

    console.groupEnd();
}

// Debugging-Funktion global verfügbar machen
window.debugStripePaymentProcess = debugStripePaymentProcess;

// Debugging-Funktion direkt ausführen ohne auf DOM zu warten
debugStripePaymentProcess();
