/**
 * HINWEIS: Diese Datei wird in der aktuellen Version nicht mehr verwendet!
 * Die Zahlungsabwicklung erfolgt jetzt über Stripe Checkout.
 * Diese Datei bleibt nur für Abwärtskompatibilität im System.
 * 
 * Siehe stattdessen das Code-Block in rsce_product_payment.html5
 */

// Leere Klasse zur Vermeidung von JS-Fehlern bei veralteten Implementierungen
class StripePaymentHandler {
    constructor() {
        console.warn('StripePaymentHandler ist veraltet und wird nicht mehr verwendet. Stripe Checkout wird stattdessen direkt im Template eingebunden.');
    }
}

// Globale Instanz für alte Implementierungen
window.stripeProductPayment = new StripePaymentHandler();

// Alte Methode für Abwärtskompatibilität
window.openPaymentModal = function(productData) {
    console.warn('openPaymentModal() ist veraltet. Bitte verwenden Sie das neue Stripe Checkout System.');
};