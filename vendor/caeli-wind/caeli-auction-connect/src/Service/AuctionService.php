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
        // Prüfen, ob Daten im Cache verfügbar sind
        $cacheFile = $this->cacheDir . '/auctions.json';
        if ($useCache && file_exists($cacheFile) && (time() - filemtime($cacheFile) < self::CACHE_LIFETIME)) {
            $this->logger->debug('Verwende Cache-Daten aus: ' . $cacheFile);
            $cachedData = file_get_contents($cacheFile);
            $auctions = json_decode($cachedData, true);
            
            if (is_array($auctions) && !empty($auctions)) {
                $this->logger->debug('Cache enthält ' . count($auctions) . ' Auktionen');
                return $auctions;
            }
            
            $this->logger->warning('Cache-Datei existiert, enthält aber keine gültigen Daten');
        }
        
        try {
            $this->logger->debug('Rufe öffentliche Auktionsdaten mit BasicAuth ab');
            
            // Direkt aus Cronjobs.php kopiert und angepasst
            $ch = curl_init();
            
            // URL aus Cronjobs.php
            $url = "https://auction.caeli-wind.de/api/auction-platform/v1/public-marketplace";
            // BasicAuth Token aus Cronjobs.php
            $BasicAuth = "VVNFUl9QTVA6a0dyTjZqM0k3VlBNMlUxVkE4NHdSRjBIVw==";
            
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Basic '.$BasicAuth
            ]);
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            
            $this->logger->debug('Anfrage an: ' . $url);
            $result = curl_exec($ch);
            
            if (curl_error($ch)) {
                $this->logger->error('API-Anfrage fehlgeschlagen (cURL): ' . curl_error($ch));
                curl_close($ch);
                return null;
            }
            
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $requestInfo = curl_getinfo($ch);
            curl_close($ch);
            
            $this->logger->debug('API-Antwort: Status ' . $httpCode . ', Request-Info: ' . json_encode($requestInfo));
            
            if ($httpCode !== 200) {
                $this->logger->error('API-Anfrage fehlgeschlagen: HTTP-Status ' . $httpCode);
                $this->logger->debug('Antwort: ' . $result);
                return null;
            }
            
            // Antwort parsen
            $auctions = json_decode($result, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('API-Antwort konnte nicht geparst werden: ' . json_last_error_msg());
                return null;
            }
            
            // Vollständige Antwort debuggen
            $this->logger->debug('API hat ' . count($auctions) . ' Auktionen zurückgegeben');
            if (!empty($auctions)) {
                // Erste Auktion für Debug-Zwecke ausgeben
                $this->logger->debug('Erste Auktion Beispiel: ' . json_encode(reset($auctions)));
                
                // Alle IDs für Debug-Zwecke ausgeben
                $ids = array_map(function($auction) {
                    return $auction['auctionId'] ?? 'keine-ID';
                }, $auctions);
                $this->logger->debug('Verfügbare Auktions-IDs von API: ' . implode(', ', $ids));
            }
            
            if (sizeof($auctions) === 0) {
                $this->logger->warning('Keine Auktionen gefunden');
            } else {
                $this->logger->info('Anzahl gefundener Auktionen: ' . sizeof($auctions));
                
                // Im Cache speichern
                if (!is_dir($this->cacheDir)) {
                    mkdir($this->cacheDir, 0755, true);
                }
                @file_put_contents($cacheFile, $result);
                $this->logger->debug('Auktionsdaten im Cache gespeichert: ' . $cacheFile);
            }
            
            // Gleiche Filterung wie in Cronjobs::getPublicMarketplace
            $filteredAuctions = [];
            foreach ($auctions as $auction) {
                // Status-Filterung wie in Cronjobs.php
                if (in_array($auction['status'], [
                    'STARTED', 'FIRST_ROUND', 'SECOND_ROUND', 'FIRST_ROUND_EVALUATION', 
                    'PRE_RELEASE', 'PREVIEW', 'OPEN_FOR_DIRECT_AWARDING', 'DIRECT_AWARDING', 'AWARDING'
                ])) {
                    $filteredAuctions[] = $this->mapPublicAuctionToInternalFormat($auction);
                }
            }
            
            if (count($filteredAuctions) === 0 && count($auctions) > 0) {
                $this->logger->warning('Alle Auktionen wurden durch den Status-Filter entfernt.');
                $statuses = array_map(function($auction) {
                    return $auction['status'] ?? 'kein-status';
                }, $auctions);
                $this->logger->debug('Verfügbare Status-Werte: ' . implode(', ', array_unique($statuses)));
                
                // Bei leerem Ergebnis nach Filterung, versuchen wir ohne Filter
                $this->logger->info('Versuche ohne Status-Filter, um mögliche Auktionen zu finden');
                foreach ($auctions as $auction) {
                    $filteredAuctions[] = $this->mapPublicAuctionToInternalFormat($auction);
                }
            }
            
            $this->logger->info('Nach Filterung verbleiben ' . count($filteredAuctions) . ' Auktionen');
            return $filteredAuctions;
            
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Abrufen der öffentlichen Auktionen: ' . $e->getMessage());
            $this->logger->error('Stack Trace: ' . $e->getTraceAsString());
            return null;
        }
    }
    
    /**
     * Konvertiert das Format der Public-API in das interne Format
     */
    private function mapPublicAuctionToInternalFormat(array $auction): array
    {
        // Debug-Ausgabe für die eingehenden Daten
        $this->logger->debug('Mapping Auction-Daten: ID=' . ($auction['auctionId'] ?? 'keine-ID') . 
                              ', AreaName=' . ($auction['areaName'] ?? 'kein AreaName') . 
                              ', Titel=' . ($auction['title'] ?? 'kein Titel-Feld'));
        
        // Sicherstellen, dass die ID ein String ist
        $auctionId = isset($auction['auctionId']) ? (string)$auction['auctionId'] : '';
        
        // Titel aus verschiedenen möglichen Feldern ermitteln (AreaName hat Priorität)
        $title = $auction['areaName'] ?? $auction['title'] ?? 'Auktion #' . $auctionId;
        
        $result = [
            'id' => $auctionId,
            'title' => $title,
            'state' => $auction['state'] ?? '',
            'status' => $auction['status'] ?? '',
            'size_ha' => $auction['areaSize'] ?? 0,
            'power_mw' => $auction['power'] ?? 0,
            'full_usage_hours' => $auction['fullUsageHours'] ?? 0,
            'full_usage_hours_secondary' => $auction['fullUsageHoursSecondary'] ?? null,
            'full_usage_hours_secondary_source' => $auction['fullUsageHoursSecondarySource'] ?? null,
            'full_usage_hours_source' => $auction['fullUsageHoursSource'] ?? null,
            'internal_rate_of_return' => $auction['internalRateOfReturnBeforeRent'] ?? 0,
            'available_from' => $auction['availableFrom'] ?? null,
            'property' => $auction['property'] ?? null,
            'planning_law' => $auction['planningLaw'] ?? null,
            'is_in_focus' => (bool)($auction['isAuctionInFocus'] ?? false),
            'interested_count' => (int)($auction['interestedCount'] ?? 0),
            'picture_filename' => $auction['areaPictureFileName'] ?? null,
            'area_progress' => $auction['areaProgress'] ?? [],
            'countdown' => $auction['countDown'] ?? null,
            // Das originale Objekt für Debugging behalten
            '_raw_data' => $auction
        ];
        
        $this->logger->debug('Mapping Ergebnis: ID=' . $result['id'] . ', Titel=' . $result['title']);
        return $result;
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
        $this->logger->debug('Auktionen werden abgerufen mit Filtern: ' . json_encode($filters));
        
        // Auktionen abrufen (ggf. aus dem Cache)
        $auctions = $this->getPublicAuctions(!$forceRefresh);
        
        if ($auctions === null) {
            $this->logger->error('Keine Auktionsdaten verfügbar');
            return [];
        }
        
        // Filter anwenden
        if (!empty($filters)) {
            $filteredAuctions = [];
            
            foreach ($auctions as $auction) {
                $include = true;
                
                // Bundesland-Filter
                if (!empty($filters['bundesland']) && 
                    strtolower($auction['state'] ?? '') !== strtolower($filters['bundesland'])) {
                    $include = false;
                }
                
                // Landkreis-Filter
                if (!empty($filters['landkreis']) && 
                    strtolower($auction['district'] ?? '') !== strtolower($filters['landkreis'])) {
                    $include = false;
                }
                
                // Status-Filter
                if (!empty($filters['status']) && 
                    $auction['status'] !== $filters['status']) {
                    $include = false;
                }
                
                // Flächengröße-Filter
                if (!empty($filters['size'])) {
                    $size = (float)($auction['size_ha'] ?? 0);
                    if (!empty($filters['size']['min']) && $size < (float)$filters['size']['min']) {
                        $include = false;
                    }
                    if (!empty($filters['size']['max']) && $size > (float)$filters['size']['max']) {
                        $include = false;
                    }
                }
                
                // Leistung-Filter
                if (!empty($filters['leistung'])) {
                    $power = (float)($auction['power_mw'] ?? 0);
                    if (!empty($filters['leistung']['min']) && $power < (float)$filters['leistung']['min']) {
                        $include = false;
                    }
                    if (!empty($filters['leistung']['max']) && $power > (float)$filters['leistung']['max']) {
                        $include = false;
                    }
                }
                
                // Volllaststunden-Filter
                if (!empty($filters['volllaststunden'])) {
                    $hours = (int)($auction['full_usage_hours'] ?? 0);
                    if (!empty($filters['volllaststunden']['min']) && $hours < (int)$filters['volllaststunden']['min']) {
                        $include = false;
                    }
                    if (!empty($filters['volllaststunden']['max']) && $hours > (int)$filters['volllaststunden']['max']) {
                        $include = false;
                    }
                }
                
                if ($include) {
                    $filteredAuctions[] = $auction;
                }
            }
            
            $auctions = $filteredAuctions;
        }
        
        $this->logger->info('Anzahl Auktionen nach Filterung: ' . count($auctions));
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
     * Öffentliche Methode zum Abrufen mehrerer Auktionen per IDs
     */
    public function getAuctionsByIds(array $ids): array
    {
        $this->logger->debug('Auktionen mit IDs werden abgerufen: ' . implode(', ', $ids));
        
        $result = [];
        $allAuctions = $this->getPublicAuctions();
        
        if ($allAuctions === null) {
            return $result;
        }
        
        foreach ($allAuctions as $auction) {
            if (in_array($auction['id'], $ids)) {
                $result[] = $auction;
            }
        }
        
        return $result;
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
            if (!empty($auction['state']) && !in_array($auction['state'], $bundeslaender)) {
                $bundeslaender[] = $auction['state'];
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
                ($bundesland === null || $auction['state'] === $bundesland) &&
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