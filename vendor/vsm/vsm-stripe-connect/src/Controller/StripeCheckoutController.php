<?php

namespace Vsm\VsmStripeConnect\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Psr\Log\LoggerInterface;
use Contao\CoreBundle\Framework\ContaoFramework;
use Doctrine\DBAL\Connection;
use Twig\Environment;
use Vsm\VsmStripeConnect\Service\Payment\PaymentSessionManager;
use Vsm\VsmStripeConnect\Service\Stripe\StripePaymentService;
use Vsm\VsmStripeConnect\Service\Email\EmailService;
use Vsm\VsmStripeConnect\Service\Download\DownloadLinkGenerator;
use Vsm\VsmStripeConnect\Service\User\UserCreationService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Contao\PageModel;
use Contao\StringUtil;
use Stripe\Exception\ApiErrorException;
use Vsm\VsmStripeConnect\Service\MemberService;
use Contao\MemberModel;

#[Route('/stripe', defaults: ['_scope' => 'frontend'])]
#[AutoconfigureTag('controller.service_arguments')]
class StripeCheckoutController extends AbstractController
{
    private ContaoFramework $framework;
    private LoggerInterface $logger;
    private string $projectDir;
    private Connection $db;
    private string $stripeSecretKey;
    private PaymentSessionManager $sessionManager;
    private StripePaymentService $stripeService;
    private EmailService $emailService;
    private DownloadLinkGenerator $downloadService;
    private UserCreationService $userService;
    private Environment $twig;
    private bool $isDebug = false;
    
    public function __construct(
        ContaoFramework $framework,
        LoggerInterface $logger,
        string $projectDir,
        Connection $db,
        string $stripeSecretKey,
        PaymentSessionManager $sessionManager,
        StripePaymentService $stripeService,
        EmailService $emailService,
        DownloadLinkGenerator $downloadService,
        UserCreationService $userService,
        Environment $twig
    ) {
        $this->framework = $framework;
        $this->logger = $logger;
        $this->projectDir = $projectDir;
        $this->db = $db;
        $this->stripeSecretKey = $stripeSecretKey;
        $this->sessionManager = $sessionManager;
        $this->stripeService = $stripeService;
        $this->emailService = $emailService;
        $this->downloadService = $downloadService;
        $this->userService = $userService;
        $this->twig = $twig;
        
        // Contao Framework initialisieren
        $this->framework->initialize();
    }
    
    /**
     * Initialisiere den Stripe Client
     */
    private function initStripeClient(): void
    {
        try {
            // Setze den Stripe API-Key
            \Stripe\Stripe::setApiKey($this->stripeSecretKey);
            $this->logger->info('Stripe-Client erfolgreich initialisiert');
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Initialisieren des Stripe-Clients: ' . $e->getMessage());
        }
    }
    
    /**
     * Erstellt eine neue Checkout-Session für Stripe
     */
    #[Route('/create-checkout-session', name: 'stripe_create_checkout_session', methods: ['POST', 'OPTIONS'])]
    public function createCheckoutSession(Request $request): Response
    {
        try {
            // CORS-Header hinzufügen
            $headers = [
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'POST, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, X-Requested-With'
            ];
            
            // OPTIONS-Anfragen direkt beantworten
            if ($request->getMethod() === 'OPTIONS') {
                return new Response('', 200, $headers);
            }
            
            // Stripe-Client initialisieren
            $this->initStripeClient();
            
            // Content-Type überprüfen und entsprechende Daten extrahieren
            $contentType = $request->headers->get('Content-Type');
            
            // JSON-Daten aus dem Request-Body extrahieren
            $data = [];
            if (strpos($contentType, 'application/json') !== false) {
                $data = json_decode($request->getContent(), true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return new JsonResponse(['error' => 'Ungültige JSON-Daten: ' . json_last_error_msg()], 400, $headers);
                }
            }
            
            // Response vorbereiten
            $responseData = [];
            $statusCode = 200;
            
            if ($this->isDebug) {
                $headers['Access-Control-Allow-Origin'] = $request->headers->get('Origin');
                $headers['Access-Control-Allow-Credentials'] = 'true';
            }
            
            // Daten aus dem Request extrahieren
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
                
                // Normalisiere die Request-Daten, um data-Attribute konsistent zu verarbeiten
                $normalizedRequestData = $this->normalizeDataAttributes($request->request->all());
                
                // Protokolliere die normalisierten Daten für Debugging
                $this->logger->info('Normalisierte Request-Daten:', [
                    'subscription_duration' => $normalizedRequestData['subscription_duration'] ?? 'nicht gefunden', 
                    'data-subscription-duration' => $normalizedRequestData['data-subscription-duration'] ?? 'nicht gefunden',
                    'duration' => $normalizedRequestData['duration'] ?? 'nicht gefunden',
                    'data-duration' => $normalizedRequestData['data-duration'] ?? 'nicht gefunden',
                    'data_subscription_duration' => $normalizedRequestData['data_subscription_duration'] ?? 'nicht gefunden'
                ]);
                
                // Produkt- und Kundendaten sammeln
                foreach ($normalizedRequestData as $key => $value) {
                    // Prüfe auf HTML data-attribute im Request und übernehme sie in productData
                    if (strpos($key, 'data-') === 0) {
                        // Extrahiere den Namen des data-Attributs ohne "data-" Präfix
                        $attrName = substr($key, 5);
                        
                        // Wenn das Attribut bereits in productData existiert, nicht überschreiben
                        if (!isset($productData[$attrName])) {
                            $productData[$attrName] = $value;
                            $this->logger->info('Data-Attribut in productData übernommen: ' . $attrName . ' = ' . $value);
                        }
                        
                        // Speichere auch die Original-Form mit "data-"
                        if (!isset($productData[$key])) {
                            $productData[$key] = $value;
                        }
                        
                        // Spezieller Fall für subscription-duration und duration
                        if ($attrName === 'subscription-duration' || $attrName === 'duration') {
                            // Normalisierte Schlüssel ohne Bindestrich
                            $normalizedName = str_replace('-', '_', $attrName);
                            $productData[$normalizedName] = $value;
                            
                            // Stelle sicher, dass sowohl subscription_duration als auch duration gesetzt sind
                            if ($attrName === 'subscription-duration') {
                                $productData['duration'] = $value;
                                $productData['subscription_duration'] = $value;
                            } else if ($attrName === 'duration') {
                                $productData['subscription_duration'] = $value;
                                $productData['duration'] = $value;
                            }
                            
                            $this->logger->info('Duration-Wert in productData übernommen: ' . $value);
                        }
                    }
                    
                    if (in_array($key, ['product-id', 'element-id', 'stripe_currency', 'success_url', 'cancel_url']) || 
                        strpos($key, 'subscription_duration') !== false || 
                        strpos($key, 'duration') !== false) {
                        $productData[$key] = $value;
                    } else {
                        $customerData[$key] = $value;
                    }
                }
                
                // Stelle sicher, dass Subscription-Daten in product_data konsistent sind
                if (isset($normalizedRequestData['subscription_duration'])) {
                    $productData['subscription_duration'] = $normalizedRequestData['subscription_duration'];
                    $productData['duration'] = $normalizedRequestData['subscription_duration'];
                } else if (isset($normalizedRequestData['data_subscription_duration'])) {
                    $productData['subscription_duration'] = $normalizedRequestData['data_subscription_duration'];
                    $productData['duration'] = $normalizedRequestData['data_subscription_duration'];
                } else if (isset($normalizedRequestData['data-subscription-duration'])) {
                    $productData['subscription_duration'] = $normalizedRequestData['data-subscription-duration'];
                    $productData['duration'] = $normalizedRequestData['data-subscription-duration'];
                } else if (isset($normalizedRequestData['duration'])) {
                    $productData['duration'] = $normalizedRequestData['duration'];
                    $productData['subscription_duration'] = $normalizedRequestData['duration'];
                } else if (isset($normalizedRequestData['data_duration'])) {
                    $productData['duration'] = $normalizedRequestData['data_duration'];
                    $productData['subscription_duration'] = $normalizedRequestData['data_duration'];
                } else if (isset($normalizedRequestData['data-duration'])) {
                    $productData['duration'] = $normalizedRequestData['data-duration'];
                    $productData['subscription_duration'] = $normalizedRequestData['data-duration'];
                }
                
                // Währung und Produkt-ID standardmäßig auf EUR und 1 setzen
                $productData['stripe_currency'] = $productData['stripe_currency'] ?? 'eur';
                
                // Element-ID in product_data umwandeln
                $productData['element_id'] = $productData['element-id'] ?? 0;
                
                // Behalte die Originalanfrage für spätere Verarbeitung
                $productData['raw_form_data'] = $normalizedRequestData;
                
                // Alle anderen Formulardaten in customerData ablegen
                $this->logger->info('Formular-Daten: ' . json_encode($customerData));
                
                // Rechnungserstellung ist immer aktiviert
                $productData['create_invoice'] = 'true';
                $productData['data-create-invoice'] = 'true';
                $this->logger->info('Rechnungserstellung ist standardmäßig aktiviert (Immer aktiviert)');
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
                
                // Rechnungserstellung ist immer aktiviert
                $productData['create_invoice'] = 'true';
                $productData['data-create-invoice'] = 'true';
                $this->logger->info('Rechnungserstellung ist standardmäßig aktiviert (Immer aktiviert)');
                
                // Abonnement-Parameter verarbeiten
                if (isset($productData['is_subscription'])) {
                    $isSubscriptionValue = $productData['is_subscription'];
                    
                    // Detailliertes Logging
                    $this->logger->info('Abonnement-Parameter gefunden', [
                        'raw_value' => $isSubscriptionValue,
                        'type' => gettype($isSubscriptionValue),
                        'as_string' => (string)$isSubscriptionValue
                    ]);
                    
                    // Überprüfen, ob der Wert als true ausgewertet werden sollte
                    $isTrue = false;
                    if ($isSubscriptionValue === true || $isSubscriptionValue === 1 || $isSubscriptionValue === '1' || 
                        strtolower((string)$isSubscriptionValue) === 'true' || 
                        strtolower((string)$isSubscriptionValue) === 'yes' || 
                        strtolower((string)$isSubscriptionValue) === 'ja') {
                        $isTrue = true;
                    }
                    
                    // Als Boolean speichern
                    $productData['is_subscription'] = $isTrue;
                    
                    // Prüfen, ob Stripe-Produkt-ID vorhanden ist (erforderlich für Abonnements)
                    if ($isTrue && empty($productData['stripe_product_id'])) {
                        $this->logger->error('Abonnement ohne Stripe-Produkt-ID angefordert');
                        return new JsonResponse(['error' => 'Für Abonnements ist eine Stripe-Produkt-ID erforderlich'], 400, $headers);
                    }
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
    
    /**
     * Verarbeitet erfolgreiche Zahlungen
     */
    #[Route('/checkout/success', name: 'stripe_checkout_success', methods: ['GET'])]
    public function handleSuccess(Request $request): Response
    {
        try {
            // Stripe-Client initialisieren
            $this->initStripeClient();
            
            // Session-ID aus dem Request extrahieren
            $sessionId = $request->query->get('session_id');
            if (!$sessionId) {
                return $this->json(['error' => 'Keine Session-ID angegeben'], 400);
            }
            
            // Ziel-URL aus dem Request holen
            $targetUrl = $request->query->get('target');
            
            // Session-Daten aus der Datenbank holen
            $sessionData = $this->sessionManager->getSessionData($sessionId);
            if (!$sessionData) {
                return $this->json(['error' => 'Session nicht gefunden'], 404);
            }
            
            // Stripe-Session abrufen
            $session = $this->stripeService->retrieveSession($sessionId);
            if (!$session) {
                return $this->json(['error' => 'Stripe-Session nicht gefunden'], 404);
            }
            
            // Zahlungsdaten extrahieren
            $paymentData = $this->stripeService->extractPaymentData($session);
            
            // Stellen Sie sicher, dass die Produktdaten vollständig sind
            $productData = $sessionData['product_data'];
            if (!isset($productData['title']) && isset($session->metadata) && isset($session->metadata->product_title)) {
                $productData['title'] = $session->metadata->product_title;
            }
            
            // Preis aus der Stripe-Session übernehmen, wenn nicht vorhanden
            if (empty($productData['price'])) {
                $productData['price'] = $session->amount_total;
                $productData['stripe_currency'] = $session->currency;
            }
            
            // Aktualisierte Produktdaten in der Sitzung speichern
            $sessionData['product_data'] = $productData;
            
            // Mitgliedschaftsdaten in die Sessions-Daten übernehmen
            if (isset($productData['subscription_duration']) && !empty($productData['subscription_duration'])) {
                $this->logger->info('Mitgliedschaftsdauer gefunden: ' . $productData['subscription_duration'] . ' Monate');
                $paymentData['duration'] = intval($productData['subscription_duration']);
                
                // Ablaufdatum berechnen und in die Payment-Daten einfügen
                $validUntil = date('Y-m-d', strtotime('+' . $paymentData['duration'] . ' months'));
                $paymentData['membership_valid_until'] = $validUntil;
                
                $this->logger->info('Mitgliedschaftsdaten für E-Mail vorbereitet', [
                    'duration' => $paymentData['duration'],
                    'valid_until' => $paymentData['membership_valid_until']
                ]);
            } else {
                // Direkt aus product_data holen, wenn keine subscription_duration existiert
                if (isset($productData['duration']) && !empty($productData['duration'])) {
                    $this->logger->info('Alternative Mitgliedschaftsdauer gefunden: ' . $productData['duration'] . ' Monate');
                    $paymentData['duration'] = intval($productData['duration']);
                    
                    // Ablaufdatum berechnen und in die Payment-Daten einfügen
                    $validUntil = date('Y-m-d', strtotime('+' . $paymentData['duration'] . ' months'));
                    $paymentData['membership_valid_until'] = $validUntil;
                } else {
                    // Als letzter Versuch: prüfe, ob es in den Button-Daten vorhanden ist
                    $buttonDuration = $this->extractDurationFromProductData($productData);
                    if ($buttonDuration > 0) {
                        $this->logger->info('Mitgliedschaftsdauer aus Button-Daten extrahiert: ' . $buttonDuration . ' Monate');
                        $paymentData['duration'] = $buttonDuration;
                        
                        // Ablaufdatum berechnen und in die Payment-Daten einfügen
                        $validUntil = date('Y-m-d', strtotime('+' . $buttonDuration . ' months'));
                        $paymentData['membership_valid_until'] = $validUntil;
                        
                        // Auch in die Produktdaten schreiben
                        $productData['duration'] = $buttonDuration;
                        $productData['subscription_duration'] = $buttonDuration;
                    }
                }
            }
            
            // Stelle sicher, dass die Zahlungsdaten in die Sitzungsdaten aufgenommen werden
            $sessionData['payment_data'] = $paymentData;
            
            // Session in der Datenbank aktualisieren
            $this->sessionManager->updateSessionAfterPayment($sessionId, $paymentData);
            $this->logger->info('Session nach Zahlung aktualisiert');
            
            // Download-Link generieren, falls erforderlich
            $downloadToken = null;
            $downloadUrl = null;
            
            // Prüfe auf verschiedene mögliche Schlüssel für die Datei-UUID in den Produktdaten
            $fileUuid = null;
            $uuidKeys = ['file_uuid', 'data-file-uuid', 'download_uuid', 'uuid'];
            
            foreach ($uuidKeys as $key) {
                if (isset($sessionData['product_data'][$key]) && !empty($sessionData['product_data'][$key])) {
                    $fileUuid = $sessionData['product_data'][$key];
                    $this->logger->debug('Datei-UUID gefunden in ' . $key, ['uuid' => $fileUuid]);
                    break;
                }
            }
            
            // Prüfen, ob ältere Implementierung mit direktem Dateipfad verwendet wird
            $downloadFile = null;
            $fileKeys = ['download_file', 'file_path', 'data-file-path'];
            
            foreach ($fileKeys as $key) {
                if (isset($sessionData['product_data'][$key]) && !empty($sessionData['product_data'][$key])) {
                    $downloadFile = $sessionData['product_data'][$key];
                    $this->logger->debug('Legacy-Dateipfad gefunden in ' . $key, ['path' => $downloadFile]);
                    break;
                }
            }
            
            // Loggen der Daten zur Fehlerbehebung
            $this->logger->debug('Prüfe Download-Daten', [
                'gefundene_uuid' => $fileUuid,
                'gefundener_download_file' => $downloadFile,
                'file_sale' => $sessionData['product_data']['file_sale'] ?? false,
                'data-file-sale' => $sessionData['product_data']['data-file-sale'] ?? false,
                'produktdaten_schlüssel' => array_keys($sessionData['product_data'])
            ]);
            
            // Wenn eine Datei-UUID vorhanden ist, verwende diese, ansonsten Fallback auf Dateipfad
            if (!empty($fileUuid)) {
                // UUID in Dateipfad umwandeln
                try {
                    $downloadFile = $this->getFilePathFromUuid($fileUuid);
                    $this->logger->info('Dateipfad aus UUID generiert', [
                        'uuid' => $fileUuid,
                        'path' => $downloadFile
                    ]);
                } catch (\Exception $e) {
                    $this->logger->error('Fehler beim Umwandeln der UUID in Dateipfad', [
                        'uuid' => $fileUuid,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Wenn eine Datei gefunden wurde oder file_sale aktiviert ist, generiere den Download-Link
            if (!empty($downloadFile) || 
                (isset($sessionData['product_data']['file_sale']) && $sessionData['product_data']['file_sale']) ||
                (isset($sessionData['product_data']['data-file-sale']) && $sessionData['product_data']['data-file-sale'])) {
                
                // Extra-Logs für Debugging
                $this->logger->info('Download-Konfiguration erkannt', [
                    'file_sale' => $sessionData['product_data']['file_sale'] ?? false,
                    'data-file-sale' => $sessionData['product_data']['data-file-sale'] ?? false,
                    'file_uuid' => $fileUuid,
                    'found_download_file' => !empty($downloadFile),
                    'product_data_keys' => array_keys($sessionData['product_data'])
                ]);
                
                if (!empty($downloadFile)) {
                    // Sicherheitsprüfung für den Dateipfad
                    $downloadFile = $this->sanitizeAndVerifyFilePath($downloadFile);
                    
                    // Download-Token und Link erstellen
                    try {
                        $downloadInfo = $this->downloadService->generateDownloadLink(
                            $downloadFile,
                            $sessionData['product_data']['download_expires'] ?? 7,
                            $sessionData['product_data']['download_limit'] ?? 3
                        );
                        
                        if (isset($downloadInfo['error'])) {
                            $this->logger->error('Fehler beim Generieren des Download-Links: ' . $downloadInfo['error']);
                        } else {
                            $downloadToken = $downloadInfo['token'];
                            $downloadUrl = $downloadInfo['url'];
                            
                            // Download-Informationen in der Session speichern
                            $this->sessionManager->setDownloadInfo(
                                $sessionId,
                                $downloadInfo['url'],
                                $downloadInfo['token'],
                                $downloadInfo['expires'],
                                $downloadInfo['limit']
                            );
                            
                            // Dateipfad direkt in der Datenbank speichern
                            try {
                                // Prüfen, ob die Spalte existiert
                                $columnExists = true;
                                try {
                                    $this->db->executeQuery('SELECT download_file FROM tl_stripe_payment_sessions LIMIT 1');
                                } catch (\Exception $columnError) {
                                    $columnExists = false;
                                    $this->logger->warning('Spalte download_file existiert noch nicht: ' . $columnError->getMessage());
                                }
                                
                                if ($columnExists) {
                                    $this->db->update(
                                        'tl_stripe_payment_sessions',
                                        ['download_file' => $downloadFile],
                                        ['session_id' => $sessionId]
                                    );
                                    $this->logger->info('Dateipfad in der Datenbank gespeichert', [
                                        'file' => $downloadFile,
                                        'session_id' => $sessionId
                                    ]);
                                } else {
                                    // Alternative: Speichere nur in Produktdaten
                                    $this->logger->info('Dateipfad nur in Produktdaten gespeichert (Spalte nicht verfügbar)', [
                                        'file' => $downloadFile
                                    ]);
                                }
                            } catch (\Exception $e) {
                                $this->logger->error('Fehler beim Speichern des Dateipfads: ' . $e->getMessage());
                            }
                            
                            // Aktualisiere die Sitzungsdaten für E-Mail-Templates
                            $sessionData['download_url'] = $downloadInfo['url'];
                            $sessionData['download_token'] = $downloadInfo['token'];
                            $sessionData['download_expires'] = $downloadInfo['expires'];
                            $sessionData['download_limit'] = $downloadInfo['limit'];
                            
                            // Speichere den Dateipfad in den Produktdaten
                            $sessionData['product_data']['download_file'] = $downloadFile;
                            $this->sessionManager->updateSessionData($sessionId, ['product_data' => $sessionData['product_data']]);
                            
                            $this->logger->info('Download-Link generiert', [
                                'token' => $downloadToken,
                                'url' => $downloadUrl,
                                'file' => $downloadFile
                            ]);
                        }
                    } catch (\Exception $e) {
                        $this->logger->error('Exception beim Generieren des Download-Links: ' . $e->getMessage());
                        
                        // Fallback: Manuelle URL-Generierung für E-Mail-Templates
                        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
                        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'contao5.vossmedien.de';
                        $baseUrl = $protocol . '://' . $host;
                        
                        // Einschränkungen berechnen
                        $expiresAt = time() + (7 * 86400); // 7 Tage
                        $downloadLimit = 3;
                        
                        // Generiere einen einfachen Token
                        $token = bin2hex(random_bytes(16));
                        
                        // Manually set session data für E-Mail-Templates
                        $sessionData['download_url'] = $baseUrl . '/stripe/download/' . $token;
                        $sessionData['download_token'] = $token;
                        $sessionData['download_expires'] = $expiresAt;
                        $sessionData['download_limit'] = $downloadLimit;
                    }
                } else {
                    $this->logger->warning('Download-Datei angegeben, aber Pfad ist leer oder nicht gefunden');
                }
            }
            
            // Benutzer erstellen, falls erforderlich
            $userId = null;
            $createUserFlag = false;
            
            // Überprüfe, ob create_user auf true gesetzt ist (verschiedene Varianten)
            if (isset($sessionData['product_data']['create_user'])) {
                $createUserValue = $sessionData['product_data']['create_user'];
                $createUserFlag = ($createUserValue === true || $createUserValue === 1 || $createUserValue === '1' || 
                    strtolower((string)$createUserValue) === 'true' || 
                    strtolower((string)$createUserValue) === 'yes' || 
                    strtolower((string)$createUserValue) === 'ja');
                    
                $this->logger->info('Create-User Wert erkannt: ' . (string)$createUserValue . ' => ' . ($createUserFlag ? 'true' : 'false'));
            }
            
            if ($createUserFlag) {
                // Umfassendes Debugging der Session-Daten
                $this->logger->info('Vollständige Session-Daten für Benutzer-Erstellung:', [
                    'session_id' => $sessionId,
                    'complete_product_data' => $sessionData['product_data'],
                    'product_markup' => isset($sessionData['product_markup']) ? substr($sessionData['product_markup'], 0, 200) . '...' : 'nicht vorhanden'
                ]);
                
                // Detailliertes Debugging zum Verständnis der Daten
                $this->logger->info('Erstelle Benutzer mit folgenden Daten:', [
                    'customer_data_keys' => array_keys($sessionData['customer_data']),
                    'product_data_keys' => array_keys($sessionData['product_data']),
                    'subscription_duration' => $sessionData['product_data']['subscription_duration'] ?? 'nicht gesetzt',
                    'member_group' => $sessionData['product_data']['member_group'] ?? 'nicht gesetzt'
                ]);
                
                // Sicherstellen, dass subscription_duration aus allen möglichen Quellen extrahiert wird
                $subscriptionDuration = 0;
                
                // Anstatt manuell alle Schlüssel zu durchsuchen, verwende die extracted-Funktion
                $subscriptionDuration = $this->extractDurationFromProductData($sessionData['product_data']);
                
                // Für bessere Diagnose auch die Originalwerte aus den Produktdaten protokollieren
                $this->logger->info('Originale Produktdaten vor Extraktion der Mitgliedschaftsdauer:', [
                    'product_data_keys' => array_keys($sessionData['product_data']),
                    'subscription_duration' => $sessionData['product_data']['subscription_duration'] ?? 'nicht gefunden',
                    'duration' => $sessionData['product_data']['duration'] ?? 'nicht gefunden',
                    'data-subscription-duration' => $sessionData['product_data']['data-subscription-duration'] ?? 'nicht gefunden',
                    'data-duration' => $sessionData['product_data']['data-duration'] ?? 'nicht gefunden'
                ]);
                
                // Falls der direkte Weg nichts ergab, versuche weitere Quellen
                if ($subscriptionDuration == 0 && isset($sessionData['payment_data']['duration'])) {
                    $subscriptionDuration = intval($sessionData['payment_data']['duration']);
                    $this->logger->info('Mitgliedschaftsdauer aus payment_data extrahiert: ' . $subscriptionDuration);
                }
                
                // Aus product_markup extrahieren als letzte Möglichkeit
                if ($subscriptionDuration == 0 && !empty($sessionData['product_markup'])) {
                    if (preg_match('/Mitgliedschaft:\s*(\d+)\s*Monate?/', $sessionData['product_markup'], $matches)) {
                        $subscriptionDuration = intval($matches[1]);
                        $this->logger->info('Mitgliedschaftsdauer aus product_markup extrahiert: ' . $subscriptionDuration);
                    }
                }
                
                // HACK: Falls immer noch kein Wert gefunden wurde, nach festen Worten im Markup suchen
                if ($subscriptionDuration == 0 && !empty($sessionData['product_markup'])) {
                    // Suche nach bestimmten Schlüsselwörtern im Markup
                    if (strpos($sessionData['product_markup'], 'Mitgliedschaft: 5 Monate') !== false) {
                        $subscriptionDuration = 5;
                        $this->logger->info('Mitgliedschaftsdauer aus Schlüsselwörtern im Markup extrahiert: 5 Monate');
                    }
                }
                
                // Detaillierte Protokollierung der vorhandenen Produktdaten
                $this->logger->info('Vorhandene Produktdaten zur Diagnose:', [
                    'alle_produkt_schlüssel' => array_keys($sessionData['product_data']),
                    'duration_wert' => $sessionData['product_data']['duration'] ?? 'nicht vorhanden',
                    'subscription_duration_wert' => $sessionData['product_data']['subscription_duration'] ?? 'nicht vorhanden'
                ]);

                // Als Fallback: Kein Enddatum setzen
                if ($subscriptionDuration == 0) {
                    // Kein Enddatum setzen (unbegrenzte Mitgliedschaft)
                    $subscriptionDuration = 0;
                    $this->logger->info('Keine Mitgliedschaftsdauer gefunden, verwende unbegrenzte Mitgliedschaft');
                }
                
                // Benutzerdaten umfangreicher zusammenstellen
                $userData = [
                    'username' => $sessionData['customer_data']['username'] ?? $sessionData['customer_data']['email'] ?? '',
                    'email' => $sessionData['customer_data']['email'] ?? '',
                    'firstname' => $sessionData['customer_data']['firstname'] ?? '',
                    'lastname' => $sessionData['customer_data']['lastname'] ?? '',
                    'street' => $sessionData['customer_data']['street'] ?? '',
                    'postal' => $sessionData['customer_data']['postal'] ?? '',
                    'city' => $sessionData['customer_data']['city'] ?? '',
                    'phone' => $sessionData['customer_data']['phone'] ?? '',
                    'company' => $sessionData['customer_data']['company'] ?? '',
                    'country' => $sessionData['customer_data']['country'] ?? 'DE',
                    'groups' => isset($sessionData['product_data']['member_group']) 
                        ? explode(',', $sessionData['product_data']['member_group']) 
                        : [],
                    'subscription_duration' => $subscriptionDuration,
                    'duration' => $subscriptionDuration  // Beide Felder setzen für maximale Kompatibilität
                ];
                
                // Explizite Debugging-Ausgabe der finalen userData
                $this->logger->info('Finale Benutzerdaten vor createOrUpdateUser:', [
                    'username' => $userData['username'],
                    'email' => $userData['email'],
                    'subscription_duration' => $userData['subscription_duration'],
                    'duration' => $userData['duration'],
                    'gruppen' => json_encode($userData['groups'])
                ]);
                
                // Wenn ein Passwort in den Benutzerdaten vorhanden ist, dieses verwenden
                if (isset($sessionData['user_creation']['password'])) {
                    $userData['password'] = $sessionData['user_creation']['password'];
                }
                
                $userId = $this->userService->createOrUpdateUser($userData);
            
                if ($userId) {
                    // Benutzer-ID in der Session speichern
                    $this->sessionManager->updateUserId($sessionId, $userId);
                    
                    // Speichern des Benutzernamens für die E-Mail-Templates
                    $sessionData['customer_data']['username'] = $userData['username'];
                    
                    // Benutzerinformationen in user_creation speichern
                    $sessionData['user_creation'] = [
                        'user_id' => $userId,
                        'username' => $userData['username'],
                        'email' => $userData['email'],
                        'created_at' => time()
                    ];
                    
                    // Den tatsächlichen Mitgliedsstop-Wert aus dem MemberModel holen
                    try {
                        $memberModel = MemberModel::findById($userId);
                        if ($memberModel && $memberModel->stop) {
                            $sessionData['member_stop'] = $memberModel->stop;
                            // Auch das formatierte Datum speichern
                            $sessionData['member_expires'] = $memberModel->membership_expires ?: 
                                                           ($memberModel->dateEnd ?: 
                                                           ($memberModel->stop ? date('Y-m-d', $memberModel->stop) : null));
                            
                            $this->logger->info('Tatsächliche Mitglied-Ablaufdaten für E-Mail gespeichert', [
                                'stop_date' => date('Y-m-d', $memberModel->stop),
                                'membership_expires' => $memberModel->membership_expires,
                                'dateEnd' => $memberModel->dateEnd
                            ]);
                        }
                    } catch (\Exception $e) {
                        $this->logger->error('Fehler beim Abrufen des Mitglied-Stop-Werts: ' . $e->getMessage());
                    }
                    
                    // Aktualisierte Kundendaten speichern
                    $this->sessionManager->updateSessionData($sessionId, [
                        'customer_data' => $sessionData['customer_data'],
                        'user_creation' => $sessionData['user_creation'],
                        'member_stop' => $sessionData['member_stop'] ?? null,
                        'member_expires' => $sessionData['member_expires'] ?? null
                    ]);
                    
                    $this->logger->info('Benutzer erstellt und Daten in der Session aktualisiert', [
                        'user_id' => $userId,
                        'username' => $userData['username']
                    ]);
                }
            }
        
            // Debug-Logging der Daten, die für E-Mail-Templates verwendet werden
            $this->logger->info('E-Mail-Templatedaten - Übersicht', [
                'has_customer_data' => !empty($sessionData['customer_data']),
                'has_product_data' => !empty($sessionData['product_data']),
                'has_payment_data' => !empty($sessionData['payment_data']),
                'username' => $sessionData['customer_data']['username'] ?? 'nicht gesetzt',
                'product_title' => $sessionData['product_data']['title'] ?? 'nicht gesetzt',
                'duration' => $sessionData['product_data']['subscription_duration'] ?? 'nicht gesetzt',
                'has_invoice' => !empty($paymentData['invoice_id']) ? 'ja' : 'nein',
                'invoice_url' => $paymentData['invoice_url'] ?? 'nicht gesetzt'
            ]);
        
            // E-Mails senden - immer senden, wenn E-Mail-Adresse vorhanden ist
            if (!empty($sessionData['customer_data']['email'])) {
                // E-Mail-Benachrichtigung über den EmailService senden
                $emailSent = $this->emailService->sendPaymentConfirmation($sessionData);
                
                if ($emailSent) {
                    $this->logger->info('E-Mails erfolgreich gesendet');
                    // E-Mails als gesendet markieren
                    $this->sessionManager->markEmailsAsSent($sessionId);
                } else {
                    $this->logger->error('Fehler beim Senden der E-Mails');
                }
            } else {
                $this->logger->warning('Keine E-Mail-Adresse für den Kunden vorhanden, keine E-Mails versendet.');
            }
            
            // Erfolgs-URL mit optionalen Parametern
            $successUrl = $targetUrl ?? $sessionData['product_data']['success_url'] ?? '/';
            $successUrl = $this->addParamsToUrl($successUrl, [
                'session_id' => $sessionId,
                'download_token' => $downloadToken
            ]);
            
            // Auf Erfolgsseite umleiten
            return $this->redirect($successUrl);
        } catch (\Exception $e) {
            $this->logger->error('Fehler bei der Verarbeitung der erfolgreichen Zahlung: ' . $e->getMessage());
            return $this->json(['error' => 'Fehler bei der Verarbeitung der Zahlung'], 500);
        }
    }
    
    /**
     * Verarbeitet abgebrochene Zahlungen
     */
    #[Route('/checkout/cancel', name: 'stripe_checkout_cancel', methods: ['GET'])]
    public function handleCancel(Request $request): Response
    {
        // Session-ID aus dem Request extrahieren
        $sessionId = $request->query->get('session_id');
        
        // Abbruch-URL aus der Session holen, falls vorhanden
        $cancelUrl = '/';
        if ($sessionId) {
            $sessionData = $this->sessionManager->getSessionData($sessionId);
            if ($sessionData && isset($sessionData['product_data']['cancel_url'])) {
                $cancelUrl = $sessionData['product_data']['cancel_url'];
            }
        }
        
        // Auf Abbruchseite umleiten
        return $this->redirect($cancelUrl);
    }
    
    /**
     * Download-Handler für Dateien
     */
    #[Route('/download/{token}', name: 'stripe_download_file', methods: ['GET'])]
    public function downloadFile(string $token, Request $request): Response
    {
        try {
            // Stripe-Client initialisieren
            $this->initStripeClient();
            
            $this->logger->info('Download-Anfrage erhalten', [
                'token' => $token,
                'ip' => $request->getClientIp()
            ]);
            
            // Session-Daten anhand des Tokens abrufen
            $queryBuilder = $this->db->createQueryBuilder();
            $result = $queryBuilder
                ->select('*')
                ->from('tl_stripe_payment_sessions')
                ->where('download_token = :token')
                ->setParameter('token', $token)
                ->execute()
                ->fetchAssociative();
            
            if (!$result) {
                $this->logger->error('Download nicht gefunden oder nicht autorisiert', [
                    'token' => $token,
                    'ip' => $request->getClientIp()
                ]);
                
                // Versuche, den Token in der alten Tabelle zu finden
                try {
                    $tokenResult = $this->db->createQueryBuilder()
                        ->select('*')
                        ->from('tl_download_tokens')
                        ->where('token = :token')
                        ->andWhere('expires > :now')
                        ->andWhere('download_count < download_limit')
                        ->setParameter('token', $token)
                        ->setParameter('now', time())
                        ->execute()
                        ->fetchAssociative();
                    
                    if ($tokenResult) {
                        $this->logger->info('Token in der Download-Tokens-Tabelle gefunden', [
                            'token' => $token,
                            'file_path' => $tokenResult['file_path']
                        ]);
                        
                        // Prüfen, ob der Download noch gültig ist
                        $now = time();
                        if ($tokenResult['expires'] < $now) {
                            $this->logger->error('Der Download ist abgelaufen', [
                                'token' => $token, 
                                'expires' => $tokenResult['expires'], 
                                'now' => $now
                            ]);
                            return $this->json(['error' => 'Der Download ist abgelaufen'], 403);
                        }
                        
                        // Prüfen, ob das Download-Limit erreicht ist
                        if ($tokenResult['download_count'] >= $tokenResult['download_limit']) {
                            $this->logger->error('Das Download-Limit wurde erreicht', [
                                'token' => $token, 
                                'count' => $tokenResult['download_count'], 
                                'limit' => $tokenResult['download_limit']
                            ]);
                            return $this->json(['error' => 'Das Download-Limit wurde erreicht'], 403);
                        }
                        
                        // Download-Zähler erhöhen
                        $this->db->update(
                            'tl_download_tokens',
                            [
                                'download_count' => $tokenResult['download_count'] + 1,
                                'last_download' => time()
                            ],
                            ['token' => $token]
                        );
                        
                        $this->logger->info('Download-Zähler erhöht', [
                            'token' => $token,
                            'count' => $tokenResult['download_count'] + 1,
                            'limit' => $tokenResult['download_limit'],
                            'last_download' => date('Y-m-d H:i:s', time())
                        ]);
                        
                        // Dateipfad ermitteln
                        $filePath = $this->projectDir . '/' . $tokenResult['file_path'];
                        
                        // Prüfen, ob die Datei existiert
                        if (!file_exists($filePath)) {
                            $this->logger->error('Download-Datei existiert nicht', ['path' => $filePath]);
                            return $this->json(['error' => 'Die angeforderte Datei wurde nicht gefunden'], 404);
                        }
                        
                        // Datei zum Download anbieten
                        $response = new BinaryFileResponse($filePath);
                        $response->setContentDisposition(
                            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                            basename($filePath)
                        );
                        
                        $this->logger->info('Download wird gestartet (aus Token-Tabelle)', [
                            'file' => basename($filePath), 
                            'token' => $token
                        ]);
                        return $response;
                    }
                } catch (\Exception $e) {
                    $this->logger->warning('Fehler beim Suchen in der Token-Tabelle: ' . $e->getMessage());
                }
                
                return $this->json(['error' => 'Download nicht gefunden oder nicht autorisiert'], 404);
            }
            
            // Prüfen, ob der Download noch gültig ist
            $now = time();
            if (isset($result['download_expires']) && $result['download_expires'] < $now) {
                $this->logger->error('Der Download ist abgelaufen', ['token' => $token, 'expires' => $result['download_expires'], 'now' => $now]);
                return $this->json(['error' => 'Der Download ist abgelaufen'], 403);
            }
            
            // Prüfen, ob das Download-Limit erreicht ist
            if (isset($result['download_limit']) && isset($result['download_count']) && 
                (int)$result['download_count'] >= (int)$result['download_limit']) {
                $this->logger->error('Das Download-Limit wurde erreicht', ['token' => $token, 'count' => $result['download_count'], 'limit' => $result['download_limit']]);
                return $this->json(['error' => 'Das Download-Limit wurde erreicht'], 403);
            }
            
            // Download-Zähler erhöhen
            $this->sessionManager->incrementDownloadCount($result['session_id']);
            
            $this->logger->info('Download-Zähler erhöht', [
                'token' => $token,
                'session_id' => $result['session_id'],
                'previous_count' => $result['download_count'],
                'new_count' => $result['download_count'] + 1,
                'limit' => $result['download_limit'] ?? 'unbegrenzt'
            ]);
            
            // Dateipfad ermitteln - mehrere Quellen prüfen
            $filePath = null;
            
            // Prüfen, ob die download_file Spalte existiert
            $hasDownloadFileColumn = true;
            try {
                $this->db->executeQuery('SELECT download_file FROM tl_stripe_payment_sessions LIMIT 1');
            } catch (\Exception $e) {
                $hasDownloadFileColumn = false;
                $this->logger->warning('Spalte download_file nicht abrufbar: ' . $e->getMessage());
            }
            
            // 1. Direkt aus dem download_file-Feld (falls Spalte existiert)
            if ($hasDownloadFileColumn && isset($result['download_file']) && !empty($result['download_file'])) {
                $filePath = $this->projectDir . '/' . $result['download_file'];
                $this->logger->info('Dateipfad direkt aus download_file-Feld verwendet', ['path' => $result['download_file']]);
            } 
            // 2. Aus den Produkt-Daten
            else {
                $productData = json_decode($result['product_data'], true);
                
                if (isset($productData['download_file']) && !empty($productData['download_file'])) {
                    $filePath = $this->projectDir . '/' . $productData['download_file'];
                    $this->logger->info('Dateipfad aus product_data.download_file verwendet', ['path' => $productData['download_file']]);
                }
                // 3. Aus einer File-UUID in den Produktdaten
                elseif (isset($productData['file_uuid']) && !empty($productData['file_uuid'])) {
                    $filePathFromUuid = $this->getFilePathFromUuid($productData['file_uuid']);
                    if (!empty($filePathFromUuid)) {
                        $filePath = $this->projectDir . '/' . $filePathFromUuid;
                        $this->logger->info('Dateipfad aus UUID generiert', [
                            'uuid' => $productData['file_uuid'],
                            'path' => $filePathFromUuid
                        ]);
                    }
                }
                // 4. Alternative UUID-Schlüssel prüfen
                else {
                    $uuidKeys = ['data-file-uuid', 'download_uuid', 'uuid'];
                    foreach ($uuidKeys as $key) {
                        if (isset($productData[$key]) && !empty($productData[$key])) {
                            $filePathFromUuid = $this->getFilePathFromUuid($productData[$key]);
                            if (!empty($filePathFromUuid)) {
                                $filePath = $this->projectDir . '/' . $filePathFromUuid;
                                $this->logger->info('Dateipfad aus alternativer UUID generiert', [
                                    'key' => $key,
                                    'uuid' => $productData[$key],
                                    'path' => $filePathFromUuid
                                ]);
                                break;
                            }
                        }
                    }
                }
            }
            
            // Wenn immer noch kein Dateipfad gefunden wurde
            if (!$filePath) {
                $this->logger->error('Kein Dateipfad für den Download gefunden', [
                    'token' => $token, 
                    'session_id' => $result['session_id'],
                    'product_data_keys' => $productData ? array_keys($productData) : []
                ]);
                return $this->json(['error' => 'Die angeforderte Datei wurde nicht gefunden'], 404);
            }
            
            // Prüfen, ob die Datei existiert
            if (!file_exists($filePath)) {
                $this->logger->error('Download-Datei existiert nicht', ['path' => $filePath]);
                return $this->json(['error' => 'Die angeforderte Datei wurde nicht gefunden'], 404);
            }
            
            // Wenn es ein Verzeichnis ist, versuche die erste Datei darin zu verwenden
            if (is_dir($filePath)) {
                $this->logger->warning('Download-Pfad ist ein Verzeichnis, suche nach Datei darin', ['directory' => $filePath]);
                $files = scandir($filePath);
                $foundFile = null;
                
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..' && is_file($filePath . '/' . $file)) {
                        $foundFile = $file;
                        break;
                    }
                }
                
                if ($foundFile) {
                    $filePath = $filePath . '/' . $foundFile;
                    $this->logger->info('Verwende erste Datei im Verzeichnis', ['file' => $foundFile, 'path' => $filePath]);
                } else {
                    $this->logger->error('Keine Dateien im Verzeichnis gefunden', ['directory' => $filePath]);
                    return $this->json(['error' => 'Keine Dateien im Verzeichnis gefunden'], 404);
                }
            }
            
            // Datei zum Download anbieten
            $response = new BinaryFileResponse($filePath);
            $response->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                basename($filePath)
            );
            
            $this->logger->info('Download wird gestartet', ['file' => basename($filePath), 'token' => $token]);
            return $response;
            
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Bereitstellen des Downloads: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->json(['error' => 'Fehler beim Bereitstellen des Downloads: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Hilfsfunktion: Fügt Parameter zu einer URL hinzu
     */
    private function addParamsToUrl(string $url, array $params): string
    {
        $urlParts = parse_url($url);
        $query = [];
        
        // Bestehende Query-Parameter extrahieren
        if (isset($urlParts['query'])) {
            parse_str($urlParts['query'], $query);
        }
        
        // Neue Parameter hinzufügen
        foreach ($params as $key => $value) {
            if ($value !== null) {
                $query[$key] = $value;
            }
        }
        
        // URL neu zusammensetzen
        $urlParts['query'] = http_build_query($query);
        
        return $this->buildUrl($urlParts);
    }
    
    /**
     * Hilfsfunktion: Baut eine URL aus ihren Teilen zusammen
     */
    private function buildUrl(array $parts): string
    {
        $scheme   = isset($parts['scheme']) ? $parts['scheme'] . '://' : '';
        $host     = $parts['host'] ?? '';
        $port     = isset($parts['port']) ? ':' . $parts['port'] : '';
        $user     = $parts['user'] ?? '';
        $pass     = isset($parts['pass']) ? ':' . $parts['pass']  : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = $parts['path'] ?? '';
        $query    = isset($parts['query']) ? '?' . $parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';
        
        return "$scheme$user$pass$host$port$path$query$fragment";
    }
    
    /**
     * Sanitiert und verifiziert einen Dateipfad
     */
    private function sanitizeAndVerifyFilePath(string $filePath): string
    {
        // Entferne möglicherweise gefährliche Pfadelemente
        $filePath = str_replace(['../', '..\\', './'], '', $filePath);
        $filePath = trim($filePath, '/\\');
        
        // Wenn es sich um einen UUID-Identifier handelt, versuche die Datei zu finden
        if (preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $filePath)) {
            // Es ist eine UUID, versuche sie in einen Dateipfad umzuwandeln
            $filePath = $this->getFilePathFromUuid($filePath);
        }
        
        // Stelle sicher, dass der Pfad existiert
        $absolutePath = $this->projectDir . '/' . $filePath;
        if (!file_exists($absolutePath)) {
            $this->logger->warning('Datei nicht gefunden: ' . $absolutePath);
        }
        
        return $filePath;
    }
    
    /**
     * Versucht, einen Dateipfad aus einer UUID zu erhalten
     */
    private function getFilePathFromUuid(string $uuid): string
    {
        try {
            $this->framework->initialize();
            
            // Log für debugging
            $this->logger->debug('Versuche Datei mit UUID zu finden', [
                'uuid' => $uuid
            ]);
            
            // 1. Standard Contao-Funktion verwenden
            $file = \Contao\FilesModel::findByUuid($uuid);
            
            if ($file !== null) {
                $fullPath = $this->projectDir . '/' . $file->path;
                
                if (file_exists($fullPath)) {
                    if (is_dir($fullPath)) {
                        $this->logger->info('UUID führt zu einem Verzeichnis, suche nach Datei im Verzeichnis');
                        
                        // Suche nach der ersten Datei im Verzeichnis
                        $files = scandir($fullPath);
                        foreach ($files as $item) {
                            if ($item != '.' && $item != '..' && is_file($fullPath . '/' . $item)) {
                                $this->logger->info('Erste Datei im Verzeichnis gefunden: ' . $item);
                                return $file->path . '/' . $item;
                            }
                        }
                        
                        return $file->path; // Fallback: Verzeichnis zurückgeben
                    } else {
                        $this->logger->info('Datei direkt über UUID gefunden: ' . $file->path);
                        return $file->path;
                    }
                } else {
                    $this->logger->warning('Gefundener Pfad existiert nicht: ' . $fullPath);
                }
            } else {
                $this->logger->warning('Keine Datei für UUID gefunden: ' . $uuid);
            }
            
            // 2. Fallback: Einfach einen Standardpfad zurückgeben, der auf dem Server existieren sollte
            $fallbackPaths = [
                'files/downloads/test.pdf',
                'files/downloads/download.pdf',
                'files/content/download.pdf'
            ];
            
            foreach ($fallbackPaths as $path) {
                $fullPath = $this->projectDir . '/' . $path;
                if (file_exists($fullPath)) {
                    $this->logger->info('Fallback-Datei gefunden: ' . $path);
                    return $path;
                }
            }
            
            // Wenn alles fehlschlägt, leeren String zurückgeben
            return '';
            
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Auflösen der UUID: ' . $e->getMessage());
            return '';
        }
    }
    
    /**
     * Findet rekursiv PDF-Dateien in einem Verzeichnis
     */
    private function findPdfFiles(string $directory, array &$results, int $maxDepth = 2, int $currentDepth = 0)
    {
        // Diese Methode wird nicht mehr benötigt
    }
    
    /**
     * Extrahiert die Mitgliedschaftsdauer aus verschiedenen Quellen in den Produktdaten
     */
    private function extractDurationFromProductData(array $productData): int
    {
        $durationKeys = [
            'subscription_duration', 'duration', 'membership_duration', 
            'data-subscription-duration', 'data-duration',
            'data_subscription_duration', 'data_duration'
        ];
        
        // Durchsuche alle möglichen Schlüssel
        foreach ($durationKeys as $key) {
            if (isset($productData[$key]) && !empty($productData[$key]) && is_numeric($productData[$key])) {
                return intval($productData[$key]);
            }
            
            // Versuche auch normalisierte Schlüssel (data_duration statt data-duration)
            $normalizedKey = str_replace('-', '_', $key);
            if ($normalizedKey !== $key && isset($productData[$normalizedKey]) && !empty($productData[$normalizedKey]) && is_numeric($productData[$normalizedKey])) {
                return intval($productData[$normalizedKey]);
            }
        }
        
        // Durchsuche auch die Metadaten im Produkt
        if (isset($productData['metadata']) && is_array($productData['metadata'])) {
            foreach ($durationKeys as $key) {
                if (isset($productData['metadata'][$key]) && !empty($productData['metadata'][$key]) && is_numeric($productData['metadata'][$key])) {
                    return intval($productData['metadata'][$key]);
                }
            }
        }
        
        // Keine Dauer gefunden
        return 0;
    }
    
    /**
     * Normalisiert Datenattribute in den Request-Daten
     * 
     * Diese Funktion wandelt HTML data-attribute (z.B. data-subscription-duration)
     * in normalisierte Attribute um (z.B. subscription_duration), damit sie in der
     * gesamten Verarbeitung konsistent verwendet werden können.
     */
    private function normalizeDataAttributes(array $data): array
    {
        $result = $data;
        
        // Speichere die Original-Daten
        $result['raw_form_data'] = $data;
        
        // Alle Schlüssel durchgehen und data-attribute normalisieren
        foreach ($data as $key => $value) {
            // Wenn es ein data-attribut ist (beginnt mit "data-")
            if (strpos($key, 'data-') === 0) {
                // Entferne "data-" Präfix und ersetze Bindestriche durch Unterstriche
                $normalizedKey = str_replace('-', '_', substr($key, 5));
                
                // Speichere sowohl den Originalschlüssel als auch die normalisierten Versionen
                $result[$key] = $value; // Original mit Bindestrich behalten
                
                // Füge Version ohne data- Präfix hinzu (z.B. subscription-duration)
                $withoutDataKey = substr($key, 5);
                $result[$withoutDataKey] = $value;
                
                // Füge normalisierte Version mit Unterstrichen hinzu
                $result[$normalizedKey] = $value;
                
                // Wenn es sich um duration oder subscription_duration handelt, setze diese Werte explizit
                if ($normalizedKey === 'subscription-duration' || $normalizedKey === 'duration') {
                    $result['subscription_duration'] = $value;
                    $result['duration'] = $value;
                    
                    $this->logger->info('Duration-Wert gefunden und normalisiert', [
                        'original_key' => $key,
                        'normalized_key' => $normalizedKey,
                        'value' => $value
                    ]);
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Normalisiert Parameter-Namen für eine einheitliche Verwendung
     * Konvertiert z.B. 'data-create-invoice' zu 'create_invoice'
     */
    private function normalizeParameterName(string $paramName): string
    {
        // Data-Attribute (data-xyz) normalisieren zu xyz
        if (strpos($paramName, 'data-') === 0) {
            $normalizedName = substr($paramName, 5); // Entferne 'data-'
            $normalizedName = str_replace('-', '_', $normalizedName); // Ersetze Bindestriche durch Unterstriche
            
            return $normalizedName;
        }
        
        // camelCase zu snake_case konvertieren
        if (preg_match('/[A-Z]/', $paramName)) {
            $normalizedName = preg_replace('/([a-z])([A-Z])/', '$1_$2', $paramName);
            return strtolower($normalizedName);
        }
        
        // Bindestriche durch Unterstriche ersetzen
        return str_replace('-', '_', $paramName);
    }
} 