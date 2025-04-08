<?php

declare(strict_types=1);

/*
 * This file is part of VSM Helper und Integrations.
 *
 * (c) Vossmedien - Christian Voss 2025 <christian@vossmedien.de>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/vsm/vsm-helper-tools
 */

namespace Vsm\VsmStripeConnect\Service;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Customer;
use Stripe\Invoice;
use Stripe\InvoiceItem;
use Stripe\Stripe;

class StripePaymentService
{
    private string $stripeSecretKey;
    private string $webhookSecret;
    private LoggerInterface $logger;

    public function __construct(
        string $stripeSecretKey,
        string $webhookSecret,
        ?LoggerInterface $logger = null
    ) {
        $this->stripeSecretKey = $stripeSecretKey;
        $this->webhookSecret = $webhookSecret;
        $this->logger = $logger ?? new NullLogger();
        
        // API-Key und Version setzen
        Stripe::setApiKey($this->stripeSecretKey);
        Stripe::setApiVersion('2025-02-24.acacia'); // Stabile API-Version für die Stripe-Integration
        
        $this->logger->info('Stripe API initialisiert', [
            'api_version' => Stripe::getApiVersion()
        ]);
    }

    /**
     * Erstellt einen PaymentIntent für eine Zahlung
     */
    public function createPaymentIntent(array $data): PaymentIntent
    {
        try {
            if (empty($data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0) {
                throw new \Exception('Invalid amount: must be a positive number');
            }

            if (empty($data['currency']) || !in_array(strtolower($data['currency']), ['eur', 'usd', 'gbp'])) {
                throw new \Exception('Invalid currency: only EUR, USD, and GBP are supported');
            }

            if (empty($data['personalData']) || empty($data['personalData']['email'])) {
                throw new \Exception('Email is required in personal data');
            }

            $customer = $this->getOrCreateCustomer($data['personalData']);
            
            // Metadaten für PaymentIntent erstellen
            $metadata = [
                'productId' => $data['productId'] ?? '',
                'productTitle' => $data['productTitle'] ?? 'Produkt',
                'elementId' => $data['elementId'] ?? '',
                'personalData' => json_encode($data['personalData']),
                'createUser' => isset($data['createUser']) && $data['createUser'] ? 'true' : 'false',
                'memberGroup' => (string)($data['memberGroup'] ?? ''),
            ];

            // Produktdaten hinzufügen, falls vorhanden
            if (!empty($data['productData'])) {
                $metadata['productData'] = json_encode([
                    'eventName' => $data['productData']['eventName'] ?? null,
                    'duration' => $data['productData']['duration'] ?? 0
                ]);
            }

            $paymentIntent = PaymentIntent::create([
                'amount' => (int)$data['amount'],
                'currency' => strtolower($data['currency']),
                'customer' => $customer->id,
                'automatic_payment_methods' => [
                    'enabled' => true
                ],
                'metadata' => $metadata
            ]);

            $this->logger->info('Payment intent created', [
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $paymentIntent->amount / 100 . ' ' . strtoupper($paymentIntent->currency),
                'customer_email' => $data['personalData']['email'] ?? 'unknown'
            ]);

            return $paymentIntent;
            
        } catch (ApiErrorException $e) {
            $this->logger->error('Stripe API error', [
                'error' => $e->getMessage(),
                'code' => $e->getHttpStatus()
            ]);
            throw $e;
        }
    }

    /**
     * Erstellt oder aktualisiert einen Kunden in Stripe
     */
    public function getOrCreateCustomer(array $personalData): Customer
    {
        try {
            if (empty($personalData['email'])) {
                throw new \Exception('Email is required to create or update a customer');
            }

            $this->logger->info('Looking for existing customer', [
                'email' => $personalData['email']
            ]);

            // Suche nach bestehendem Kunden per E-Mail
            $customers = Customer::all([
                'email' => $personalData['email'],
                'limit' => 1
            ]);

            if (!empty($customers->data)) {
                $customer = $customers->data[0];
                $this->logger->info('Updating existing customer', [
                    'customer_id' => $customer->id,
                    'email' => $personalData['email']
                ]);

                // Update der Kundendaten für die Rechnung
                $updateData = [
                    'address' => [
                        'line1' => $personalData['street'] ?? null,
                        'postal_code' => $personalData['postal'] ?? null,
                        'city' => $personalData['city'] ?? null,
                        'country' => $personalData['country'] ?? 'DE',
                    ],
                    'name' => trim(($personalData['firstname'] ?? '') . ' ' . ($personalData['lastname'] ?? '')),
                    'metadata' => [
                        'company' => $personalData['company'] ?? null,
                        'vat_id' => $personalData['vat_id'] ?? null
                    ]
                ];
                
                // Telefonnummer nur hinzufügen, wenn vorhanden
                if (!empty($personalData['phone'])) {
                    $updateData['phone'] = $personalData['phone'];
                }
                
                $customer = $customer->update($updateData);
                
                return $customer;
            }

            // Alle erforderlichen Felder für neuen Kunden prüfen
            $this->logger->info('Creating new customer', [
                'email' => $personalData['email']
            ]);

            // Grundlegende Daten für neuen Kunden
            $customerData = [
                'email' => $personalData['email'],
                'name' => trim(($personalData['firstname'] ?? '') . ' ' . ($personalData['lastname'] ?? '')),
                'address' => [
                    'line1' => $personalData['street'] ?? null,
                    'postal_code' => $personalData['postal'] ?? null,
                    'city' => $personalData['city'] ?? null,
                    'country' => $personalData['country'] ?? 'DE',
                ],
                'metadata' => [
                    'company' => $personalData['company'] ?? null,
                    'vat_id' => $personalData['vat_id'] ?? null
                ],
                'invoice_settings' => [
                    'default_payment_method' => null,
                    'custom_fields' => null,
                    'footer' => null,
                ],
            ];
            
            // Telefonnummer nur hinzufügen, wenn vorhanden
            if (!empty($personalData['phone'])) {
                $customerData['phone'] = $personalData['phone'];
            }

            $customer = Customer::create($customerData);
            
            $this->logger->info('New customer created', [
                'customer_id' => $customer->id,
                'email' => $customer->email
            ]);
            
            return $customer;
            
        } catch (ApiErrorException $e) {
            $this->logger->error('Stripe API error in customer creation/update', [
                'error' => $e->getMessage(),
                'code' => $e->getHttpStatus(),
                'email' => $personalData['email'] ?? 'unknown'
            ]);
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('Error creating/updating customer', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Erstellt eine Rechnung für eine Zahlung
     */
    public function createInvoiceForPayment(PaymentIntent $payment): Invoice
    {
        try {
            if (empty($payment->customer)) {
                throw new \Exception('Payment hat keinen zugeordneten Kunden');
            }

            // Extrahiere Metadaten
            $personalData = $this->extractPersonalData($payment);
            $productData = $this->extractProductData($payment);

            // Bereite Rechnungsdaten vor
            $invoiceItemData = [
                'customer' => $payment->customer,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'description' => $payment->metadata->productTitle ?? 'Produkt'
            ];

            // Rechnungsposition erstellen
            $invoiceItem = InvoiceItem::create($invoiceItemData);

            // Rechnungsmeta-Daten
            $invoiceMeta = [
                'payment_intent' => $payment->id,
                'auto_advance' => true,
                'collection_method' => 'charge_automatically',
                'customer' => $payment->customer,
                'metadata' => [
                    'payment_intent_id' => $payment->id,
                    'product_title' => $payment->metadata->productTitle ?? 'Produkt'
                ]
            ];

            // Füge Adressdaten hinzu, wenn vorhanden
            if (!empty($personalData['firstname']) && !empty($personalData['lastname'])) {
                $invoiceMeta['customer_name'] = $personalData['firstname'] . ' ' . $personalData['lastname'];
            }

            if (!empty($personalData['email'])) {
                $invoiceMeta['customer_email'] = $personalData['email'];
            }

            // Adressen
            if (!empty($personalData['street']) || !empty($personalData['city'])) {
                $address = [
                    'line1' => $personalData['street'] ?? '',
                    'postal_code' => $personalData['postal'] ?? '',
                    'city' => $personalData['city'] ?? '',
                    'country' => $personalData['country'] ?? 'DE'
                ];
                
                $invoiceMeta['customer_address'] = $address;
            }

            // Rechnung erstellen
            $invoice = Invoice::create($invoiceMeta);

            // Rechnung sofort finalisieren
            $invoice = Invoice::finalizeInvoice($invoice->id);

            $this->logger->info('Rechnung erstellt', [
                'invoice_id' => $invoice->id,
                'payment_id' => $payment->id,
                'status' => $invoice->status
            ]);

            return $invoice;
        } catch (ApiErrorException $e) {
            $this->logger->error('Stripe API Fehler beim Erstellen der Rechnung', [
                'error' => $e->getMessage(),
                'payment_id' => $payment->id
            ]);
            throw $e;
        }
    }

    /**
     * Storniert eine Rechnung
     */
    public function voidInvoice(string $invoiceId): Invoice
    {
        try {
            // Rechnung direkt abrufen
            $invoice = Invoice::retrieve($invoiceId);
            
            // Prüfen, ob die Rechnung bereits storniert ist
            if ($invoice->status === 'void') {
                $this->logger->info('Rechnung bereits storniert', [
                    'invoice_id' => $invoiceId,
                    'status' => $invoice->status
                ]);
                return $invoice;
            }
            
            try {
                // Direkte Verwendung der Stripe API ohne zusätzliche Parameter
                $updateParams = ['status' => 'void'];
                $invoice = Invoice::update($invoiceId, $updateParams);
                
                $this->logger->info('Rechnung erfolgreich storniert', [
                    'invoice_id' => $invoiceId,
                    'status' => $invoice->status,
                    'used_params' => json_encode($updateParams)
                ]);
            } catch (\Exception $e) {
                $this->logger->error('Fehler bei Invoice::update, versuche alternativen Ansatz', [
                    'error' => $e->getMessage()
                ]);
                
                // Alternativer Ansatz: nur die absolut notwendigen Felder verwenden
                $invoice = $invoice->update(['status' => 'void']);
            }
            
            return $invoice;
        } catch (ApiErrorException $e) {
            $this->logger->error('Stripe API Fehler beim Stornieren der Rechnung', [
                'error' => $e->getMessage(),
                'code' => $e->getHttpStatus() ?? 'unbekannt',
                'error_type' => $e->getStripeCode() ?? 'unbekannt',
                'request_id' => $e->getRequestId() ?? 'unbekannt',
                'invoice_id' => $invoiceId
            ]);
            throw $e;
        }
    }
    
    /**
     * Gibt alle Rechnungen zu einem Payment Intent zurück
     */
    public function getInvoicesForPaymentIntent(string $paymentIntentId): array
    {
        try {
            $invoices = Invoice::all([
                'payment_intent' => $paymentIntentId
            ]);

            $this->logger->info('Rechnungen für PaymentIntent abgerufen', [
                'payment_intent_id' => $paymentIntentId,
                'count' => count($invoices->data)
            ]);

            return $invoices->data;
        } catch (ApiErrorException $e) {
            $this->logger->error('Stripe API Fehler beim Abrufen der Rechnungen', [
                'error' => $e->getMessage(),
                'payment_intent_id' => $paymentIntentId
            ]);
            return [];
        }
    }
    
    /**
     * Extrahiert Personendaten aus den Metadaten eines PaymentIntents
     */
    private function extractPersonalData(PaymentIntent $payment): array
    {
        if (empty($payment->metadata->personalData)) {
            return [];
        }

        $data = json_decode($payment->metadata->personalData, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->warning('Fehler beim Dekodieren der PersonalData', [
                'error' => json_last_error_msg(),
                'data' => $payment->metadata->personalData
            ]);
            return [];
        }

        return $data;
    }

    /**
     * Extrahiert Produktdaten aus den Metadaten eines PaymentIntents
     */
    private function extractProductData(PaymentIntent $payment): array
    {
        if (empty($payment->metadata->productData)) {
            return [];
        }

        $data = json_decode($payment->metadata->productData, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->warning('Fehler beim Dekodieren der ProductData', [
                'error' => json_last_error_msg(),
                'data' => $payment->metadata->productData
            ]);
            return [];
        }

        return $data;
    }

    /**
     * Erhält den Webhook-Secret für die Signaturvalidierung
     */
    public function getWebhookSecret(): string
    {
        return $this->webhookSecret;
    }

    /**
     * Ruft einen bestehenden PaymentIntent ab
     *
     * @param string $paymentIntentId Die ID des PaymentIntents
     * @return PaymentIntent Der abgerufene PaymentIntent
     * @throws \Exception
     */
    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent
    {
        try {
            return \Stripe\PaymentIntent::retrieve($paymentIntentId);
        } catch (ApiErrorException $e) {
            $this->logger->error('Fehler beim Abrufen des PaymentIntent: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Ruft ein Stripe-Produkt anhand seiner ID ab
     */
    public function getProduct(string $productId): ?\Stripe\Product
    {
        try {
            return \Stripe\Product::retrieve($productId);
        } catch (ApiErrorException $e) {
            $this->logger->error('Fehler beim Abrufen des Stripe-Produkts: ' . $e->getMessage(), [
                'product_id' => $productId
            ]);
            return null;
        }
    }
} 