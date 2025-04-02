<?php

declare(strict_types=1);

/*
 * This file is part of Caeli Auction Connect.
 *
 * (c) Caeli Wind - Christian Voss 2025 <christian.voss@caeli-wind.de>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/caeli-wind/caeli-auction-connect
 */

namespace CaeliWind\CaeliAuctionConnect\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AuctionService
{
    /**
     * Konstanten für den Cache
     */
    private const CACHE_LIFETIME = 3600; // 1 Stunde
    private const CACHE_DIR = 'var/caeli-auction-data';

    private ?string $apiToken = null;
    private ?string $csrfToken = null;
    private HttpClientInterface $httpClient;
    private string $apiUrl;
    private string $apiUsername;
    private string $apiPassword;
    private string $cookieFile;
    private string $cacheDir;

    /**
     * Konfiguration der filterbaren Felder.
     * Schlüssel: Der im $filters-Array erwartete Schlüssel.
     * Wert: Ein Array mit Konfigurationsoptionen:
     *   - 'field': Der Schlüssel im $auction-Array, auf den gefiltert wird.
     *   - 'type': Der Typ des Filters ('exact', 'minmax', 'lowercase_exact').
     *   - 'value_type' (optional): Erwarteter Datentyp des Wertes ('string', 'int', 'float', 'bool'). Standard: 'string'.
     */
    private array $filterableFields = [
        'bundesland' => ['field' => 'bundesland', 'type' => 'lowercase_exact'],
        'landkreis' => ['field' => 'district', 'type' => 'lowercase_exact'], // Beachte: Feld heißt 'district' im Mapping
        'status' => ['field' => 'status', 'type' => 'exact'],
        'size' => ['field' => 'flaeche_ha', 'type' => 'minmax', 'value_type' => 'float'],
        'leistung' => ['field' => 'leistung_mw', 'type' => 'minmax', 'value_type' => 'float'],
        'volllaststunden' => ['field' => 'volllaststunden', 'type' => 'minmax', 'value_type' => 'int'],
        // Weitere Felder können hier hinzugefügt werden, z.B.:
        // 'some_bool_field' => ['field' => 'is_active', 'type' => 'exact', 'value_type' => 'bool'],
        // 'some_exact_string' => ['field' => 'projekt_name', 'type' => 'exact'],
    ];

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ParameterBagInterface $params
    ) {
        $this->apiUrl = rtrim($this->params->get('caeli_auction.api_url'), '/');
        $this->apiUsername = $this->params->get('caeli_auction.api_username');
        $this->apiPassword = $this->params->get('caeli_auction.api_password');
        $this->httpClient = HttpClient::create();

        // Cookie-Datei für die API-Anfragen
        $this->cookieFile = sys_get_temp_dir() . '/caeli_auction_' . md5(session_id() . time()) . '.txt';

        // Cache-Verzeichnis
        $this->cacheDir = $_SERVER['DOCUMENT_ROOT'] . '/../' . self::CACHE_DIR;
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }

        $this->logger->debug('AuctionService initialisiert mit URL: ' . $this->apiUrl . ', Cache: ' . $this->cacheDir);
    }

    /**
     * API-Login und Token-Speicherung - direkt aus Cronjobs.php kopiert
     * Exakte Nachbildung der funktionierenden Implementierung
     */
    public function login(): bool
    {
        try {
            $this->logger->debug('Starte API-Login mit Benutzer: ' . $this->apiUsername);

            // Direkt aus Cronjobs.php kopiert
            $fields = json_encode([
                "email" => $this->apiUsername,
                "password" => $this->apiPassword,
            ]);

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $this->apiUrl . '/auth/login');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);
            curl_setopt($curl, CURLOPT_COOKIEJAR, $this->cookieFile);
            curl_setopt($curl, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);

            $this->logger->debug('Login-Anfrage an: ' . $this->apiUrl . '/auth/login');
            $result = curl_exec($curl);

            if (curl_error($curl)) {
                $this->logger->error('API-Login fehlgeschlagen (cURL): ' . curl_error($curl));
                curl_close($curl);
                return false;
            }

            $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            $this->logger->debug('Login-Antwort: Status ' . $statusCode . ', Inhalt: ' . $result);

            // Prüfung des Status-Codes
            if ($statusCode !== 200) {
                $this->logger->error('API-Login fehlgeschlagen: HTTP-Status ' . $statusCode);
                return false;
            }

            // Versuchen, das CSRF-Token zu extrahieren
            $data = json_decode($result, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('API-Antwort konnte nicht geparst werden: ' . json_last_error_msg());
                return false;
            }

            if (!isset($data['tokens']['csrf_session_id'])) {
                $this->logger->error('Kein CSRF-Token in der API-Antwort gefunden');
                return false;
            }

            $this->csrfToken = $data['tokens']['csrf_session_id'];
            $this->logger->info('API-Login erfolgreich, CSRF-Token erhalten: ' . $this->csrfToken);
            return true;

        } catch (\Exception $e) {
            $this->logger->error('API-Login fehlgeschlagen: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Methode zum Abrufen von Informationen zu einer spezifischen Auktion
     * Direkt aus Cronjobs.php (getPlotInfo) kopiert und angepasst
     */
    private function fetchAuctionRaw(string $id): ?array
    {
        try {
            // Sicherstellen, dass wir eingeloggt sind
            if (!$this->csrfToken && !$this->login()) {
                $this->logger->error('Nicht eingeloggt und Login fehlgeschlagen');
                return null;
            }

            // Exakt aus Cronjobs.php kopiert
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $this->apiUrl . '/api/auctions/' . $id);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($curl, CURLOPT_COOKIEJAR, $this->cookieFile);
            curl_setopt($curl, CURLOPT_COOKIEFILE, $this->cookieFile);
            curl_setopt($curl, CURLINFO_HEADER_OUT, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, [
                'X-CSRF-Token: ' . $this->csrfToken
            ]);

            $this->logger->debug('Anfrage nach Auktion: ' . $this->apiUrl . '/api/auctions/' . $id);
            $result = curl_exec($curl);

            if (curl_error($curl)) {
                $this->logger->error('API-Anfrage fehlgeschlagen (cURL): ' . curl_error($curl));
                curl_close($curl);
                return null;
            }

            $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $requestInfo = curl_getinfo($curl);
            curl_close($curl);

            $this->logger->debug('Auktions-Antwort: Status ' . $statusCode . ', Request: ' . json_encode($requestInfo));

            if ($statusCode !== 200) {
                $this->logger->error('API-Anfrage fehlgeschlagen: HTTP-Status ' . $statusCode);
                $this->logger->debug('Antwort: ' . $result);

                // Bei 401/403 versuchen wir einen erneuten Login
                if (in_array($statusCode, [401, 403])) {
                    $this->logger->warning('Versuche erneuten Login nach Authentifizierungsfehler');
                    $this->csrfToken = null;
                    $this->login();
                }

                return null;
            }

            // Antwort parsen
            $data = json_decode($result, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('API-Antwort konnte nicht geparst werden: ' . json_last_error_msg());
                return null;
            }

            return $data;

        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Abrufen der Auktion: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Methode zum Abrufen aller Auktionen
     * Ähnlich zu getPlotInfo, aber für alle Auktionen
     */
    private function fetchAuctionsRaw(array $filters = []): ?array
    {
        try {
            // Sicherstellen, dass wir eingeloggt sind
            if (!$this->csrfToken && !$this->login()) {
                $this->logger->error('Nicht eingeloggt und Login fehlgeschlagen');
                return null;
            }

            // Gleiche Struktur wie fetchAuctionRaw, aber anderer Endpunkt
            $url = $this->apiUrl . '/api/auctions';

            // Filter hinzufügen, falls vorhanden
            if (!empty($filters)) {
                $queryParams = [];

                // Bundesland
                if (!empty($filters['bundesland'])) {
                    $queryParams[] = 'bundesland=' . urlencode($filters['bundesland']);
                }

                // Landkreis
                if (!empty($filters['landkreis'])) {
                    $queryParams[] = 'landkreis=' . urlencode($filters['landkreis']);
                }

                // Status
                if (!empty($filters['status'])) {
                    $queryParams[] = 'status=' . urlencode($filters['status']);
                }

                // Flächengröße
                if (!empty($filters['size'])) {
                    if (!empty($filters['size']['min'])) {
                        $queryParams[] = 'flaeche_min=' . (int)$filters['size']['min'];
                    }
                    if (!empty($filters['size']['max'])) {
                        $queryParams[] = 'flaeche_max=' . (int)$filters['size']['max'];
                    }
                }

                // Leistung
                if (!empty($filters['leistung'])) {
                    if (!empty($filters['leistung']['min'])) {
                        $queryParams[] = 'leistung_min=' . (int)$filters['leistung']['min'];
                    }
                    if (!empty($filters['leistung']['max'])) {
                        $queryParams[] = 'leistung_max=' . (int)$filters['leistung']['max'];
                    }
                }

                if (!empty($queryParams)) {
                    $url .= '?' . implode('&', $queryParams);
                }
            }

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($curl, CURLOPT_COOKIEJAR, $this->cookieFile);
            curl_setopt($curl, CURLOPT_COOKIEFILE, $this->cookieFile);
            curl_setopt($curl, CURLINFO_HEADER_OUT, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, [
                'X-CSRF-Token: ' . $this->csrfToken
            ]);

            $this->logger->debug('Anfrage nach Auktionen: ' . $url);
            $result = curl_exec($curl);

            if (curl_error($curl)) {
                $this->logger->error('API-Anfrage fehlgeschlagen (cURL): ' . curl_error($curl));
                curl_close($curl);
                return null;
            }

            $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $requestInfo = curl_getinfo($curl);
            curl_close($curl);

            $this->logger->debug('Auktionen-Antwort: Status ' . $statusCode . ', Request: ' . json_encode($requestInfo));

            if ($statusCode !== 200) {
                $this->logger->error('API-Anfrage fehlgeschlagen: HTTP-Status ' . $statusCode);
                $this->logger->debug('Antwort: ' . $result);

                // Bei 401/403 versuchen wir einen erneuten Login
                if (in_array($statusCode, [401, 403])) {
                    $this->logger->warning('Versuche erneuten Login nach Authentifizierungsfehler');
                    $this->csrfToken = null;
                    $this->login();
                }

                return null;
            }

            // Antwort parsen
            $data = json_decode($result, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('API-Antwort konnte nicht geparst werden: ' . json_last_error_msg());
                return null;
            }

            return $data;

        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Abrufen der Auktionen: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Implementierung analog zu getPublicMarketplace() aus Cronjobs.php
     * Verwendet Basic Authentication statt Login/CSRF
     *
     * @param bool $useCache Gibt an, ob der Cache verwendet werden soll
     * @return array|null
     */
    private function getPublicAuctions(bool $useCache = true): ?array
    {
        $cacheFile = $this->cacheDir . '/auctions.json';
        $this->logger->debug('[getPublicAuctions] Prüfe Cache: ' . $cacheFile . ' | useCache=' . ($useCache ? 'true' : 'false'));

        if ($useCache && file_exists($cacheFile)) {
            $cacheAge = time() - filemtime($cacheFile);
            $this->logger->debug('[getPublicAuctions] Cache-Datei gefunden, Alter: ' . $cacheAge . 's (Lifetime: ' . self::CACHE_LIFETIME . 's)');

            if ($cacheAge < self::CACHE_LIFETIME) {
                $this->logger->info('[getPublicAuctions] Versuche, gültigen Cache zu verwenden: ' . $cacheFile);
                $cachedData = @file_get_contents($cacheFile);

                if ($cachedData === false) {
                    $this->logger->error('[getPublicAuctions] FEHLER beim Lesen der Cache-Datei: ' . $cacheFile);
                } else {
                    $this->logger->debug('[getPublicAuctions] Cache-Datei erfolgreich gelesen (Größe: ' . strlen($cachedData) . ' Bytes).');

                    $auctionsFromCache = json_decode($cachedData, true);

                    if (json_last_error() === JSON_ERROR_NONE && is_array($auctionsFromCache)) {
                        $rawCount = count($auctionsFromCache);
                        // exit; // --> Hier evtl. stoppen zum Prüfen der Ausgabe
                        $this->logger->info('[getPublicAuctions] Cache JSON erfolgreich dekodiert. Enthält ' . $rawCount . ' Roh-Auktionen. Starte Mapping...');

                        $mappedAuctions = [];
                        $mappingErrors = 0;
                        foreach ($auctionsFromCache as $index => $auctionRaw) {
                            try {
                                // Versuch, die einzelne Auktion zu mappen
                                $mappedAuction = $this->mapPublicAuctionToInternalFormat($auctionRaw);
                                $mappedAuctions[] = $mappedAuction;
                                // Optional: Detailliertes Logging für erfolgreiches Mapping
                                // $this->logger->debug('[getPublicAuctions] Cache-Item ' . $index . ' erfolgreich gemappt.', ['id' => $mappedAuction['id'] ?? 'unbekannt']);
                            } catch (\Throwable $e) {
                                // Fehler beim Mappen einer einzelnen Auktion loggen, aber weitermachen
                                $mappingErrors++;
                                $this->logger->error('[getPublicAuctions] FEHLER beim Mappen einer Auktion aus Cache (Index: ' . $index . '): ' . $e->getMessage(), [
                                    'auctionId_raw' => $auctionRaw['auctionId'] ?? 'unbekannt',
                                    'exception_trace' => $e->getTraceAsString() // Mehr Details bei Fehlern
                                ]);
                            }
                        }

                        $mappedCount = count($mappedAuctions);
                        $this->logger->info('[getPublicAuctions] Mapping aus Cache beendet. ' . $mappedCount . ' von ' . $rawCount . ' Auktionen erfolgreich gemappt (' . $mappingErrors . ' Fehler).');

                        if ($mappedCount > 0) {
                            $this->logger->info('[getPublicAuctions] Gebe ' . $mappedCount . ' gemappte Auktionen aus Cache zurück.');
                            return $mappedAuctions; // Gemappte Daten zurückgeben
                        } else {
                            $this->logger->warning('[getPublicAuctions] Cache war gültig, aber nach Mapping sind keine Auktionen übrig geblieben (oder alle enthielten Fehler). Versuche API-Abruf.');
                            // Nicht abbrechen, sondern versuchen, frisch von der API zu laden
                        }

                    } else {
                        $this->logger->error('[getPublicAuctions] FEHLER: Cache-Datei ist KEIN gültiges JSON oder kein Array.', ['json_error' => json_last_error_msg()]);
                        @unlink($cacheFile); // Korrupten Cache löschen
                        $this->logger->info('[getPublicAuctions] Korrupte Cache-Datei gelöscht: ' . $cacheFile);
                    }
                }
            } else {
                $this->logger->info('[getPublicAuctions] Cache ist abgelaufen.');
            }
        } else {
             if ($useCache) $this->logger->info('[getPublicAuctions] Keine Cache-Datei gefunden.');
             else $this->logger->info('[getPublicAuctions] Cache-Nutzung ist deaktiviert (forceRefresh=true).');
        }

        // ----- Wenn Cache nicht verwendet/gültig/erfolgreich war, von API laden -----
        try {
            $this->logger->info('[getPublicAuctions] Lade Auktionsdaten von der Public API...');

            $ch = curl_init();
            $url = $this->params->get('caeli_auction.marketplace_api_url');
            $BasicAuth = $this->params->get('caeli_auction.marketplace_api_auth');

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic '.$BasicAuth]);
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            // Ggf. Timeout hinzufügen
            // curl_setopt($ch, CURLOPT_TIMEOUT, 15); // 15 Sekunden Timeout

            $this->logger->debug('[getPublicAuctions] Sende API-Anfrage an: ' . $url);
            $result = curl_exec($ch);
            $curlError = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($curlError) {
                $this->logger->error('[getPublicAuctions] API-Anfrage fehlgeschlagen (cURL Fehler): ' . $curlError);
                return null;
            }

            $this->logger->debug('[getPublicAuctions] API-Antwort erhalten: HTTP-Status ' . $httpCode);

            if ($httpCode !== 200) {
                $this->logger->error('[getPublicAuctions] API-Anfrage fehlgeschlagen: Unerwarteter HTTP-Status ' . $httpCode);
                $this->logger->debug('[getPublicAuctions] API-Antwort-Body: ' . substr($result ?: '', 0, 500)); // Gekürzter Body
                return null;
            }

            $auctionsFromApi = json_decode($result, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($auctionsFromApi)) {
                $this->logger->error('[getPublicAuctions] FEHLER: API-Antwort ist KEIN gültiges JSON oder kein Array.', ['json_error' => json_last_error_msg()]);
                return null;
            }

            $apiRawCount = count($auctionsFromApi);
            $this->logger->info('[getPublicAuctions] API hat ' . $apiRawCount . ' Roh-Auktionen zurückgegeben. Speichere Rohdaten im Cache...');

            // Im Cache speichern (die Rohdaten!)
            if (!is_dir($this->cacheDir)) {
                @mkdir($this->cacheDir, 0755, true);
            }
            if (@file_put_contents($cacheFile, $result) === false) {
                $this->logger->error('[getPublicAuctions] FEHLER beim Schreiben der Cache-Datei: ' . $cacheFile);
            } else {
                $this->logger->debug('[getPublicAuctions] API-Rohdaten im Cache gespeichert: ' . $cacheFile);
            }

            // Mapping der API-Daten
            $this->logger->info('[getPublicAuctions] Starte Mapping der ' . $apiRawCount . ' Auktionen von API...');
            $mappedAuctions = [];
            $mappingErrorsApi = 0;
            $filteredOutCount = 0;

            // Definiere gültige Status
            $validStatuses = [
                'STARTED', 'FIRST_ROUND', 'SECOND_ROUND', 'FIRST_ROUND_EVALUATION',
                'PRE_RELEASE', 'PREVIEW', 'OPEN_FOR_DIRECT_AWARDING', 'DIRECT_AWARDING', 'AWARDING'
            ];

            foreach ($auctionsFromApi as $index => $auctionRaw) {
                $status = $auctionRaw['status'] ?? null;
                $auctionIdForLogApi = $auctionRaw['auctionId'] ?? 'unbekannt';

                // Status-Filterung *vor* dem Mapping
                if (in_array($status, $validStatuses)) {
                    try {
                        $mappedAuction = $this->mapPublicAuctionToInternalFormat($auctionRaw);
                        $mappedAuctions[] = $mappedAuction;
                        // $this->logger->debug('[getPublicAuctions] API-Item ' . $index . ' erfolgreich gemappt.', ['id' => $mappedAuction['id'] ?? 'unbekannt']);
                    } catch (\Throwable $e) {
                        $mappingErrorsApi++;
                        $this->logger->error('[getPublicAuctions] FEHLER beim Mappen einer Auktion von API (Index: ' . $index . '): ' . $e->getMessage(), [
                            'auctionId_raw' => $auctionIdForLogApi,
                            'exception_trace' => $e->getTraceAsString()
                        ]);
                    }
                } else {
                    $filteredOutCount++;
                    $this->logger->debug('[getPublicAuctions] API-Item übersprungen wegen Status-Filter', ['id' => $auctionIdForLogApi, 'status' => $status]);
                }
            }

            $mappedApiCount = count($mappedAuctions);
            $this->logger->info('[getPublicAuctions] Mapping von API-Daten beendet. ' . $mappedApiCount . ' von ' . ($apiRawCount - $filteredOutCount) . ' Auktionen (nach Statusfilter) erfolgreich gemappt (' . $mappingErrorsApi . ' Fehler). ' . $filteredOutCount . ' wurden wegen Status entfernt.');

             // Fallback ohne Statusfilter (wie vorher), falls $mappedAuctions leer ist, aber $auctionsFromApi nicht
             if ($mappedApiCount === 0 && $apiRawCount > 0 && $filteredOutCount === $apiRawCount) { // Nur wenn *alle* wegen Status gefiltert wurden
                 $this->logger->warning('[getPublicAuctions] Alle Auktionen wurden durch den Status-Filter entfernt. Versuche Fallback ohne Status-Filter.');
                 $statuses = array_map(fn($a) => $a['status'] ?? 'kein-status', $auctionsFromApi);
                 $this->logger->debug('[getPublicAuctions] Verfügbare Status-Werte von API (für Fallback): ' . implode(', ', array_unique($statuses)));

                $mappingErrorsFallback = 0;
                foreach ($auctionsFromApi as $index => $auctionRaw) { // Erneut über Rohdaten iterieren
                     try {
                         $mappedAuction = $this->mapPublicAuctionToInternalFormat($auctionRaw);
                         $mappedAuctions[] = $mappedAuction; // Füge zum selben Array hinzu
                     } catch (\Throwable $e) {
                         $mappingErrorsFallback++;
                         $this->logger->error('[getPublicAuctions] FEHLER beim Mappen von API (Fallback, Index: ' . $index . '): ' . $e->getMessage(), [
                             'auctionId_raw' => $auctionRaw['auctionId'] ?? 'unbekannt',
                             'exception_trace' => $e->getTraceAsString()
                         ]);
                     }
                 }
                 $finalMappedCountFallback = count($mappedAuctions); // Endgültige Zahl nach Fallback
                 $this->logger->info('[getPublicAuctions] Mapping von API-Daten (Fallback) beendet. Insgesamt ' . $finalMappedCountFallback . ' Auktionen gemappt (' . $mappingErrorsFallback . ' Fehler im Fallback).');
             }


            $this->logger->info('[getPublicAuctions] Gebe ' . count($mappedAuctions) . ' gemappte Auktionen von API zurück.');
            return $mappedAuctions;

        } catch (\Throwable $e) { // Throwable fängt auch ParseError etc.
            $this->logger->error('[getPublicAuctions] KRITISCHER FEHLER beim Abrufen/Verarbeiten der öffentlichen Auktionen von API: ' . $e->getMessage());
            $this->logger->error('Stack Trace: ' . $e->getTraceAsString());
            return null; // Sicherstellen, dass bei Fehlern null zurückgegeben wird
        }
    }

    /**
     * Öffentliche Methode zum Abrufen mehrerer Auktionen per IDs
     * Korrigiert: Prüft IDs robuster nach der Konvertierung.
     */
    public function getAuctionsByIds(array $ids): array
    {
        $this->logger->debug('Auktionen mit IDs werden abgerufen: ' . implode(', ', $ids));

        $result = [];
        // Hole *alle* öffentlichen Auktionen (diese sind bereits gemappt durch getPublicAuctions)
        $allAuctions = $this->getPublicAuctions();

        if ($allAuctions === null) {
            $this->logger->warning('Keine öffentlichen Auktionen beim Abruf für getAuctionsByIds gefunden.');
            return []; // Leeres Array zurückgeben
        }

        // IDs normalisieren (zu Strings konvertieren) für konsistenten Vergleich
        $normalizedIds = array_map('strval', $ids);
        $this->logger->debug('Suche nach normalisierten IDs: ' . implode(', ', $normalizedIds));

        foreach ($allAuctions as $auction) {
            // DEBUGGING: Logge die ID jeder geprüften Auktion
            $currentAuctionIdForLog = $auction['id'] ?? ($auction['_raw_data']['auctionId'] ?? 'KEINE_ID_GEFUNDEN');
            $this->logger->debug('[getAuctionsByIds] Prüfe Auktion mit ID: ' . $currentAuctionIdForLog);

            // Prüfe, ob die 'id' (die in mapPublicAuctionToInternalFormat als String gesetzt wird) existiert und im Array der gesuchten IDs ist
            if (isset($auction['id']) && in_array($auction['id'], $normalizedIds, true)) {
                 $this->logger->debug('[getAuctionsByIds] Auktion gefunden für ID: ' . $auction['id']);
                $result[] = $auction;
            }
             // Fallback: Prüfe die rohe auctionId, falls das Mapping fehlschlug oder 'id' fehlt
             elseif (isset($auction['_raw_data']['auctionId']) && in_array((string)$auction['_raw_data']['auctionId'], $normalizedIds, true)) {
                 $rawId = (string)$auction['_raw_data']['auctionId'];
                 $this->logger->debug('Auktion über _raw_data gefunden für ID: ' . $rawId);
                 // Füge die ID hinzu, falls sie fehlt
                 if (!isset($auction['id'])) {
                     $auction['id'] = $rawId;
                 }
                 $result[] = $auction;
             }
        }

        if (empty($result)) {
             $this->logger->warning('Keine der gesuchten IDs (' . implode(', ', $normalizedIds) . ') konnte in den verfügbaren Auktionsdaten gefunden werden.');
             // Optional: Logge verfügbare IDs zum Debuggen
             $availableIds = [];
             foreach ($allAuctions as $a) {
                 $availableIds[] = (string)($a['id'] ?? $a['_raw_data']['auctionId'] ?? 'FEHLENDE_ID');
             }
             $this->logger->debug('Verfügbare IDs nach Mapping waren: ' . implode(', ', array_unique($availableIds)));
        } else {
             $this->logger->info(count($result) . ' Auktionen für die angeforderten IDs gefunden.');
        }

        return $result;
    }

    /**
     * Konvertiert die öffentlichen Auktionsdaten in das interne Format für die Verwendung in Contao
     * Korrigiert: Stellt sicher, dass auctionId als String übergeben wird und Bildpfade hinzugefügt werden.
     */
    private function mapPublicAuctionToInternalFormat(array $auction): array
    {
        $auctionIdRaw = $auction['auctionId'] ?? null;
        $this->logger->debug('Konvertiere Auktion: ' . $auctionIdRaw);

        // Kernfelder - Stelle sicher, dass die ID als String gespeichert wird
        $auctionIdString = $auctionIdRaw !== null ? (string)$auctionIdRaw : null;
        $result = [
            '_raw_data' => $auction,
            'id' => $auctionIdString, // Konsistente String-ID hinzufügen
            'picture_path' => null,    // Standardmäßig null
            'picture_filename' => null // Standardmäßig null
        ];

        // Bild herunterladen und Pfade hinzufügen, falls Dateiname und ID vorhanden
        $pictureFilename = $auction['areaPictureFileName'] ?? null;
        if (!empty($pictureFilename) && $auctionIdString !== null) {
             $this->logger->debug('Versuche Bild herunterzuladen', ['file' => $pictureFilename, 'id' => $auctionIdString]);
            // Stelle sicher, dass auctionId als String übergeben wird
            $localImagePath = $this->downloadAuctionImage($pictureFilename, $auctionIdString);

             if (!empty($localImagePath)) {
                 $this->logger->debug('Bild erfolgreich heruntergeladen/gefunden', ['localPath' => $localImagePath]);
                 // Erzeuge den relativen Web-Pfad aus dem DOCUMENT_ROOT
                 // Wichtig: Passe dies an, falls Contao eine andere Methode zur Pfadgenerierung erwartet (z.B. über FilesModel)
                 $webPath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $localImagePath);
                 $result['picture_path'] = $webPath; // Relativer Pfad für <img src="...">
                 $result['picture_filename'] = $pictureFilename;
                 // DEBUGGING: Logge den generierten Web-Pfad
                 $this->logger->debug('[mapPublicAuctionToInternalFormat] Generierter picture_path: ' . $webPath, ['id' => $auctionIdString]);
             } else {
                  $this->logger->warning('Bild konnte nicht heruntergeladen oder gefunden werden', ['file' => $pictureFilename, 'id' => $auctionIdString]);
                  // DEBUGGING: Logge, dass kein Pfad gesetzt wurde
                  $this->logger->debug('[mapPublicAuctionToInternalFormat] picture_path bleibt null (Download fehlgeschlagen)', ['id' => $auctionIdString]);
             }
        } else {
             if (empty($pictureFilename)) $this->logger->debug('Kein Bild-Dateiname für Auktion vorhanden', ['id' => $auctionIdString]);
             if ($auctionIdString === null) $this->logger->debug('Keine Auktions-ID für Bild-Download vorhanden', ['file' => $pictureFilename]);
             // DEBUGGING: Logge, dass kein Pfad gesetzt wurde
             $this->logger->debug('[mapPublicAuctionToInternalFormat] picture_path bleibt null (kein Dateiname oder ID)', ['id' => $auctionIdString]);
        }


        // Füge hier weitere Mappings von API-Feldern zu internen Feldern hinzu,
        // achte dabei auf korrekte Datentypen (string, int, float, bool, array, null)
        $result['status'] = $auction['status'] ?? null;
        $result['bundesland'] = $auction['state'] ?? null; // Annahme: API 'state' -> intern 'bundesland'
        $result['district'] = $auction['district'] ?? null; // Prüfe, ob 'district' im API-Response existiert
        $result['title'] = $auction['areaName'] ?? 'Unbenannte Auktion ' . $auctionIdString;
        $result['flaeche_ha'] = isset($auction['areaSize']) ? (float)$auction['areaSize'] : null;
        $result['leistung_mw'] = isset($auction['power']) ? (float)$auction['power'] : null;
        $result['volllaststunden'] = isset($auction['fullUsageHours']) ? (int)$auction['fullUsageHours'] : null;
        // ... weitere Felder hier mappen ...


        return $result;
    }

    /**
     * Lädt ein Auktionsbild von der API herunter und speichert es lokal
     */
    private function downloadAuctionImage(string $filename, ?string $auctionId = null): string
    {
        // Zielverzeichnis im Contao-Dateisystem
        $targetDir = $_SERVER['DOCUMENT_ROOT'] . '/files/auction/images';
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        // Wenn eine Auktions-ID übergeben wurde, erstellen wir ein Unterverzeichnis pro Auktion
        if ($auctionId) {
            $targetDir = $_SERVER['DOCUMENT_ROOT'] . '/files/auction/images/' . $auctionId;
            if (!is_dir($targetDir)) {
                @mkdir($targetDir, 0755, true);
            }
        }

        $targetFile = $targetDir . '/' . $filename;

        // Prüfen, ob die Datei bereits existiert und aktuell ist
        if (file_exists($targetFile) && filemtime($targetFile) > (time() - 86400)) {
            $this->logger->debug('Bild existiert bereits und ist aktuell: ' . $filename);
            return $targetFile;
        }

        try {
            // Bildadresse auf der API
            $apiBaseUrl = rtrim($this->params->get('caeli_auction.marketplace_api_url'), '/');
            $imageUrl = $apiBaseUrl . '/area-picture/' . $filename;

            // BasicAuth aus NEdev Methode übernehmen
            $BasicAuth = $this->params->get('caeli_auction.marketplace_api_auth');

            $this->logger->debug('Lade Bild herunter: ' . $imageUrl . ' mit Auth');

            // Anfrage exakt wie in NEdev implementieren
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $imageUrl);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($curl, CURLOPT_HTTPHEADER, [
                'Authorization: Basic ' . $BasicAuth
            ]);
            curl_setopt($curl, CURLINFO_HEADER_OUT, true);

            $result = curl_exec($curl);
            $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($statusCode !== 200) {
                $this->logger->error('Fehler beim Herunterladen des Bildes: HTTP ' . $statusCode);
                return '';
            }

            // Speichern der Datei
            if (file_put_contents($targetFile, $result) !== false) {
                $this->logger->info('Bild erfolgreich heruntergeladen: ' . $filename);

                // Optional: Erstellen einer unschärfen Version und Webp-Konvertierung wie in NEdev
                if (class_exists('\Imagick')) {
                    try {
                        // Unscharfes Bild generieren
                        $image = new \Imagick($targetFile);
                        $blurFilename = md5($auctionId ?? $filename) . ".png";
                        $blurTargetFile = $targetDir . '/' . $blurFilename;

                        // Unschärfe anwenden
                        $image->blurImage(10, 6);
                        file_put_contents($blurTargetFile, $image);

                        // WebP konvertieren
                        $this->convertPngToWebp($targetFile);
                        $this->convertPngToWebp($blurTargetFile);
                    } catch (\Exception $e) {
                        $this->logger->warning('Fehler bei der Bildverarbeitung: ' . $e->getMessage());
                    }
                }

                return $targetFile;
            } else {
                $this->logger->error('Fehler beim Speichern des Bildes: ' . $filename);
            }
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Herunterladen des Bildes: ' . $e->getMessage());
        }

        return '';
    }

    /**
     * Konvertiert ein PNG-Bild in WebP-Format
     * Direkt aus NEdev kopiert
     */
    private function convertPngToWebp(string $file): void
    {
        if (!file_exists($file) || mime_content_type($file) != "image/png") {
            $this->logger->warning('Datei ist keine PNG-Datei: ' . $file);
            return;
        }

        // Webp-Datei existiert bereits?
        $webpFile = str_replace('png', 'webp', $file);
        if (file_exists($webpFile)) {
            return;
        }

        // get png in question
        $pngimg = imagecreatefrompng($file);
        // get dimensions of image
        $w = imagesx($pngimg);
        $h = imagesy($pngimg);
        // create a canvas
        $im = imagecreatetruecolor($w, $h);
        imageAlphaBlending($im, false);
        imageSaveAlpha($im, true);
        // By default, the canvas is black, so make it transparent
        $trans = imagecolorallocatealpha($im, 0, 0, 0, 127);
        imagefilledrectangle($im, 0, 0, $w - 1, $h - 1, $trans);
        // copy png to canvas
        imagecopy($im, $pngimg, 0, 0, 0, 0, $w, $h);
        // save canvas as webp
        imagewebp($im, $webpFile);
        // clean up
        imagedestroy($im);
    }

    /**
     * Löscht den Cache für die Auktionsdaten
     */
    public function clearCache(): bool
    {
        $cacheFile = $this->cacheDir . '/auctions.json';
        if (file_exists($cacheFile)) {
            $result = @unlink($cacheFile);
            $this->logger->info('Cache gelöscht: ' . $cacheFile . ' (Erfolg: ' . ($result ? 'ja' : 'nein') . ')');
            return $result;
        }

        return false;
    }

    /**
     * Öffentliche Methode zum Abrufen von Auktionen
     *
     * @param array $filters Filter für die Auktionen
     * @param bool $forceRefresh Erzwingt eine Aktualisierung der Daten (kein Cache)
     * @return array
     */
    public function getAuctions(array $filters = [], bool $forceRefresh = false): array
    {
        $this->logger->debug('[getAuctions] Auktionen werden abgerufen mit Filtern: ' . json_encode($filters));

        // Auktionen abrufen (ggf. aus dem Cache)
        $auctions = $this->getPublicAuctions(!$forceRefresh);

        if ($auctions === null) {
            $this->logger->error('[getAuctions] Keine Auktionsdaten von getPublicAuctions erhalten.');
            return [];
        }
        $initialCount = count($auctions);
        $this->logger->info('[getAuctions] ' . $initialCount . ' gemapte Auktionen von getPublicAuctions erhalten.');


        // Filter anwenden (auf die *gemappten* Daten)
        if (!empty($filters)) {
            $this->logger->debug('[getAuctions] Wende Filter an: ' . json_encode($filters));
            $filteredAuctions = [];

            foreach ($auctions as $auction) {
                $include = true;
                $auctionIdForLog = $auction['id'] ?? 'unbekannte_ID'; // Für Logging

                foreach ($filters as $filterKey => $filterValue) {
                    // Überspringe leere Filterwerte (außer bei boolschen Filtern, falls implementiert)
                    if (empty($filterValue) && !is_bool($filterValue)) {
                         // Optional: Loggen, dass Filter wegen leerem Wert übersprungen wird
                         // $this->logger->debug("[getAuctions] Filter '$filterKey' übersprungen (leerer Wert).", ['auction_id' => $auctionIdForLog]);
                        continue;
                    }

                    // Prüfe, ob der Filter konfiguriert ist
                    if (!isset($this->filterableFields[$filterKey])) {
                        $this->logger->warning("[getAuctions] Unbekannter Filter '$filterKey' wurde ignoriert.", ['auction_id' => $auctionIdForLog]);
                        continue;
                    }

                    $config = $this->filterableFields[$filterKey];
                    $auctionField = $config['field'];
                    $filterType = $config['type'];
                    $valueType = $config['value_type'] ?? 'string'; // Standard: string

                    // Hole den Wert aus der Auktion, prüfe auf Existenz
                    if (!isset($auction[$auctionField])) {
                        $this->logger->debug("[getAuctions] Filter '$filterKey' übersprungen (Feld '$auctionField' nicht in Auktion vorhanden).", ['auction_id' => $auctionIdForLog]);
                         $include = false; // Auktionen ohne das Feld ausschließen
                        break; // Zum nächsten Auktions-Datensatz gehen
                    }
                    $auctionValue = $auction[$auctionField];

                    // --- Filterlogik basierend auf dem Typ ---
                    switch ($filterType) {
                        case 'exact':
                            // Typumwandlung falls nötig
                            if ($valueType === 'bool' && !is_bool($filterValue)) {
                                $filterValue = filter_var($filterValue, FILTER_VALIDATE_BOOLEAN);
                            } elseif ($valueType === 'int' && !is_int($filterValue)) {
                                $filterValue = (int)$filterValue;
                            } elseif ($valueType === 'float' && !is_float($filterValue)) {
                                $filterValue = (float)$filterValue;
                            }
                             // Null-Prüfung für Status
                             if ($auctionField === 'status' && ($auctionValue ?? null) !== $filterValue) {
                                $this->logger->debug("[getAuctions] Filter '$filterKey' (exact) nicht erfüllt.", ['auction_id' => $auctionIdForLog, 'auction_value' => $auctionValue ?? '?', 'filter_value' => $filterValue]);
                                $include = false;
                             } elseif ($auctionField !== 'status' && $auctionValue != $filterValue) { // != für Typ-unsensiblen Vergleich, außer bei Status
                                 $this->logger->debug("[getAuctions] Filter '$filterKey' (exact) nicht erfüllt.", ['auction_id' => $auctionIdForLog, 'auction_value' => $auctionValue ?? '?', 'filter_value' => $filterValue]);
                                 $include = false;
                             }
                            break;

                        case 'lowercase_exact':
                            if (strtolower((string)$auctionValue ?? '') !== strtolower((string)$filterValue)) {
                                $this->logger->debug("[getAuctions] Filter '$filterKey' (lowercase_exact) nicht erfüllt.", ['auction_id' => $auctionIdForLog, 'auction_value' => $auctionValue ?? '?', 'filter_value' => $filterValue]);
                                $include = false;
                            }
                            break;

                        case 'minmax':
                            if (!is_array($filterValue)) {
                                $this->logger->warning("[getAuctions] Filter '$filterKey' (minmax) erfordert ein Array als Wert (gegeben: " . gettype($filterValue) . "). Filter ignoriert.", ['auction_id' => $auctionIdForLog]);
                                break; // Ignoriere diesen spezifischen Filter für diese Auktion
                            }
                             if ($auctionValue === null) {
                                 $this->logger->debug("[getAuctions] Filter '$filterKey' (minmax) übersprungen (kein Wert in Auktion).", ['auction_id' => $auctionIdForLog]);
                                 $include = false; // Annahme: Auktionen ohne Wert rausfiltern
                                 break; // Breche die Filterprüfung für DIESE Auktion ab, da min/max nicht anwendbar
                             }

                            $numericAuctionValue = 0;
                            if ($valueType === 'int') {
                                $numericAuctionValue = (int)$auctionValue;
                            } elseif ($valueType === 'float') {
                                $numericAuctionValue = (float)$auctionValue;
                            } else {
                                 $this->logger->warning("[getAuctions] Filter '$filterKey' (minmax) erfordert value_type 'int' oder 'float'. Aktuell: '$valueType'.", ['auction_id' => $auctionIdForLog]);
                                 break;
                            }


                            // Min-Prüfung
                            if (!empty($filterValue['min'])) {
                                $minFilter = ($valueType === 'int') ? (int)$filterValue['min'] : (float)$filterValue['min'];
                                if ($numericAuctionValue < $minFilter) {
                                    $this->logger->debug("[getAuctions] Filter '$filterKey' (min) nicht erfüllt.", ['auction_id' => $auctionIdForLog, 'auction_value' => $numericAuctionValue, 'filter_min' => $minFilter]);
                                    $include = false;
                                }
                            }

                            // Max-Prüfung (nur wenn Min-Prüfung bestanden oder nicht vorhanden)
                            if ($include && !empty($filterValue['max'])) {
                                $maxFilter = ($valueType === 'int') ? (int)$filterValue['max'] : (float)$filterValue['max'];
                                if ($numericAuctionValue > $maxFilter) {
                                    $this->logger->debug("[getAuctions] Filter '$filterKey' (max) nicht erfüllt.", ['auction_id' => $auctionIdForLog, 'auction_value' => $numericAuctionValue, 'filter_max' => $maxFilter]);
                                    $include = false;
                                }
                            }
                            break;

                        // Füge hier weitere Filtertypen hinzu, falls benötigt (z.B. 'contains', 'date_range', etc.)

                        default:
                            $this->logger->warning("[getAuctions] Unbekannter Filtertyp '$filterType' für Filter '$filterKey'. Filter ignoriert.", ['auction_id' => $auctionIdForLog]);
                            break;
                    }

                    // Wenn ein Filter nicht erfüllt ist, brauchen wir nicht weiter zu prüfen für diese Auktion
                    if (!$include) {
                        break; // Bricht die innere foreach ($filters as ...) Schleife ab
                    }
                } // Ende Filter-Schleife

                if ($include) {
                    // $this->logger->debug('[getAuctions] Auktion erfüllt alle Filter', ['auction_id' => $auctionIdForLog]);
                    $filteredAuctions[] = $auction;
                }
            } // Ende Auktions-Schleife

            $auctions = $filteredAuctions;
            $this->logger->info('[getAuctions] Nach Filterung verbleiben ' . count($auctions) . ' Auktionen.');
        } else {
             $this->logger->info('[getAuctions] Keine Filter angewendet, ' . $initialCount . ' Auktionen werden zurückgegeben.');
        }


        return array_values($auctions); // Indizes zurücksetzen
    }

    /**
     * Öffentliche Methode zum Abrufen einer spezifischen Auktion per ID
     */
    public function getAuctionById(string $id): ?array
    {
        $this->logger->debug('Auktion mit ID wird abgerufen: ' . $id);

        // Alle Auktionen abrufen und Cache-Refresh erzwingen
        $auctions = $this->getPublicAuctions(false);

        if (empty($auctions)) {
            $this->logger->warning('Keine Auktionen gefunden, daher kann auch keine Auktion mit ID ' . $id . ' gefunden werden.');
            return null;
        }

        $this->logger->debug('Anzahl gefundener Auktionen: ' . count($auctions));

        // Debug: Alle verfügbaren IDs ausgeben
        $availableIds = [];
        foreach ($auctions as $auction) {
            if (isset($auction['id'])) {
                $availableIds[] = (string)$auction['id'];
            }
            if (isset($auction['_raw_data']) && isset($auction['_raw_data']['auctionId'])) {
                $availableIds[] = 'raw:' . (string)$auction['_raw_data']['auctionId'];
            }
        }
        $this->logger->debug('Verfügbare Auktions-IDs: ' . implode(', ', $availableIds));

        // Debug: Gesamte Auktionsdaten anzeigen
        $this->logger->debug('Erste Auktion zum Debuggen: ' . (isset($auctions[0]) ? json_encode($auctions[0]) : 'keine verfügbar'));

        // Normalisierte ID für Vergleiche
        $normalizedId = trim((string)$id);

        // Erweiterte Suche mit mehreren Strategien
        foreach ($auctions as $auction) {
            // 1. Standard-ID-Vergleich (als String)
            if (isset($auction['id']) && (string)$auction['id'] === $normalizedId) {
                $this->logger->debug('Auktion mit ID ' . $id . ' wurde gefunden (direkter String-Vergleich)');
                return $auction;
            }

            // 2. ID-Vergleich (als Integer)
            if (isset($auction['id']) && is_numeric($auction['id']) && is_numeric($normalizedId) && (int)$auction['id'] === (int)$normalizedId) {
                $this->logger->debug('Auktion mit ID ' . $id . ' wurde gefunden (Integer-Vergleich)');
                return $auction;
            }

            // 3. Raw-Data-Vergleich (als String)
            if (isset($auction['_raw_data']) && isset($auction['_raw_data']['auctionId']) && (string)$auction['_raw_data']['auctionId'] === $normalizedId) {
                $this->logger->debug('Auktion mit ID ' . $id . ' wurde über _raw_data gefunden (String-Vergleich)');
                return $auction;
            }

            // 4. Raw-Data-Vergleich (als Integer)
            if (isset($auction['_raw_data']) && isset($auction['_raw_data']['auctionId']) &&
                is_numeric($auction['_raw_data']['auctionId']) && is_numeric($normalizedId) &&
                (int)$auction['_raw_data']['auctionId'] === (int)$normalizedId) {
                $this->logger->debug('Auktion mit ID ' . $id . ' wurde über _raw_data gefunden (Integer-Vergleich)');
                return $auction;
            }

            // 5. Unschärfe Suche mit Contains
            if (isset($auction['id']) && strpos((string)$auction['id'], $normalizedId) !== false) {
                $this->logger->debug('Auktion mit ID ' . $id . ' wurde über teilweise Übereinstimmung gefunden');
                return $auction;
            }
        }

        // Wenn immer noch nichts gefunden wurde, versuchen wir es mit einem frischen API-Aufruf ohne Cache
        $this->logger->warning('Keine Auktion mit ID ' . $id . ' gefunden im ersten Durchlauf, versuche ohne Cache');
        $this->clearCache();
        $auctions = $this->getPublicAuctions(false);

        if (!empty($auctions)) {
            foreach ($auctions as $auction) {
                if (isset($auction['id']) && ((string)$auction['id'] === $normalizedId || (int)$auction['id'] === (int)$normalizedId)) {
                    $this->logger->debug('Auktion mit ID ' . $id . ' wurde im zweiten Versuch ohne Cache gefunden');
                    return $auction;
                }
            }
        }

        $this->logger->warning('Keine Auktion mit ID ' . $id . ' gefunden, auch nicht im zweiten Versuch');
        $this->logger->warning('Gesuchte ID: ' . $id . ' (normalisiert: ' . $normalizedId . '), Verfügbare IDs: ' . implode(', ', $availableIds));
        return null;
    }

    /**
     * Alle verfügbaren Bundesländer abrufen
     */
    public function getAllBundeslaender(): array
    {
        // Auktionen abrufen
        $auctions = $this->getAuctions();

        // Bundesländer extrahieren
        $bundeslaender = [];
        foreach ($auctions as $auction) {
            if (!empty($auction['bundesland']) && !in_array($auction['bundesland'], $bundeslaender)) {
                $bundeslaender[] = $auction['bundesland'];
            }
        }

        sort($bundeslaender);
        return $bundeslaender;
    }

    /**
     * Alle verfügbaren Landkreise abrufen, optional nach Bundesland gefiltert
     */
    public function getAllLandkreise(bool $asKeyValuePairs = false, ?string $bundesland = null): array
    {
        // Auktionen abrufen
        $auctions = $this->getAuctions();

        // Landkreise extrahieren
        $landkreise = [];
        foreach ($auctions as $auction) {
            if (!empty($auction['district']) &&
                ($bundesland === null || $auction['bundesland'] === $bundesland) &&
                !in_array($auction['district'], $landkreise)) {
                $landkreise[] = $auction['district'];
            }
        }

        sort($landkreise);

        if ($asKeyValuePairs) {
            $result = [];
            foreach ($landkreise as $landkreis) {
                $result[$landkreis] = $landkreis;
            }
            return $result;
        }

        return $landkreise;
    }

    /**
     * Methode für Controller-Kompatibilität - delegiert an getAuctions
     *
     * @param array $filters Filter für die Auktionen
     * @return array
     */
    public function filterAuctions(array $filters = []): array
    {
        return $this->getAuctions($filters);
    }

    /**
     * Beim Beenden der PHP-Ausführung aufräumen
     */
    public function __destruct()
    {
        // Cookie-Datei löschen, wenn sie existiert
        if (file_exists($this->cookieFile)) {
            @unlink($this->cookieFile);
        }
    }
}
