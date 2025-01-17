class UIHandler {
    constructor(form) {
        this.form = form;
    }

    updateModal(productData, currency) {
        this.resetForm();
        this.updateProductId(productData.id);
        this.updateProductInfo(productData, currency);
    }

    resetForm() {
        this.form.reset();
        this.form.querySelectorAll('.is-invalid').forEach(input => input.classList.remove('is-invalid'));
        this.form.querySelector('[data-payment-error]').classList.add('d-none');
    }

    updateProductId(id) {
        this.form.querySelector('#product-id').value = id;
    }

    updateProductInfo(productData, currency) {
        const priceInfo = this.form.querySelector('[data-product-info]');
        const template = document.getElementById('product-info-template');
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
            clone.querySelector(selector).textContent = dataset[key];
        });
    }

    setProductData(clone, productData, currency) {
        clone.querySelector('[data-product-title]').textContent = productData.title;
        clone.querySelector('[data-product-price]').textContent = this.formatCurrency(productData.price, currency);

        if (productData.duration > 0) {
            this.setDurationInfo(clone, productData.duration);
        }
    }

    setDurationInfo(clone, duration) {
        const durationRow = clone.querySelector('[data-duration-row]');
        const expiryRow = clone.querySelector('[data-expiry-row]');

        durationRow.classList.remove('d-none');
        expiryRow.classList.remove('d-none');

        clone.querySelector('[data-product-duration]').textContent = `${duration} Monate`;
        clone.querySelector('[data-product-expiry]').textContent = this.calculateExpiryDate(duration);
    }

    showError(message) {
        const errorElement = this.form.querySelector('[data-payment-error]');
        errorElement.textContent = message;
        errorElement.classList.remove('d-none');
    }

    toggleLoadingState(isLoading) {
        const submitButton = this.form.querySelector('[data-payment-submit]');
        const spinner = this.form.querySelector('[data-payment-spinner]');

        submitButton.disabled = isLoading;
        spinner.classList.toggle('d-none', !isLoading);
    }

    formatCurrency(amount, currency) {
        return new Intl.NumberFormat('de-DE', {
            style: 'currency',
            currency: currency.toUpperCase()
        }).format(amount);
    }

    calculateExpiryDate(months) {
        const date = new Date();
        date.setMonth(date.getMonth() + parseInt(months));
        return date.toLocaleDateString('de-DE', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    }

    initializePriceDisplays() {
        document.querySelectorAll('[data-price-display]').forEach(element => {
            const amount = parseFloat(element.dataset.amount);
            const currency = element.dataset.currency;
            element.textContent = this.formatCurrency(amount, currency);
        });
    }
}