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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller für die Erfolgsbehandlung von Stripe-Checkout
 */
class SuccessHandlerController extends BaseStripeController
{
    use UtilityTrait;
    
    /**
     * Verarbeitet erfolgreiche Stripe-Zahlungen
     */
    #[Route('/checkout/success', name: 'stripe_checkout_success', methods: ['GET'])]
    public function handleSuccess(Request $request): Response
    {
        try {
            // Stripe-Client initialisieren
            $this->initStripeClient();
            
            // Session-ID aus dem Request extrahieren
            $sessionId = $request->query->get('session_id');
            // Zusätzlich Payment Intent ID aus dem Request holen (falls vorhanden)
            $paymentIntentId = $request->query->get('payment_intent_id');
            
            // Umfassendes Logging für Debugging
            $this->logger->info('Erfolgsseite aufgerufen mit folgenden Parametern:', [
                'sessionId' => $sessionId,
                'paymentIntentId' => $paymentIntentId,
                'alle_parameter' => $request->query->all(),
                'request_uri' => $request->getRequestUri(),
                'request_method' => $request->getMethod(),
                'ip_address' => $request->getClientIp()
            ]);
            
            // DATENBANK-VERBINDUNG TESTEN
            try {
                $testResult = $this->connection->executeQuery('SELECT 1')->fetchOne();
                $this->logger->info('Datenbankverbindung OK: ' . $testResult);
                
                // Prüfen, ob die Tabellen existieren
                $tables = $this->connection->executeQuery("SHOW TABLES")->fetchFirstColumn();
                $this->logger->info('Vorhandene Tabellen in der Datenbank:', [
                    'tables' => $tables,
                    'has_stripe_sessions' => in_array('tl_stripe_payment_sessions', $tables) ? 'ja' : 'nein',
                    'has_stripe_locks' => in_array('tl_stripe_locks', $tables) ? 'ja' : 'nein',
                    'has_download_tokens' => in_array('tl_download_tokens', $tables) ? 'ja' : 'nein'
                ]);
                
                // Anzahl der Einträge in der Tabelle prüfen
                if (in_array('tl_stripe_payment_sessions', $tables)) {
                    $count = $this->connection->executeQuery("SELECT COUNT(*) FROM tl_stripe_payment_sessions")->fetchOne();
                    $this->logger->info('Anzahl der Einträge in tl_stripe_payment_sessions: ' . $count);
                    
                    if ($sessionId) {
                        $sessionExists = $this->connection->executeQuery(
                            "SELECT COUNT(*) FROM tl_stripe_payment_sessions WHERE session_id = ?",
                            [$sessionId]
                        )->fetchOne();
                        $this->logger->info('Session mit ID ' . $sessionId . ' existiert: ' . ($sessionExists ? 'ja' : 'nein'));
                    }
                    
                    if ($paymentIntentId) {
                        $piExists = $this->connection->executeQuery(
                            "SELECT COUNT(*) FROM tl_stripe_payment_sessions WHERE payment_intent_id = ?",
                            [$paymentIntentId]
                        )->fetchOne();
                        $this->logger->info('Session mit Payment Intent ID ' . $paymentIntentId . ' existiert: ' . ($piExists ? 'ja' : 'nein'));
                    }
                }
            } catch (\Exception $dbTestError) {
                $this->logger->error('Fehler beim Testen der Datenbankverbindung: ' . $dbTestError->getMessage(), [
                    'trace' => $dbTestError->getTraceAsString()
                ]);
            }

            if (!$sessionId && !$paymentIntentId) {
                $this->logger->error('Keine Session-ID oder Payment Intent ID angegeben');
                return $this->json([
                    'error' => 'Keine Session-ID oder Payment Intent ID angegeben',
                    'status' => 'error',
                    'debug' => [
                        'request_uri' => $request->getRequestUri(),
                        'request_method' => $request->getMethod()
                    ]
                ], 400);
            }
            
            // Ziel-URL aus dem Request holen
            $targetUrl = $request->query->get('target');
            
            // Überprüfen, ob die Session-ID eine Payment Intent ID ist
            $isPaymentIntent = false;
            $paymentIntent = null;
            
            if (!$paymentIntentId && $sessionId && strpos($sessionId, 'pi_') === 0) {
                $this->logger->info('Session-ID scheint eine Payment Intent ID zu sein, versuche als Payment Intent zu behandeln');
                $paymentIntentId = $sessionId;
                $isPaymentIntent = true;
            }
            
            // Wenn wir eine Payment Intent ID haben, versuchen wir zuerst diese abzurufen
            if ($paymentIntentId) {
                try {
                    $this->logger->info('Versuche Payment Intent abzurufen: ' . $paymentIntentId);
                    $paymentIntent = $this->stripeService->retrievePaymentIntent($paymentIntentId);
                    
                    if ($paymentIntent) {
                        $this->logger->info('Payment Intent erfolgreich abgerufen', [
                            'id' => $paymentIntent->id,
                            'status' => $paymentIntent->status,
                            'amount' => $paymentIntent->amount,
                            'currency' => $paymentIntent->currency
                        ]);
                        
                        // Wenn der Status nicht erfolgreich ist, abbrechen
                        if ($paymentIntent->status !== 'succeeded') {
                            $this->logger->warning('Payment Intent nicht erfolgreich: ' . $paymentIntent->status);
                            return $this->json(['error' => 'Zahlung nicht erfolgreich. Status: ' . $paymentIntent->status], 400);
                        }
                        
                        $isPaymentIntent = true;
                    }
                } catch (\Exception $e) {
                    $this->logger->error('Fehler beim Abrufen des Payment Intent: ' . $e->getMessage());
                }
            }
            
            // Versuchen Sitzungsdaten aus der Datenbank zu holen
            $sessionData = null;
            
            // Wenn eine Session-ID vorhanden ist, versuchen wir diese zuerst
            if ($sessionId) {
                $this->logger->info('Versuche Session-Daten mit ID abzurufen: ' . $sessionId);
                $sessionData = $this->sessionManager->getSessionData($sessionId);
                
                if ($sessionData) {
                    $this->logger->info('Session-Daten erfolgreich abgerufen');
                } else {
                    $this->logger->warning('Keine Session-Daten mit ID gefunden: ' . $sessionId);
                }
            }
            
            // Wenn keine Session-Daten gefunden wurden, versuchen wir es mit der Payment Intent ID
            if (!$sessionData && $paymentIntentId) {
                $this->logger->info('Keine Session gefunden mit Session-ID. Versuche mit Payment Intent ID: ' . $paymentIntentId);
                
                // 1. Versuche in der Tabelle nach payment_intent_id zu suchen
                $sessionData = $this->sessionManager->getSessionDataByPaymentIntentId($paymentIntentId);
                
                // 2. Falls das nicht funktioniert und die Session-ID gleich der Payment Intent ID ist
                if (!$sessionData && $sessionId === $paymentIntentId) {
                    $this->logger->info('Session-ID ist gleich Payment Intent ID: ' . $paymentIntentId);
                    // Payment Intent von Stripe abrufen
                    try {
                        $paymentIntent = $this->stripeService->retrievePaymentIntent($paymentIntentId);
                        if ($paymentIntent && $paymentIntent->metadata && $paymentIntent->metadata->toArray()) {
                            $metadata = $paymentIntent->metadata->toArray();
                            
                            // Minimale Sitzungsdaten aus dem Payment Intent erstellen
                            $sessionData = [
                                'session_id' => $paymentIntentId,
                                'payment_intent_id' => $paymentIntentId,
                                'customer_data' => [
                                    'email' => $paymentIntent->receipt_email,
                                ],
                                'product_data' => [
                                    'title' => $metadata['product_title'] ?? 'Unbekanntes Produkt',
                                    'price' => $paymentIntent->amount,
                                    'currency' => $paymentIntent->currency,
                                    'tax_rate' => $metadata['tax_rate'] ?? 19,
                                ],
                                'payment_data' => [
                                    'payment_intent_id' => $paymentIntentId,
                                    'amount' => $paymentIntent->amount,
                                    'currency' => $paymentIntent->currency,
                                    'status' => $paymentIntent->status,
                                ],
                                'metadata' => $metadata,
                            ];
                            
                            // Session in der Datenbank speichern für zukünftige Anfragen
                            try {
                                $this->sessionManager->createSession($sessionData);
                                $this->logger->info('Session-Daten aus Payment Intent erstellt und in Datenbank gespeichert');
                            } catch (\Exception $e) {
                                $this->logger->error('Fehler beim Speichern der Session-Daten: ' . $e->getMessage());
                                // Trotzdem weitermachen, da wir die Daten bereits haben
                            }
                        }
                    } catch (\Exception $e) {
                        $this->logger->error('Fehler beim Abrufen des Payment Intent: ' . $e->getMessage());
                    }
                }
            }
            
            // Wenn wir immer noch keine Session-Daten haben, Fehler zurückgeben
            if (!$sessionData) {
                $this->logger->error('Session nicht gefunden', [
                    'session_id' => $sessionId,
                    'payment_intent_id' => $paymentIntentId
                ]);
                
                // Versuche, eine neue Session zu erstellen, wenn wir einen Payment Intent haben
                if ($paymentIntent) {
                    $this->logger->info('Versuche, eine neue Session aus dem Payment Intent zu erstellen');
                    
                    $metadata = $paymentIntent->metadata ? $paymentIntent->metadata->toArray() : [];
                    
                    // Minimale Sitzungsdaten aus dem Payment Intent erstellen
                    $sessionData = [
                        'session_id' => $paymentIntentId,
                        'payment_intent_id' => $paymentIntentId,
                        'client_secret' => null, // Nicht mehr benötigt nach erfolgreicher Zahlung
                        'customer_data' => [
                            'email' => $paymentIntent->receipt_email,
                        ],
                        'product_data' => [
                            'title' => $metadata['product_title'] ?? 'Unbekanntes Produkt',
                            'price' => $paymentIntent->amount,
                            'currency' => $paymentIntent->currency,
                            'tax_rate' => $metadata['tax_rate'] ?? 19,
                            'success_url' => $targetUrl ?? '/checkout/success'
                        ],
                        'payment_data' => [
                            'payment_intent_id' => $paymentIntentId,
                            'amount' => $paymentIntent->amount,
                            'currency' => $paymentIntent->currency,
                            'status' => $paymentIntent->status,
                        ],
                        'metadata' => $metadata,
                    ];
                    
                    // Session in der Datenbank speichern für zukünftige Anfragen
                    try {
                        $this->sessionManager->createSession($sessionData);
                        $this->logger->info('Neue Session-Daten aus Payment Intent erstellt und in Datenbank gespeichert');
                    } catch (\Exception $e) {
                        $this->logger->error('Fehler beim Speichern der neuen Session-Daten: ' . $e->getMessage());
                        
                        // NOTFALL: Direkter SQL-Insert als letzter Versuch
                        try {
                            $this->logger->warning('Versuche direkten SQL-Insert als letzter Versuch...');
                            
                            // JSON-Kodierung für LONGTEXT-Felder
                            $jsonCustomerData = json_encode($sessionData['customer_data'] ?? []);
                            $jsonProductData = json_encode($sessionData['product_data'] ?? []);
                            $jsonPaymentData = json_encode($sessionData['payment_data'] ?? []);
                            $jsonMetadata = json_encode($sessionData['metadata'] ?? []);
                            
                            $minimalInsert = "INSERT INTO tl_stripe_payment_sessions 
                                             (session_id, payment_intent_id, tstamp, created_at, status, 
                                              customer_data, product_data, payment_data, metadata) 
                                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                            
                            $this->connection->executeStatement($minimalInsert, [
                                $paymentIntentId, // session_id
                                $paymentIntentId, // payment_intent_id
                                time(), // tstamp
                                time(), // created_at
                                'succeeded', // status
                                $jsonCustomerData, // customer_data
                                $jsonProductData, // product_data
                                $jsonPaymentData, // payment_data
                                $jsonMetadata // metadata
                            ]);
                            
                            $this->logger->info('Direkter SQL-Insert erfolgreich durchgeführt');
                            
                            // Direkt prüfen, ob der Eintrag tatsächlich erstellt wurde
                            $checkInsert = $this->connection->fetchAssociative(
                                'SELECT id FROM tl_stripe_payment_sessions WHERE session_id = ?',
                                [$paymentIntentId]
                            );
                            
                            if ($checkInsert) {
                                $this->logger->info('INSERT-Bestätigung: Eintrag wurde erfolgreich erstellt mit ID ' . $checkInsert['id']);
                            } else {
                                $this->logger->error('INSERT-Bestätigung: Eintrag wurde NICHT erstellt!');
                            }
                            
                        } catch (\Exception $sqlEx) {
                            $this->logger->error('Auch direkter SQL-Insert fehlgeschlagen: ' . $sqlEx->getMessage(), [
                                'exception' => get_class($sqlEx),
                                'trace' => $sqlEx->getTraceAsString()
                            ]);
                        }
                        
                        // Trotzdem weitermachen, da wir die Daten bereits haben
                    }
                    
                    // Optional: Prüfen, ob der Eintrag tatsächlich in der Datenbank existiert
                    try {
                        $checkEntry = $this->connection->fetchAssociative(
                            'SELECT id FROM tl_stripe_payment_sessions WHERE session_id = ? OR payment_intent_id = ?',
                            [$paymentIntentId, $paymentIntentId]
                        );
                        
                        if ($checkEntry) {
                            $this->logger->info('Session existiert nun in der Datenbank mit ID: ' . $checkEntry['id']);
                        } else {
                            $this->logger->error('Session existiert immer noch nicht in der Datenbank! Schwerwiegender Fehler.');
                        }
                    } catch (\Exception $checkEx) {
                        $this->logger->error('Fehler bei der Überprüfung des Datenbankeintrags: ' . $checkEx->getMessage());
                    }
                } else {
                    return $this->json(['error' => 'Session nicht gefunden'], 404);
                }
            }
            
            // Zahlungsdaten und Session-Informationen vorbereiten
            $paymentData = [];
            $session = null;

            // Bei Payment Intent verwenden wir die Daten aus dem Payment Intent
            if ($isPaymentIntent && $paymentIntent) {
                $this->logger->info('Verwende Payment Intent für Zahlungsdaten');
                
                // Zahlungsdaten aus dem Payment Intent extrahieren
                $paymentData = [
                    'payment_intent_id' => $paymentIntent->id,
                    'amount' => $paymentIntent->amount,
                    'currency' => $paymentIntent->currency,
                    'status' => $paymentIntent->status,
                    'payment_method' => $paymentIntent->payment_method,
                    'receipt_email' => $paymentIntent->receipt_email,
                    'created' => $paymentIntent->created,
                    'metadata' => $paymentIntent->metadata ? $paymentIntent->metadata->toArray() : [],
                ];
                
                // Fülle Produktdaten mit Werten aus dem Payment Intent
                $productData = $sessionData['product_data'] ?? [];
                if (!isset($productData['title']) && isset($paymentIntent->metadata) && isset($paymentIntent->metadata->product_title)) {
                    $productData['title'] = $paymentIntent->metadata->product_title;
                }
                
                // Preis aus dem Payment Intent übernehmen, wenn nicht vorhanden
                if (empty($productData['price'])) {
                    $productData['price'] = $paymentIntent->amount;
                    $productData['stripe_currency'] = $paymentIntent->currency;
                }
                
                // Aktualisierte Produktdaten in der Sitzung speichern
                $sessionData['product_data'] = $productData;
            } else {
                // Nur bei einer regulären Stripe-Session versuchen, diese abzurufen
                try {
                    $this->logger->info('Versuche Stripe-Session abzurufen: ' . $sessionId);
                    $session = $this->stripeService->retrieveSession($sessionId);
                    
                    if (!$session) {
                        $this->logger->error('Stripe-Session nicht gefunden');
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
                } catch (\Exception $e) {
                    $this->logger->error('Fehler beim Abrufen der Stripe-Session: ' . $e->getMessage());
                    // Wir brechen hier nicht ab, da wir möglicherweise bereits Session-Daten haben
                }
            }
            
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
            
            // Bestimme die korrekte Session-ID für Datenbankoperationen
            // Wenn in sessionData keine session_id vorhanden ist, verwende die ursprüngliche sessionId
            $correctSessionId = isset($sessionData['session_id']) ? $sessionData['session_id'] : $sessionId;
            $this->logger->info('Verwende Session-ID für DB-Updates: ' . $correctSessionId . ' (Original: ' . $sessionId . ')');

            // Session in der Datenbank aktualisieren
            $this->sessionManager->updateSessionAfterPayment($correctSessionId, $paymentData);
            $this->logger->info('Session nach Zahlung aktualisiert mit ID: ' . $correctSessionId);
            
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
                                $correctSessionId,
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
                                        ['session_id' => $correctSessionId]
                                    );
                                    $this->logger->info('Dateipfad in der Datenbank gespeichert', [
                                        'file' => $downloadFile,
                                        'session_id' => $correctSessionId
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
                            $this->sessionManager->updateSessionData($correctSessionId, ['product_data' => $sessionData['product_data']]);
                            
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
                    'session_id' => $correctSessionId,
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
                
                // 1. Direkt aus den Schlüsseln in product_data
                $durationKeys = [
                    'subscription_duration', 'duration', 'membership_duration', 
                    'data-subscription-duration', 'data-duration', 'data_subscription_duration'
                ];
                
                foreach ($durationKeys as $key) {
                    if (isset($sessionData['product_data'][$key]) && !empty($sessionData['product_data'][$key])) {
                        $subscriptionDuration = intval($sessionData['product_data'][$key]);
                        $this->logger->info('Mitgliedschaftsdauer aus Schlüssel ' . $key . ' extrahiert: ' . $subscriptionDuration);
                        break;
                    }
                }
                
                // 2. Wenn keine Dauer gefunden wurde, prüfen wir payment_data
                if ($subscriptionDuration == 0 && isset($sessionData['payment_data']['duration'])) {
                    $subscriptionDuration = intval($sessionData['payment_data']['duration']);
                    $this->logger->info('Mitgliedschaftsdauer aus payment_data extrahiert: ' . $subscriptionDuration);
                }
                
                // 3. Wenn immer noch keine Dauer gefunden wurde, prüfen wir das productMarkup
                if ($subscriptionDuration == 0 && !empty($sessionData['product_markup'])) {
                    if (preg_match('/Mitgliedschaft:\s*(\d+)\s*Monate?/', $sessionData['product_markup'], $matches)) {
                        $subscriptionDuration = intval($matches[1]);
                        $this->logger->info('Mitgliedschaftsdauer aus product_markup extrahiert: ' . $subscriptionDuration);
                    }
                }
                
                // Detaillierte Protokollierung der vorhandenen Produktdaten
                $this->logger->info('Vorhandene Produktdaten zur Diagnose:', [
                    'alle_produkt_schlüssel' => array_keys($sessionData['product_data']),
                    'duration_wert' => $sessionData['product_data']['duration'] ?? 'nicht vorhanden',
                    'subscription_duration_wert' => $sessionData['product_data']['subscription_duration'] ?? 'nicht vorhanden'
                ]);

                // TEMPORÄRE LÖSUNG: Hardcoded Duration für Testzwecke, später entfernen!
                // In der Produktivversion wird die Duration aus der Produktkonfiguration kommen
                if ($subscriptionDuration == 0 && isset($sessionData['product_data']['duration']) && 
                    is_numeric($sessionData['product_data']['duration']) && $sessionData['product_data']['duration'] > 0) {
                    $subscriptionDuration = intval($sessionData['product_data']['duration']);
                    $this->logger->info('Setze subscription_duration aus duration-Feld: ' . $subscriptionDuration);
                }

                // 4. Als Fallback: Kein Enddatum setzen
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
                    'subscription_duration' => $subscriptionDuration
                ];
                
                // Explizite Debugging-Ausgabe der finalen userData
                $this->logger->info('Finale Benutzerdaten vor createOrUpdateUser:', [
                    'username' => $userData['username'],
                    'email' => $userData['email'],
                    'subscription_duration' => $userData['subscription_duration'],
                    'gruppen' => json_encode($userData['groups'])
                ]);
                
                // Wenn ein Passwort in den Benutzerdaten vorhanden ist, dieses verwenden
                if (isset($sessionData['user_creation']['password'])) {
                    $userData['password'] = $sessionData['user_creation']['password'];
                }
                
                $userId = $this->userService->createOrUpdateUser($userData);
            
                if ($userId) {
                    // Benutzer-ID in der Session speichern
                    $this->sessionManager->updateUserId($correctSessionId, $userId);
                    
                    // Speichern des Benutzernamens für die E-Mail-Templates
                    $sessionData['customer_data']['username'] = $userData['username'];
                    
                    // Benutzerinformationen in user_creation speichern
                    $sessionData['user_creation'] = [
                        'user_id' => $userId,
                        'username' => $userData['username'],
                        'email' => $userData['email'],
                        'created_at' => time()
                    ];
                    
                    // Aktualisierte Kundendaten speichern
                    $this->sessionManager->updateSessionData($correctSessionId, [
                        'customer_data' => $sessionData['customer_data'],
                        'user_creation' => $sessionData['user_creation']
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
                    $this->sessionManager->markEmailsAsSent($correctSessionId);
                } else {
                    $this->logger->error('Fehler beim Senden der E-Mails');
                }
            } else {
                $this->logger->warning('Keine E-Mail-Adresse für den Kunden vorhanden, keine E-Mails versendet.');
            }
            
            // Erfolgs-URL mit optionalen Parametern
            $successUrl = $targetUrl ?? $sessionData['product_data']['success_url'] ?? '/';
            $successUrl = $this->addParamsToUrl($successUrl, [
                'session_id' => $correctSessionId,
                'download_token' => $downloadToken
            ]);
            
            // Auf Erfolgsseite umleiten
            return $this->redirect($successUrl);
        } catch (\Exception $e) {
            $this->logger->error('Fehler bei der Verarbeitung der erfolgreichen Zahlung: ' . $e->getMessage());
            return $this->json(['error' => 'Fehler bei der Verarbeitung der Zahlung'], 500);
        }
    }
} 