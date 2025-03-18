class UIHandler {
    constructor(formElement) {
        if (!formElement || !(formElement instanceof HTMLFormElement)) {
            console.error('UIHandler: Ungültiges Formular-Element übergeben', formElement);
            throw new Error('UIHandler erfordert ein gültiges HTML-Formular-Element');
        }
        this.form = formElement;
        this.spinner = this.form.querySelector('[data-form-spinner]');
        this.errorElement = this.form.querySelector('[data-form-error]');
        this.productInfo = this.form.querySelector('[data-product-info]');
        this.productTemplate = document.getElementById('product-info-template');
        
        // Elemente initialisieren
        this.init();
    }
    
    init() {
        console.log('UIHandler initialisiert für Formular:', this.form.id || '(ohne ID)');
    }
    
    /**
     * Setzt den Ladezustand des Formulars
     * @param {boolean} isLoading - true, wenn das Formular lädt, sonst false
     */
    setLoading(isLoading) {
        const submitButton = this.form.querySelector('button[type="submit"]');
        
        if (isLoading) {
            submitButton.disabled = true;
            this.spinner.classList.remove('d-none');
        } else {
            submitButton.disabled = false;
            this.spinner.classList.add('d-none');
        }
    }
    
    /**
     * Alias für setLoading für Abwärtskompatibilität
     * @param {boolean} isLoading - true, wenn das Formular lädt, sonst false
     */
    toggleLoadingState(isLoading) {
        this.setLoading(isLoading);
    }
    
    /**
     * Zeigt eine Fehlermeldung an
     * @param {string} message - Die anzuzeigende Fehlermeldung
     */
    showError(message) {
        if (this.errorElement) {
            this.errorElement.textContent = message;
            this.errorElement.classList.remove('d-none');
        } else {
            console.error('Fehler:', message);
        }
    }
    
    /**
     * Verbirgt die Fehlermeldung
     */
    hideError() {
        if (this.errorElement) {
            this.errorElement.classList.add('d-none');
        }
    }
    
    /**
     * Aktualisiert die Produktinformationen im Formular
     * @param {Object} productData - Die Produktdaten
     * @param {string} currency - Die Währung
     */
    updateProductInfo(productData, currency = 'EUR') {
        if (!this.productInfo || !this.productTemplate) return;
        
        // Produktinfos aus dem Template klonen
        const productInfoContent = this.productTemplate.content.cloneNode(true);
        
        // Produkttitel aktualisieren
        const titleElement = productInfoContent.querySelector('[data-product-title]');
        if (titleElement) {
            titleElement.textContent = productData.title || 'Produkt';
        }
        
        // Preis formatieren und aktualisieren
        const priceElement = productInfoContent.querySelector('[data-product-price]');
        if (priceElement) {
            const formattedPrice = this.formatPrice(productData.price, currency);
            priceElement.textContent = formattedPrice;
        }
        
        // Steuer berechnen und anzeigen
        const taxRow = productInfoContent.querySelector('[data-tax-row]');
        const taxElement = productInfoContent.querySelector('[data-tax-amount]');
        if (taxRow && taxElement && productData.price) {
            const taxRate = productData.tax_rate || 19; // Standard: 19% MwSt.
            const priceInCents = parseFloat(productData.price);
            // Berechnung der MwSt. aus dem Bruttopreis
            const taxAmount = priceInCents * (taxRate / (100 + taxRate));
            taxElement.textContent = this.formatPrice(taxAmount, currency);
        }
        
        // Mitgliedschaftsdauer anzeigen, wenn verfügbar
        const durationRow = productInfoContent.querySelector('[data-duration-row]');
        const durationElement = productInfoContent.querySelector('[data-product-duration]');
        if (durationRow && durationElement && productData.duration) {
            const duration = parseInt(productData.duration, 10);
            if (duration > 0) {
                durationRow.classList.remove('d-none');
                durationElement.textContent = duration + (duration === 1 ? ' Monat' : ' Monate');
                
                // Ablaufdatum berechnen und anzeigen
                const expiryRow = productInfoContent.querySelector('[data-expiry-row]');
                const expiryElement = productInfoContent.querySelector('[data-product-expiry]');
                if (expiryRow && expiryElement) {
                    const today = new Date();
                    const expiryDate = new Date(today.setMonth(today.getMonth() + duration));
                    expiryRow.classList.remove('d-none');
                    expiryElement.textContent = expiryDate.toLocaleDateString('de-DE');
                }
            }
        }
        
        // Inhalt ins DOM einfügen
        this.productInfo.innerHTML = '';
        this.productInfo.appendChild(productInfoContent);
    }
    
    /**
     * Formatiert einen Preis für die Anzeige
     * @param {number} price - Der Preis in der kleinsten Einheit (z.B. Cent)
     * @param {string} currency - Die Währung
     * @returns {string} - Der formatierte Preis
     */
    formatPrice(price, currency = 'EUR') {
        let amount = parseFloat(price);
        
        // Wenn der Preis in Cent ist und über 1000, dann ist es wahrscheinlich in der kleinsten Einheit
        if (amount > 1000 && !isNaN(amount)) {
            amount = amount / 100;
        }
        
        return new Intl.NumberFormat('de-DE', {
            style: 'currency',
            currency: currency.toUpperCase()
        }).format(amount);
    }
    
    /**
     * Schließt das Modal, falls vorhanden
     */
    closeModal() {
        const modalElement = this.form.closest('.modal');
        if (modalElement && window.bootstrap && window.bootstrap.Modal) {
            const modal = window.bootstrap.Modal.getInstance(modalElement);
            if (modal) {
                modal.hide();
            }
        }
    }

    updateModal(productData, currency) {
        try {
            if (!productData) {
                console.warn('Keine Produktdaten für updateModal übergeben');
                return;
            }
            
            this.resetForm();
            this.updateProductId(productData.id);
            this.updateProductInfo(productData, currency || 'EUR');
        } catch (error) {
            console.error('Fehler in updateModal:', error);
        }
    }

    resetForm() {
        this.form.reset();
        this.form.querySelectorAll('.is-invalid').forEach(input => input.classList.remove('is-invalid'));
        
        // Fehlermeldung ausblenden, falls vorhanden
        const errorElement = this.form.querySelector('[data-form-error]');
        if (errorElement) {
            errorElement.classList.add('d-none');
        }
    }

    updateProductId(id) {
        const productIdField = this.form.querySelector('#product-id');
        if (productIdField) {
            productIdField.value = id;
        } else {
            console.warn('Element #product-id nicht gefunden');
        }
    }

    setDurationInfo(clone, duration) {
        try {
            const durationRow = clone.querySelector('[data-duration-row]');
            const expiryRow = clone.querySelector('[data-expiry-row]');
            const durationField = clone.querySelector('[data-product-duration]');
            const expiryField = clone.querySelector('[data-product-expiry]');

            // Zeilen einblenden, falls vorhanden
            if (durationRow) {
                durationRow.classList.remove('d-none');
            }
            
            if (expiryRow) {
                expiryRow.classList.remove('d-none');
            }
            
            // Werte setzen, falls Elemente vorhanden
            if (durationField) {
                durationField.textContent = `${duration} Monate`;
            }
            
            if (expiryField) {
                expiryField.textContent = this.calculateExpiryDate(duration);
            }
        } catch (error) {
            console.error('Fehler beim Setzen der Laufzeitinformationen:', error);
        }
    }

    formatCurrency(amount, currency) {
        try {
            return new Intl.NumberFormat('de-DE', {
                style: 'currency',
                currency: currency.toUpperCase()
            }).format(amount);
        } catch (error) {
            console.error('Fehler bei der Formatierung des Währungsbetrags:', error);
            return amount + ' ' + currency;
        }
    }

    calculateExpiryDate(months) {
        try {
            const date = new Date();
            date.setMonth(date.getMonth() + parseInt(months));
            
            // Formatieren mit führenden Nullen für Tag und Monat
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            
            return `${day}.${month}.${year}`;
        } catch (error) {
            console.error('Fehler bei der Berechnung des Ablaufdatums:', error);
            return '(Datum konnte nicht berechnet werden)';
        }
    }

    initializePriceDisplays() {
        try {
            document.querySelectorAll('[data-price-display]').forEach(element => {
                if (element && element.dataset.amount && element.dataset.currency) {
                    const amount = parseFloat(element.dataset.amount);
                    const currency = element.dataset.currency;
                    
                    if (!isNaN(amount)) {
                        element.textContent = this.formatCurrency(amount, currency);
                    }
                }
            });
        } catch (error) {
            console.warn('Fehler beim Initialisieren der Preisanzeigen:', error);
        }
    }
}