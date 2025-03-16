/**
 * Stripe Initialisierung
 * 
 * Beispiel für die Initialisierung der Stripe-Komponenten.
 */

import { StripePaymentForm, StripeElementsForm } from './components/index.js';

document.addEventListener('DOMContentLoaded', function() {
    // Stripe-Schlüssel aus dem DOM oder Konfiguration laden
    const stripeKey = document.querySelector('meta[name="stripe-key"]')?.getAttribute('content') || 'pk_test_your_key';
    
    // Debug-Modus aktivieren, wenn URL-Parameter debug=1 vorhanden ist
    const urlParams = new URLSearchParams(window.location.search);
    const debug = urlParams.get('debug') === '1';
    
    // Benutzerdefinierte Texte
    const customTexts = {
        submitButton: 'Bezahlen',
        loadingButton: 'Verarbeitung...',
        error: 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.',
        errorRequired: 'Bitte füllen Sie alle erforderlichen Felder aus.',
        successMessage: 'Vielen Dank für Ihren Einkauf!'
    };
    
    // Erfolgs- und Abbruch-URLs
    const successUrl = '/payment/success';
    const cancelUrl = '/payment/cancel';
    
    // Prüfen, welche Art von Formular initialisiert werden soll
    if (document.querySelector('#payment-form')) {
        // Standard-Zahlungsformular initialisieren
        const paymentForm = new StripePaymentForm({
            stripeKey: stripeKey,
            debug: debug,
            texts: customTexts,
            successUrl: successUrl,
            cancelUrl: cancelUrl,
            formSelector: '#payment-form'
        });
        
        console.log('Stripe Payment Form initialisiert');
    }
    
    // Prüfen, ob ein Elements-Formular vorhanden ist
    if (document.querySelector('#elements-form')) {
        // Elements-Formular initialisieren
        const elementsForm = new StripeElementsForm({
            stripeKey: stripeKey,
            debug: debug,
            texts: customTexts,
            successUrl: successUrl,
            cancelUrl: cancelUrl,
            formSelector: '#elements-form',
            cardElementSelector: '#card-element',
            cardErrorSelector: '#card-errors'
        });
        
        console.log('Stripe Elements Form initialisiert');
    }
}); 