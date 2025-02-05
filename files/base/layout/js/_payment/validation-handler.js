class ValidationHandler {
    constructor(form, texts) {
        this.form = form;
        this.texts = texts;
        this.submitButton = form.querySelector('[data-payment-submit]');
        this.spinner = form.querySelector('[data-payment-spinner]');
        this._validateEmail = null;
        this._validateUsername = null;
        this._validatePassword = null;
        this.hasErrors = false;
    }

    initialize() {
        const emailInput = this.form.querySelector('#email');
        const usernameInput = this.form.querySelector('#username');
        const passwordInput = this.form.querySelector('#password');

        emailInput?.removeEventListener('input', this._validateEmail);
        usernameInput?.removeEventListener('input', this._validateUsername);
        passwordInput?.removeEventListener('input', this._validatePassword);

        this._validateEmail = () => {
            const isValid = this.validateEmail(emailInput.value);
            this.toggleError(emailInput, isValid, this.texts.validationEmail);
            return isValid;
        };

        this._validateUsername = () => {
            const isValid = this.validateUsername(usernameInput.value);
            this.toggleError(usernameInput, isValid, this.texts.validationUsername);
            return isValid;
        };

        this._validatePassword = () => {
            const isValid = this.validatePassword(passwordInput.value);
            this.toggleError(passwordInput, isValid, this.texts.validationPassword);
            return isValid;
        };

        if (emailInput) {
            emailInput.addEventListener('input', this._validateEmail);
            emailInput.addEventListener('blur', this._validateEmail);
        }

        if (usernameInput) {
            usernameInput.addEventListener('input', this._validateUsername);
            usernameInput.addEventListener('blur', this._validateUsername);
        }

        if (passwordInput) {
            passwordInput.addEventListener('input', this._validatePassword);
            passwordInput.addEventListener('blur', this._validatePassword);
        }

        if (emailInput && usernameInput) {
            this.initCombinedValidation(emailInput, usernameInput);
        }
    }

    initCombinedValidation(emailInput, usernameInput) {
        let timeout;

        const validateBoth = async () => {
            const emailValid = this._validateEmail();
            const usernameValid = this._validateUsername();

            if (!emailValid || !usernameValid) return;

            try {
                this.toggleLoadingState(true);
                const result = await this.checkUserExists(emailInput.value, usernameInput.value);
                if (!result.valid) {
                    this.showCombinedError(result.message, emailInput, usernameInput);
                } else {
                    this.clearCombinedError(emailInput, usernameInput);
                }
            } catch (error) {
                this.showCombinedError(this.texts.networkError, emailInput, usernameInput);
            } finally {
                this.toggleLoadingState(false);
            }
        };

        const debouncedValidation = () => {
            clearTimeout(timeout);
            timeout = setTimeout(validateBoth, 500);
        };

        emailInput.removeEventListener('input', debouncedValidation);
        usernameInput.removeEventListener('input', debouncedValidation);

        emailInput.addEventListener('input', debouncedValidation);
        usernameInput.addEventListener('input', debouncedValidation);
        emailInput.addEventListener('blur', validateBoth);
        usernameInput.addEventListener('blur', validateBoth);
    }

    async checkUserExists(email, username) {
        const response = await fetch('/stripe/check-user', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ email, username })
        });

        if (!response.ok) throw new Error(this.texts.networkError);
        return await response.json();
    }

    validateEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    validateUsername(username) {
        return /^[a-zA-Z0-9_-]{3,20}$/.test(username);
    }

    validatePassword(password) {
        return /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/.test(password);
    }

    updateSubmitButtonState() {
        if (this.submitButton) {
            this.submitButton.disabled = this.hasErrors;
        }
    }

    toggleLoadingState(isLoading) {
        if (this.submitButton && this.spinner) {
            this.submitButton.disabled = isLoading || this.hasErrors;
            this.spinner.classList.toggle('d-none', !isLoading);
        }
    }

    toggleError(input, isValid, message) {
        if (!input) return;

        input.classList.toggle('is-invalid', !isValid);
        let feedback = input.nextElementSibling;

        if (!feedback?.classList.contains('invalid-feedback')) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            input.parentNode.insertBefore(feedback, input.nextSibling);
        }

        feedback.textContent = message;
        feedback.style.display = isValid ? 'none' : 'block';

        this.hasErrors = this.form.querySelectorAll('.is-invalid').length > 0;
        this.updateSubmitButtonState();
    }

    showCombinedError(message, emailInput, usernameInput) {
        if (!emailInput || !usernameInput) return;

        [emailInput, usernameInput].forEach(input => {
            input.classList.add('is-invalid');
            let feedback = input.nextElementSibling;
            if (!feedback?.classList.contains('invalid-feedback')) {
                feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                input.parentNode.insertBefore(feedback, input.nextSibling);
            }
            feedback.textContent = message;
            feedback.style.display = 'block';
        });

        this.hasErrors = true;
        this.updateSubmitButtonState();
    }

    clearCombinedError(emailInput, usernameInput) {
        if (!emailInput || !usernameInput) return;

        [emailInput, usernameInput].forEach(input => {
            input.classList.remove('is-invalid');
            const feedback = input.nextElementSibling;
            if (feedback?.classList.contains('invalid-feedback')) {
                feedback.style.display = 'none';
            }
        });

        this.hasErrors = this.form.querySelectorAll('.is-invalid').length > 0;
        this.updateSubmitButtonState();
    }
}