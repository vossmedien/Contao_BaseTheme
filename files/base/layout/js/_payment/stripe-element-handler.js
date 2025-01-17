class StripeElementHandler {
    constructor(stripe) {
        this.stripe = stripe;
        this.elements = null;
    }

    async initialize(amount, currency) {
        this.elements = this.stripe.elements(this.createElementOptions(amount, currency));
        const paymentElement = this.elements.create('payment');
        this.mountElement(paymentElement);
        this.initializeListeners(paymentElement);
        return this.elements;
    }

    createElementOptions(amount, currency) {
        return {
            mode: 'payment',
            amount: Math.round(amount * 100),
            currency: currency.toLowerCase(),
            locale: 'de',
            appearance: this.getAppearanceOptions()
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
        container.innerHTML = '';
        paymentElement.mount('#payment-element');
    }

    initializeListeners(paymentElement, errorCallback) {
        paymentElement.on('change', (event) => {
            const displayError = document.getElementById('payment-errors');
            if (event.error) {
                displayError.textContent = errorCallback(event.error.code);
                displayError.style.display = 'block';
            } else {
                displayError.textContent = '';
                displayError.style.display = 'none';
            }
        });
    }
}