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
namespace Vsm\VsmHelperTools\Controller\Stripe;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller für die Hauptfunktionalität des Stripe-Checkouts
 */
class CheckoutController extends BaseStripeController
{
    use UtilityTrait;
    
    /**
     * Erstellt eine Stripe-Checkout-Session
     */
    #[Route('/create-checkout-session', name: 'stripe_create_checkout_session', methods: ['POST'])]
    public function createCheckoutSession(Request $request): Response
    {
        try {
            // CORS-Header für lokale Entwicklung
            $origin = $request->headers->get('Origin');
            
            // Response vorbereiten
            $responseData = [];
            $statusCode = 200;
            
            if ($origin && isset($this->isDebug) && $this->isDebug) {
                $headers = [
                    'Access-Control-Allow-Origin' => $origin,
                    'Access-Control-Allow-Credentials' => 'true',
                    'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
                    'Access-Control-Allow-Headers' => 'Origin, Content-Type, X-Auth-Token, X-Requested-With'
                ];
            } else {
                $headers = [];
            }
            
            // Stripe-Client initialisieren
            $this->initStripeClient();
            
            // Daten aus dem Request extrahieren
            $data = json_decode($request->getContent(), true);
            
            if (empty($data)) {
                // Fallback für form-data
                $customerData = [];
                $productData = [];
                
                // Erforderliche Felder
                $requiredFields = ['email', 'product-id', 'element-id'];
                foreach ($requiredFields as $field) {
                    if (!$request->request->has($field)) {
                        return new JsonResponse(['error' => 'Erforderliches Feld fehlt: ' . $field], 400, $headers);
                    }
                }
                
                // Produkt- und Kundendaten sammeln
                foreach ($request->request->all() as $key => $value) {
                    if (in_array($key, ['product-id', 'element-id', 'stripe_currency', 'success_url', 'cancel_url'])) {
                        $productData[$key] = $value;
                    } else {
                        $customerData[$key] = $value;
                    }
                }
                
                // Währung und Produkt-ID standardmäßig auf EUR und 1 setzen
                $productData['stripe_currency'] = $productData['stripe_currency'] ?? 'eur';
                
                // Element-ID in product_data umwandeln
                $productData['element_id'] = $productData['element-id'] ?? 0;
                
                // Alle anderen Formulardaten in customerData ablegen
                $this->logger->info('Formular-Daten: ' . json_encode($customerData));
                
                // Prüfe auf HTML data-attribute im Request und übernehme sie in productData
                foreach ($request->request->all() as $key => $value) {
                    // Behandle data-attribute im Format data-xyz
                    if (strpos($key, 'data-') === 0) {
                        $normalizedKey = $this->normalizeParameterName($key);
                        $productData[$normalizedKey] = $value;
                        
                        // Original-Key immer auch beibehalten für Kompatibilität
                        $productData[$key] = $value;
                        
                        $this->logger->info('Data-Attribut gefunden und normalisiert: ' . $key . ' → ' . $normalizedKey . ' = ' . $value);
                    }
                }
                
                // Standardmäßig Rechnungserstellung aktivieren, wenn nicht explizit deaktiviert
                if (!isset($productData['create_invoice'])) {
                    $productData['create_invoice'] = 1;
                    $this->logger->info('Standardmäßig Rechnungserstellung aktiviert');
                }
                
                // Besondere Behandlung für data-create-invoice
                if (isset($request->request->all()['data-create-invoice'])) {
                    $createInvoiceValue = $request->request->all()['data-create-invoice'];
                    
                    // Detailliertes Logging für Debugging
                    $this->logger->info('Rechnungserstellung-Parameter gefunden', [
                        'raw_value' => $createInvoiceValue,
                        'type' => gettype($createInvoiceValue),
                        'as_string' => (string)$createInvoiceValue
                    ]);
                    
                    // Überprüfen, ob der Wert als true ausgewertet werden sollte
                    // Explizite Überprüfung aller möglichen Formate
                    $isTrue = false;
                    if ($createInvoiceValue === true || $createInvoiceValue === 1 || $createInvoiceValue === '1' || 
                        strtolower((string)$createInvoiceValue) === 'true' || 
                        strtolower((string)$createInvoiceValue) === 'yes' || 
                        strtolower((string)$createInvoiceValue) === 'ja') {
                        $isTrue = true;
                    }
                    
                    // Als String 'true' oder 'false' speichern
                    $productData['create_invoice'] = $isTrue ? 'true' : 'false';
                    
                    $this->logger->info('Rechnungserstellung Parameter gesetzt auf: ' . $productData['create_invoice']);
                }
            } else {
                // JSON-Daten verarbeiten
                $customerData = $data['customer_data'] ?? $data['personalData'] ?? $data['customer'] ?? [];
                $productData = $data['product_data'] ?? $data['productData'] ?? $data['product'] ?? [];
                
                // Besonderer Fall: customer und product wurden mit Javascript separat definiert
                if (empty($customerData) && !empty($data['customer']) && is_array($data['customer'])) {
                    $customerData = $data['customer'];
                }
                
                if (empty($productData) && !empty($data['product']) && is_array($data['product'])) {
                    $productData = $data['product'];
                }
                
                // success_url und cancel_url aus dem Request auslesen
                $productData['success_url'] = $data['success_url'] ?? $data['successUrl'] ?? $productData['success_url'] ?? '';
                $productData['cancel_url'] = $data['cancel_url'] ?? $data['cancelUrl'] ?? $productData['cancel_url'] ?? '';
                
                // Erforderliche Felder
                if (empty($customerData['email'])) {
                    return new JsonResponse(['error' => 'Customer email is required'], 400, $headers);
                }
                
                if (empty($productData['title'])) {
                    $productData['title'] = 'Produkt';
                }
                
                // Standardwerte für Produkt-Daten
                $productData['stripe_currency'] = $productData['stripe_currency'] ?? 'eur';
                
                // Normalisierung der Parameter für JSON-Requests
                foreach ($productData as $key => $value) {
                    // Normalisiere auch die JSON-Keys für eine einheitliche Behandlung
                    $normalizedKey = $this->normalizeParameterName($key);
                    if ($normalizedKey !== $key) {
                        $productData[$normalizedKey] = $value;
                        $this->logger->debug("Parameter normalisiert: $key → $normalizedKey");
                    }
                }
                
                // Standardmäßig Rechnungserstellung aktivieren, wenn nicht explizit deaktiviert
                if (!isset($productData['create_invoice'])) {
                    $productData['create_invoice'] = 1;
                    $this->logger->info('Standardmäßig Rechnungserstellung für JSON-Request aktiviert');
                }
                
                // Stelle sicher, dass create_invoice als String-Wert gespeichert wird
                if (isset($productData['create_invoice'])) {
                    $createInvoiceValue = $productData['create_invoice'];
                    
                    // Detailliertes Logging für Debugging
                    $this->logger->info('Rechnungserstellung-Parameter in JSON gefunden', [
                        'raw_value' => $createInvoiceValue,
                        'type' => gettype($createInvoiceValue),
                        'as_string' => (string)$createInvoiceValue
                    ]);
                    
                    // Überprüfen, ob der Wert als true ausgewertet werden sollte
                    // Explizite Überprüfung aller möglichen Formate
                    $isTrue = false;
                    if ($createInvoiceValue === true || $createInvoiceValue === 1 || $createInvoiceValue === '1' || 
                        strtolower((string)$createInvoiceValue) === 'true' || 
                        strtolower((string)$createInvoiceValue) === 'yes' || 
                        strtolower((string)$createInvoiceValue) === 'ja') {
                        $isTrue = true;
                    }
                    
                    // Als String 'true' oder 'false' speichern
                    $productData['create_invoice'] = $isTrue ? 'true' : 'false';
                    
                    $this->logger->info('Rechnungserstellung Parameter für JSON gesetzt auf: ' . $productData['create_invoice']);
                }
            }
            
            // Wichtig: Passwort für createUser separieren, nicht in der Datenbank speichern
            $createUser = $data['create_user'] ?? $data['createUser'] ?? $productData['create_user'] ?? false;
            
            // Sicherstellen, dass createUser korrekt als Boolean ausgewertet wird
            if (!is_bool($createUser)) {
                $createUser = ($createUser === true || $createUser === 1 || $createUser === '1' || 
                    strtolower((string)$createUser) === 'true' || 
                    strtolower((string)$createUser) === 'yes' || 
                    strtolower((string)$createUser) === 'ja');
                    
                $this->logger->info('Create-User Parameter ausgewertet als: ' . ($createUser ? 'true' : 'false'));
            }
            
            $userPassword = null;
            
            // Wenn Benutzer erstellt werden soll, Passwort sichern und dann aus customerData entfernen
            if ($createUser && isset($customerData['password'])) {
                $userPassword = $customerData['password'];
                // Passwort aus den customerData entfernen, damit es nicht in der DB gespeichert wird
                unset($customerData['password']);
            }
            
            $this->logger->info('Daten für Checkout-Session: ' . json_encode([
                'customer_keys' => array_keys($customerData),
                'product_keys' => array_keys($productData),
                'create_user' => $createUser
            ]));
            
            // Stripe-Session erstellen
            $session = $this->stripeService->createCheckoutSession($customerData, $productData);
            
            // Session-Daten speichern
            $sessionData = [
                'session_id' => $session->id,
                'stripe_session_id' => $session->id,
                'customer_data' => $customerData,
                'product_data' => $productData
            ];
            
            // Stellen Sie sicher, dass create_user explizit in product_data gesetzt wird
            if ($createUser) {
                $sessionData['product_data']['create_user'] = true;
            }
            
            // Wenn ein Produktmarkup übergeben wurde, dieses speichern
            if (!empty($request->request->get('productMarkup'))) {
                $sessionData['product_markup'] = $request->request->get('productMarkup');
            }
            
            // Wenn ein Button-Markup übergeben wurde, dieses speichern
            if (!empty($request->request->get('buttonMarkup'))) {
                $sessionData['product_button_markup'] = $request->request->get('buttonMarkup');
            }
            
            // Speichern von Daten für Mitgliedergruppen
            if (!empty($productData['member_group'])) {
                $sessionData['member_group'] = $productData['member_group'];
            }
            
            // Wenn User erstellt werden soll, Username und Passwort für die spätere Verarbeitung speichern
            if ($createUser && $userPassword) {
                // Passwort hinzufügen, aber nur für die Verarbeitung im UserCreationService
                $sessionData['user_creation'] = [
                    'username' => $customerData['username'] ?? null,
                    'password' => $userPassword
                ];
            }
            
            $this->sessionManager->createSession($sessionData);
            
            // Umfangreiches Logging der Session-Daten für Problemdiagnose
            $this->logger->info('Checkout-Session finalisiert und an Client gesendet', [
                'session_id' => $session->id,
                'customer_email' => $customerData['email'] ?? 'nicht gesetzt',
                'product_title' => $productData['title'] ?? 'nicht gesetzt',
                'create_invoice' => isset($productData['create_invoice']) ? ($productData['create_invoice'] ? 'ja' : 'nein') : 'nicht gesetzt',
                'data-create-invoice' => isset($productData['data-create-invoice']) ? ($productData['data-create-invoice'] ? 'ja' : 'nein') : 'nicht gesetzt',
                'product_data_keys' => array_keys($productData)
            ]);
            
            // Redirect URL zurückgeben - formatiert für den JavaScript-Handler
            $responseData = [
                'url' => $session->url,
                'id' => $session->id
            ];
            
            return new JsonResponse($responseData, $statusCode, $headers);
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Erstellen der Checkout-Session: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Fehler beim Erstellen der Checkout-Session: ' . $e->getMessage()], 500);
        }
    }
} 