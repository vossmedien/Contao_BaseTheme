class StripePaymentHandler {
    constructor() {
        this.stripe = null;
        this.elements = null;
        this.form = null;
        this.config = null;
        this.validationHandler = null;
        this.elementHandler = null;
        this.uiHandler = null;
        this.init();
    }

    init() {
        document.addEventListener('DOMContentLoaded', () => {
            this.form = document.querySelector('form[data-stripe-payment]');
            if (!this.form) return;

            this.config = this.initializeConfig();
            this.uiHandler = new UIHandler(this.form);
            this.uiHandler.initializePriceDisplays();
            this.initializeFormHandling();
        });
    }

    initializeConfig() {
        return {
            stripeKey: this.form.dataset.stripeKey,
            createUser: this.form.dataset.createUser === 'true' || this.form.dataset.createUser === '1',
            successUrl: this.form.dataset.successUrl,
            productData: JSON.parse(this.form.dataset.productData || '[]')
        };
    }

    async openModal(productData) {
        this.stripe = Stripe(this.config.stripeKey);

        // UI-Handling
        this.uiHandler.updateModal(productData, this.form.dataset.currency || 'eur');

        // Stripe Elements
        this.elementHandler = new StripeElementHandler(this.stripe);
        this.elements = await this.elementHandler.initialize(
            productData.price,
            this.form.dataset.currency || 'eur',
            (errorCode) => this.translateErrorMessage(errorCode)
        );

        // Validation wenn nötig
        if (this.config.createUser) {
            this.initializeValidation();
        }

        // Modal öffnen
        const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
        modal.show();
    }

    getTexts() {
        const textsElement = document.getElementById('payment-texts');
        if (!textsElement) return {};

        const stripeErrors = JSON.parse(textsElement?.dataset.stripeErrors || '{}');

        return {
            errorRequired: textsElement.dataset.errorRequired,
            errorGeneral: textsElement.dataset.errorGeneral,
            errorProductNotFound: textsElement.dataset.errorProductNotFound,
            validationPassword: textsElement.dataset.validationPassword,
            validationEmail: textsElement.dataset.validationEmail,
            validationUsername: textsElement.dataset.validationUsername,
            networkError: textsElement.dataset.networkError,
            stripeErrors
        };
    }

    initializeValidation() {
        if (!this.config.createUser) return;

        if (!this.validationHandler) {
            this.validationHandler = new ValidationHandler(this.form, this.getTexts());
        }

        this.validationHandler.initialize();
    }

    initializeFormHandling() {
        this.form.addEventListener('submit', async (event) => {
            event.preventDefault();
            await this.handleFormSubmit(event);
        });
    }

    async handleFormSubmit(event) {
        const texts = this.getTexts();

        // Validiere required fields
        const requiredFields = this.form.querySelectorAll('[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            }
        });

        if (!isValid) {
            this.uiHandler.showError(texts.errorRequired);
            return;
        }

        // UI in Loading-Zustand versetzen
        this.uiHandler.toggleLoadingState(true);

        // Form Daten sammeln
        const formData = new FormData(this.form);
        const personalData = {};
        for (const [key, value] of formData.entries()) {
            personalData[key] = key === 'password' ? btoa(value) : value;
        }

        try {
            await this.handlePayment(personalData);
        } catch (error) {
            this.uiHandler.showError(error.message || texts.errorGeneral);
            this.uiHandler.toggleLoadingState(false);
        }
    }

    async handlePayment(personalData) {
        // Stripe Elements submitten
        const {error: submitError} = await this.elements.submit();
        if (submitError) throw submitError;

        // Payment Intent erstellen
        const paymentIntent = await this.createPaymentIntent(personalData);

        // Payment bestätigen
        await this.confirmPayment(paymentIntent.clientSecret, personalData);
    }

    async createPaymentIntent(personalData) {
        const productId = this.form.querySelector('#product-id').value;
        const selectedProduct = this.config.productData.find(p => p.id === parseInt(productId));

        if (!selectedProduct) {
            throw new Error(this.getTexts().errorProductNotFound);
        }

        const response = await fetch('/stripe/create-payment-intent', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                productId: productId,
                productTitle: selectedProduct.title,
                elementId: personalData['element-id'],
                amount: Math.round(parseFloat(selectedProduct.price) * 100),
                currency: this.form.dataset.currency || 'eur',
                personalData: personalData,
                createUser: this.config.createUser,
                productData: {
                    eventName: selectedProduct.eventName,
                    duration: selectedProduct.duration
                }
            })
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || this.getTexts().errorGeneral);
        }

        return await response.json();
    }

    async confirmPayment(clientSecret, personalData) {
        const baseUrl = window.location.origin;
        const returnUrl = this.config.successUrl.startsWith('http')
            ? this.config.successUrl
            : baseUrl + '/' + this.config.successUrl.replace(/^\//, '');

        const {error} = await this.stripe.confirmPayment({
            elements: this.elements,
            clientSecret,
            confirmParams: {
                return_url: returnUrl,
                payment_method_data: {
                    billing_details: {
                        name: `${personalData.firstname} ${personalData.lastname}`,
                        email: personalData.email,
                        address: {
                            line1: personalData.street || '',
                            postal_code: personalData.postal || '',
                            city: personalData.city || ''
                        },
                        phone: personalData.phone || ''
                    }
                }
            }
        });

        if (error) throw error;
    }

    translateErrorMessage(errorCode) {
        const texts = this.getTexts();
        return texts.stripeErrors[errorCode] || texts.stripeErrors.default;
    }
}

window.stripeProductPayment = new StripePaymentHandler();

window.openPaymentModal = function (productData) {
    window.stripeProductPayment.openModal(productData);
};