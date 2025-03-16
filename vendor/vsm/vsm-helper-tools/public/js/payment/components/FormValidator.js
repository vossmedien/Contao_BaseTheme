/**
 * FormValidator-Klasse für Formularvalidierung
 * 
 * Stellt Methoden zur Validierung von Formularen und Eingabefeldern bereit.
 */
class FormValidator {
    /**
     * Konstruktor für den FormValidator
     * @param {Object} config - Konfigurationsobjekt
     */
    constructor(config = {}) {
        this.texts = config.texts || {};
        this.debug = config.debug || false;
        this.uiHandler = config.uiHandler || null;
        
        if (this.debug) {
            console.log('FormValidator initialisiert mit Texten:', this.texts);
        }
    }
    
    /**
     * Validiert ein Formular
     * @param {HTMLFormElement} form - Das zu validierende Formular
     * @returns {boolean} - Ob das Formular gültig ist
     */
    validateForm(form) {
        if (this.debug) {
            console.log('Validiere Formular');
        }
        
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
                
                if (this.debug) {
                    console.warn('Pflichtfeld nicht ausgefüllt:', field.name || field.id);
                }
            } else {
                field.classList.remove('is-invalid');
                
                // Spezielle Validierung für E-Mail-Felder
                if (field.type === 'email' && !this.validateEmail(field.value)) {
                    field.classList.add('is-invalid');
                    isValid = false;
                    
                    if (this.debug) {
                        console.warn('Ungültige E-Mail-Adresse:', field.value);
                    }
                }
                
                // Spezielle Validierung für Passwortfelder
                if (field.type === 'password' && field.dataset.validatePassword !== 'false') {
                    if (!this.validatePassword(field.value)) {
                        field.classList.add('is-invalid');
                        isValid = false;
                        
                        if (this.debug) {
                            console.warn('Passwort entspricht nicht den Anforderungen');
                        }
                    }
                }
            }
        });

        if (!isValid) {
            this.showFormError(this.texts.errorRequired || 'Bitte füllen Sie alle Pflichtfelder aus.', form);
        }

        return isValid;
    }
    
    /**
     * Validiert eine E-Mail-Adresse
     * @param {string} email - Die zu validierende E-Mail-Adresse
     * @returns {boolean} - Ob die E-Mail-Adresse gültig ist
     */
    validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    /**
     * Validiert ein Passwort
     * @param {string} password - Das zu validierende Passwort
     * @returns {boolean} - Ob das Passwort gültig ist
     */
    validatePassword(password) {
        // Mindestens 8 Zeichen, ein Großbuchstabe, ein Kleinbuchstabe und eine Zahl
        const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;
        return passwordRegex.test(password);
    }
    
    /**
     * Zeigt einen Fehler im Formular an
     * @param {string} errorMessage - Die Fehlermeldung, die angezeigt werden soll
     * @param {HTMLFormElement} form - Das Formular, in dem der Fehler angezeigt werden soll
     */
    showFormError(errorMessage, form) {
        if (this.debug) {
            console.log('Formularfehler:', errorMessage);
        }
        
        // UI-Handler verwenden, wenn verfügbar
        if (this.uiHandler && typeof this.uiHandler.showFormError === 'function') {
            this.uiHandler.showFormError(errorMessage, form);
            return;
        }

        // Fallback: Eigene Implementierung
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
    }
}

// Export für ES6 Module
export default FormValidator; 