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
 * Controller zur Verwaltung von Stripe Payment Intents für direktes Erfassen von Zahlungen im Formular
 */
class PaymentIntentController extends BaseStripeController
{
    use UtilityTrait;
    
    /**
     * Erstellt einen neuen Payment Intent
     */
    #[Route('/stripe/create-payment-intent', name: 'stripe_create_payment_intent', methods: ['POST'])]
    public function createPaymentIntent(Request $request): Response
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
            
            // Datenbankdiagnose ausführen
            try {
                $dbStatus = $this->checkDatabaseConnection();
                $this->logger->info('Datenbankstatus beim Payment Intent erstellen:', $dbStatus);
            } catch (\Exception $e) {
                $this->logger->error('Fehler bei Datenbankprüfung: ' . $e->getMessage());
            }
            
            // Stripe-Client initialisieren
            $this->initStripeClient();
            
            // Daten aus dem Request extrahieren
            $data = json_decode($request->getContent(), true);
            
            if (empty($data)) {
                return $this->json(['error' => 'Keine Daten empfangen'], 400, $headers);
            }
            
            // Pflichtfelder prüfen
            if (!isset($data['amount']) || !isset($data['currency'])) {
                return $this->json(['error' => 'Betrag und Währung sind erforderlich'], 400, $headers);
            }
            
            // Betrag formatieren (sicherstellen, dass es sich um eine ganze Zahl handelt)
            $amount = (int)round(floatval($data['amount']));
            
            $this->logger->info('Erstelle Payment Intent mit folgenden Daten:', [
                'amount' => $amount,
                'currency' => strtolower($data['currency']),
                'description' => $data['description'] ?? 'Zahlung',
                'email' => $data['customer_email'] ?? 'nicht angegeben'
            ]);
            
            // Payment Intent erstellen
            $paymentIntent = $this->stripeService->createPaymentIntent([
                'amount' => $amount,
                'currency' => strtolower($data['currency']),
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
                'description' => $data['description'] ?? 'Zahlung',
                'receipt_email' => $data['customer_email'] ?? null,
                'metadata' => $data['metadata'] ?? [],
            ]);
            
            if (!$paymentIntent || !$paymentIntent->id) {
                $this->logger->error('Fehler beim Erstellen des Payment Intent: Keine gültige Antwort von Stripe');
                return $this->json([
                    'error' => 'Fehler bei der Kommunikation mit Stripe',
                    'dbSuccess' => false
                ], 500, $headers);
            }
            
            // Session-ID generieren für spätere Verwendung (unabhängig von der Payment Intent ID)
            $sessionId = $paymentIntent->id;
            
            // Produkt- und Kundendaten extrahieren, falls vorhanden
            $productData = $data['product_data'] ?? $data['product'] ?? [];
            $customerData = $data['customer_data'] ?? $data['customer'] ?? [];
            
            // Daten für eine spätere Verarbeitung speichern
            $sessionData = [
                'session_id' => $sessionId,
                'payment_intent_id' => $paymentIntent->id,
                'client_secret' => $paymentIntent->client_secret,
                'customer_data' => $customerData,
                'product_data' => $productData,
                'metadata' => $data['metadata'] ?? [],
                'amount' => $amount,
                'currency' => $data['currency'],
                'description' => $data['description'] ?? 'Zahlung'
            ];
            
            // Debug-Ausgabe der sessionData
            $this->logger->info('Vorbereitete Session-Daten:', [
                'session_id' => $sessionData['session_id'],
                'payment_intent_id' => $sessionData['payment_intent_id'],
                'data_keys' => array_keys($sessionData)
            ]);
            
            // Versuche, in der Datenbank zu speichern und fange mögliche Fehler ab
            $dbSuccess = true;
            $dbError = null;
            $session = null;
            
            try {
                // Versuche zunächst, zu prüfen, ob bereits eine Session mit dieser PaymentIntent-ID existiert
                $existingSession = $this->sessionManager->getSessionDataByPaymentIntentId($paymentIntent->id);
                
                if ($existingSession) {
                    $this->logger->info('Session mit dieser Payment Intent ID existiert bereits, keine neue Session erstellt', [
                        'payment_intent_id' => $paymentIntent->id
                    ]);
                    
                    // Wir verwenden die existierende Session und müssen keine neue erstellen
                    $session = new \Vsm\VsmHelperTools\Model\PaymentSession(
                        $existingSession['session_id'],
                        $existingSession['status'] ?? 'pending',
                        $sessionData
                    );
                } else {
                    // Keine existierende Session gefunden, neue erstellen
                    $this->logger->info('Erstelle neue Session in der Datenbank');
                    $session = $this->sessionManager->createSession($sessionData);
                
                    if (!$session) {
                        $dbSuccess = false;
                        $dbError = 'Sitzungsobjekt konnte nicht erstellt werden';
                        $this->logger->error('Fehler beim Erstellen der Session: Keine Session zurückgegeben');
                    } else {
                        $this->logger->info('Session erfolgreich erstellt', [
                            'session_id' => $session->getSessionId(),
                            'payment_intent_id' => $paymentIntent->id
                        ]);
                    }
                }
            } catch (\Exception $e) {
                $dbSuccess = false;
                $dbError = $e->getMessage();
                $this->logger->error('Fehler beim Speichern der Session in der Datenbank: ' . $e->getMessage(), [
                    'exception' => get_class($e),
                    'trace' => $e->getTraceAsString()
                ]);
                
                // Trotz Fehler weitermachen, um den Payment Intent zurückzugeben
            }
            
            // Umfangreiches Logging für Problemdiagnose
            $this->logger->info('Payment Intent erstellt', [
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $amount,
                'currency' => $data['currency'],
                'customer_email' => $data['customer_email'] ?? 'nicht gesetzt',
                'description' => $data['description'] ?? 'Zahlung',
                'db_success' => $dbSuccess ? 'ja' : 'nein',
                'db_error' => $dbError
            ]);

            // DIREKTE DATENBANK-ÜBERPRÜFUNG - Teste, ob der Eintrag tatsächlich vorhanden ist
            try {
                $dbCheck = $this->connection->fetchAssociative(
                    'SELECT id, session_id, payment_intent_id FROM tl_stripe_payment_sessions WHERE payment_intent_id = ?',
                    [$paymentIntent->id]
                );
                
                if ($dbCheck) {
                    $this->logger->info('Datenbank-Bestätigung: Eintrag ist tatsächlich in der DB vorhanden!', [
                        'db_id' => $dbCheck['id'],
                        'db_session_id' => $dbCheck['session_id']
                    ]);
                } else {
                    // NOTFALL: Direkter SQL-Insert als letzter Versuch
                    $this->logger->warning('Datenbank-Bestätigung: Eintrag ist NICHT in der DB! Versuche direkten SQL-Insert...');
                    
                    try {
                        $minimalInsert = "INSERT INTO tl_stripe_payment_sessions 
                                         (session_id, payment_intent_id, client_secret, tstamp, created_at, status) 
                                         VALUES (?, ?, ?, ?, ?, ?)";
                        
                        $this->connection->executeStatement($minimalInsert, [
                            $sessionId,
                            $paymentIntent->id,
                            $paymentIntent->client_secret,
                            time(),
                            time(),
                            'pending'
                        ]);
                        
                        $this->logger->info('Direkter SQL-Insert erfolgreich durchgeführt');
                        $dbSuccess = true;
                    } catch (\Exception $sqlEx) {
                        $this->logger->error('Auch direkter SQL-Insert fehlgeschlagen: ' . $sqlEx->getMessage());
                        // Hier nicht abbrechen, wir wollen trotzdem den Payment Intent zurückgeben
                    }
                }
            } catch (\Exception $dbCheckEx) {
                $this->logger->error('Fehler bei der direkten Datenbank-Überprüfung: ' . $dbCheckEx->getMessage());
            }
            
            // Client Secret und ID zurückgeben mit DB-Status
            return $this->json([
                'id' => $paymentIntent->id,
                'clientSecret' => $paymentIntent->client_secret,
                'amount' => $amount,
                'currency' => $data['currency'],
                'session_id' => $sessionId,
                'dbSuccess' => $dbSuccess,
                'dbError' => $dbError
            ], 200, $headers);
            
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Erstellen des Payment Intent: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->json([
                'error' => 'Fehler beim Erstellen des Payment Intent: ' . $e->getMessage(),
                'dbSuccess' => false
            ], 500, $headers);
        }
    }
    
    /**
     * Verarbeitet erfolgreiche Payment Intent-Zahlungen
     */
    #[Route('/stripe/payment-intent-success', name: 'stripe_payment_intent_success', methods: ['GET'])]
    public function handlePaymentIntentSuccess(Request $request): Response
    {
        try {
            // Parameter aus dem Request extrahieren
            $paymentIntentId = $request->query->get('payment_intent_id');
            $paymentStatus = $request->query->get('payment_status');
            $sessionId = $request->query->get('session_id');
            
            if (!$paymentIntentId) {
                return $this->json(['error' => 'Payment Intent ID fehlt'], 400);
            }
            
            // Stripe-Client initialisieren
            $this->initStripeClient();
            
            // Payment Intent abrufen
            $paymentIntent = $this->stripeService->retrievePaymentIntent($paymentIntentId);
            
            if (!$paymentIntent) {
                return $this->json(['error' => 'Payment Intent nicht gefunden'], 404);
            }
            
            // Überprüfen, ob die Zahlung erfolgreich war
            if ($paymentIntent->status !== 'succeeded') {
                return $this->json(['error' => 'Zahlung nicht erfolgreich. Status: ' . $paymentIntent->status], 400);
            }
            
            // Sitzungsdaten aus der Datenbank abrufen
            $sessionData = $this->sessionManager->getSessionData($sessionId);
            
            if (!$sessionData) {
                return $this->json(['error' => 'Sitzungsdaten nicht gefunden'], 404);
            }
            
            // Zahlungsdaten extrahieren
            $paymentData = [
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $paymentIntent->amount,
                'currency' => $paymentIntent->currency,
                'status' => $paymentIntent->status,
                'payment_method' => $paymentIntent->payment_method,
                'receipt_email' => $paymentIntent->receipt_email,
                'created' => $paymentIntent->created,
                'metadata' => $paymentIntent->metadata->toArray(),
            ];
            
            // Sitzung in der Datenbank aktualisieren
            $this->sessionManager->updateSessionAfterPayment($sessionId, $paymentData);
            
            // Erfolgs-URL aus dem Request oder den Sitzungsdaten
            $successUrl = $request->query->get('return_url') ?? 
                          $sessionData['product_data']['success_url'] ?? 
                          '/';
            
            // Erfolgs-URL mit optionalen Parametern
            $successUrl = $this->addParamsToUrl($successUrl, [
                'session_id' => $sessionId,
                'payment_intent_id' => $paymentIntentId
            ]);
            
            // Auf Erfolgsseite umleiten
            return $this->redirect($successUrl);
            
        } catch (\Exception $e) {
            $this->logger->error('Fehler bei der Verarbeitung der Payment Intent-Zahlung: ' . $e->getMessage());
            return $this->json(['error' => 'Fehler bei der Verarbeitung der Zahlung: ' . $e->getMessage()], 500);
        }
    }
} 