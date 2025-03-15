<?php

namespace Vsm\VsmHelperTools\Controller;

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
use Vsm\VsmHelperTools\Service\Payment\PaymentSessionManager;
use Vsm\VsmHelperTools\Service\Stripe\StripePaymentService;
use Vsm\VsmHelperTools\Service\Email\EmailService;
use Vsm\VsmHelperTools\Service\Download\DownloadLinkGenerator;
use Vsm\VsmHelperTools\Service\User\UserCreationService;

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
     * Erstellt eine Checkout-Session für Stripe
     */
    #[Route('/create-checkout-session', name: 'stripe_create_checkout_session', methods: ['POST'], defaults: ['_scope' => 'frontend', '_token_check' => false])]
    public function createCheckoutSession(Request $request): Response
    {
        try {
            // Debug: Alle Request-Daten loggen
            $this->logger->info('Incoming request data: ' . json_encode($request->request->all()));
            
            // JSON-Inhaltstyp erkennen
            $isJsonRequest = $request->headers->get('Content-Type') === 'application/json';
            
            if ($isJsonRequest) {
                // Bei JSON-Anfragen den Inhalt direkt auslesen
                $data = json_decode($request->getContent(), true);
                $this->logger->info('JSON-Request erkannt. JSON-Daten: ' . json_encode($data));
                
                // Verarbeiten der JSON-Daten mit der richtigen Struktur
                if (isset($data['personalData'])) {
                    $customerData = $data['personalData'];
                } elseif (isset($data['customer'])) {
                    $customerData = $data['customer'];
                } else {
                    $customerData = [];
                }
                
                if (isset($data['productData'])) {
                    $productData = $data['productData'];
                } elseif (isset($data['product'])) {
                    $productData = $data['product'];
                } else {
                    $productData = [];
                }
                
                // Erfolgs- und Abbruch-URLs setzen
                $productData['success_url'] = $data['successUrl'] ?? $request->request->get('success_url');
                $productData['cancel_url'] = $data['cancelUrl'] ?? $request->request->get('cancel_url');
            } else {
                // Formular-Daten aus dem Request extrahieren (alter Code)
                $customerData = $request->request->all('customer');
                $this->logger->info('Customer data: ' . json_encode($customerData));
                
                $productData = $request->request->all('product');
                $this->logger->info('Product data: ' . json_encode($productData));
                
                // Erfolgs- und Abbruch-URLs setzen
                $productData['success_url'] = $request->request->get('success_url');
                $productData['cancel_url'] = $request->request->get('cancel_url');
            }
            
            $this->logger->info('Verarbeitete Daten: Customer: ' . json_encode($customerData) . ', Product: ' . json_encode($productData));
            
            // Fallback für leere Kundendaten
            if (empty($customerData) || empty($customerData['email'])) {
                // Versuchen, die E-Mail aus den allgemeinen Request-Daten zu extrahieren
                if ($request->request->has('email')) {
                    $customerData['email'] = $request->request->get('email');
                } elseif (isset($data['email'])) {
                    $customerData['email'] = $data['email'];
                } else {
                    throw new \InvalidArgumentException('E-Mail-Adresse ist erforderlich');
                }
            }
            
            // Stripe-Session erstellen
            $session = $this->stripeService->createCheckoutSession($customerData, $productData);
            
            // Session in der Datenbank speichern
            $this->sessionManager->createSession([
                'session_id' => $session->id,
                'stripe_session_id' => $session->id,
                'customer_data' => $customerData,
                'product_data' => $productData
            ]);
            
            // Redirect URL zurückgeben - formatiert für den JavaScript-Handler
            return $this->json([
                'session_url' => $session->url,
                'url' => $session->url,
                'id' => $session->id
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Erstellen der Checkout-Session: ' . $e->getMessage());
            return $this->json(['error' => 'Fehler beim Erstellen der Checkout-Session: ' . $e->getMessage()], 500);
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
            
            // Session in der Datenbank aktualisieren
            $this->sessionManager->updateSessionAfterPayment($sessionId, $paymentData);
            
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
            if (isset($sessionData['product_data']['create_user']) && $sessionData['product_data']['create_user']) {
                $userData = [
                    'username' => $sessionData['customer_data']['email'] ?? '',
                    'email' => $sessionData['customer_data']['email'] ?? '',
                    'firstname' => $sessionData['customer_data']['firstname'] ?? '',
                    'lastname' => $sessionData['customer_data']['lastname'] ?? '',
                    'groups' => isset($sessionData['product_data']['user_groups']) 
                        ? explode(',', $sessionData['product_data']['user_groups']) 
                        : [],
                    'subscription_duration' => $sessionData['product_data']['subscription_duration'] ?? 0
                ];
                
                $userId = $this->userService->createOrUpdateUser($userData);
            
            if ($userId) {
                    // Benutzer-ID in der Session speichern
                $this->sessionManager->updateUserId($sessionId, $userId);
                }
            }
        
        // E-Mails senden - immer senden, wenn E-Mail-Adresse vorhanden ist
        if (!empty($sessionData['customer_data']['email'])) {
            $emailData = [
                'customer' => $sessionData['customer_data'],
                'product' => $sessionData['product_data'],
                'payment' => $paymentData,
                'download_url' => $downloadUrl ?? null,
                'download_token' => $downloadToken ?? null,
                'download_expires' => $sessionData['product_data']['download_expires'] ?? 7,
                'download_limit' => $sessionData['product_data']['download_limit'] ?? 3
            ];
            
            // E-Mail-Benachrichtigung über den EmailService senden
            $this->emailService->sendPaymentConfirmation($sessionData);
            
            // E-Mails als gesendet markieren
            $this->sessionManager->markEmailsAsSent($sessionId);
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
} 