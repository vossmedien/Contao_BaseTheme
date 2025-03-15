<?php

declare(strict_types=1);

namespace Vsm\VsmHelperTools\Controller;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Email;
use Contao\FilesModel;
use Contao\MemberModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Vsm\VsmHelperTools\Helper\EmailHelper;
use Vsm\VsmHelperTools\Service\FileDownloadService;
use Vsm\VsmHelperTools\Service\PaymentProcessorService;
use Vsm\VsmHelperTools\Service\StripePaymentService;
use Twig\Environment;

/**
 * Controller für Stripe Checkout
 */
#[Route('/stripe', defaults: ['_scope' => 'frontend'])]
class StripeCheckoutController extends AbstractController
{
    private ContaoFramework $framework;
    private LoggerInterface $logger;
    private string $projectDir;
    private Connection $db;
    private string $stripeSecretKey;
    private PaymentProcessorService $paymentProcessorService;
    private StripePaymentService $stripeService;
    private FileDownloadService $fileDownloadService;
    private Environment $twig;

    public function __construct(
        ContaoFramework $framework,
        LoggerInterface $logger,
        string $projectDir,
        Connection $db,
        string $stripeSecretKey = null,
        PaymentProcessorService $paymentProcessorService = null,
        StripePaymentService $stripeService = null,
        FileDownloadService $fileDownloadService = null,
        Environment $twig
    ) {
        $this->framework = $framework;
        $this->logger = $logger;
        $this->projectDir = $projectDir;
        $this->db = $db;
        $this->stripeSecretKey = $stripeSecretKey ?? $_ENV['STRIPE_SECRET_KEY'] ?? '';
        $this->paymentProcessorService = $paymentProcessorService;
        $this->stripeService = $stripeService;
        $this->fileDownloadService = $fileDownloadService;
        $this->twig = $twig;
    }

    /**
     * Erstellt eine Stripe Checkout-Session
     */
    #[Route('/create-checkout-session', name: 'stripe_create_checkout_session', methods: ['POST'])]
    public function createCheckoutSession(Request $request): Response
    {
        $this->framework->initialize();
        
        try {
            // CORS-Header für alle Antworten
            $headers = [
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'POST, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, X-Requested-With'
            ];
            
            // Prüfen Sie, ob es sich um eine OPTIONS-Anfrage handelt (CORS Preflight)
            if ($request->getMethod() === 'OPTIONS') {
                return new Response('', 200, $headers);
            }
            
            // Daten aus der Anfrage lesen
            $data = json_decode($request->getContent(), true);
            if (!$data) {
                $this->logger->error('Ungültige JSON-Daten in der Anfrage', [
                    'context' => ContaoContext::ERROR,
                    'content' => $request->getContent()
                ]);
                throw new \Exception('Ungültige Anfragedaten. JSON konnte nicht geparst werden.');
            }
            
            // Validiere notwendige Felder
            if (empty($data['productData']) || 
                empty($data['productData']['title']) || 
                !isset($data['productData']['price'])) {
                $this->logger->error('Unvollständige Produktdaten', [
                    'context' => ContaoContext::ERROR,
                    'data' => $data
                ]);
                throw new \Exception('Produktdaten unvollständig oder fehlen');
            }

            // Prüfe, ob Stripe-Key konfiguriert ist
            if (empty($this->stripeSecretKey)) {
                $this->logger->error('Stripe Secret Key nicht konfiguriert', [
                    'context' => ContaoContext::ERROR
                ]);
                throw new \Exception('Stripe ist nicht korrekt konfiguriert. Bitte kontaktieren Sie den Administrator.');
            }
            
            // Stelle sicher, dass die URLs vollständig sind
            $baseUrl = $request->getSchemeAndHttpHost();
            
            // Korrigiere URLs, um vollständige URLs zu gewährleisten
            if (!empty($data['successUrl']) && !preg_match('/^https?:\/\//', $data['successUrl'])) {
                $data['successUrl'] = rtrim($baseUrl, '/') . '/' . ltrim($data['successUrl'], '/');
                $this->logger->info('Success-URL korrigiert zu absoluter URL', [
                    'url' => $data['successUrl']
                ]);
            }
            
            if (!empty($data['cancelUrl']) && !preg_match('/^https?:\/\//', $data['cancelUrl'])) {
                $data['cancelUrl'] = rtrim($baseUrl, '/') . '/' . ltrim($data['cancelUrl'], '/');
                $this->logger->info('Cancel-URL korrigiert zu absoluter URL', [
                    'url' => $data['cancelUrl']
                ]);
            }
            
            $this->logger->info('Erstelle Stripe Checkout Session', [
                'context' => ContaoContext::GENERAL,
                'product_title' => $data['productData']['title'],
                'price' => $data['productData']['price'] ?? 0,
                'currency' => $data['productData']['currency'] ?? 'eur',
                'success_url' => $data['successUrl'] ?? $baseUrl,
                'cancel_url' => $data['cancelUrl'] ?? $baseUrl
            ]);
            
            // Temporäre Session-Daten speichern
            $sessionToken = $this->storeSessionData($data);
            $this->logger->info('Session-Daten gespeichert mit Token: ' . $sessionToken);
            
            // Stripe-API initialisieren
            Stripe::setApiKey($this->stripeSecretKey);
            Stripe::setApiVersion('2025-02-24.acacia'); // Stabile API-Version für die Stripe-Integration
            
            // Stelle sicher, dass gültige URLs für Erfolg und Abbruch vorhanden sind
            if (empty($data['successUrl']) || !filter_var($data['successUrl'], FILTER_VALIDATE_URL)) {
                $data['successUrl'] = $baseUrl;
                $this->logger->warning('Ungültige success_url, verwende Basis-URL als Fallback', [
                    'fallback_url' => $baseUrl
                ]);
            }
            
            if (empty($data['cancelUrl']) || !filter_var($data['cancelUrl'], FILTER_VALIDATE_URL)) {
                $data['cancelUrl'] = $baseUrl;
                $this->logger->warning('Ungültige cancel_url, verwende Basis-URL als Fallback', [
                    'fallback_url' => $baseUrl
                ]);
            }
            
            // Einen Stripe-Kunden erstellen oder abrufen, wenn E-Mail angegeben ist
            $stripeCustomerId = null;
            if (!empty($data['personalData']['email'])) {
                try {
                    // Suche nach existierendem Kunden
                    $existingCustomers = \Stripe\Customer::all([
                        'email' => $data['personalData']['email'],
                        'limit' => 1,
                    ]);
                    
                    if (!empty($existingCustomers->data)) {
                        $stripeCustomerId = $existingCustomers->data[0]->id;
                        $this->logger->info('Existierenden Stripe-Kunden gefunden', [
                            'email' => $data['personalData']['email'],
                            'customer_id' => $stripeCustomerId
                        ]);
                    } else {
                        // Neuen Kunden erstellen
                        $customerData = [
                            'email' => $data['personalData']['email'],
                            'name' => trim(($data['personalData']['firstname'] ?? '') . ' ' . ($data['personalData']['lastname'] ?? '')),
                            'metadata' => [
                                'session_token' => $sessionToken,
                                'product_id' => $data['productData']['id'] ?? null,
                            ]
                        ];
                        
                        // Adressdaten hinzufügen, wenn verfügbar
                        if (!empty($data['personalData']['street']) || !empty($data['personalData']['city'])) {
                            $customerData['address'] = [
                                'line1' => $data['personalData']['street'] ?? '',
                                'postal_code' => $data['personalData']['postal'] ?? '',
                                'city' => $data['personalData']['city'] ?? '',
                                'country' => $data['personalData']['country'] ?? 'DE',
                            ];
                        }
                        
                        // Telefonnummer hinzufügen, wenn verfügbar
                        if (!empty($data['personalData']['phone'])) {
                            $customerData['phone'] = $data['personalData']['phone'];
                        }
                        
                        $customer = \Stripe\Customer::create($customerData);
                        $stripeCustomerId = $customer->id;
                        
                        $this->logger->info('Neuen Stripe-Kunden erstellt', [
                            'email' => $data['personalData']['email'],
                            'customer_id' => $stripeCustomerId
                        ]);
                    }
                } catch (\Exception $e) {
                    $this->logger->warning('Fehler beim Erstellen des Stripe-Kunden: ' . $e->getMessage(), [
                        'error' => $e->getMessage()
                    ]);
                    // Wir setzen fort ohne Kunde, da dies nicht kritisch ist
                }
            }
            
            // Checkout-Session Parameter erstellen
            $sessionParams = [
                'payment_method_types' => [
                    'card',
                    'eps',
                    'giropay',
                    'sofort',
                    'sepa_debit',
                    'klarna',
                    'paypal'
                ],
                'line_items' => [[
                    'price_data' => [
                        'currency' => strtolower($data['productData']['currency'] ?? 'eur'),
                        'product_data' => [
                            'name' => $data['productData']['title'],
                        ],
                        'unit_amount' => (int)$data['productData']['price'],
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $data['successUrl'],
                'cancel_url' => $data['cancelUrl'],
                'metadata' => [
                    'session_id' => $data['sessionId'],
                    'product_id' => $data['productData']['id'],
                    'element_id' => $data['elementId'],
                    'create_user' => $data['createUser'] ? 'true' : 'false',
                    'session_token' => $sessionToken, // Speichere den Token für späteren Zugriff
                    'notification_id' => $data['productData']['notification_id'] ?? null,
                ],
                'payment_intent_data' => [
                    'metadata' => [
                        'session_id' => $data['sessionId'],
                        'product_id' => $data['productData']['id'],
                        'element_id' => $data['elementId'],
                        'session_token' => $sessionToken,
                        'notification_id' => $data['productData']['notification_id'] ?? null,
                    ]
                ],
                // Automatische Rechnungserstellung aktivieren
                'invoice_creation' => [
                    'enabled' => true,
                    'invoice_data' => [
                        'description' => 'Rechnung für ' . $data['productData']['title'],
                        'footer' => 'Vielen Dank für Ihren Einkauf.',
                        'rendering_options' => [
                            'amount_tax_display' => 'include_inclusive_tax'
                        ],
                        'metadata' => [
                            'product_id' => $data['productData']['id'],
                            'element_id' => $data['elementId'],
                            'session_token' => $sessionToken
                        ]
                    ]
                ],
                // Steuern für Deutschland, standardmäßig 19% MwSt.
                'automatic_tax' => [
                    'enabled' => true
                ]
            ];
            
            // Customer Email Feld nur hinzufügen, wenn kein Customer-ID vorhanden ist
            if (!$stripeCustomerId && !empty($data['personalData']['email'])) {
                $sessionParams['customer_email'] = $data['personalData']['email'];
            }
            
            // Wenn ein Stripe-Kunde existiert, diesen zur Session hinzufügen
            if ($stripeCustomerId) {
                $sessionParams['customer'] = $stripeCustomerId;
                // Wenn ein Kunde angegeben wird, darf customer_email nicht gesetzt sein
                unset($sessionParams['customer_email']);
            }
            
            // Prüfe, ob Firmenname vorhanden ist - dann Firmenrechnung aktivieren
            if (!empty($data['personalData']['company'])) {
                // Metadaten für die Rechnungsstellung anpassen für Business-Kunden
                $sessionParams['invoice_creation']['invoice_data']['custom_fields'] = [
                    [
                        'name' => 'Firma',
                        'value' => $data['personalData']['company']
                    ]
                ];
                
                // USt-ID hinzufügen, falls vorhanden
                if (!empty($data['personalData']['vat_id'])) {
                    $sessionParams['invoice_creation']['invoice_data']['custom_fields'][] = [
                        'name' => 'USt-ID',
                        'value' => $data['personalData']['vat_id']
                    ];
                }
            }
            
            $this->logger->info('Erstelle Checkout-Session mit automatischer Rechnungserstellung', [
                'context' => ContaoContext::GENERAL,
                'invoice_enabled' => true,
                'customer_email' => $data['personalData']['email'] ?? null,
                'customer_id' => $stripeCustomerId
            ]);
            
            // Checkout-Session erstellen
            $session = Session::create($sessionParams);
            
            $this->logger->info('Checkout-Session erfolgreich erstellt', [
                'context' => ContaoContext::GENERAL,
                'session_id' => $session->id,
                'payment_intent' => $session->payment_intent,
                'customer_id' => $stripeCustomerId
            ]);
            
            return new JsonResponse(['id' => $session->id, 'token' => $sessionToken], 200, $headers);
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $this->logger->error('Stripe API Fehler: ' . $e->getMessage(), [
                'context' => ContaoContext::ERROR,
                'error_code' => $e->getStripeCode(),
                'http_status' => $e->getHttpStatus(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return new JsonResponse([
                'error' => 'Stripe Fehler: ' . $e->getMessage(),
                'code' => $e->getStripeCode()
            ], 400, $headers);
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Erstellen der Checkout-Session: ' . $e->getMessage(), [
                'context' => ContaoContext::ERROR,
                'trace' => $e->getTraceAsString()
            ]);
            
            return new JsonResponse(['error' => $e->getMessage()], 400, $headers);
        }
    }
    
    /**
     * Verarbeitet die erfolgreiche Zahlung
     */
    #[Route('/checkout/success', name: 'stripe_checkout_success', methods: ['GET'])]
    public function handleSuccess(Request $request): Response
    {
        $this->framework->initialize();
        
        try {
            $sessionId = $request->query->get('session_id');
            $paymentId = $request->query->get('payment_id');
            $targetUrl = $request->query->get('target');
            
            if (!$sessionId) {
                throw new \Exception('Keine Session-ID gefunden');
            }
            
            $this->logger->info('Verarbeite erfolgreiche Zahlung', [
                'context' => ContaoContext::GENERAL,
                'session_id' => $sessionId,
                'payment_id' => $paymentId,
                'target_url' => $targetUrl
            ]);
            
            // Stripe-API initialisieren
            Stripe::setApiKey($this->stripeSecretKey);
            
            // Checkout-Session abrufen
            $session = Session::retrieve($sessionId);
            
            // Direkt hier den Status der Zahlung prüfen
            // Der Redirect erfolgt erst nach erfolgreicher Zahlung durch Stripe
            $paymentStatus = $session->payment_status;
            
            // Bei "paid" oder "no_payment_required" handeln
            if ($paymentStatus === 'paid' || $paymentStatus === 'no_payment_required') {
                $this->logger->info('Zahlung erfolgreich abgeschlossen', [
                    'context' => ContaoContext::GENERAL,
                    'session_id' => $sessionId,
                    'payment_status' => $paymentStatus
                ]);
            } else {
                // Bei anderem Status (meist "unpaid") nur loggen und trotzdem fortfahren
                // da Stripe den Redirect nur bei erfolgreicher Zahlung auslöst
                $this->logger->warning('Unerwarteter Zahlungsstatus, aber fortfahren', [
                    'context' => ContaoContext::GENERAL,
                    'session_id' => $sessionId,
                    'payment_status' => $paymentStatus
                ]);
            }
            
            // Gespeicherte Session-Daten abrufen - nur wenn payment_id existiert
            $sessionData = null;
            if ($paymentId) {
                $sessionData = $this->getSessionData($paymentId);
                
                if ($sessionData) {
                    $this->logger->info('Session-Daten aus payment_id gefunden', [
                        'context' => ContaoContext::GENERAL,
                        'payment_id' => $paymentId
                    ]);
                }
            }
            
            // Wenn keine Daten gefunden wurden, versuchen wir Metadaten aus der Session zu holen
            if (!$sessionData) {
                $this->logger->info('Keine Session-Daten per payment_id gefunden, versuche aus Stripe-Session', [
                    'context' => ContaoContext::GENERAL,
                    'session_id' => $sessionId
                ]);
                
                // Versuche, Daten aus der Stripe-Session-Metadaten zu extrahieren
                $sessionToken = $session->metadata['session_token'] ?? null;
                
                if ($sessionToken) {
                    $sessionData = $this->getSessionData($sessionToken);
                    
                    if ($sessionData) {
                        $this->logger->info('Session-Daten aus Token gefunden', [
                            'context' => ContaoContext::GENERAL,
                            'session_token' => $sessionToken
                        ]);
                    }
                }
            }
            
            // Weiterhin keine Daten? Dann aus Session-ID versuchen
            if (!$sessionData) {
                // Versuch mit der Session-ID direkt
                $sessionData = $this->getSessionData($sessionId);
                
                if ($sessionData) {
                    $this->logger->info('Session-Daten aus Session-ID gefunden', [
                        'context' => ContaoContext::GENERAL,
                        'session_id' => $sessionId
                    ]);
                }
            }
            
            // Wenn immer noch keine Daten, dann aus den Metadaten der Session rekonstruieren
            if (!$sessionData) {
                $this->logger->warning('Keine Session-Daten in der Datenbank gefunden, rekonstruiere aus Stripe-Session', [
                    'context' => ContaoContext::GENERAL,
                    'session_id' => $sessionId
                ]);
                
                // Basis-Daten aus Session extrahieren
                $lineItem = $session->line_items->data[0] ?? null;
                $productName = $lineItem && $lineItem->price && $lineItem->price->product ? 
                             $lineItem->price->product->name : 'Produkt';
                
                // Metadaten als Fallback verwenden
                $sessionData = [
                    'session_id' => $sessionId,
                    'element_id' => $session->metadata['element_id'] ?? null,
                    'product_data' => [
                        'id' => $session->metadata['product_id'] ?? '0',
                        'title' => $productName,
                        'price' => $session->amount_total ?? 0,
                        'currency' => $session->currency ?? 'eur',
                        'notification_id' => $session->metadata['notification_id'] ?? null
                    ],
                    'personal_data' => [
                        'email' => $session->customer_details->email ?? null,
                        'name' => $session->customer_details->name ?? 'Kunde'
                    ],
                    'create_user' => ($session->metadata['create_user'] ?? 'false') === 'true'
                ];
                
                // Wenn Customer Details vorhanden, Name zerlegen
                if (!empty($sessionData['personal_data']['name']) && 
                    !isset($sessionData['personal_data']['firstname'])) {
                    $nameParts = explode(' ', $sessionData['personal_data']['name'], 2);
                    $sessionData['personal_data']['firstname'] = $nameParts[0] ?? '';
                    $sessionData['personal_data']['lastname'] = $nameParts[1] ?? '';
                }
            }
            
            // Füge session_id als Metadaten hinzu
            $sessionData['session_id'] = $sessionId;
            
            // Zahlung erfolgreich verarbeiten
            $result = $this->processSuccessfulPayment($session, $sessionData);
            
            // Nach der Verarbeitung zur Zielseite weiterleiten
            $finalUrl = $targetUrl ?: '/';
            $finalUrl .= (strpos($finalUrl, '?') !== false ? '&' : '?') . 'payment_status=' . 
                        ($result['success'] ? 'success' : 'error');
            
            // Parameter für Debugging hinzufügen
            if ($result['success'] && isset($result['download_link'])) {
                $finalUrl .= '&download=' . urlencode($result['download_link']);
            }
            
            if ($result['success'] && isset($result['email_sent'])) {
                $finalUrl .= '&email_sent=' . ($result['email_sent'] ? 'true' : 'false');
            }
            
            // Schließlich zur Zielseite mit Statusparametern weiterleiten
            return $this->redirect($finalUrl);
            
        } catch (\Exception $e) {
            $this->logger->error('Fehler bei der Verarbeitung der erfolgreichen Zahlung: ' . $e->getMessage(), [
                'context' => ContaoContext::ERROR,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Im Fehlerfall zur Zielseite mit Fehlerparameter weiterleiten
            $errorUrl = $targetUrl ?: '/';
            return $this->redirect($errorUrl . (strpos($errorUrl, '?') !== false ? '&' : '?') . 'payment_status=error');
        }
    }
    
    /**
     * Speichert temporäre Session-Daten für spätere Verwendung
     */
    private function storeSessionData(array $data): string
    {
        $sessionId = uniqid('stripe_session_');
        $expiry = time() + 3600; // 1 Stunde gültig
        $create_user = !empty($data['createUser']) && $data['createUser'] === true;
        $element_id = !empty($data['elementId']) ? (int)$data['elementId'] : 0;
        
        // Produkt- und Persönliche Daten als JSON speichern
        $productData = $data['productData'] ? json_encode($data['productData']) : null;
        $personalData = $data['personalData'] ? json_encode($data['personalData']) : null;
        
        // E-Mail-Template-Informationen extrahieren
        $notification_id = $data['productData']['notification_id'] ?? null;
        $admin_template = $data['productData']['admin_template'] ?? null;
        $user_template = $data['productData']['user_template'] ?? null;
        $sender_email = $data['productData']['sender_email'] ?? null;
        $admin_email = $data['productData']['admin_email'] ?? null;
        
        $this->logger->info('Speichere E-Mail-Template-Informationen', [
            'admin_template' => $admin_template,
            'user_template' => $user_template,
            'sender_email' => $sender_email, 
            'admin_email' => $admin_email
        ]);
        
        try {
            $this->db->executeStatement(
                "INSERT INTO tl_stripe_checkout_sessions 
                (id, tstamp, expiry, product_data, personal_data, create_user, element_id, notification_id, admin_template, user_template, sender_email, admin_email) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $sessionId,
                    time(),
                    $expiry,
                    $productData,
                    $personalData,
                    $create_user ? '1' : '',
                    $element_id,
                    $notification_id,
                    $admin_template,
                    $user_template,
                    $sender_email,
                    $admin_email
                ]
            );
                    
            $this->logger->info('Session-Daten gespeichert', [
                'context' => ContaoContext::GENERAL,
                'session_id' => $sessionId
            ]);
            
            return $sessionId;
        } catch (\Exception $e) {
            // Fehlerbehandlung - wenn fehlende Spalten das Problem sind, versuche sie hinzuzufügen
            $this->logger->error('Fehler beim Speichern der Session-Daten: ' . $e->getMessage(), [
                'context' => ContaoContext::ERROR,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Versuche fehlende Spalten hinzuzufügen
            $this->addMissingSessionColumns();
            
            // Erneut versuchen - diesmal ohne die neuen Spalten
            $this->db->executeStatement(
                "INSERT INTO tl_stripe_checkout_sessions 
                (id, tstamp, expiry, product_data, personal_data, create_user, element_id, notification_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $sessionId,
                    time(),
                    $expiry,
                    $productData,
                    $personalData,
                    $create_user ? '1' : '',
                    $element_id,
                    $notification_id
                ]
            );
            
            $this->logger->info('Session-Daten mit reduziertem Datenset gespeichert', [
                'context' => ContaoContext::GENERAL,
                'session_id' => $sessionId
            ]);
            
            return $sessionId;
        }
    }
    
    /**
     * Holt temporäre Sessiondaten aus der Datenbank
     */
    private function getSessionData(string $sessionId): ?array
    {
        // Tabelle erstellen, falls sie nicht existiert
        $this->createSessionTable();
        
        // Daten abrufen
        $data = $this->db->fetchAssociative(
            "SELECT * FROM tl_stripe_checkout_sessions WHERE id = ?",
            [$sessionId]
        );
        
        if (!$data) {
            return null;
        }
        
        // JSON-Daten dekodieren
        $data['product_data'] = json_decode($data['product_data'], true);
        $data['personal_data'] = json_decode($data['personal_data'], true);
        $data['create_user'] = $data['create_user'] === '1';
        
        return $data;
    }
    
    /**
     * Löscht temporäre Sessiondaten aus der Datenbank
     */
    private function deleteSessionData(string $sessionId): void
    {
        $this->db->delete('tl_stripe_checkout_sessions', ['id' => $sessionId]);
        
        $this->logger->info('Session-Daten gelöscht', [
            'context' => ContaoContext::GENERAL,
            'session_id' => $sessionId
        ]);
    }
    
    /**
     * Erstellt die Session-Tabelle, falls sie nicht existiert
     */
    private function createSessionTable(): void
    {
        try {
            $tablesExist = $this->db->fetchAllAssociative("SHOW TABLES LIKE 'tl_stripe_checkout_sessions'");
        
            if (empty($tablesExist)) {
                $this->db->executeStatement("
CREATE TABLE `tl_stripe_checkout_sessions` (
  `id` varchar(255) NOT NULL,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `expiry` int(10) unsigned NOT NULL DEFAULT 0,
  `product_data` text NULL,
  `personal_data` text NULL,
  `create_user` char(1) NOT NULL DEFAULT '0',
  `element_id` int(10) unsigned NOT NULL DEFAULT 0,
                  `notification_id` varchar(255) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");
                
                $this->logger->info('Tabelle tl_stripe_checkout_sessions erstellt');
            } else {
                // Prüfen, ob das notification_id-Feld existiert
                $columnsResult = $this->db->fetchAllAssociative("SHOW COLUMNS FROM `tl_stripe_checkout_sessions` LIKE 'notification_id'");
                
                if (empty($columnsResult)) {
                    // Spalte hinzufügen, falls sie nicht existiert
                    $this->db->executeStatement("
                    ALTER TABLE `tl_stripe_checkout_sessions` 
                    ADD COLUMN `notification_id` varchar(255) NULL
                    ");
                    
                    $this->logger->info('Spalte notification_id zur Tabelle tl_stripe_checkout_sessions hinzugefügt');
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Erstellen der Session-Tabelle: ' . $e->getMessage(), [
                'context' => ContaoContext::ERROR,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Verarbeitet eine erfolgreiche Zahlung
     */
    private function processSuccessfulPayment(Session $session, array $sessionData): array
    {
        try {
            $this->logger->info('Verarbeite erfolgreiche Zahlung für Session: ' . $session->id, [
                'context' => ContaoContext::GENERAL
            ]);
            
            // PaymentIntent aus der Session extrahieren
            $paymentIntentId = $session->payment_intent;
            
            // PaymentIntent direkt von Stripe laden, um alle Informationen zu haben
            $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentIntentId);
            
            $this->logger->debug('PaymentIntent geladen', [
                'payment_intent_id' => $paymentIntentId,
                'payment_intent_status' => $paymentIntent->status
            ]);
            
            // Persönliche Daten aus der Session extrahieren
            $personalData = [
                'email' => $session->customer_details->email ?? '',
                'firstname' => $session->customer_details->name ? explode(' ', $session->customer_details->name)[0] : '',
                'lastname' => $session->customer_details->name ? substr(strstr($session->customer_details->name, ' '), 1) : '',
                'company' => $session->customer_details->address->company ?? '',
                'street' => $session->customer_details->address->line1 ?? '',
                'postal' => $session->customer_details->address->postal_code ?? '',
                'city' => $session->customer_details->address->city ?? '',
                'country' => $session->customer_details->address->country ?? 'DE',
                'phone' => $session->customer_details->phone ?? '',
            ];
            
            // Notification ID aus verschiedenen Quellen extrahieren
            $notificationId = null;
            
            // 1. Versuche die notification_id aus der Session-Metadata zu bekommen
            if (isset($session->metadata->notification_id)) {
                $notificationId = $session->metadata->notification_id;
                $this->logger->info('Notification ID aus Session-Metadata geladen: ' . $notificationId);
            }
            // 2. Alternativ aus dem PaymentIntent-Metadata
            elseif (isset($paymentIntent->metadata->notification_id)) {
                $notificationId = $paymentIntent->metadata->notification_id;
                $this->logger->info('Notification ID aus PaymentIntent-Metadata geladen: ' . $notificationId);
            }
            
            // Produkt-Daten zusammensetzen
            $productData = [
                'title' => $session->metadata->product_name ?? 'Produkt',
                'price' => $session->amount_total,
                'currency' => $session->currency,
            ];
            
            // Notification ID hinzufügen, falls vorhanden
            if ($notificationId) {
                $productData['notification_id'] = $notificationId;
            }
            
            // Rechnungsdaten suchen und verarbeiten
            $invoiceData = null;
            
            // 1. Prüfen, ob eine direkte Rechnungs-ID in der Session vorhanden ist
            if (isset($session->invoice) && is_string($session->invoice)) {
                try {
                    $invoice = \Stripe\Invoice::retrieve($session->invoice);
                    $invoiceData = [
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->number,
                        'invoice_url' => $invoice->hosted_invoice_url,
                        'invoice_pdf' => $invoice->invoice_pdf,
                        'invoice_date' => date('Y-m-d', $invoice->created),
                        'invoice_total' => $invoice->total / 100,
                        'invoice_currency' => strtoupper($invoice->currency)
                    ];
                    $this->logger->info('Rechnungsdaten aus der Session-Rechnungs-ID geladen', [
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->number
                    ]);
                } catch (\Exception $e) {
                    $this->logger->warning('Fehler beim Laden der Rechnung über Session-Rechnungs-ID: ' . $e->getMessage());
                }
            }
            
            // 2. Wenn keine Rechnung in der Session gefunden wurde, nach Rechnungen für den Kunden suchen
            if (!$invoiceData && isset($session->customer)) {
                try {
                    // Nach der neuesten Rechnung für diesen Kunden suchen
                    $invoices = \Stripe\Invoice::all([
                        'customer' => $session->customer,
                        'limit' => 1,
                        'status' => 'paid',
                    ]);
                    
                    if (count($invoices->data) > 0) {
                        $invoice = $invoices->data[0];
                        $invoiceData = [
                            'invoice_id' => $invoice->id,
                            'invoice_number' => $invoice->number,
                            'invoice_url' => $invoice->hosted_invoice_url,
                            'invoice_pdf' => $invoice->invoice_pdf,
                            'invoice_date' => date('Y-m-d', $invoice->created),
                            'invoice_total' => $invoice->total / 100,
                            'invoice_currency' => strtoupper($invoice->currency)
                        ];
                        $this->logger->info('Rechnungsdaten über Kundensuche gefunden', [
                            'invoice_id' => $invoice->id,
                            'invoice_number' => $invoice->number
                        ]);
                    } else {
                        $this->logger->info('Keine bezahlten Rechnungen für diesen Kunden gefunden');
                    }
                } catch (\Exception $e) {
                    $this->logger->warning('Fehler beim Suchen nach Kundenrechnungen: ' . $e->getMessage());
                }
            }
            
            // Ergebnisarray vorbereiten
            $result = [
                'success' => true,
                'message' => 'Die Zahlung wurde erfolgreich verarbeitet.',
                'session_id' => $session->id,
                'payment_intent_id' => $paymentIntentId,
                'customer_email' => $personalData['email'],
                'amount' => $session->amount_total / 100,
                'currency' => strtoupper($session->currency)
            ];
            
            // Rechnungsdaten zum Ergebnis hinzufügen, wenn vorhanden
            if ($invoiceData) {
                $result['invoice'] = [
                    'id' => $invoiceData['invoice_id'],
                    'number' => $invoiceData['invoice_number'],
                    'url' => $invoiceData['invoice_url'],
                    'pdf' => $invoiceData['invoice_pdf'],
                    'date' => $invoiceData['invoice_date']
                ];
                $this->logger->info('Rechnungsdaten zum Ergebnis hinzugefügt');
            }
            
            // Einen Download-Link erstellen, wenn Dateiverkauf
            $downloadLink = null;
            if (isset($session->metadata->file_sale) && $session->metadata->file_sale === 'true' && isset($session->metadata->file_path)) {
                $downloadToken = uniqid('dl_');
                $filePath = $session->metadata->file_path;
                
                // Download-Link erstellen
                $downloadLink = $this->createDownloadLink($filePath, $session->id, $personalData['email'], $downloadToken, $productData);
                
                if ($downloadLink) {
                    $result['download_link'] = $downloadLink;
                    $this->logger->info('Download-Link zum Ergebnis hinzugefügt: ' . $downloadLink);
                }
            }
            
            // E-Mail-Benachrichtigung senden
            $notificationSent = $this->sendEmailNotification($personalData, $paymentIntent, $productData, $downloadLink);
            
            // Führe weitere spezifische Aktionen für diesen Zahlungstyp aus
            return $result;
            
        } catch (\Exception $e) {
            $this->logger->error('Fehler bei der Verarbeitung der erfolgreichen Zahlung: ' . $e->getMessage(), [
                'context' => ContaoContext::ERROR,
                'trace' => $e->getTraceAsString(),
                'session_id' => $session->id
            ]);
            
            return [
                'success' => false,
                'message' => 'Bei der Verarbeitung der Zahlung ist ein Fehler aufgetreten: ' . $e->getMessage(),
                'session_id' => $session->id
            ];
        }
    }
    
    /**
     * Erstellt einen Benutzer
     */
    private function createUser(array $personalData): ?MemberModel
    {
        try {
            $this->logger->info('Erstelle Benutzer', [
                'context' => ContaoContext::GENERAL,
                'email' => $personalData['email'] ?? 'Unbekannt'
            ]);
            
            // Prüfen, ob Benutzer bereits existiert
            $existingMember = MemberModel::findByEmail($personalData['email']);
            
            if ($existingMember) {
                $this->logger->info('Benutzer existiert bereits', [
                    'context' => ContaoContext::GENERAL,
                    'email' => $personalData['email'],
                    'member_id' => $existingMember->id
                ]);
                
                return $existingMember;
            }
            
            // Neuen Benutzer anlegen
            $member = new MemberModel();
            $member->tstamp = time();
            $member->dateAdded = time();
            $member->firstname = $personalData['firstname'] ?? '';
            $member->lastname = $personalData['lastname'] ?? '';
            $member->email = $personalData['email'] ?? '';
            $member->username = $personalData['username'] ?? $personalData['email'] ?? '';
            
            // Passwort nur setzen, wenn vorhanden
            if (!empty($personalData['password'])) {
                $password = base64_decode($personalData['password']);
                $member->password = password_hash($password, PASSWORD_DEFAULT);
            }
            
            $member->street = $personalData['street'] ?? '';
            $member->postal = $personalData['postal'] ?? '';
            $member->city = $personalData['city'] ?? '';
            $member->country = $personalData['country'] ?? 'DE';
            $member->phone = $personalData['phone'] ?? '';
            $member->company = $personalData['company'] ?? '';
            $member->login = '1';
            $member->disable = '';
            $member->start = '';
            $member->stop = '';
            
            $member->save();
            
            $this->logger->info('Benutzer erstellt', [
                'context' => ContaoContext::GENERAL,
                'email' => $personalData['email'],
                'member_id' => $member->id
            ]);
            
            // E-Mail mit Zugangsdaten senden
            $this->sendRegistrationEmail($member);
            
            return $member;
            
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Erstellen des Benutzers: ' . $e->getMessage(), [
                'context' => ContaoContext::ERROR,
                'trace' => $e->getTraceAsString()
            ]);
            
            return null;
        }
    }
    
    /**
     * Erstellt einen Download-Link für eine gekaufte Datei
     */
    private function createDownloadLink(string $filePath, string $sessionId, string $customerEmail, string $downloadToken, array $productData): ?string
    {
        try {
            $this->logger->info('Erstelle Download-Link', [
                'context' => ContaoContext::GENERAL,
                'file_path' => $filePath,
                'session_id' => $sessionId,
                'customer_email' => $customerEmail
            ]);
            
            // Prüfen, ob alle erforderlichen Daten vorhanden sind
            if (empty($filePath)) {
                throw new \Exception('Fehlende Datei-Informationen');
            }
            
            // Download-Eintrag erstellen
            $downloadExpires = intval($productData['download_expires'] ?? 7);
            $downloadLimit = intval($productData['download_limit'] ?? 3);
            
            $token = $this->fileDownloadService->createDownloadEntry(
                $filePath,
                $downloadToken,
                $downloadExpires,
                $downloadLimit,
                $sessionId,
                $customerEmail
            );
            
            if (!$token) {
                throw new \Exception('Fehler beim Erstellen des Download-Eintrags');
            }
            
            // Download-Link generieren
            $downloadLink = $this->fileDownloadService->generateDownloadLink($token);
            
            $this->logger->info('Download-Link erstellt', [
                'context' => ContaoContext::GENERAL,
                'token' => $token,
                'download_link' => $downloadLink
            ]);
            
            return $downloadLink;
            
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Erstellen des Download-Links: ' . $e->getMessage(), [
                'context' => ContaoContext::ERROR,
                'trace' => $e->getTraceAsString()
            ]);
            
            return null;
        }
    }
    
    /**
     * Sendet eine E-Mail-Benachrichtigung für eine erfolgreiche Zahlung
     */
    private function sendEmailNotification(array $personalData, $paymentInfo, array $productData, ?string $downloadLink = null): bool
    {
        try {
            // Erweitertes Logging für die Fehlersuche
            $this->logger->debug('sendEmailNotification wurde aufgerufen', [
                'context' => ContaoContext::GENERAL,
                'has_personal_data' => !empty($personalData),
                'payment_info_type' => is_object($paymentInfo) ? get_class($paymentInfo) : 'unknown',
                'has_product_data' => !empty($productData),
                'has_download_link' => !empty($downloadLink),
                'admin_template' => $productData['admin_template'] ?? 'nicht gesetzt',
                'user_template' => $productData['user_template'] ?? 'nicht gesetzt'
            ]);

            // Eingabevalidierung - wichtige Daten prüfen
            if (empty($personalData['email'])) {
                $this->logger->error('E-Mail-Benachrichtigung kann nicht gesendet werden: Keine E-Mail-Adresse vorhanden');
                return false;
            }

            // Preisdaten formatieren - abhängig davon, ob ein PaymentIntent oder eine Session übergeben wurde
            if ($paymentInfo instanceof \Stripe\PaymentIntent) {
                $amount = $paymentInfo->amount / 100;
                $currency = strtoupper($paymentInfo->currency);
                $orderId = $paymentInfo->id;
            } elseif ($paymentInfo instanceof \Stripe\Checkout\Session) {
                $amount = $paymentInfo->amount_total / 100;
                $currency = strtoupper($paymentInfo->currency);
                $orderId = $paymentInfo->payment_intent ?? $paymentInfo->id;
            } else {
                // Fallback, wenn weder PaymentIntent noch Session
                $amount = $productData['price'] / 100;
                $currency = strtoupper($productData['currency'] ?? 'EUR');
                $orderId = uniqid('order_');
            }
            
            // Rechnungsdaten für die E-Mail-Benachrichtigung vorbereiten
            $invoiceData = null;
            if (isset($paymentInfo->invoice_data)) {
                // Wenn wir bereits formatierte Rechnungsdaten haben
                $invoiceData = $paymentInfo->invoice_data;
                $this->logger->info('Formatierte Rechnungsdaten für E-Mail gefunden', [
                    'invoice_id' => $invoiceData['invoice_id'] ?? 'Unbekannt'
                ]);
            } elseif (isset($paymentInfo->invoice) && is_string($paymentInfo->invoice)) {
                // Wenn wir nur eine Rechnungs-ID haben, versuche die Rechnung zu laden
                try {
                    $invoice = \Stripe\Invoice::retrieve($paymentInfo->invoice);
                    $invoiceData = [
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->number,
                        'invoice_url' => $invoice->hosted_invoice_url,
                        'invoice_pdf' => $invoice->invoice_pdf,
                        'invoice_date' => date('Y-m-d', $invoice->created),
                        'invoice_total' => $invoice->total / 100,
                        'invoice_currency' => strtoupper($invoice->currency)
                    ];
                    $this->logger->info('Rechnungsdaten für E-Mail geladen', [
                        'invoice_id' => $invoice->id
                    ]);
                } catch (\Exception $e) {
                    $this->logger->warning('Fehler beim Laden der Rechnungsdaten für E-Mail: ' . $e->getMessage());
                }
            }
            
            // Erstelle Download-Informationen, falls ein Download-Link vorhanden ist
            $downloadData = null;
            if ($downloadLink) {
                $expiryDate = new \DateTime();
                $expiryDate->add(new \DateInterval('P' . ($productData['download_expires'] ?? 7) . 'D'));
                
                $downloadData = [
                    'download_url' => $downloadLink,
                    'expires_date' => $expiryDate->format('d.m.Y'),
                    'download_limit' => $productData['download_limit'] ?? 3
                ];
            }
            
            // E-Mail-Daten vorbereiten
            $emailData = [
                'order_id' => $orderId,
                'product_name' => $productData['title'] ?? 'Produkt',
                'product_price' => $amount . ' ' . $currency,
                'customer_name' => trim(($personalData['firstname'] ?? '') . ' ' . ($personalData['lastname'] ?? '')),
                'customer_email' => $personalData['email'],
                'download_expires' => $productData['download_expires'] ?? 7,
                'download_limit' => $productData['download_limit'] ?? 3,
                'download_link' => $downloadLink ?? ''
            ];
            
            // Rechnungsdaten zum E-Mail-Array hinzufügen, wenn verfügbar
            if ($invoiceData) {
                $emailData['invoice_id'] = $invoiceData['invoice_id'] ?? '';
                $emailData['invoice_number'] = $invoiceData['invoice_number'] ?? '';
                $emailData['invoice_url'] = $invoiceData['invoice_url'] ?? '';
                $emailData['invoice_pdf'] = $invoiceData['invoice_pdf'] ?? '';
                $emailData['invoice_date'] = $invoiceData['invoice_date'] ?? '';
                $emailData['has_invoice'] = 'true';
                
                $this->logger->info('Rechnungsdaten zu E-Mail-Benachrichtigung hinzugefügt', [
                    'invoice_id' => $invoiceData['invoice_id'] ?? '',
                    'invoice_url' => $invoiceData['invoice_url'] ?? ''
                ]);
            } else {
                $emailData['has_invoice'] = 'false';
                $this->logger->info('Keine Rechnungsdaten für E-Mail-Benachrichtigung verfügbar');
            }
            
            // Download-Daten zum E-Mail-Array hinzufügen, wenn verfügbar
            if ($downloadData) {
                $emailData['download_url'] = $downloadData['download_url'];
                $emailData['download_expires_date'] = $downloadData['expires_date'];
                $emailData['has_download'] = 'true';
            } else {
                $emailData['has_download'] = 'false';
            }
            
            $this->logger->info('E-Mail-Daten vorbereitet', [
                'customer_email' => $personalData['email'],
                'product_name' => $emailData['product_name'],
                'has_download_link' => !empty($downloadLink)
            ]);
            
            // Prüfen, ob spezifische E-Mail-Templates aus den Produktdaten angegeben wurden
            $adminTemplate = $productData['admin_template'] ?? null;
            $userTemplate = $productData['user_template'] ?? null;
            $senderEmail = $productData['sender_email'] ?? 'shop@vossmedien.de';
            $adminEmail = $productData['admin_email'] ?? 'info@vossmedien.de';
            
            // Ausführliche Protokollierung der Template-Informationen
            $this->logger->info('E-Mail-Template-Informationen gefunden:', [
                'admin_template' => $adminTemplate,
                'user_template' => $userTemplate,
                'sender_email' => $senderEmail,
                'admin_email' => $adminEmail
            ]);
            
            // SCHRITT 1: Sende Kunde Bestätigungs-E-Mail mit dem angegebenen Template
            if ($userTemplate) {
                $this->logger->info('Sende Kunden-E-Mail mit Template: ' . $userTemplate);
                $sent = $this->sendTemplateEmail(
                    $personalData['email'],
                    'Bestellbestätigung: ' . $productData['title'],
                    $userTemplate,
                    $emailData,
                    $senderEmail,
                    'Vossmedien Shop'
                );
                
                if (!$sent) {
                    $this->logger->error('Fehler beim Senden der Kunden-E-Mail mit Template: ' . $userTemplate);
                }
            } else {
                $this->logger->info('Kein Kunden-E-Mail-Template angegeben, verwende Standard-E-Mail');
                // Fallback: Verwende die alte Methode mit Standard-Template
                $sent = $this->sendStandardEmail(
                    $personalData['email'],
                    'Bestellbestätigung: ' . $productData['title'],
                    $emailData,
                    $senderEmail,
                    'Vossmedien Shop'
                );
            }
            
            // SCHRITT 2: Sende Admin Benachrichtigungs-E-Mail mit dem angegebenen Template
            if ($adminTemplate && $adminEmail) {
                $this->logger->info('Sende Admin-E-Mail mit Template: ' . $adminTemplate);
                $sent = $this->sendTemplateEmail(
                    $adminEmail,
                    'Neue Bestellung: ' . $productData['title'],
                    $adminTemplate,
                    $emailData,
                    $senderEmail,
                    'Vossmedien Shop'
                );
                
                if (!$sent) {
                    $this->logger->error('Fehler beim Senden der Admin-E-Mail mit Template: ' . $adminTemplate);
                }
            } else if ($adminEmail) {
                $this->logger->info('Kein Admin-E-Mail-Template angegeben, verwende Standard-E-Mail');
                // Sende einfache Benachrichtigung an Admin
                $sent = $this->sendStandardEmail(
                    $adminEmail,
                    'Neue Bestellung: ' . $productData['title'],
                    $emailData,
                    $senderEmail,
                    'Vossmedien Shop'
                );
            }
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Senden der E-Mail-Benachrichtigung: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
    
    /**
     * Sendet eine E-Mail mit einem bestimmten Template
     */
    private function sendTemplateEmail(string $to, string $subject, string $template, array $data, string $from = 'shop@vossmedien.de', string $fromName = 'Shop'): bool
    {
        try {
            $this->logger->info('Bereite Template-E-Mail vor', [
                'to' => $to,
                'subject' => $subject,
                'template' => $template
            ]);
            
            // Optionen für die Template-Suche
            $templatePath = $template;
            if (!str_ends_with($templatePath, '.html5') && !str_ends_with($templatePath, '.html.twig')) {
                $templatePath .= '.html5';
            }
            
            $config = \Contao\System::getContainer()->getParameter('kernel.project_dir');
            
            // Mögliche Pfade für das Template
            $templatePaths = [
                $config . '/templates/emails/' . $templatePath,
                $config . '/templates/' . $templatePath,
                $config . '/vendor/vsm/vsm-helper-tools/templates/emails/' . $templatePath,
                $config . '/vendor/vsm/vsm-helper-tools/templates/' . $templatePath
            ];
            
            $templateContent = null;
            
            // Template-Datei finden
            foreach ($templatePaths as $path) {
                $this->logger->info('Suche Template in: ' . $path);
                if (file_exists($path)) {
                    $templateContent = file_get_contents($path);
                    $this->logger->info('Template gefunden: ' . $path);
                    break;
                }
            }
            
            if (!$templateContent) {
                $this->logger->error('Kein Template in den angegebenen Pfaden gefunden für: ' . $template);
                
                // Über den EmailHelper versuchen (nur wenn er existiert)
                if (class_exists('\\Vsm\\VsmHelperTools\\Helper\\EmailHelper')) {
                    $templateContent = \Vsm\VsmHelperTools\Helper\EmailHelper::loadEmailTemplate($template);
                    if ($templateContent) {
                        $this->logger->info('Template über EmailHelper geladen');
                    } else {
                        $this->logger->error('Template konnte auch nicht über EmailHelper geladen werden');
                        return false;
                    }
                } else {
                    return false;
                }
            }
            
            // Token in Template ersetzen
            foreach ($data as $key => $value) {
                $templateContent = str_replace('##' . $key . '##', $value, $templateContent);
            }
            
            // E-Mail senden
            $email = new \Contao\Email();
            $email->from = $from;
            $email->fromName = $fromName;
            $email->subject = $subject;
            $email->html = $templateContent;
            $email->text = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $templateContent));
            
            $email->sendTo($to);
            
            $this->logger->info('E-Mail erfolgreich gesendet', [
                'to' => $to,
                'subject' => $subject,
                'template' => $template
            ]);
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Senden der Template-E-Mail: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }
    
    /**
     * Sendet eine Standard-E-Mail ohne spezifisches Template
     */
    private function sendStandardEmail(string $to, string $subject, array $data, string $from = 'shop@vossmedien.de', string $fromName = 'Shop'): bool
    {
        try {
            $this->logger->info('Sende Standard-E-Mail', [
                'to' => $to,
                'subject' => $subject
            ]);
            
            // Einfaches HTML-Gerüst erstellen
            $html = '
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <title>' . $subject . '</title>
                </head>
                <body>
                    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                        <h1 style="color: #333;">' . $subject . '</h1>
                        <p>Vielen Dank für Ihre Bestellung!</p>
                        <div style="margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                            <h2 style="margin-top: 0;">Bestelldetails</h2>
                            <p><strong>Bestellnummer:</strong> ' . $data['order_id'] . '</p>
                            <p><strong>Produkt:</strong> ' . $data['product_name'] . '</p>
                            <p><strong>Preis:</strong> ' . $data['product_price'] . '</p>
                        </div>';
            
            // Download-Link hinzufügen, wenn vorhanden
            if (!empty($data['download_link'])) {
                $html .= '
                        <div style="margin: 20px 0; padding: 20px; background-color: #f5f5f5; border-radius: 5px;">
                            <h2 style="margin-top: 0;">Download-Informationen</h2>
                            <p>Sie können Ihre Datei hier herunterladen:</p>
                            <p><a href="' . $data['download_link'] . '" style="display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;">Download starten</a></p>
                            <p><small>Der Download-Link ist ' . $data['download_expires'] . ' Tage gültig und kann maximal ' . $data['download_limit'] . ' Mal verwendet werden.</small></p>
                        </div>';
            }
            
            // Rechnungslink hinzufügen, wenn vorhanden
            if (!empty($data['invoice_url'])) {
                $html .= '
                        <div style="margin: 20px 0; padding: 20px; background-color: #f5f5f5; border-radius: 5px;">
                            <h2 style="margin-top: 0;">Rechnung</h2>
                            <p>Ihre Rechnung können Sie hier einsehen:</p>
                            <p><a href="' . $data['invoice_url'] . '" style="display: inline-block; padding: 10px 20px; background-color: #28a745; color: white; text-decoration: none; border-radius: 5px;">Rechnung ansehen</a></p>
                        </div>';
            }
            
            $html .= '
                        <p style="margin-top: 30px;">Bei Fragen stehen wir Ihnen gerne zur Verfügung.</p>
                        <p>Mit freundlichen Grüßen,<br>Ihr Shop-Team</p>
                    </div>
                </body>
                </html>
            ';
            
            // E-Mail senden
            $email = new \Contao\Email();
            $email->from = $from;
            $email->fromName = $fromName;
            $email->subject = $subject;
            $email->html = $html;
            $email->text = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html));
            
            $email->sendTo($to);
            
            $this->logger->info('Standard-E-Mail erfolgreich gesendet', [
                'to' => $to,
                'subject' => $subject
            ]);
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Senden der Standard-E-Mail: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }
    
    /**
     * Fügt fehlende Spalten zur Session-Tabelle hinzu
     */
    private function addMissingSessionColumns(): void
    {
        try {
            $this->logger->info('Versuche fehlende Spalten zur Session-Tabelle hinzuzufügen');
            
            // Prüfen und Hinzufügen der Spalte admin_template
            $this->addColumnIfNotExists('tl_stripe_checkout_sessions', 'admin_template', "varchar(255) NULL");
            
            // Prüfen und Hinzufügen der Spalte user_template
            $this->addColumnIfNotExists('tl_stripe_checkout_sessions', 'user_template', "varchar(255) NULL");
            
            // Prüfen und Hinzufügen der Spalte sender_email
            $this->addColumnIfNotExists('tl_stripe_checkout_sessions', 'sender_email', "varchar(255) NULL");
            
            // Prüfen und Hinzufügen der Spalte admin_email
            $this->addColumnIfNotExists('tl_stripe_checkout_sessions', 'admin_email', "varchar(255) NULL");
            
            $this->logger->info('Fehlende Spalten wurden erfolgreich hinzugefügt');
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Hinzufügen fehlender Spalten: ' . $e->getMessage(), [
                'context' => ContaoContext::ERROR,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Fügt eine Spalte zur Tabelle hinzu, wenn sie nicht existiert
     */
    private function addColumnIfNotExists(string $table, string $column, string $definition): void
    {
        try {
            $columnsResult = $this->db->fetchAllAssociative("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
            
            if (empty($columnsResult)) {
                $this->db->executeStatement("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
                $this->logger->info("Spalte {$column} zur Tabelle {$table} hinzugefügt");
            } else {
                $this->logger->info("Spalte {$column} existiert bereits in Tabelle {$table}");
            }
        } catch (\Exception $e) {
            $this->logger->error("Fehler beim Hinzufügen der Spalte {$column}: " . $e->getMessage());
            throw $e;
        }
    }
} 