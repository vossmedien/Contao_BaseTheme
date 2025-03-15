<?php

namespace Vsm\VsmHelperTools\Service\Stripe;

use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\PaymentIntent;
use Stripe\Webhook;
use Stripe\Event;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Psr\Log\LoggerInterface;

class StripePaymentService
{
    private string $stripeSecretKey;
    private string $webhookSecret;
    private LoggerInterface $logger;
    private ?StripeClient $stripe = null;
    
    public function __construct(
        string $stripeSecretKey, 
        LoggerInterface $logger,
        string $webhookSecret = ''
    ) {
        $this->stripeSecretKey = $stripeSecretKey;
        $this->webhookSecret = $webhookSecret;
        $this->logger = $logger;
        
        // Stripe API-Key setzen
        Stripe::setApiKey($this->stripeSecretKey);
        
        // Stripe-Client initialisieren
        try {
            $this->stripe = new StripeClient($this->stripeSecretKey);
            $this->logger->info('Stripe-Client erfolgreich initialisiert');
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Initialisieren des Stripe-Clients: ' . $e->getMessage());
        }
    }
    
    /**
     * Gibt den Webhook-Secret zurück
     */
    public function getWebhookSecret(): string
    {
        return $this->webhookSecret;
    }
    
    /**
     * Setzt den Webhook-Secret
     */
    public function setWebhookSecret(string $webhookSecret): void
    {
        $this->webhookSecret = $webhookSecret;
    }
    
    /**
     * Erstellt eine Checkout-Session für eine Zahlung
     */
    public function createCheckoutSession(array $customerData, array $productData): Session
    {
        try {
            // Erst den Kunden finden oder erstellen
            $customer = $this->findOrCreateCustomer($customerData);
            
            // URLs validieren und korrigieren
            $successUrl = $this->ensureAbsoluteUrl($productData['success_url'] ?? '');
            $cancelUrl = $this->ensureAbsoluteUrl($productData['cancel_url'] ?? '');
            
            if (empty($successUrl) || empty($cancelUrl)) {
                throw new \InvalidArgumentException('Erfolgs- und Abbruch-URLs sind erforderlich');
            }
            
            // Preis in Cent umrechnen
            $price = 0;
            if (isset($productData['price'])) {
                // Verschiedene Preisformate unterstützen
                if (is_string($productData['price'])) {
                    // Komma durch Punkt ersetzen und Währungssymbole entfernen
                    $priceStr = preg_replace('/[^\d,.]/', '', $productData['price']);
                    $priceStr = str_replace(',', '.', $priceStr);
                    $price = (float) $priceStr;
                } else {
                    $price = (float) $productData['price'];
                }
                
                // Logging für Debugging
                $this->logger->info('Originaler Preis vor Berechnung: ' . $price . ' EUR');
                
                // Steuersatz ermitteln
                $taxRate = isset($productData['tax_rate']) ? (float)$productData['tax_rate'] : 19.0;
                $taxIncluded = isset($productData['tax_included']) ? (bool)$productData['tax_included'] : true;
                
                // Prüfen, ob der Preis bereits in Cent ist (höher als 1000 deutet darauf hin)
                $isInCents = ($price > 1000);
                
                // Nettopreis berechnen, wenn der Preis die Steuer bereits enthält
                if ($taxIncluded && $taxRate > 0) {
                    // Wenn der Preis in Cent ist, erstmal zurück in Euro konvertieren für die Berechnung
                    if ($isInCents) {
                        $originalPrice = $price / 100;
                        $netAmount = $originalPrice / (1 + ($taxRate / 100));
                        $priceInCents = (int) round($netAmount * 100);
                        $this->logger->info('Nettobetrag berechnet (bereits in Cent): ' . 
                            $priceInCents . ' Cent (von Brutto: ' . $price . ' Cent, ' . $originalPrice . ' EUR)');
                    } else {
                        // Normaler Fall - Preis in Euro
                        $originalPrice = $price;
                        $price = $price / (1 + ($taxRate / 100));
                        $priceInCents = (int) round($price * 100);
                        $this->logger->info('Nettobetrag berechnet: ' . 
                            $price . ' EUR / ' . $priceInCents . ' Cent (von Brutto: ' . $originalPrice . ' EUR)');
                    }
                } else {
                    // Der Preis ist Netto oder keine Steuer
                    if ($isInCents) {
                        $priceInCents = (int) $price;
                        $this->logger->info('Preis ist bereits in Cent und Netto: ' . $priceInCents);
                    } else {
                        $priceInCents = (int) round($price * 100);
                        $this->logger->info('Nettopreis in Cent umgerechnet: ' . $priceInCents);
                    }
                }
                
                // Setze den Preis in Cent
                $price = $priceInCents;
                $this->logger->info('Finaler Preisbetrag in Cent für Stripe: ' . $price);
            }
            
            // Parameter für die Session vorbereiten
            $sessionParams = [
                'customer' => $customer->id,
                'payment_method_types' => ['card', 'sepa_debit', 'giropay', 'sofort', 'paypal', 'eps', 'ideal', 'bancontact'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => strtolower($productData['stripe_currency'] ?? 'eur'),
                        'product_data' => [
                            'name' => $productData['title'] ?? 'Produkt',
                        ],
                        'unit_amount' => $price, // Korrigierte Preisberechnung
                        'tax_behavior' => 'exclusive', // Wir verwenden explizite Steuersätze, daher 'exclusive'
                    ],
                    'quantity' => 1,
                    // Steuersatz hinzufügen
                    'tax_rates' => [$this->getOrCreateTaxRate(isset($productData['tax_rate']) ? (float)$productData['tax_rate'] : 19.0)],
                ]],
                'mode' => 'payment',
                'success_url' => $successUrl . (strpos($successUrl, '?') !== false ? '&' : '?') . 'session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $cancelUrl . (strpos($cancelUrl, '?') !== false ? '&' : '?') . 'session_id={CHECKOUT_SESSION_ID}',
                // Billing-Adressfeld aktivieren
                'billing_address_collection' => 'required',
                // Automatische Rechnungserstellung aktivieren, wenn gewünscht
                'invoice_creation' => [
                    'enabled' => isset($productData['create_invoice']) ? (bool)$productData['create_invoice'] : true,
                ],
                // Anzeige der steuerlichen Details im Checkout-Fenster aktivieren
                'tax_id_collection' => [
                    'enabled' => true,
                ],
                'customer_update' => [
                    'shipping' => 'auto',
                    'address' => 'auto',
                    'name' => 'auto',
                ],
            ];
            
            // Beschreibung nur hinzufügen, wenn sie nicht leer ist
            if (!empty($productData['description'])) {
                $sessionParams['line_items'][0]['price_data']['product_data']['description'] = $productData['description'];
            }
            
            // Metadaten hinzufügen
            $metadata = [
                'customer_email' => $customerData['email'] ?? '',
                'product_id' => $productData['id'] ?? '',
                'product_title' => $productData['title'] ?? '',
            ];
            
            // Zusätzliche Metadaten für Downloads, falls vorhanden
            if (!empty($productData['download_file'])) {
                $metadata['download_file'] = $productData['download_file'];
                $metadata['download_expires'] = $productData['download_expires'] ?? 7;
                $metadata['download_limit'] = $productData['download_limit'] ?? 3;
            }
            
            // Überschreibe Metadaten mit spezifischen Werten aus $productData['metadata']
            if (!empty($productData['metadata']) && is_array($productData['metadata'])) {
                $metadata = array_merge($metadata, $productData['metadata']);
            }
            
            $sessionParams['metadata'] = $metadata;
            
            // Checkout-Session erstellen
            $session = Session::create($sessionParams);
            
            $this->logger->info('Checkout-Session erstellt', [
                'session_id' => $session->id,
                'customer_id' => $customer->id,
                'product_title' => $productData['title'],
            ]);
            
            return $session;
        } catch (ApiErrorException $e) {
            $this->logger->error('Fehler beim Erstellen der Checkout-Session: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Ruft eine Checkout-Session ab
     */
    public function retrieveSession(string $sessionId): ?Session
    {
        try {
            return Session::retrieve($sessionId);
        } catch (ApiErrorException $e) {
            $this->logger->error('Fehler beim Abrufen der Session: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Extrahiert Zahlungsdaten aus einer Stripe-Session
     */
    public function extractPaymentData($session): array
    {
        if (!$session) {
            $this->logger->error('Keine gültige Session für die Extraktion der Zahlungsdaten');
            return [];
        }
        
        $paymentData = [
            'payment_id' => $session->payment_intent ?? $session->id,
            'customer_id' => $session->customer ?? null,
            'payment_method' => 'credit_card',
            'payment_method_details' => 'Kreditkarte (Stripe)',
            'status' => $session->status ?? 'unknown',
            'amount' => $session->amount_total ?? 0,
            'amount_total' => $session->amount_total ?? 0,
            'currency' => $session->currency ?? 'eur',
            'paid_at' => time(),
            'payment_method_id' => $session->payment_method ?? null,
        ];
        
        // Zusätzliche Felder für die E-Mail-Templates
        $formattedAmount = number_format(($session->amount_total ?? 0) / 100, 2, ',', '.');
        $paymentData['amount_formatted'] = $formattedAmount . ' ' . strtoupper($session->currency ?? 'EUR');
        
        // Rechnungsinformationen hinzufügen, falls vorhanden
        if (isset($session->invoice) && !empty($session->invoice) && $this->stripe !== null) {
            try {
                $invoice = $this->stripe->invoices->retrieve($session->invoice);
                
                $paymentData['invoice_id'] = $invoice->id;
                $paymentData['invoice_number'] = $invoice->number;
                $paymentData['invoice_url'] = $invoice->hosted_invoice_url;
                $paymentData['invoice_pdf'] = $invoice->invoice_pdf;
                $paymentData['invoice_date'] = $invoice->created;
                $paymentData['has_invoice'] = true;
                
                $this->logger->info('Rechnungsdaten extrahiert', [
                    'invoice_id' => $invoice->id,
                    'invoice_url' => $invoice->hosted_invoice_url
                ]);
            } catch (\Exception $e) {
                $this->logger->warning('Fehler beim Laden der Rechnungsdaten: ' . $e->getMessage());
                // Trotz Fehler fortfahren, da dies nicht kritisch ist
            }
        } else if (isset($session->invoice) && !empty($session->invoice)) {
            $this->logger->warning('Rechnung vorhanden, aber Stripe-Client nicht initialisiert');
        }
        
        $this->logger->info('Zahlungsdaten aus Stripe-Session extrahiert', [
            'payment_id' => $paymentData['payment_id'],
            'status' => $paymentData['status'],
            'amount' => $paymentData['amount'],
            'currency' => $paymentData['currency']
        ]);
        
        return $paymentData;
    }
    
    /**
     * Sucht oder erstellt einen Kunden in Stripe
     */
    private function findOrCreateCustomer(array $customerData): Customer
    {
        try {
            // Überprüfen, ob die E-Mail-Adresse angegeben wurde
            if (empty($customerData['email'])) {
                throw new \InvalidArgumentException('E-Mail-Adresse ist erforderlich');
            }
            
            // Nach einem bestehenden Kunden suchen
            $customers = Customer::all([
                'email' => $customerData['email'],
                'limit' => 1,
            ]);
            
            if (!empty($customers->data)) {
                $this->logger->info('Existierenden Kunden gefunden: ' . $customers->data[0]->id);
                return $customers->data[0];
            }
            
            // Neuen Kunden erstellen
            $customerParams = [
                'email' => $customerData['email'],
                'name' => trim(($customerData['firstname'] ?? '') . ' ' . ($customerData['lastname'] ?? '')),
                'metadata' => [
                    'source' => 'contao_checkout',
                ],
            ];
            
            // Adressdaten hinzufügen, falls vorhanden
            if (!empty($customerData['street']) || !empty($customerData['city'])) {
                $customerParams['address'] = [
                    'line1' => $customerData['street'] ?? '',
                    'postal_code' => $customerData['postal'] ?? '',
                    'city' => $customerData['city'] ?? '',
                    'country' => $customerData['country'] ?? 'DE',
                ];
            }
            
            // Telefonnummer hinzufügen, falls vorhanden
            if (!empty($customerData['phone'])) {
                $customerParams['phone'] = $customerData['phone'];
            }
            
            $customer = Customer::create($customerParams);
            
            $this->logger->info('Neuen Kunden erstellt: ' . $customer->id);
            
            return $customer;
        } catch (ApiErrorException $e) {
            $this->logger->error('Fehler beim Erstellen des Kunden: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Erstellt einen Payment-Intent für direkte Zahlungen mit JavaScript API
     */
    public function createPaymentIntent(array $paymentData): PaymentIntent
    {
        try {
            // Validierung der Eingabedaten
            if (empty($paymentData['amount']) || !is_numeric($paymentData['amount'])) {
                throw new \InvalidArgumentException('Ungültiger Betrag');
            }
            
            if (empty($paymentData['currency']) || !in_array(strtolower($paymentData['currency']), ['eur', 'usd', 'gbp'])) {
                throw new \InvalidArgumentException('Ungültige Währung');
            }
            
            // Metadaten vorbereiten
            $metadata = [
                'product_id' => (string)($paymentData['productId'] ?? ''),
                'product_title' => $paymentData['productTitle'] ?? '',
                'element_id' => (string)($paymentData['elementId'] ?? '')
            ];
            
            // Persönliche Daten als JSON speichern, falls vorhanden
            if (!empty($paymentData['personalData'])) {
                $metadata['personal_data'] = json_encode($paymentData['personalData']);
            }
            
            // Benutzer erstellen, falls gewünscht
            if (isset($paymentData['createUser'])) {
                $metadata['create_user'] = $paymentData['createUser'] ? 'true' : 'false';
            }
            
            // E-Mail-Template, falls angegeben
            if (!empty($paymentData['emailTemplate'])) {
                $metadata['email_template'] = $paymentData['emailTemplate'];
            }
            
            // Produktdaten hinzufügen, falls vorhanden
            if (!empty($paymentData['productData'])) {
                $metadata['product_data'] = json_encode($paymentData['productData']);
            }
            
            // Payment Intent erstellen
            $paymentIntent = PaymentIntent::create([
                'amount' => (int)$paymentData['amount'],
                'currency' => strtolower($paymentData['currency']),
                'automatic_payment_methods' => [
                    'enabled' => true
                ],
                'metadata' => $metadata
            ]);
            
            $this->logger->info('Payment Intent erstellt', [
                'id' => $paymentIntent->id,
                'amount' => $paymentIntent->amount,
                'currency' => $paymentIntent->currency
            ]);
            
            return $paymentIntent;
        } catch (ApiErrorException $e) {
            $this->logger->error('Fehler bei der Erstellung des Payment Intent: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Validiert die Signature eines Webhook-Events
     */
    public function validateWebhookSignature(string $payload, string $sigHeader): ?Event
    {
        if (empty($payload) || empty($sigHeader)) {
            $this->logger->error('Fehlender Payload oder Signatur');
            return null;
        }
        
        if (empty($this->webhookSecret)) {
            $this->logger->error('Webhook-Secret ist nicht konfiguriert');
            return null;
        }
        
        try {
            // Verifiziere die Stripe Webhook-Signatur
            $event = Webhook::constructEvent($payload, $sigHeader, $this->webhookSecret);
            
            $this->logger->info('Webhook event validiert: ' . $event->type);
            return $event;
        } catch (\UnexpectedValueException $e) {
            $this->logger->error('Ungültiger Payload: ' . $e->getMessage());
            return null;
        } catch (SignatureVerificationException $e) {
            $this->logger->error('Ungültige Signatur: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Verarbeitet ein Webhook-Event basierend auf dem Typ
     */
    public function processWebhookEvent(Event $event): array
    {
        $this->logger->info('Verarbeite Webhook-Event', [
            'event_type' => $event->type,
            'event_id' => $event->id
        ]);
        
        switch ($event->type) {
            case 'payment_intent.succeeded':
                return $this->handlePaymentIntentSucceeded($event->data->object);
                
            case 'payment_intent.canceled':
                return $this->handlePaymentIntentCanceled($event->data->object);
                
            case 'charge.succeeded':
                return $this->handleChargeSucceeded($event->data->object);
                
            default:
                $this->logger->info('Nicht verarbeiteter Event-Typ: ' . $event->type);
                return [
                    'success' => true,
                    'message' => 'Event empfangen, aber nicht verarbeitet',
                    'event_type' => $event->type
                ];
        }
    }
    
    /**
     * Verarbeitet ein payment_intent.succeeded Event
     */
    private function handlePaymentIntentSucceeded($paymentIntent): array
    {
        $this->logger->info('Payment Intent erfolgreich', [
            'payment_intent_id' => $paymentIntent->id,
            'amount' => $paymentIntent->amount / 100,
            'currency' => $paymentIntent->currency,
            'has_metadata' => !empty($paymentIntent->metadata) ? 'ja' : 'nein'
        ]);
        
        return [
            'success' => true,
            'message' => 'Payment Intent erfolgreich verarbeitet',
            'payment_intent_id' => $paymentIntent->id
        ];
    }
    
    /**
     * Verarbeitet ein payment_intent.canceled Event
     */
    private function handlePaymentIntentCanceled($paymentIntent): array
    {
        $this->logger->info('Payment Intent abgebrochen', [
            'payment_intent_id' => $paymentIntent->id,
            'cancellation_reason' => $paymentIntent->cancellation_reason ?? 'unbekannt',
            'status' => $paymentIntent->status
        ]);
        
        return [
            'success' => true,
            'message' => 'Payment Intent Abbruch verarbeitet',
            'payment_intent_id' => $paymentIntent->id
        ];
    }
    
    /**
     * Verarbeitet ein charge.succeeded Event
     */
    private function handleChargeSucceeded($charge): array
    {
        $this->logger->info('Charge erfolgreich', [
            'charge_id' => $charge->id,
            'amount' => $charge->amount / 100,
            'currency' => $charge->currency
        ]);
        
        return [
            'success' => true,
            'message' => 'Charge erfolgreich verarbeitet',
            'charge_id' => $charge->id
        ];
    }
    
    /**
     * Stellt sicher, dass eine URL absolut ist (mit Schema)
     */
    private function ensureAbsoluteUrl(string $url): string
    {
        // URL ist leer oder null
        if (empty($url)) {
            return 'https://' . $_SERVER['HTTP_HOST'] . '/';
        }
        
        // Prüfen, ob die URL bereits ein Schema hat
        if (preg_match('#^https?://#i', $url)) {
            return $url; // URL hat bereits ein Schema, nichts zu tun
        }
        
        // Wenn die URL mit // beginnt (Schema-relatives URL)
        if (strpos($url, '//') === 0) {
            return 'https:' . $url;
        }
        
        // Wenn die URL mit / beginnt (Server-relative URL)
        if (strpos($url, '/') === 0) {
            return 'https://' . $_SERVER['HTTP_HOST'] . $url;
        }
        
        // Ansonsten eine vollständige URL erstellen
        return 'https://' . $_SERVER['HTTP_HOST'] . '/' . $url;
    }
    
    /**
     * Findet oder erstellt einen Steuersatz in Stripe
     */
    private function getOrCreateTaxRate(float $taxRate): string
    {
        // Steuersatz auf gültige Werte beschränken
        if ($taxRate < 0 || $taxRate > 100) {
            $this->logger->warning('Ungültiger Steuersatz: ' . $taxRate . '%. Verwende Standardwert 19%.');
            $taxRate = 19.0;
        }
        
        $taxRateName = "MwSt. " . number_format($taxRate, 2, '.', '') . "%";
        $taxRateId = null;
        
        try {
            // Versuche, einen vorhandenen Steuersatz zu finden
            $taxRates = \Stripe\TaxRate::all(['limit' => 100]);
            
            foreach ($taxRates->data as $rate) {
                if (abs($rate->percentage - $taxRate) < 0.01 && $rate->active) {
                    // Wenn der Steuersatz bereits existiert und aktiv ist, verwenden wir diesen
                    $taxRateId = $rate->id;
                    $this->logger->info('Vorhandenen Steuersatz gefunden: ' . $taxRateId);
                    break;
                }
            }
            
            // Wenn kein passender Steuersatz gefunden wurde, erstelle einen neuen
            if (!$taxRateId) {
                $newTaxRate = \Stripe\TaxRate::create([
                    'display_name' => $taxRateName,
                    'description' => 'Mehrwertsteuer ' . number_format($taxRate, 2, '.', '') . '%',
                    'percentage' => $taxRate,
                    'inclusive' => false, // Auf false gesetzt, um mit tax_behavior='exclusive' konsistent zu sein
                    'country' => 'DE',
                    'jurisdiction' => 'DE',
                    'tax_type' => 'vat',
                    'active' => true
                ]);
                
                $taxRateId = $newTaxRate->id;
                $this->logger->info('Neuen Steuersatz erstellt: ' . $taxRateId);
            }
            
            return $taxRateId;
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Erstellen des Steuersatzes: ' . $e->getMessage());
            return ''; // Leerer String im Fehlerfall
        }
    }
} 