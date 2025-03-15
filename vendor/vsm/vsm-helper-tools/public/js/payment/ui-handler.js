class UIHandler {
    constructor(form) {
        if (!form || !(form instanceof HTMLFormElement)) {
            console.error('UIHandler: Ungültiges Formular-Element übergeben', form);
            throw new Error('UIHandler erfordert ein gültiges HTML-Formular-Element');
        }
        this.form = form;
        console.log('UIHandler initialisiert für Formular:', form.id || '(ohne ID)');
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

    updateProductInfo(productData, currency) {
        const priceInfo = this.form.querySelector('[data-product-info]');
        if (!priceInfo) {
            console.warn('Element [data-product-info] nicht gefunden');
            return;
        }
        
        const template = document.getElementById('product-info-template');
        if (!template) {
            console.warn('Template #product-info-template nicht gefunden');
            return;
        }
        
        const clone = this.createTemplateClone(template, productData, currency);
        priceInfo.innerHTML = '';
        priceInfo.appendChild(clone);
    }

    createTemplateClone(template, productData, currency) {
        const clone = template.content.cloneNode(true);

        // Template Labels
        this.setTemplateLabels(clone, template.dataset);

        // Product Data
        this.setProductData(clone, productData, currency);

        return clone;
    }

    setTemplateLabels(clone, dataset) {
        const labels = {
            heading: '[data-template-heading]',
            productLabel: '[data-template-product-label]',
            priceLabel: '[data-template-price-label]',
            priceBadge: '[data-template-price-badge]',
            durationLabel: '[data-template-duration-label]',
            expiryLabel: '[data-template-expiry-label]'
        };

        Object.entries(labels).forEach(([key, selector]) => {
            const element = clone.querySelector(selector);
            if (element && dataset[key]) {
                element.textContent = dataset[key];
            }
        });
    }

    setProductData(clone, productData, currency) {
        try {
            // Produkttitel setzen
            const titleElement = clone.querySelector('[data-product-title]');
            if (titleElement) {
                titleElement.textContent = productData.title;
            }
            
            // Preis setzen
            const priceElement = clone.querySelector('[data-product-price]');
            if (priceElement) {
                priceElement.textContent = this.formatCurrency(productData.price, currency);
            }

            // Steuerberechnung und -anzeige
            const taxRate = productData.tax_rate || 19;
            const priceValue = parseFloat(productData.price);
            const netPrice = priceValue / (1 + (taxRate / 100));
            const taxAmount = priceValue - netPrice;
            
            // Steuerzeile anzeigen
            const taxRow = clone.querySelector('[data-tax-row]');
            if (taxRow) {
                // Ändere die Beschriftung, um den Steuersatz anzuzeigen
                const taxLabel = taxRow.querySelector('th');
                if (taxLabel) {
                    taxLabel.textContent = `enthaltene MwSt. (${taxRate}%)`;
                }
                
                // Zeige den Steuerbetrag an
                const taxAmountElement = taxRow.querySelector('[data-tax-amount]');
                if (taxAmountElement) {
                    taxAmountElement.textContent = this.formatCurrency(taxAmount, currency);
                }
            }

            if (productData.duration > 0) {
                this.setDurationInfo(clone, productData.duration);
            }
        } catch (error) {
            console.error('Fehler beim Setzen der Produktdaten:', error);
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

    showError(message) {
        try {
            const errorElement = this.form.querySelector('[data-form-error]');
            if (errorElement) {
                errorElement.textContent = message;
                errorElement.classList.remove('d-none');
            } else {
                // Fallback: Alert anzeigen, wenn kein Error-Element gefunden wird
                console.warn('Kein Element mit data-form-error gefunden, zeige Fehler als Alert:', message);
                alert('Fehler: ' + message);
            }
        } catch (error) {
            console.error('Fehler beim Anzeigen der Fehlermeldung:', error);
            alert('Fehler: ' + message);
        }
    }

    toggleLoadingState(isLoading) {
        try {
            const submitButton = this.form.querySelector('button[type="submit"]');
            const spinner = this.form.querySelector('[data-form-spinner]');

            if (submitButton) {
                submitButton.disabled = isLoading;
            }
            
            if (spinner) {
                spinner.classList.toggle('d-none', !isLoading);
            }
        } catch (error) {
            console.error('Fehler beim Umschalten des Ladezustands:', error);
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