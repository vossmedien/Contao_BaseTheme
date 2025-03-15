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

namespace Vsm\VsmHelperTools\Service\Payment;

use Contao\CoreBundle\Monolog\ContaoContext;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Vsm\VsmHelperTools\Model\PaymentSession;
use Vsm\VsmHelperTools\Service\Email\EmailService;
use Vsm\VsmHelperTools\Service\User\UserCreationService;
use Vsm\VsmHelperTools\Service\Download\DownloadLinkGenerator;

class PaymentSessionManager
{
    protected Connection $connection;
    protected CsrfTokenManagerInterface $csrfTokenManager;
    protected LoggerInterface $logger;
    protected EmailService $emailService;
    protected UserCreationService $userService;
    protected DownloadLinkGenerator $downloadService;

    public function __construct(
        Connection $connection,
        CsrfTokenManagerInterface $csrfTokenManager,
        LoggerInterface $logger,
        EmailService $emailService = null,
        UserCreationService $userService = null,
        DownloadLinkGenerator $downloadService = null
    ) {
        $this->connection = $connection;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->logger = $logger;
        $this->emailService = $emailService;
        $this->userService = $userService;
        $this->downloadService = $downloadService;
    }

    /**
     * Initialisiert die Datenbanktabelle und stellt sicher, dass alle benötigten Spalten vorhanden sind
     */
    public function ensureTableStructure(): bool
    {
        // Diese Methode ist nicht mehr notwendig, da die Tabellen über die DCA-Dateien definiert werden
        // Die Contao Install-Tool / Migrations übernehmen die Erstellung der Tabellen
        $this->logger->info('Die Tabellen werden über die DCA-Dateien verwaltet');
        return true;
    }

    /**
     * Erstellt eine neue Zahlungssitzung
     */
    public function createSession(array $data): PaymentSession
    {
        // Prüfen, ob die session_id bereits vorhanden ist
        if (isset($data['session_id'])) {
            $sessionId = $data['session_id'];
        } else {
            // Eindeutige Session-ID generieren
            $sessionId = bin2hex(random_bytes(16));
        }
        
        try {
            // Daten vorbereiten
            $insertData = [
                'tstamp' => time(),
                'created_at' => time(),
                'session_id' => $sessionId,
                'status' => 'pending',
                'emails_sent' => '',
            ];
            
            // Kundendaten einfügen, wenn vorhanden
            if (isset($data['customer_data'])) {
                $insertData['customer_data'] = is_array($data['customer_data']) ? json_encode($data['customer_data']) : $data['customer_data'];
            }
            
            // Produktdaten einfügen, wenn vorhanden
            if (isset($data['product_data'])) {
                $insertData['product_data'] = is_array($data['product_data']) ? json_encode($data['product_data']) : $data['product_data'];
            }
            
            // Stripe-Sessions-ID einfügen, wenn vorhanden
            if (isset($data['stripe_session_id'])) {
                $insertData['stripe_session_id'] = $data['stripe_session_id'];
            }
            
            // Metadaten speichern
            $insertData['metadata'] = json_encode($data);
            
            // In die Datenbank einfügen
            $this->connection->insert('tl_stripe_payment_sessions', $insertData);
            
            $this->logger->info('Zahlungssitzung erstellt', [
                'session_id' => $sessionId,
                'data' => array_keys($data) // Nur Schlüssel protokollieren, keine sensiblen Daten
            ]);
            
            // PaymentSession-Objekt erstellen und zurückgeben
            return new PaymentSession(
                $sessionId,
                'pending',
                $data
            );
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Erstellen der Zahlungssitzung: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Ruft eine Zahlungssitzung anhand ihrer ID ab
     */
    public function getSession(string $sessionId): ?PaymentSession
    {
        try {
            $result = $this->connection->fetchAssociative(
                'SELECT * FROM tl_stripe_payment_sessions WHERE session_id = :session_id',
                ['session_id' => $sessionId]
            );
            
            if (!$result) {
                $this->logger->warning('Zahlungssitzung nicht gefunden', ['session_id' => $sessionId]);
                return null;
            }
            
            // Metadaten aus JSON dekodieren
            $metadata = [];
            
            if (!empty($result['customer_data'])) {
                $metadata['personal_data'] = json_decode($result['customer_data'], true);
            }
            
            if (!empty($result['product_data'])) {
                $metadata['product_data'] = json_decode($result['product_data'], true);
            }
            
            if (!empty($result['payment_data'])) {
                $metadata['payment_data'] = json_decode($result['payment_data'], true);
            }
            
            // PaymentSession-Objekt erstellen und zurückgeben
            return new PaymentSession(
                $result['session_id'],
                $result['status'],
                $metadata
            );
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Abrufen der Zahlungssitzung: ' . $e->getMessage(), [
                'session_id' => $sessionId,
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            
            return null;
        }
    }

    /**
     * Aktualisiert den Status einer Zahlungssitzung
     */
    public function updateSessionStatus(string $sessionId, string $status, array $additionalData = []): bool
    {
        try {
            $updateData = [
                'status' => $status,
                'tstamp' => time(),
            ];
            
            // Zusätzliche Daten hinzufügen
            if (isset($additionalData['payment_id'])) {
                $updateData['payment_data'] = json_encode([
                    'payment_id' => $additionalData['payment_id'],
                    'updated_at' => time(),
                ]);
            }
            
            if (isset($additionalData['checkout_id'])) {
                $updateData['payment_data'] = json_encode([
                    'checkout_id' => $additionalData['checkout_id'],
                    'updated_at' => time(),
                ]);
            }
            
            // Status-spezifische Zeitstempel
            if ($status === PaymentSession::STATUS_COMPLETED && isset($additionalData['completed'])) {
                $updateData['completed'] = $additionalData['completed'];
            } elseif ($status === PaymentSession::STATUS_FAILED && isset($additionalData['failed'])) {
                $updateData['failed'] = $additionalData['failed'];
            }
            
            // In der Datenbank aktualisieren
            $affected = $this->connection->update(
                'tl_stripe_payment_sessions',
                $updateData,
                ['session_id' => $sessionId]
            );
            
            if ($affected === 0) {
                $this->logger->warning('Keine Zahlungssitzung zum Aktualisieren gefunden', ['session_id' => $sessionId]);
                return false;
            }
            
            $this->logger->info('Zahlungssitzungsstatus aktualisiert', [
                'session_id' => $sessionId,
                'status' => $status
            ]);
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Aktualisieren des Zahlungssitzungsstatus: ' . $e->getMessage(), [
                'session_id' => $sessionId,
                'status' => $status,
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }

    /**
     * Verarbeitet eine erfolgreiche Zahlung
     */
    public function processSuccessfulPayment(string $sessionId, array $paymentData): bool
    {
        $session = $this->getSession($sessionId);
        if (!$session) {
            $this->logger->error('Zahlungssitzung nicht gefunden', [
                'session_id' => $sessionId,
                'payment_id' => $paymentData['payment_id'] ?? null
            ]);
            return false;
        }
        
        // Session als abgeschlossen markieren
        $this->updateSessionStatus(
            $sessionId, 
            PaymentSession::STATUS_COMPLETED, 
            [
                'payment_id' => $paymentData['payment_id'] ?? null,
                'completed' => time()
            ]
        );
        
        $metadata = $session->getMetadata();
        $personalData = $metadata['personal_data'] ?? [];
        $productData = $metadata['product_data'] ?? [];
        
        // Download-Link generieren, wenn ein Download-Service verfügbar ist
        $downloadInfo = [];
        if ($this->downloadService && is_array($productData) && isset($productData['download_file'])) {
            try {
                // Download-Link generieren
                $downloadInfo = $this->downloadService->generateDownloadLink(
                    $personalData,
                    $productData,
                    $sessionId
                );
                
                // Download-Informationen in der Session speichern
                if (!empty($downloadInfo)) {
                    $this->connection->update(
                        'tl_stripe_payment_sessions',
                        [
                            'download_link' => $downloadInfo['link'] ?? '',
                            'download_token' => $downloadInfo['token'] ?? '',
                            'download_expires' => $downloadInfo['expires'] ?? 0,
                            'download_limit' => $downloadInfo['limit'] ?? 0,
                        ],
                        ['session_id' => $sessionId]
                    );
                }
            } catch (\Exception $e) {
                $this->logger->error('Fehler beim Generieren des Download-Links: ' . $e->getMessage(), [
                    'session_id' => $sessionId,
                    'exception' => get_class($e)
                ]);
            }
        }
        
        // Benutzer erstellen, wenn ein Benutzer-Service verfügbar ist
        $userId = 0;
        $subscriptionDuration = 0;
        if ($this->userService && is_array($personalData) && !empty($personalData['email'])) {
            try {
                $subscriptionInfo = [];
                if (is_array($productData)) {
                    $subscriptionInfo = [
                        'duration' => $productData['subscription_duration'] ?? 0,
                        'groups' => $productData['member_groups'] ?? [],
                        'product_name' => $productData['name'] ?? 'Unbekanntes Produkt',
                        'product_id' => $productData['id'] ?? 0,
                    ];
                }
                
                // Benutzer erstellen oder aktualisieren
                $result = $this->userService->createOrUpdateUser(
                    $personalData,
                    $subscriptionInfo
                );
                
                if ($result && isset($result['user_id'])) {
                    $userId = $result['user_id'];
                    $subscriptionDuration = $result['subscription_duration'] ?? 0;
                    
                    // Benutzer-ID in der Session speichern
                    $this->connection->update(
                        'tl_stripe_payment_sessions',
                        [
                            'user_id' => $userId,
                            'subscription_duration' => $subscriptionDuration,
                        ],
                        ['session_id' => $sessionId]
                    );
                }
            } catch (\Exception $e) {
                $this->logger->error('Fehler beim Erstellen/Aktualisieren des Benutzers: ' . $e->getMessage(), [
                    'session_id' => $sessionId,
                    'email' => $personalData['email'] ?? 'keine E-Mail',
                    'exception' => get_class($e)
                ]);
            }
        }
        
        // E-Mail senden, wenn ein E-Mail-Service verfügbar ist
        if ($this->emailService && is_array($personalData) && !empty($personalData['email'])) {
            try {
                // An den Kunden
                $this->emailService->sendPaymentConfirmation(
                    $personalData,
                    $productData,
                    $paymentData,
                    $downloadInfo
                );
                
                // An den Administrator
                $this->emailService->sendAdminNotification(
                    $personalData,
                    $productData,
                    $paymentData,
                    $downloadInfo
                );
                
                // E-Mails als gesendet markieren
                $this->connection->update(
                    'tl_stripe_payment_sessions',
                    ['emails_sent' => 1],
                    ['session_id' => $sessionId]
                );
            } catch (\Exception $e) {
                $this->logger->error('Fehler beim Senden der Bestätigungs-E-Mail: ' . $e->getMessage(), [
                    'session_id' => $sessionId,
                    'email' => $personalData['email'] ?? 'keine E-Mail',
                    'exception' => get_class($e)
                ]);
            }
        }
        
        return true;
    }

    /**
     * Verarbeitet eine fehlgeschlagene Zahlung
     */
    public function processFailedPayment(string $sessionId, array $paymentData, string $errorMessage = null): bool
    {
        $session = $this->getSession($sessionId);
        if (!$session) {
            $this->logger->error('Zahlungssitzung nicht gefunden', [
                'session_id' => $sessionId,
                'payment_id' => $paymentData['payment_id'] ?? null
            ]);
            return false;
        }
        
        // Session als fehlgeschlagen markieren
        $this->updateSessionStatus(
            $sessionId, 
            PaymentSession::STATUS_FAILED, 
            [
                'payment_id' => $paymentData['payment_id'] ?? null,
                'failed' => time()
            ]
        );
        
        // Optional: E-Mail über fehlgeschlagene Zahlung senden
        if ($this->emailService) {
            try {
                $metadata = $session->getMetadata();
                $personalData = $metadata['personal_data'] ?? [];
                
                if (is_array($personalData) && !empty($personalData['email'])) {
                    $this->emailService->sendPaymentFailureNotification(
                        $personalData,
                        $paymentData,
                        $errorMessage
                    );
                }
            } catch (\Exception $e) {
                $this->logger->error('Fehler beim Senden der Fehlermeldung: ' . $e->getMessage());
            }
        }
        
        return true;
    }

    /**
     * Verarbeitet eine abgebrochene Zahlung
     */
    public function processCanceledPayment(string $sessionId, array $paymentData): bool
    {
        $session = $this->getSession($sessionId);
        if (!$session) {
            $this->logger->error('Zahlungssitzung nicht gefunden', [
                'session_id' => $sessionId,
                'payment_id' => $paymentData['payment_id'] ?? null
            ]);
            return false;
        }
        
        // Session als abgebrochen markieren
        $this->updateSessionStatus(
            $sessionId, 
            PaymentSession::STATUS_CANCELED, 
            [
                'payment_id' => $paymentData['payment_id'] ?? null,
                'canceled' => time()
            ]
        );
        
        return true;
    }

    /**
     * Bereinigt abgelaufene Zahlungssitzungen
     */
    public function cleanupExpiredSessions(int $expiryTime = 86400): int
    {
        $now = time();
        $cutoff = $now - $expiryTime;
        
        try {
            // Lösche alle abgelaufenen Sitzungen, die nicht abgeschlossen wurden
            $result = $this->connection->executeStatement(
                'DELETE FROM tl_stripe_payment_sessions WHERE created_at < ? AND status != ?',
                [$cutoff, PaymentSession::STATUS_COMPLETED]
            );
            
            $this->logger->info('Abgelaufene Zahlungssitzungen bereinigt', [
                'deleted_sessions' => $result
            ]);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Fehler bei der Bereinigung abgelaufener Zahlungssitzungen: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            
            return 0;
        }
    }

    /**
     * Findet eine Sitzung anhand der Zahlungs- oder Checkout-ID
     */
    public function findSessionByPaymentOrCheckoutId(string $id): ?PaymentSession
    {
        try {
            // Suche nach payment_data.payment_id oder payment_data.checkout_id
            $result = $this->connection->fetchAssociative(
                "SELECT * FROM tl_stripe_payment_sessions 
                 WHERE payment_data LIKE :search_pattern1 
                 OR payment_data LIKE :search_pattern2",
                [
                    'search_pattern1' => '%"payment_id":"' . $id . '"%',
                    'search_pattern2' => '%"checkout_id":"' . $id . '"%'
                ]
            );
            
            if (!$result) {
                return null;
            }
            
            // Metadaten aus JSON dekodieren
            $metadata = [];
            
            if (!empty($result['customer_data'])) {
                $metadata['personal_data'] = json_decode($result['customer_data'], true);
            }
            
            if (!empty($result['product_data'])) {
                $metadata['product_data'] = json_decode($result['product_data'], true);
            }
            
            if (!empty($result['payment_data'])) {
                $metadata['payment_data'] = json_decode($result['payment_data'], true);
            }
            
            // PaymentSession-Objekt erstellen und zurückgeben
            return new PaymentSession(
                $result['session_id'],
                $result['status'],
                $metadata
            );
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Suchen der Sitzung anhand der Zahlungs-ID: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Ruft die Sitzungsdaten anhand der Session-ID ab
     */
    public function getSessionData(string $sessionId): ?array
    {
        try {
            $queryBuilder = $this->connection->createQueryBuilder();
            $result = $queryBuilder
                ->select('*')
                ->from('tl_stripe_payment_sessions')
                ->where('session_id = :sessionId')
                ->setParameter('sessionId', $sessionId)
                ->execute()
                ->fetchAssociative();
            
            if (!$result) {
                return null;
            }
            
            // Deserialisiere die JSON-Daten
            $data = [
                'session_id' => $result['session_id'],
                'status' => $result['status'],
                'created_at' => $result['created_at'],
                'customer_data' => $result['customer_data'] ? json_decode($result['customer_data'], true) : [],
                'product_data' => $result['product_data'] ? json_decode($result['product_data'], true) : [],
                'payment_data' => $result['payment_data'] ? json_decode($result['payment_data'], true) : [],
                'download_token' => $result['download_token'],
                'download_url' => $result['download_url'],
                'download_expires' => $result['download_expires'],
                'download_limit' => $result['download_limit'],
                'download_count' => $result['download_count'],
            ];
            
            return $data;
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Abrufen der Zahlungssitzung: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            
            return null;
        }
    }
    
    /**
     * Aktualisiert die Zahlungssitzung nach erfolgreicher Zahlung
     */
    public function updateSessionAfterPayment(string $sessionId, array $paymentData): bool
    {
        try {
            // Aktuelle Daten abrufen
            $existingData = $this->getSessionData($sessionId);
            if (!$existingData) {
                throw new \Exception('Zahlungssitzung nicht gefunden: ' . $sessionId);
            }
            
            // Produktdaten aus der bestehenden Sitzung übernehmen
            $productData = $existingData['product_data'] ?? [];
            
            // Preis in Produktdaten aktualisieren, falls nicht vorhanden oder 0
            if (empty($productData['price']) && isset($paymentData['amount_total'])) {
                $productData['price'] = $paymentData['amount_total'];
                $productData['stripe_currency'] = $paymentData['currency'] ?? 'EUR';
            }
            
            // Update-Daten zusammenstellen
            $updateData = [
                'status' => 'completed',
                'tstamp' => time(),
                'paid_at' => $paymentData['paid_at'] ?? time(),
                'amount' => $paymentData['amount_total'] ?? 0,
                'currency' => $paymentData['currency'] ?? 'EUR',
                'payment_data' => json_encode($paymentData),
                'product_data' => json_encode($productData)
            ];
            
            // Debugging: Log der Update-Daten
            $this->logger->debug('Aktualisiere Zahlungssitzung mit Daten', [
                'session_id' => $sessionId,
                'status' => $updateData['status'],
                'amount' => $updateData['amount'],
                'has_payment_data' => !empty($paymentData),
                'product_data_keys' => array_keys($productData)
            ]);
            
            $this->connection->update(
                'tl_stripe_payment_sessions',
                $updateData,
                ['session_id' => $sessionId]
            );
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Aktualisieren der Zahlungssitzung: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }
    
    /**
     * Setzt die Download-Informationen in der Zahlungssitzung
     */
    public function setDownloadInfo(string $sessionId, string $downloadUrl, string $downloadToken, int $downloadExpires, int $downloadLimit): bool
    {
        try {
            $updateData = [
                'download_url' => $downloadUrl,
                'download_token' => $downloadToken,
                'download_expires' => $downloadExpires,
                'download_limit' => $downloadLimit,
                'download_count' => 0,
                'tstamp' => time(),
            ];
            
            // Hole aktuelle Session-Daten, um den Dateipfad zu finden
            $session = $this->getSessionData($sessionId);
            if ($session && !empty($session['product_data']['download_file'])) {
                $updateData['download_file'] = $session['product_data']['download_file'];
                $this->logger->info('Dateipfad in Download-Info hinzugefügt', [
                    'file_path' => $session['product_data']['download_file']
                ]);
            }
            
            $this->connection->update(
                'tl_stripe_payment_sessions',
                $updateData,
                ['session_id' => $sessionId]
            );
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Setzen der Download-Informationen: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }
    
    /**
     * Markiert E-Mails als gesendet in der Zahlungssitzung
     */
    public function markEmailsAsSent(string $sessionId): bool
    {
        try {
            $updateData = [
                'emails_sent' => '1',
                'tstamp' => time(),
            ];
            
            $this->connection->update(
                'tl_stripe_payment_sessions',
                $updateData,
                ['session_id' => $sessionId]
            );
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Markieren der E-Mails als gesendet: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }
    
    /**
     * Aktualisiert die Benutzer-ID in der Zahlungssitzung
     */
    public function updateUserId(string $sessionId, int $userId): bool
    {
        try {
            $updateData = [
                'user_id' => $userId,
                'tstamp' => time(),
            ];
            
            $this->connection->update(
                'tl_stripe_payment_sessions',
                $updateData,
                ['session_id' => $sessionId]
            );
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Aktualisieren der Benutzer-ID: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }
    
    /**
     * Erhöht den Download-Zähler in der Zahlungssitzung
     */
    public function incrementDownloadCount(string $sessionId): bool
    {
        try {
            // Prüfen, ob die Spalte last_download existiert
            $hasLastDownloadColumn = true;
            try {
                $this->connection->executeQuery('SELECT last_download FROM tl_stripe_payment_sessions LIMIT 1');
            } catch (\Exception $e) {
                $hasLastDownloadColumn = false;
                $this->logger->warning('Spalte last_download nicht vorhanden in tl_stripe_payment_sessions: ' . $e->getMessage());
            }

            // Aktuelle Daten abrufen
            $queryBuilder = $this->connection->createQueryBuilder();
            $result = $queryBuilder
                ->select('download_count, download_limit, download_token')
                ->from('tl_stripe_payment_sessions')
                ->where('session_id = :sessionId')
                ->setParameter('sessionId', $sessionId)
                ->execute()
                ->fetchAssociative();
            
            if (!$result) {
                $this->logger->error('Keine Session mit der ID gefunden für Download-Zähler', [
                    'session_id' => $sessionId
                ]);
                return false;
            }
            
            // Prüfen ob Download-Limit erreicht wurde
            $currentCount = (int)$result['download_count'];
            $limit = (int)($result['download_limit'] ?? 3);
            
            if ($currentCount >= $limit) {
                $this->logger->warning('Download-Limit bereits erreicht', [
                    'session_id' => $sessionId,
                    'current_count' => $currentCount,
                    'limit' => $limit
                ]);
                return false;
            }
            
            // Download-Zähler erhöhen und Timestamp aktualisieren
            $newTimestamp = time();
            $updateData = [
                'download_count' => $currentCount + 1,
                'tstamp' => $newTimestamp
            ];
            
            // Nur last_download setzen, wenn die Spalte existiert
            if ($hasLastDownloadColumn) {
                $updateData['last_download'] = $newTimestamp;
            }
            
            $this->connection->update(
                'tl_stripe_payment_sessions',
                $updateData,
                ['session_id' => $sessionId]
            );
            
            // Aktualisiere auch den Eintrag in tl_download_tokens, falls vorhanden
            if (!empty($result['download_token'])) {
                try {
                    // Zuerst aktuelle Werte abfragen
                    $tokenData = $this->connection->fetchAssociative(
                        'SELECT download_count, download_limit FROM tl_download_tokens WHERE token = ?',
                        [$result['download_token']]
                    );
                    
                    if ($tokenData) {
                        $tokenCurrentCount = (int)$tokenData['download_count'];
                        
                        // Update mit direktem Befehl für tl_download_tokens
                        // Hier können wir last_download nutzen, da wir wissen, dass diese Spalte existiert
                        $this->connection->executeStatement(
                            'UPDATE tl_download_tokens SET download_count = ?, last_download = ? WHERE token = ?',
                            [$tokenCurrentCount + 1, $newTimestamp, $result['download_token']]
                        );
                        
                        $this->logger->info('Download-Zähler in tl_download_tokens aktualisiert', [
                            'token' => $result['download_token'],
                            'alte_anzahl' => $tokenCurrentCount,
                            'neue_anzahl' => $tokenCurrentCount + 1,
                            'timestamp' => date('Y-m-d H:i:s', $newTimestamp)
                        ]);
                    } else {
                        $this->logger->warning('Token nicht in der Tabelle tl_download_tokens gefunden', [
                            'token' => $result['download_token']
                        ]);
                    }
                } catch (\Exception $e) {
                    // Fehler beim Aktualisieren des Token-Eintrags - nur loggen, nicht abbrechen
                    $this->logger->warning('Fehler beim Aktualisieren des Token-Eintrags: ' . $e->getMessage(), [
                        'exception' => get_class($e),
                        'token' => $result['download_token'],
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
            
            $this->logger->info('Download-Zähler erhöht', [
                'session_id' => $sessionId,
                'new_count' => $currentCount + 1,
                'limit' => $limit,
                'timestamp' => date('Y-m-d H:i:s', $newTimestamp)
            ]);
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Erhöhen des Download-Zählers: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }

    /**
     * Aktualisiert bestimmte Daten in der Session
     */
    public function updateSessionData(string $sessionId, array $data): bool
    {
        try {
            $updateData = [
                'tstamp' => time(),
            ];
            
            // Aktuelle Session-Daten abrufen
            $currentData = $this->getSessionData($sessionId);
            if (!$currentData) {
                $this->logger->error('Session nicht gefunden für Aktualisierung', [
                    'session_id' => $sessionId
                ]);
                return false;
            }
            
            // Produkt-Daten aktualisieren
            if (isset($data['product_data'])) {
                $updateData['product_data'] = json_encode($data['product_data']);
            }
            
            // Kunden-Daten aktualisieren
            if (isset($data['customer_data'])) {
                $updateData['customer_data'] = json_encode($data['customer_data']);
            }
            
            // Weitere Daten aktualisieren
            if (isset($data['download_file'])) {
                // Prüfen, ob die Spalte existiert, bevor wir sie aktualisieren
                $columnExists = true;
                try {
                    $this->connection->executeQuery('SELECT download_file FROM tl_stripe_payment_sessions LIMIT 1');
                } catch (\Exception $e) {
                    $columnExists = false;
                    $this->logger->warning('Spalte download_file existiert noch nicht: ' . $e->getMessage());
                }
                
                if ($columnExists) {
                    $updateData['download_file'] = $data['download_file'];
                } else {
                    // Wenn die Spalte nicht existiert, speichern wir es in den Produktdaten
                    $productData = isset($updateData['product_data']) ? 
                        json_decode($updateData['product_data'], true) : 
                        json_decode($currentData['product_data'] ?? '{}', true);
                    
                    if (!is_array($productData)) {
                        $productData = [];
                    }
                    
                    $productData['download_file'] = $data['download_file'];
                    $updateData['product_data'] = json_encode($productData);
                    
                    $this->logger->info('Dateipfad in Produktdaten gespeichert (Spalte nicht verfügbar)', [
                        'file' => $data['download_file']
                    ]);
                }
            }
            
            $this->connection->update(
                'tl_stripe_payment_sessions',
                $updateData,
                ['session_id' => $sessionId]
            );
            
            $this->logger->info('Session-Daten aktualisiert', [
                'session_id' => $sessionId,
                'updated_fields' => array_keys($updateData)
            ]);
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Aktualisieren der Session-Daten: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }
} 