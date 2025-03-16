/**
 * Stripe Elements Initialisierung
 * 
 * Diese Datei initialisiert die Stripe Elements für die direkte Kartenerfassung im Modal.
 * Die Zahlung wird über den PaymentIntentController abgewickelt.
 */
document.addEventListener('DOMContentLoaded', function() {
    // Konfiguration aus dem DOM laden
    const configElement = document.getElementById('stripe-config');
    if (!configElement) return;
    
    // Konfiguration parsen
    let config = {};
    try {
        config = JSON.parse(configElement.textContent);
    } catch (error) {
        console.error('Fehler beim Parsen der Stripe-Konfiguration:', error);
        return;
    }
    
    // Texte für Fehlermeldungen laden
    const paymentTexts = document.getElementById('payment-texts');
    const errorStrings = paymentTexts ? JSON.parse(paymentTexts.dataset.stripeErrors || '{}') : {};
    
    // Debug-Modus
    const isDebug = config.debug || false;
    if (isDebug) {
        console.log('Stripe Elements Initialisierung mit Konfiguration:', config);
    }
    
    // Stripe initialisieren
    let stripe = null;
    let elements = null;
    let card = null;
    let clientSecret = null;
    let currentProductData = null;
    
    /**
     * Stripe initialisieren
     */
    function initStripe() {
        if (!config.stripeKey) {
            console.error('Stripe Public Key fehlt in der Konfiguration');
            return;
        }
        
        // Stripe-Instanz erstellen
        stripe = Stripe(config.stripeKey);
    }
    
    /**
     * Stripe Elements initialisieren mit Produktpreis
     * @returns {Object|null} Das initialisierte Elements-Objekt oder null bei Fehler
     */
    function initStripeElements(amount) {
        if (!stripe) {
            try {
                initStripe();
            } catch (error) {
                console.error('Fehler beim Initialisieren von Stripe:', error);
                return null;
            }
        }
        
        // Sicherstellen, dass stripe initialisiert wurde
        if (!stripe) {
            console.error('Stripe konnte nicht initialisiert werden');
            return null;
        }
        
        // Sicherstellen, dass amount > 0 ist
        amount = amount && amount > 0 ? amount : 100;
        
        try {
            // Elemente initialisieren, falls noch nicht geschehen oder neu erstellen
            // Wenn Payment Element verwendet werden soll
            if (config.usePaymentElement) {
                const options = {
                    mode: 'payment',
                    amount: Math.round(amount * 100), // Betrag in Cent umwandeln
                    currency: config.currency.toLowerCase(),
                    appearance: {
                        theme: 'stripe',
                        labels: 'floating',
                        variables: {
                            colorPrimary: '#0d6efd',
                            colorBackground: '#ffffff',
                            colorText: '#30313d',
                            colorDanger: '#dc3545',
                            fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial',
                            spacingUnit: '4px',
                            borderRadius: '4px'
                        }
                    },
                    paymentMethodOrder: ['card', 'paypal', 'klarna', 'giropay', 'sofort', 'sepa_debit', 'ideal', 'bancontact'],
                    business: {
                        name: document.title
                    },
                    locale: 'de'
                };
                
                if (isDebug) {
                    console.log('Stripe Elements Optionen:', options);
                }
                
                // Neue Elements-Instanz erstellen
                try {
                    // Globale Variable elements setzen
                    elements = stripe.elements(options);
                    
                    if (!elements) {
                        console.error('Fehler: Stripe Elements konnte nicht initialisiert werden');
                        return null;
                    }
                    
                    if (isDebug) {
                        console.log('Stripe Elements erfolgreich initialisiert:', elements);
                    }
                    
                    // Payment Element erstellen
                    const paymentElement = elements.create('payment');
                    
                    if (!paymentElement) {
                        console.error('Fehler: Payment Element konnte nicht erstellt werden');
                        return null;
                    }
                    
                    // Payment Element in den Container einbinden
                    const paymentElementContainer = document.querySelector(config.paymentElementSelector || '#payment-element');
                    if (paymentElementContainer) {
                        try {
                            paymentElement.mount(paymentElementContainer);
                            
                            if (isDebug) {
                                console.log('Payment Element erfolgreich in Container eingebunden');
                            }
                            
                            paymentElement.on('change', (event) => {
                                const displayError = document.querySelector(config.cardErrorSelector || '#card-errors');
                                if (displayError) {
                                    if (event.error) {
                                        // Spezifische Fehlermeldung anzeigen
                                        const errorMessage = errorStrings[event.error.code] || event.error.message || errorStrings.default;
                                        displayError.textContent = errorMessage;
                                        displayError.classList.add('visible');
                                    } else {
                                        displayError.textContent = '';
                                        displayError.classList.remove('visible');
                                    }
                                }
                            });
                            
                        } catch (error) {
                            console.error('Fehler beim Mounten des Payment Elements:', error);
                            return null;
                        }
                    } else {
                        console.error('Payment Element Container nicht gefunden');
                        return null;
                    }
                } catch (error) {
                    console.error('Fehler bei der Erstellung von Stripe Elements:', error);
                    return null;
                }
            } else {
                // Klassisches Card Element als Fallback
                elements = stripe.elements({
                    locale: 'de'
                });
                
                // CardElement initialisieren, wenn es noch nicht existiert
                if (!card) {
                    // Stil für das Karten-Element
                    const cardElementStyle = {
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
                    
                    // Stripe Card-Element erstellen
                    card = elements.create('card', {
                        style: cardElementStyle,
                        hidePostalCode: true
                    });
                    
                    // Card-Element in den Container einbinden
                    const cardElementContainer = document.querySelector(config.cardElementSelector || '#card-element');
                    if (cardElementContainer) {
                        card.mount(cardElementContainer);
                        
                        // Event-Listener für Änderungen am Card-Element
                        card.on('change', function(event) {
                            const displayError = document.querySelector(config.cardErrorSelector || '#card-errors');
                            if (displayError) {
                                if (event.error) {
                                    // Spezifische Fehlermeldung anzeigen
                                    const errorMessage = errorStrings[event.error.code] || event.error.message || errorStrings.default;
                                    displayError.textContent = errorMessage;
                                    displayError.classList.add('visible');
                                } else {
                                    displayError.textContent = '';
                                    displayError.classList.remove('visible');
                                }
                            }
                        });
                    } else {
                        console.error('Card Element Container nicht gefunden');
                        return null;
                    }
                }
            }
            
            if (isDebug) {
                console.log('Stripe Elements initialisiert:', {
                    usePaymentElement: config.usePaymentElement,
                    elementsExists: !!elements,
                    cardExists: !!card
                });
            }
            
            return elements;
            
        } catch (error) {
            console.error('Fehler bei der Initialisierung von Stripe Elements:', error);
            return null;
        }
    }
    
    /**
     * Checkbox für Zahlungsbuttons einrichten
     */
    function setupCheckoutButtons() {
        // Alle checkout-buttons finden
        const checkoutButtons = document.querySelectorAll('[data-checkout-button]');
        
        checkoutButtons.forEach(button => {
            button.addEventListener('click', function(event) {
                event.preventDefault();
                
                // Produkt-ID aus dem Button-Attribut holen
                const productId = this.dataset.productId;
                if (!productId) {
                    console.error('Keine Produkt-ID für den Checkout-Button definiert');
                    return;
                }
                
                // Modal öffnen
                const modal = document.getElementById('customerDataModal');
                if (!modal) {
                    console.error('Modal nicht gefunden');
                    return;
                }
                
                // Bootstrap Modal-Instanz holen
                const bsModal = new bootstrap.Modal(modal);
                bsModal.show();
                
                // Stripe initialisieren (ohne Elements)
                initStripe();
                
                // Produktinformationen anzeigen
                displayProductInfo(productId);
            });
        });
    }
    
    /**
     * Produktinformationen im Modal anzeigen
     */
    function displayProductInfo(productId) {
        // Produkt-ID im versteckten Feld speichern
        const productIdInput = document.getElementById('product-id');
        if (productIdInput) {
            productIdInput.value = productId;
        }
        
        // Produktdaten aus der Konfiguration holen
        const productDataForm = document.querySelector('form[data-product-data]');
        const productDataString = productDataForm?.dataset.productData;
        
        if (!productDataString) {
            console.error('Keine Produktdaten gefunden');
            return;
        }
        
        let productData;
        try {
            // HTML-Entities dekodieren und JSON parsen
            const decodedString = decodeHTMLEntities(productDataString);
            productData = JSON.parse(decodedString);
            
            // Als globale Variable für das Formulsr speichern
            window.currentProductData = productData;
            
            if (isDebug) {
                console.log('Produktdaten geladen:', productData);
            }
        } catch (error) {
            console.error('Fehler beim Parsen der Produktdaten:', error);
            return;
        }
        
        // Produkt anhand der ID finden
        const product = productData.find(p => p.id.toString() === productId.toString());
        if (!product) {
            console.error('Produkt nicht gefunden:', productId);
            return;
        }
        
        // Debug-Ausgabe für Produkt
        if (isDebug) {
            console.log('Ausgewähltes Produkt:', product);
        }
        
        // Aktuelle Produktdaten speichern
        currentProductData = product;
        
        // Sicherstellen, dass Preis und Steuersatz numerisch sind
        product.price = parseFloat(product.price);
        product.tax_rate = parseFloat(product.tax_rate || 19);
        
        if (isDebug) {
            console.log('Verarbeitete Produktdaten:', {
                price: product.price,
                tax_rate: product.tax_rate
            });
        }
        
        // Jetzt mit dem bekannten Preis Stripe Elements initialisieren
        initStripeElements(product.price);
        
        // Template für die Produktinformationen finden und anwenden
        const templateContent = document.getElementById('product-info-template');
        const productInfoContainer = document.querySelector('[data-product-info]');
        
        if (templateContent && productInfoContainer) {
            // Template klonen und Inhalte füllen
            const productInfoTemplate = templateContent.content.cloneNode(true);
            
            // Produkttitel
            const productTitleElement = productInfoTemplate.querySelector('[data-product-title]');
            if (productTitleElement) {
                productTitleElement.textContent = product.title;
            }
            
            // Preis formatieren - WICHTIG: Preis ist in Euro, nicht in Cent!
            const productPriceElement = productInfoTemplate.querySelector('[data-product-price]');
            if (productPriceElement) {
                // Hier formatieren wir direkt in Euro, nicht durch 100 teilen
                const formattedPrice = formatCurrency(product.price, config.currency, false);
                productPriceElement.textContent = formattedPrice;
            }
            
            // Steuerbetrag berechnen und anzeigen
            const taxRow = productInfoTemplate.querySelector('[data-tax-row]');
            const taxAmountElement = productInfoTemplate.querySelector('[data-tax-amount]');
            if (taxRow && taxAmountElement) {
                if (isDebug) {
                    console.log('Steuerberechnung für:', {
                        price: product.price,
                        taxRate: product.tax_rate
                    });
                }
                
                // Mehrwertsteuer vom Bruttobetrag: Brutto * Steuersatz / (100 + Steuersatz)
                const taxAmount = calculateTax(product.price, product.tax_rate);
                
                if (isDebug) {
                    console.log('Berechneter Steuerbetrag:', taxAmount);
                }
                
                // Auch hier in Euro formatieren, nicht durch 100 teilen
                taxAmountElement.textContent = formatCurrency(taxAmount, config.currency, false);
            }
            
            // Laufzeit anzeigen, falls vorhanden
            const durationRow = productInfoTemplate.querySelector('[data-duration-row]');
            const durationElement = productInfoTemplate.querySelector('[data-product-duration]');
            if (durationRow && durationElement && product.duration) {
                durationRow.classList.remove('d-none');
                durationElement.textContent = formatDuration(product.duration);
            }
            
            // Produktinformationen in den Container einfügen
            productInfoContainer.innerHTML = '';
            productInfoContainer.appendChild(productInfoTemplate);
        }
    }
    
    /**
     * HTML-Entities dekodieren
     */
    function decodeHTMLEntities(text) {
        const textArea = document.createElement('textarea');
        textArea.innerHTML = text;
        return textArea.value;
    }
    
    /**
     * Event-Listener für das Zahlungsformular
     */
    function setupFormSubmission() {
        const form = document.querySelector(config.formSelector || '#customerDataForm');
        if (!form) return;
        
        form.addEventListener('submit', async function(event) {
            event.preventDefault();
            
            // Formular-Status setzen
            setFormSubmitting(true);
            
            // Formular validieren
            if (!validateForm(form)) {
                setFormSubmitting(false);
                return;
            }
            
            try {
                // Wenn wir noch kein clientSecret haben, einen Payment Intent erstellen
                if (!clientSecret) {
                    try {
                        const paymentIntentData = await createPaymentIntent(form);
                        
                        if (isDebug) {
                            console.log('Payment Intent erstellt:', paymentIntentData);
                        }
                        
                        if (!paymentIntentData.clientSecret) {
                            throw new Error('Keine clientSecret in der Antwort erhalten.');
                        }
                        
                        clientSecret = paymentIntentData.clientSecret;
                    } catch (error) {
                        console.error('Fehler beim Erstellen des Payment Intent:', error);
                        showFormError('Fehler beim Erstellen der Zahlung: ' + error.message);
                        setFormSubmitting(false);
                        return;
                    }
                }
                
                if (isDebug) {
                    console.log('Verwende clientSecret für Zahlung:', clientSecret ? 'Vorhanden' : 'Fehlt');
                }
                
                // Zahlungsdaten mit Elements bestätigen
                let result;
                if (config.usePaymentElement) {
                    // Für das PaymentElement
                    if (!clientSecret) {
                        throw new Error('clientSecret fehlt für die Zahlungsbestätigung');
                    }
                    
                    // PROBLEM: Stellen sicher, dass Stripe Elements initialisiert sind
                    if (!elements || !stripe) {
                        console.error('Stripe oder Elements nicht initialisiert. Versuche neu zu initialisieren...');
                        
                        // Stripe initialisieren falls nötig
                        if (!stripe) {
                            try {
                                initStripe();
                                if (!stripe) {
                                    throw new Error('Stripe konnte nicht initialisiert werden');
                                }
                            } catch (error) {
                                console.error('Fehler bei Stripe-Initialisierung:', error);
                                throw new Error('Fehler bei der Stripe-Initialisierung: ' + error.message);
                            }
                        }
                        
                        // Versuche Elements neu zu initialisieren
                        if (!elements && stripe) {
                            try {
                                if (currentProductData && currentProductData.price) {
                                    initStripeElements(currentProductData.price);
                                } else {
                                    initStripeElements(100); // Fallback-Wert
                                }
                                
                                // Überprüfen, ob die Elemente jetzt initialisiert sind
                                if (!elements) {
                                    throw new Error('Elements konnten nicht erstellt werden');
                                }
                            } catch (error) {
                                console.error('Fehler bei Elements-Initialisierung:', error);
                                throw new Error('Fehler bei der Elements-Initialisierung: ' + error.message);
                            }
                        }
                    }
                    
                    // Prüfe noch einmal, ob Elements wirklich existiert
                    if (!elements) {
                        throw new Error('Elements-Objekt fehlt auch nach Initialisierungsversuch. Bitte Seite neu laden.');
                    }
                    
                    if (isDebug) {
                        console.log('Starte confirmPayment mit Elements:', {
                            elementsExists: !!elements,
                            clientSecret: clientSecret ? 'Vorhanden' : 'Fehlt',
                            stripeExists: !!stripe
                        });
                        
                        // Element-Konfiguration prüfen
                        console.log('Elements-Konfiguration:', {
                            mode: elements._componentType || 'Unbekannt',
                            currency: elements._commonOptions?.currency || 'Unbekannt',
                            amount: elements._commonOptions?.amount || 'Unbekannt'
                        });
                    }
                    
                    try {
                        // Zuerst die Elemente validieren mit submit()
                        const { error: submitError } = await elements.submit();
                        if (submitError) {
                            handlePaymentError(submitError);
                            return;
                        }
                        
                        // Erstelle eine absolute URL für return_url
                        let returnUrl = config.successUrl.replace('{CHECKOUT_SESSION_ID}', clientSecret.split('_secret_')[0]);
                        
                        // Prüfen, ob die URL bereits ein Protokoll enthält
                        if (!returnUrl.startsWith('http://') && !returnUrl.startsWith('https://')) {
                            // Wenn nicht, absolute URL erstellen
                            const baseUrl = window.location.protocol + '//' + window.location.host;
                            
                            // Falls die URL mit / beginnt, einfach zum Basis-URL hinzufügen
                            if (returnUrl.startsWith('/')) {
                                returnUrl = baseUrl + returnUrl;
                            } else {
                                // Ansonsten als relativen Pfad zur aktuellen Seite behandeln
                                returnUrl = baseUrl + '/' + returnUrl;
                            }
                        }
                        
                        if (isDebug) {
                            console.log('Verwende return_url für Stripe:', returnUrl);
                        }
                        
                        // Dann erst confirmPayment ausführen
                        const { error } = await stripe.confirmPayment({
                            elements,
                            clientSecret: clientSecret,
                            confirmParams: {
                                return_url: returnUrl,
                                receipt_email: form.querySelector('#email')?.value,
                                payment_method_data: {
                                    billing_details: getBillingDetailsFromForm(form)
                                }
                            },
                            redirect: 'if_required'
                        });
                        
                        if (error) {
                            // Fehler bei der Zahlung
                            handlePaymentError(error);
                        } else {
                            // Erfolg oder Weiterleitung für 3D Secure
                            const paymentIntent = await stripe.retrievePaymentIntent(clientSecret);
                            if (paymentIntent.paymentIntent.status === 'succeeded') {
                                handleSuccessfulPayment(paymentIntent.paymentIntent);
                            }
                        }
                    } catch (confirmError) {
                        console.error('Fehler bei stripe.confirmPayment:', confirmError);
                        handlePaymentError({
                            type: 'validation_error',
                            message: confirmError.message || 'Fehler bei der Zahlungsbestätigung'
                        });
                    }
                } else {
                    // Für das klassische Card Element
                    if (!clientSecret) {
                        throw new Error('clientSecret fehlt für die Zahlungsbestätigung');
                    }
                    
                    if (!card) {
                        throw new Error('Card Element wurde nicht initialisiert');
                    }
                    
                    result = await stripe.confirmCardPayment(clientSecret, {
                        payment_method: {
                            card: card,
                            billing_details: getBillingDetailsFromForm(form)
                        }
                    });
                    
                    if (result.error) {
                        // Fehler bei der Zahlung
                        handlePaymentError(result.error);
                    } else if (result.paymentIntent.status === 'succeeded') {
                        // Erfolgreiche Zahlung
                        handleSuccessfulPayment(result.paymentIntent);
                    } else {
                        // Zahlung erfordert zusätzliche Aktionen (z.B. 3D Secure)
                        console.log('Zahlung erfordert weitere Aktionen:', result.paymentIntent.status);
                    }
                }
                
            } catch (error) {
                console.error('Fehler bei der Zahlungsverarbeitung:', error);
                showFormError('Ein unerwarteter Fehler ist aufgetreten: ' + error.message);
                setFormSubmitting(false);
            }
        });
    }
    
    /**
     * Erstellt einen Payment Intent auf dem Server
     */
    async function createPaymentIntent(form) {
        // Formular- und Produktdaten sammeln
        const formData = new FormData(form);
        const productId = formData.get('product-id');
        
        if (!productId) {
            console.error('Keine Produkt-ID im Formular gefunden');
            throw new Error('Keine Produkt-ID gefunden. Bitte wählen Sie ein Produkt aus.');
        }
        
        // Globale Produktdaten verwenden, die wir vorher gespeichert haben
        if (!window.currentProductData) {
            console.error('Keine globalen Produktdaten gefunden');
            throw new Error('Keine Produktdaten gefunden. Bitte laden Sie die Seite neu und versuchen Sie es erneut.');
        }
        
        // Produkt anhand der ID finden
        const currentProductData = window.currentProductData.find(p => p.id.toString() === productId.toString());
        if (!currentProductData) {
            console.error('Produkt nicht in Produktdaten gefunden:', {productId, allProducts: window.currentProductData});
            throw new Error('Produkt nicht gefunden. Bitte laden Sie die Seite neu und versuchen Sie es erneut.');
        }
        
        // Sicherstellen, dass der Preis numerisch ist
        const price = parseFloat(currentProductData.price);
        if (isNaN(price) || price <= 0) {
            console.error('Ungültiger Preis im Produkt:', currentProductData);
            throw new Error('Ungültiger Preis. Bitte kontaktieren Sie den Support.');
        }
        
        // Persönliche Daten aus dem Formular extrahieren
        const personalData = {
            email: formData.get('email'),
            firstname: formData.get('firstname'),
            lastname: formData.get('lastname'),
            company: formData.get('company'),
            street: formData.get('street'),
            postal: formData.get('postal'),
            city: formData.get('city'),
            country: formData.get('country'),
            phone: formData.get('phone')
        };
        
        // E-Mail-Adresse validieren
        if (!personalData.email) {
            throw new Error('Bitte geben Sie eine E-Mail-Adresse ein.');
        }
        
        // Payment Intent Daten vorbereiten
        const amountInCents = Math.round(price * 100);
        
        const paymentData = {
            // Preis in Cent umwandeln für Stripe
            amount: amountInCents,
            currency: config.currency.toLowerCase(),
            description: `Zahlung für: ${currentProductData.title}`,
            customer_email: personalData.email,
            metadata: {
                product_id: productId,
                product_title: currentProductData.title,
                element_id: formData.get('element-id')
            },
            product_data: {
                title: currentProductData.title,
                price: amountInCents, // Auch hier in Cent umwandeln
                tax_rate: parseFloat(currentProductData.tax_rate) || 19,
                duration: parseInt(currentProductData.duration) || 0,
                success_url: config.successUrl
            },
            customer_data: personalData
        };
        
        if (isDebug) {
            console.log('Sende Payment Intent Anfrage an Server mit Daten:', paymentData);
        }
        
        try {
            // API-Aufruf zum Erstellen des Payment Intents
            const response = await fetch('/stripe/create-payment-intent', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(paymentData)
            });
            
            // Prüfe HTTP-Status
            if (!response.ok) {
                const errorText = await response.text();
                console.error('Fehler bei der Serverantwort:', response.status, errorText);
                throw new Error(`Server antwortet mit Fehler ${response.status}: ${errorText}`);
            }
            
            // Antwort auswerten
            const result = await response.json();
            
            if (isDebug) {
                console.log('Server-Antwort für Payment Intent:', result);
            }
            
            if (!result || !result.clientSecret) {
                console.error('Ungültige Server-Antwort:', result);
                throw new Error('Serverantwort enthält keinen clientSecret');
            }
            
            // Detailliertes Debugging der erhaltenen Daten
            if (isDebug) {
                console.log('DEBUGGING SERVER-ANTWORT:', {
                    id: result.id ? 'vorhanden' : 'fehlt',
                    clientSecret: result.clientSecret ? 'vorhanden' : 'fehlt',
                    client_secret: result.client_secret ? 'vorhanden' : 'fehlt',
                    amount: result.amount,
                    currency: result.currency,
                    session_id: result.session_id ? 'vorhanden' : 'fehlt',
                    dbSuccess: result.dbSuccess ?? 'nicht definiert',
                    dbError: result.dbError || 'kein Fehler',
                    komplette_antwort: JSON.stringify(result)
                });
            }
            
            // Konsistentes Format sicherstellen
            const response_data = {
                id: result.id,
                clientSecret: result.clientSecret || result.client_secret,
                amount: result.amount,
                currency: result.currency,
                // Session-ID aus der Antwort speichern (falls vorhanden)
                session_id: result.session_id || result.id
            };
            
            // Speichere die Session-ID global für die Weiterleitung
            window.paymentSessionId = response_data.session_id;
            
            if (isDebug) {
                console.log('Extrahierter clientSecret:', response_data.clientSecret);
                console.log('Session-ID für Weiterleitung:', window.paymentSessionId);
                console.log('Originale Server-Felder:', {
                    clientSecret: result.clientSecret,
                    client_secret: result.client_secret,
                    session_id: result.session_id,
                    id: result.id,
                    dbSuccess: result.dbSuccess
                });
            }
            
            // Zusätzliche Überprüfung - wurde die Session in der DB gespeichert?
            if (result.dbSuccess === false) {
                console.warn('Server konnte Session nicht in Datenbank speichern', result.dbError);
                // Trotzdem fortfahren - eventuell kann die Zahlung trotzdem abgeschlossen werden
                // Der Server wird beim erfolgreichen Abschluss der Zahlung eine weitere Chance haben,
                // die Session zu speichern
            }
            
            return response_data;
        } catch (fetchError) {
            console.error('Fehler bei API-Aufruf:', fetchError);
            showToast(fetchError.message || 'Fehler bei der Kommunikation mit dem Server');
            hideLoadingIndicator();
            return null;
        }
    }
    
    /**
     * Extrahiert Rechnungsdaten aus dem Formular
     */
    function getBillingDetailsFromForm(form) {
        const formData = new FormData(form);
        
        return {
            name: [formData.get('firstname'), formData.get('lastname')].filter(Boolean).join(' '),
            email: formData.get('email'),
            phone: formData.get('phone'),
            address: {
                line1: formData.get('street'),
                postal_code: formData.get('postal'),
                city: formData.get('city'),
                country: formData.get('country')
            }
        };
    }
    
    /**
     * Fehlermeldung bei der Zahlung behandeln
     */
    function handlePaymentError(error) {
        console.error('Fehler bei der Zahlungsverarbeitung:', error);
        
        let errorMessage = 'Es ist ein Fehler bei der Zahlungsverarbeitung aufgetreten: ';
        
        if (error.type === 'card_error' || error.type === 'validation_error') {
            // Spezifische Kartenfehler
            errorMessage += error.message;
        } else {
            // Allgemeiner Fehler
            errorMessage += 'Bitte versuchen Sie es später noch einmal.';
        }
        
        showFormError(errorMessage);
        setFormSubmitting(false);
    }
    
    /**
     * Erfolgreiche Zahlung behandeln
     */
    function handleSuccessfulPayment(paymentIntent) {
        console.log('Zahlung erfolgreich:', paymentIntent);
        
        // Erfolgs-URL mit Session ID erstellen
        let successUrl = config.successUrl;
        
        // Debug-Ausgabe
        if (isDebug) {
            console.log('Payment-Success-Daten für Weiterleitung:', {
                paymentIntent: paymentIntent ? 'vorhanden' : 'nicht vorhanden',
                payment_intent_id: paymentIntent ? paymentIntent.id : 'nicht verfügbar',
                window_session_id: window.paymentSessionId || 'nicht gesetzt',
                success_url_template: successUrl
            });
        }
        
        // Wenn wir eine gespeicherte Session-ID haben, verwende diese
        // Ansonsten fallback auf die Payment Intent ID
        const sessionId = window.paymentSessionId || paymentIntent.id;
        
        // Session-ID in die URL einfügen
        successUrl = successUrl.replace('{CHECKOUT_SESSION_ID}', sessionId);
        
        // Prüfen, ob die URL eine unserer Standard-Endpoints ist und in diesem Fall die richtige Route verwenden
        if (successUrl.includes('/stripe/checkout/success')) {
            console.log('Standard-Checkout-Route erkannt, verwende /stripe/payment-intent-success stattdessen');
            successUrl = successUrl.replace('/stripe/checkout/success', '/stripe/payment-intent-success');
        }
        
        // Auch die ggf. gehardcodete Route korrigieren
        if (successUrl === '/checkout/success') {
            successUrl = '/stripe/payment-intent-success';
        }
        
        // Sicherstellen, dass es eine absolute URL ist
        if (!successUrl.startsWith('http://') && !successUrl.startsWith('https://')) {
            const baseUrl = window.location.protocol + '//' + window.location.host;
            
            if (successUrl.startsWith('/')) {
                successUrl = baseUrl + successUrl;
            } else {
                successUrl = baseUrl + '/' + successUrl;
            }
        }
        
        // Zusätzlichen Parameter für Payment Intent ID und Session ID hinzufügen
        // Zuerst prüfen, ob die URL bereits Parameter enthält
        const hasParams = successUrl.includes('?');
        const paramPrefix = hasParams ? '&' : '?';
        
        // Payment Intent ID hinzufügen
        successUrl += paramPrefix + 'payment_intent_id=' + paymentIntent.id;
        
        // Session ID hinzufügen, wenn verfügbar und unterschiedlich von PI-ID
        if (sessionId && sessionId !== paymentIntent.id) {
            successUrl += '&session_id=' + sessionId;
        }
        
        if (isDebug) {
            console.log('Weiterleitung zu:', successUrl);
            console.log('Verwendete Session-ID:', sessionId);
            console.log('Payment Intent ID:', paymentIntent.id);
        }
        
        // Zur Erfolgsseite weiterleiten
        window.location.href = successUrl;
    }
    
    /**
     * Formular-Validierung
     */
    function validateForm(form) {
        // HTML5-Validierung verwenden
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            
            // Fehlermeldung für das erste ungültige Feld anzeigen
            const invalidField = form.querySelector(':invalid');
            if (invalidField) {
                invalidField.focus();
            }
            
            return false;
        }
        
        return true;
    }
    
    /**
     * Formular-Status während der Übermittlung setzen
     */
    function setFormSubmitting(isSubmitting) {
        const form = document.querySelector(config.formSelector || '#customerDataForm');
        if (!form) return;
        
        const submitButton = form.querySelector('button[type="submit"]');
        const spinner = form.querySelector('[data-form-spinner]');
        
        if (submitButton) {
            submitButton.disabled = isSubmitting;
        }
        
        if (spinner) {
            if (isSubmitting) {
                spinner.classList.remove('d-none');
            } else {
                spinner.classList.add('d-none');
            }
        }
    }
    
    /**
     * Fehlermeldung im Formular anzeigen
     */
    function showFormError(message) {
        const errorElement = document.querySelector('[data-form-error]');
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.classList.remove('d-none');
            
            // Zum Fehlerelement scrollen
            errorElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
    
    /**
     * Betrag formatieren
     * @param {number} amount - Der Betrag
     * @param {string} currency - Die Währung
     * @param {boolean} isCents - Ob der Betrag in Cent (true) oder Euro (false) angegeben ist
     */
    function formatCurrency(amount, currency, isCents = true) {
        const formatter = new Intl.NumberFormat('de-DE', {
            style: 'currency',
            currency: currency.toUpperCase()
        });
        
        // Wenn der Betrag in Cent ist, durch 100 teilen
        const formattedAmount = isCents ? amount / 100 : amount;
        return formatter.format(formattedAmount);
    }
    
    /**
     * Steuer berechnen
     * @param {number} amount - Der Bruttobetrag
     * @param {number} taxRate - Der Steuersatz in Prozent
     * @returns {number} - Der Steuerbetrag
     */
    function calculateTax(amount, taxRate) {
        // Sicherstellen, dass alle Werte numerisch sind
        const numAmount = parseFloat(amount);
        const numTaxRate = parseFloat(taxRate);
        
        if (isNaN(numAmount) || isNaN(numTaxRate)) {
            console.error('Ungültige Werte für Steuerberechnung:', {amount, taxRate});
            return 0;
        }
        
        // Mehrwertsteuer vom Bruttobetrag: Brutto * Steuersatz / (100 + Steuersatz)
        const taxAmount = (numAmount * numTaxRate) / (100 + numTaxRate);
        
        if (isDebug) {
            console.log('Steuerberechnung Details:', {
                bruttoBetrag: numAmount,
                steuersatz: numTaxRate,
                berechnung: `${numAmount} * ${numTaxRate} / (100 + ${numTaxRate}) = ${taxAmount}`,
                steuerBetrag: taxAmount
            });
        }
        
        return taxAmount;
    }
    
    /**
     * Laufzeit formatieren
     */
    function formatDuration(duration) {
        // Duration in Tagen
        if (duration === 1) {
            return '1 Tag';
        } else if (duration < 30) {
            return `${duration} Tage`;
        } else if (duration === 30 || duration === 31) {
            return '1 Monat';
        } else if (duration < 365) {
            const months = Math.round(duration / 30);
            return `${months} Monate`;
        } else if (duration === 365 || duration === 366) {
            return '1 Jahr';
        } else {
            const years = Math.round(duration / 365);
            return `${years} Jahre`;
        }
    }
    
    // Event-Listener für Checkout-Buttons einrichten
    setupCheckoutButtons();
    
    // Event-Listener für Formular-Übermittlung einrichten
    setupFormSubmission();
}); 