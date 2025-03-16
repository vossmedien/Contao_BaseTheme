/**
 * UIHandler-Klasse für UI-bezogene Funktionen
 * 
 * Verwaltet alle UI-Elemente und Interaktionen wie Loading-Indikatoren,
 * Fehlermeldungen und visuelle Feedback-Elemente.
 */
class UIHandler {
    /**
     * Konstruktor für den UIHandler
     * @param {Object} config - Konfigurationsobjekt
     */
    constructor(config = {}) {
        this.texts = config.texts || {};
        this.debug = config.debug || false;
        
        if (this.debug) {
            console.log('UIHandler initialisiert mit Texten:', this.texts);
        }
    }
    
    /**
     * Zeigt einen Ladeindikator an
     */
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

    /**
     * Versteckt den Ladeindikator
     */
    hideLoadingIndicator() {
        const loader = document.getElementById('stripe-checkout-loader');
        if (loader) {
            loader.style.display = 'none';
        }
    }
    
    /**
     * Schaltet den Ladezustand um
     * @param {boolean} isLoading - Ob der Ladezustand aktiv sein soll
     */
    toggleLoadingState(isLoading) {
        if (isLoading) {
            this.showLoadingIndicator();
        } else {
            this.hideLoadingIndicator();
        }
    }
    
    /**
     * Zeigt eine Fehlermeldung in einem Formular an
     * @param {string} errorMessage - Die anzuzeigende Fehlermeldung
     * @param {HTMLElement} form - Das Formular, in dem der Fehler angezeigt werden soll
     */
    showFormError(errorMessage, form) {
        // Bestehende Fehlermeldungen entfernen
        const existingErrors = form.querySelectorAll('.stripe-form-error');
        existingErrors.forEach(error => error.remove());
        
        // Neue Fehlermeldung erstellen
        const errorElement = document.createElement('div');
        errorElement.className = 'stripe-form-error';
        errorElement.style.color = 'red';
        errorElement.style.marginBottom = '15px';
        errorElement.style.padding = '10px';
        errorElement.style.border = '1px solid red';
        errorElement.style.borderRadius = '4px';
        errorElement.style.backgroundColor = 'rgba(255, 0, 0, 0.05)';
        errorElement.textContent = errorMessage;
        
        // Fehlermeldung am Anfang des Formulars einfügen
        form.insertBefore(errorElement, form.firstChild);
        
        // Zum Anfang des Formulars scrollen
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    
    /**
     * Setzt den Ladezustand eines Formulars
     * @param {HTMLElement} form - Das Formular
     * @param {boolean} isLoading - Ob das Formular im Ladezustand sein soll
     */
    setFormLoading(form, isLoading) {
        // Alle Eingabefelder und Buttons im Formular finden
        const inputs = form.querySelectorAll('input, select, textarea, button');
        
        // Ladezustand für alle Elemente setzen
        inputs.forEach(input => {
            input.disabled = isLoading;
        });
        
        // Submit-Button finden und Text ändern
        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            if (isLoading) {
                submitButton.dataset.originalText = submitButton.textContent;
                submitButton.textContent = this.texts.loadingText || 'Wird geladen...';
            } else if (submitButton.dataset.originalText) {
                submitButton.textContent = submitButton.dataset.originalText;
            }
        }
    }
}

// Export für ES6 Module
export default UIHandler; 