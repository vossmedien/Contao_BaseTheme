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
    private const RAW_AUCTIONS_CACHE_FILE = 'auctions.json';
    private const MAPPED_AUCTIONS_CACHE_FILE = 'auctions_mapped.json';

    private ?string $csrfToken = null;
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
        'id' => ['field' => 'id', 'type' => 'exact', 'value_type' => 'string'], // Für direkte ID-Filter
        'auctionId' => ['field' => 'id', 'type' => 'exact', 'value_type' => 'string'], // Alias für ID-Filter, falls Benutzer auctionId schreibt
        'bundesland' => ['field' => 'bundesland', 'type' => 'lowercase_exact'],
        'landkreis' => ['field' => 'district', 'type' => 'lowercase_exact'], // Beachte: Feld heißt 'district' im Mapping
        'status' => ['field' => 'status', 'type' => 'exact'],
        'size' => ['field' => 'flaeche_ha', 'type' => 'minmax', 'value_type' => 'float'],
        'leistung' => ['field' => 'leistung_mw', 'type' => 'minmax', 'value_type' => 'float'],
        'volllaststunden' => ['field' => 'volllaststunden', 'type' => 'minmax', 'value_type' => 'int'],
        'property' => ['field' => 'property', 'type' => 'exact'],
        'focus' => ['field' => 'focus', 'type' => 'exact', 'value_type' => 'bool'], // Korrigiert: Das Feld im gemappten Array heißt 'focus'
        'irr' => ['field' => 'internalRateOfReturnBeforeRent', 'type' => 'minmax', 'value_type' => 'float'],
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
                if ($statusCode === 404) {
                    $this->logger->info('API-Anfrage für Auktion ' . $id . ' ergab 404 (nicht gefunden).');
                } else {
                    $this->logger->error('API-Anfrage fehlgeschlagen: HTTP-Status ' . $statusCode);
                }
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
     * Implementierung analog zu getPublicMarketplace() aus Cronjobs.php
     * Verwendet Basic Authentication statt Login/CSRF
     *
     * @param bool $useCache Gibt an, ob der Cache verwendet werden soll
     * @return array|null
     */
    private function getPublicAuctions(bool $useCache = true): ?array
    {
        $rawCacheFile = $this->cacheDir . '/' . self::RAW_AUCTIONS_CACHE_FILE;
        $mappedCacheFile = $this->cacheDir . '/' . self::MAPPED_AUCTIONS_CACHE_FILE;

        $this->logger->debug('[getPublicAuctions] Prüfe Caches. Raw: ' . $rawCacheFile . ', Mapped: ' . $mappedCacheFile . ' | useCache=' . ($useCache ? 'true' : 'false'));

        // 1. Versuche, gemappte Daten aus dem Cache zu laden
        if ($useCache && file_exists($mappedCacheFile)) {
            $mappedCacheAge = time() - filemtime($mappedCacheFile);
            $this->logger->debug('[getPublicAuctions] Gemappte Cache-Datei gefunden, Alter: ' . $mappedCacheAge . 's (Lifetime: ' . self::CACHE_LIFETIME . 's)');

            if ($mappedCacheAge < self::CACHE_LIFETIME) {
                $this->logger->info('[getPublicAuctions] Versuche, gültigen gemappten Cache zu verwenden: ' . $mappedCacheFile);
                $cachedMappedData = @file_get_contents($mappedCacheFile);

                if ($cachedMappedData === false) {
                    $this->logger->error('[getPublicAuctions] FEHLER beim Lesen der gemappten Cache-Datei: ' . $mappedCacheFile);
                } else {
                    $this->logger->debug('[getPublicAuctions] Gemappte Cache-Datei erfolgreich gelesen (Größe: ' . strlen($cachedMappedData) . ' Bytes).');
                    $auctionsFromMappedCache = json_decode($cachedMappedData, true);

                    if (json_last_error() === JSON_ERROR_NONE && is_array($auctionsFromMappedCache)) {
                        $this->logger->info('[getPublicAuctions] Gemappter Cache JSON erfolgreich dekodiert. Gebe ' . count($auctionsFromMappedCache) . ' Auktionen aus gemapptem Cache zurück.');
                        return $auctionsFromMappedCache;
                    } else {
                        $this->logger->error('[getPublicAuctions] FEHLER: Gemappte Cache-Datei ist KEIN gültiges JSON oder kein Array.', ['json_error' => json_last_error_msg()]);
                        @unlink($mappedCacheFile); // Korrupten gemappten Cache löschen
                        $this->logger->info('[getPublicAuctions] Korrupte gemappte Cache-Datei gelöscht: ' . $mappedCacheFile);
                    }
                }
            } else {
                $this->logger->info('[getPublicAuctions] Gemappter Cache ist abgelaufen.');
            }
        } else {
            if ($useCache) $this->logger->info('[getPublicAuctions] Keine gemappte Cache-Datei gefunden.');
            // Kein 'else' für '!$useCache', da wir dann sowieso alles neu machen
        }

        // 2. Wenn gemappter Cache nicht verfügbar/gültig, lade Rohdaten und mappe sie
        $this->logger->info('[getPublicAuctions] Gemappter Cache nicht verwendet oder ungültig. Lade/verarbeite Rohdaten...');

        $auctionsRaw = null;

        // Lade Rohdaten aus dem Rohdaten-Cache oder von der API
        if ($useCache && file_exists($rawCacheFile)) {
            $rawCacheAge = time() - filemtime($rawCacheFile);
            $this->logger->debug('[getPublicAuctions] Rohdaten-Cache-Datei gefunden, Alter: ' . $rawCacheAge . 's');
            if ($rawCacheAge < self::CACHE_LIFETIME) {
                $this->logger->info('[getPublicAuctions] Versuche, gültigen Rohdaten-Cache zu verwenden: ' . $rawCacheFile);
                $cachedRawData = @file_get_contents($rawCacheFile);
                if ($cachedRawData !== false) {
                    $auctionsFromRawCache = json_decode($cachedRawData, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($auctionsFromRawCache)) {
                        $this->logger->info('[getPublicAuctions] Rohdaten-Cache JSON erfolgreich dekodiert. ' . count($auctionsFromRawCache) . ' Roh-Auktionen geladen.');
                        $auctionsRaw = $auctionsFromRawCache;
                    } else {
                        $this->logger->error('[getPublicAuctions] FEHLER: Rohdaten-Cache ist KEIN gültiges JSON.', ['json_error' => json_last_error_msg()]);
                        @unlink($rawCacheFile);
                    }
                } else {
                    $this->logger->error('[getPublicAuctions] FEHLER beim Lesen der Rohdaten-Cache-Datei: ' . $rawCacheFile);
                }
            } else {
                $this->logger->info('[getPublicAuctions] Rohdaten-Cache ist abgelaufen.');
            }
        }

        // Wenn Rohdaten nicht aus Cache geladen wurden (oder Cache deaktiviert), von API holen
        if ($auctionsRaw === null) {
            if (!$useCache) $this->logger->info('[getPublicAuctions] Cache-Nutzung ist deaktiviert (forceRefresh=true für Rohdaten).');
            else $this->logger->info('[getPublicAuctions] Keine gültigen Rohdaten aus Cache geladen, frage API an.');

            try {
                $this->logger->info('[getPublicAuctions] Lade Auktions-Rohdaten von der Public API...');
                $ch = curl_init();
                $url = $this->params->get('caeli_auction.marketplace_api_url');
                $BasicAuth = $this->params->get('caeli_auction.marketplace_api_auth');
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic '.$BasicAuth]);
                curl_setopt($ch, CURLINFO_HEADER_OUT, true);
                $result = curl_exec($ch);
                $curlError = curl_error($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($curlError) {
                    $this->logger->error('[getPublicAuctions] API-Anfrage für Rohdaten fehlgeschlagen (cURL Fehler): ' . $curlError);
                    return null;
                }
                if ($httpCode !== 200) {
                    $this->logger->error('[getPublicAuctions] API-Anfrage für Rohdaten fehlgeschlagen: HTTP-Status ' . $httpCode, ['body' => substr($result ?: '', 0, 500)]);
                    return null;
                }
                $auctionsFromApi = json_decode($result, true);
                if (json_last_error() !== JSON_ERROR_NONE || !is_array($auctionsFromApi)) {
                    $this->logger->error('[getPublicAuctions] FEHLER: API-Antwort für Rohdaten ist KEIN gültiges JSON.', ['json_error' => json_last_error_msg()]);
                    return null;
                }
                $this->logger->info('[getPublicAuctions] API hat ' . count($auctionsFromApi) . ' Roh-Auktionen zurückgegeben. Speichere im Rohdaten-Cache...');
                if (!is_dir($this->cacheDir)) @mkdir($this->cacheDir, 0755, true);
                if (@file_put_contents($rawCacheFile, $result) === false) {
                    $this->logger->error('[getPublicAuctions] FEHLER beim Schreiben der Rohdaten-Cache-Datei: ' . $rawCacheFile);
                } else {
                    $this->logger->debug('[getPublicAuctions] Rohdaten im Cache gespeichert: ' . $rawCacheFile);
                }
                $auctionsRaw = $auctionsFromApi;
            } catch (\Throwable $e) {
                $this->logger->error('[getPublicAuctions] KRITISCHER FEHLER beim API-Abruf der Rohdaten: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
                return null;
            }
        }

        if ($auctionsRaw === null) {
            $this->logger->error('[getPublicAuctions] Konnte keine Roh-Auktionsdaten laden (weder Cache noch API).');
            return null;
        }

        // 3. Mappe die Rohdaten
        $this->logger->info('[getPublicAuctions] Starte Mapping von ' . count($auctionsRaw) . ' Roh-Auktionen...');
        $mappedAuctions = [];
        $mappingErrors = 0;
        $filteredOutCount = 0;
        $validStatuses = [
            'STARTED', 'FIRST_ROUND', 'SECOND_ROUND', 'FIRST_ROUND_EVALUATION',
            'PRE_RELEASE', 'PREVIEW', 'OPEN_FOR_DIRECT_AWARDING', 'DIRECT_AWARDING', 'AWARDING'
        ];

        foreach ($auctionsRaw as $index => $auctionSingleRaw) {
            $status = $auctionSingleRaw['status'] ?? null;
            $auctionIdForLogApi = $auctionSingleRaw['auctionId'] ?? 'unbekannt';

            if (in_array($status, $validStatuses)) {
                try {
                    $mappedAuction = $this->mapPublicAuctionToInternalFormat($auctionSingleRaw);
                    $mappedAuctions[] = $mappedAuction;
                } catch (\Throwable $e) {
                    $mappingErrors++;
                    $this->logger->error('[getPublicAuctions] FEHLER beim Mappen einer Roh-Auktion (Index: ' . $index . '): ' . $e->getMessage(), [
                        'auctionId_raw' => $auctionIdForLogApi,
                        'exception_trace' => $e->getTraceAsString()
                    ]);
                }
            } else {
                $filteredOutCount++;
                // $this->logger->debug('[getPublicAuctions] Roh-Item übersprungen wegen Status-Filter', ['id' => $auctionIdForLogApi, 'status' => $status]);
            }
        }
        $this->logger->info('[getPublicAuctions] Mapping beendet. ' . count($mappedAuctions) . ' von ' . (count($auctionsRaw) - $filteredOutCount) . ' Auktionen (nach Statusfilter) erfolgreich gemappt (' . $mappingErrors . ' Fehler). ' . $filteredOutCount . ' wurden wegen Status entfernt.');

        // 4. Speichere gemappte Daten im Cache
        if (!is_dir($this->cacheDir)) @mkdir($this->cacheDir, 0755, true);
        $mappedJsonToWrite = json_encode($mappedAuctions);
        if (json_last_error() !== JSON_ERROR_NONE) {
             $this->logger->error('[getPublicAuctions] FEHLER beim Enkodieren der gemappten Auktionen zu JSON.', ['json_error' => json_last_error_msg()]);
        } elseif (@file_put_contents($mappedCacheFile, $mappedJsonToWrite) === false) {
            $this->logger->error('[getPublicAuctions] FEHLER beim Schreiben der gemappten Cache-Datei: ' . $mappedCacheFile);
        } else {
            $this->logger->info('[getPublicAuctions] Gemappte Daten im Cache gespeichert: ' . $mappedCacheFile . ' (Größe: ' . strlen($mappedJsonToWrite) . ' Bytes)');
        }

        $this->logger->info('[getPublicAuctions] Gebe ' . count($mappedAuctions) . ' frisch gemappte Auktionen zurück.');
        return $mappedAuctions;
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
            // $this->logger->debug('[getAuctionsByIds] Prüfe Auktion mit ID: ' . $currentAuctionIdForLog);

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
     * Korrigiert: Übernimmt alle relevanten Felder und benennt sie ggf. um.
     */
    private function mapPublicAuctionToInternalFormat(array $auction): array
    {
        $auctionIdRaw = $auction['auctionId'] ?? null;
        $this->logger->debug('Konvertiere Auktion: ' . $auctionIdRaw);

        $auctionIdString = $auctionIdRaw !== null ? (string)$auctionIdRaw : null;

        // Starte mit den Rohdaten und der ID
        $result = [
            '_raw_data' => $auction,
            'id' => $auctionIdString,
        ];

        // Felder mappen (Name im $result => Name im $auction Roh-Array)
        $fieldMapping = [
            'bundesland' => 'state',
            'status' => 'status',
            'leistung_mw' => 'power',
            'flaeche_ha' => 'areaSize',
            'volllaststunden' => 'fullUsageHours',
            'volllaststunden_quelle' => 'fullUsageHoursSource',
            'internalRateOfReturnBeforeRent' => 'internalRateOfReturnBeforeRent',
            'availableFrom' => 'availableFrom',
            'property' => 'property',
            'planningLaw' => 'planningLaw',
            'focus' => 'isAuctionInFocus',
            'countDown' => 'countDown',
            // 'district' fehlt in deinem Beispiel, muss ggf. aus anderer Quelle kommen oder API angepasst werden?
            // Wenn 'district' doch vorhanden ist, hier hinzufügen: 'district' => 'district',
            // areaProgress wird nicht direkt gemappt, aber über _raw_data zugänglich
        ];

        // Übertrage die gemappten Felder
        foreach ($fieldMapping as $internalKey => $apiKey) {
             // Verwende null coalescing operator, um sicherzustellen, dass der Schlüssel existiert,
             // auch wenn der Wert in der API null ist.
            $result[$internalKey] = $auction[$apiKey] ?? null;
        }

        // Bildpfade initialisieren
        $result['picture_path'] = null;
        $result['picture_filename'] = null;
        $result['blurred_picture_path'] = null;

        // Bild herunterladen und Pfade hinzufügen
        $pictureFilename = $auction['areaPictureFileName'] ?? null;
        if (!empty($pictureFilename) && $auctionIdString !== null) {
             $this->logger->debug('Versuche Bild herunterzuladen', ['file' => $pictureFilename, 'id' => $auctionIdString]);
            $localImagePath = $this->downloadAuctionImage($pictureFilename, $auctionIdString);

             if (!empty($localImagePath)) {
                 $this->logger->debug('Bild erfolgreich heruntergeladen/gefunden', ['localPath' => $localImagePath]);
                 $webPath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $localImagePath);
                 $result['picture_path'] = $webPath;
                 $result['picture_filename'] = $pictureFilename;
                 $this->logger->debug('[mapPublicAuctionToInternalFormat] Generierter picture_path: ' . $webPath, ['id' => $auctionIdString]);

                 // Pfad zum geblurrten Bild hinzufügen
                 $targetDir = dirname($localImagePath);
                 $blurPngFilename = md5($auctionIdString ?? $pictureFilename) . ".png";
                 $blurPngTargetFile = $targetDir . '/' . $blurPngFilename;
                 $blurWebpTargetFile = str_replace('.png', '.webp', $blurPngTargetFile);

                 $blurredWebPath = null;
                 if (file_exists($blurWebpTargetFile)) { // Bevorzuge WebP
                      $blurredWebPath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $blurWebpTargetFile);
                 } elseif (file_exists($blurPngTargetFile)) { // Fallback auf PNG
                      $blurredWebPath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $blurPngTargetFile);
                 }

                 if ($blurredWebPath) {
                    $result['blurred_picture_path'] = $blurredWebPath;
                    $this->logger->debug('[mapPublicAuctionToInternalFormat] Generierter blurred_picture_path: ' . $blurredWebPath, ['id' => $auctionIdString]);
                 } else {
                    $this->logger->warning('[mapPublicAuctionToInternalFormat] Geblurrtes Bild (WebP oder PNG) nicht gefunden, obwohl erwartet.', ['id' => $auctionIdString, 'tried_webp' => $blurWebpTargetFile, 'tried_png' => $blurPngTargetFile]);
                 }

             } else {
                  $this->logger->warning('Bild konnte nicht heruntergeladen oder gefunden werden', ['file' => $pictureFilename, 'id' => $auctionIdString]);
                  $this->logger->debug('[mapPublicAuctionToInternalFormat] picture_path bleibt null (Download fehlgeschlagen)', ['id' => $auctionIdString]);
             }
        } else {
             if (empty($pictureFilename)) $this->logger->debug('Kein Bild-Dateiname für Auktion vorhanden', ['id' => $auctionIdString]);
             if ($auctionIdString === null) $this->logger->debug('Keine Auktions-ID für Bild-Download vorhanden', ['file' => $pictureFilename]);
             $this->logger->debug('[mapPublicAuctionToInternalFormat] picture_path bleibt null (kein Dateiname oder ID)', ['id' => $auctionIdString]);
        }

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
        $rawCacheFile = $this->cacheDir . '/' . self::RAW_AUCTIONS_CACHE_FILE;
        $mappedCacheFile = $this->cacheDir . '/' . self::MAPPED_AUCTIONS_CACHE_FILE;
        $successRaw = true;
        $successMapped = true;

        if (file_exists($rawCacheFile)) {
            $successRaw = @unlink($rawCacheFile);
            $this->logger->info('Rohdaten-Cache gelöscht: ' . $rawCacheFile . ' (Erfolg: ' . ($successRaw ? 'ja' : 'nein') . ')');
        }
        if (file_exists($mappedCacheFile)) {
            $successMapped = @unlink($mappedCacheFile);
            $this->logger->info('Gemappter Cache gelöscht: ' . $mappedCacheFile . ' (Erfolg: ' . ($successMapped ? 'ja' : 'nein') . ')');
        }
        return $successRaw && $successMapped;
    }

    /**
     * Methode zum Abrufen der Auktionen mit Filterung und Sortierung.
     * Kombiniert aus getMarketplaceList() und sortMarketplaceList() aus Cronjobs.php
     *
     * @param array $filters Filter für die Auktionen im Format ['fieldKey' => 'wert'] oder für minmax-Felder: ['fieldKey' => ['min' => x, 'max' => y]]
     * @param bool $forceRefresh Gibt an, ob der Cache ignoriert werden soll
     * @param string|null $sortBy Das Feld, nach dem sortiert werden soll (für Abwärtskompatibilität)
     * @param string $sortDirection Die Sortierrichtung ('asc' oder 'desc') (für Abwärtskompatibilität)
     * @param array $sortRules Array von Sortierregeln im Format [['field' => 'feldname', 'direction' => 'asc'], ...]
     * @return array Die gefilterten und sortierten Auktionen
     */
    public function getAuctions(array $filters = [], bool $forceRefresh = false, ?string $sortBy = null, string $sortDirection = 'asc', array $sortRules = []): array
    {
        $this->logger->debug('[getAuctions] Auktionen werden abgerufen', [ // Präfix [TESTLOG-ERROR] entfernt
            'raw_filters' => $filters,
            'forceRefresh' => $forceRefresh,
            'sortBy' => $sortBy,
            'sortDirection' => $sortDirection,
            'sortRules' => $sortRules,
        ]);

        $auctions = $this->getPublicAuctions(!$forceRefresh);
        if ($auctions === null) {
            $this->logger->debug('[getAuctions] Keine Auktionsdaten von getPublicAuctions erhalten.'); // War [TESTLOG-ERROR]
            return [];
        }
        $initialCount = count($auctions);
        $this->logger->info('[getAuctions] ' . $initialCount . ' gemapte Auktionen von getPublicAuctions erhalten.');

        $structuredFilters = [];
        foreach ($filters as $key => $value) {
            if ($value === null || (is_string($value) && $value === '')) continue;

            if (str_ends_with($key, '_min')) {
                $baseKey = substr($key, 0, -4);
                if (!isset($this->filterableFields[$baseKey]) || $this->filterableFields[$baseKey]['type'] !== 'minmax') {
                    $this->logger->warning("[getAuctions] Ungültiger _min Suffix für nicht-minmax Feld: {$baseKey}. Filter '$key' wird ignoriert.");
                    continue;
                }
                $structuredFilters[$baseKey]['min'] = $value;
            } elseif (str_ends_with($key, '_max')) {
                $baseKey = substr($key, 0, -4);
                if (!isset($this->filterableFields[$baseKey]) || $this->filterableFields[$baseKey]['type'] !== 'minmax') {
                    $this->logger->warning("[getAuctions] Ungültiger _max Suffix für nicht-minmax Feld: {$baseKey}. Filter '$key' wird ignoriert.");
                    continue;
                }
                $structuredFilters[$baseKey]['max'] = $value;
            } else {
                $structuredFilters[$key] = $value;
            }
        }
        $this->logger->debug('[getAuctions] Strukturierte Filter für Verarbeitung:', $structuredFilters);

        if (!empty($structuredFilters)) {
            $this->logger->debug('[getAuctions] Wende strukturierte Filter an: ' . json_encode($structuredFilters));
            $filteredAuctions = [];

            foreach ($auctions as $auction) {
                $include = true;
                $auctionIdForLog = $auction['id'] ?? 'unbekannte_ID';

                foreach ($structuredFilters as $filterKeyWithPotentialSuffix => $filterValue) {
                    // WICHTIG: $matchFound muss für jeden einzelnen Filter zurückgesetzt werden!
                    $matchFound = false; // Oder benenne es um zu $currentFilterMatches = false;

                    if ($filterValue === null || (is_string($filterValue) && $filterValue === '' && !is_bool($filterValue)) || (is_array($filterValue) && empty($filterValue) && $filterKeyWithPotentialSuffix !== 'focus')) {
                        // Wenn der Filterwert leer ist (außer bei 'focus', wo 'false' relevant sein kann),
                        // betrachten wir diesen spezifischen Filter als nicht aktiv oder nicht einschränkend.
                        // Aber wir wollen nicht, dass er $include fälschlicherweise auf false setzt.
                        // Stattdessen sollte $matchFound für diesen leeren Filter true sein, damit er nicht ausschließt.
                        // ODER wir setzen $matchFound hier nicht und verlassen uns darauf, dass die Logik unten korrekt greift
                        // und diesen Filter quasi überspringt, ohne $include zu ändern.
                        // Für boolesche Filter wie 'focus' muss der Wert 'false' aber verarbeitet werden.

                        // Wenn der Filter-Input explizit leer ist und es sich NICHT um 'focus' handelt, überspringen wir ihn,
                        // da er die Auktion nicht weiter einschränken soll.
                        // $matchFound bleibt hier auf dem Initialwert (false), was dazu führen würde,
                        // dass der Filter als NICHT ERFÜLLT gilt, wenn keine Werte zum Vergleichen da sind.
                        // Das ist problematisch. Ein leerer Filter (außer focus=false) sollte die Auktion nicht ausschließen.

                        // Korrektere Logik: Wenn ein Filterwert leer ist (und es nicht focus ist), sollte er nicht zum Ausschluss führen.
                        // Wir setzen $matchFound auf true, damit dieser spezielle (leere) Filter die Auktion nicht rauswirft.
                        if ($filterKeyWithPotentialSuffix !== 'focus' || ($filterValue !== 'false' && $filterValue !== false)) {
                             // $this->logger->debug("[FilterLoop] Auktion: {$auctionIdForLog}, FilterKey: '{$filterKeyWithPotentialSuffix}' hat leeren Wert (außer focus=false) und wird als 'match' behandelt, um nicht auszuschließen.");
                             $matchFound = true; // Behandle als Match, um nicht fälschlicherweise auszuschließen
                             // ABER: Die eigentliche Filterung für diesen Key findet dann nicht statt.
                             // Besser: Diesen Filter einfach überspringen, ohne $matchFound zu setzen oder $include zu ändern.
                             // Wir müssen sicherstellen, dass der Standardfall (leerer Filterstring) nicht zum Ausschluss führt.
                             // Die aktuelle Logik unten (`if (!$matchFound) { $include = false; }`) ist das Problem bei leeren Filtern.

                             // Wenn $filterValue leer ist und nicht focus, dann ist dieser Filter nicht aktiv.
                             // $include sollte nicht beeinflusst werden. $matchFound sollte nicht ausgewertet werden.
                             // Wir können `continue;` verwenden, um zum nächsten Filter zu springen.
                             // Aber die Logik `if (!$matchFound)` würde dann mit dem $matchFound des *vorherigen* Filters arbeiten.

                             // Neuer Ansatz: Die `if (!$matchFound)` Prüfung muss spezifischer sein.
                             // $matchFound wird oben auf false gesetzt. Wenn ein Filter nicht zutrifft, bleibt er false.
                             // Wenn ein Filterwert leer ist (und nicht focus=false), dann sollte dieser Filter die Auktion NICHT ausschließen.
                             // Der Filter ist dann einfach nicht aktiv.

                            // Wenn der Wert leer ist (und es nicht `focus=false` ist), überspringe diesen Filter komplett.
                            // Die Variable `$include` wird dann nicht durch diesen speziellen Filter beeinflusst.
                            if ($filterKeyWithPotentialSuffix === 'focus' && ($filterValue === 'false' || $filterValue === false)) {
                                // focus=false ist ein aktiver Filter, nicht überspringen
                            } else {
                                $this->logger->debug("[FilterLoop] Auktion: {$auctionIdForLog}, FilterKey: '{$filterKeyWithPotentialSuffix}' wird übersprungen (Wert ist leer oder nicht relevant).");
                                continue; // Nächster Filter für diese Auktion
                            }
                        }
                    }

                    $baseFilterKey = $filterKeyWithPotentialSuffix;
                    $operatorSuffix = '';

                    // Suffix-Extraktion für Operatoren wie __in, __ne, etc.
                    if (str_ends_with($filterKeyWithPotentialSuffix, '__in')) {
                        $operatorSuffix = '__in';
                        $baseFilterKey = substr($filterKeyWithPotentialSuffix, 0, -4);
                    } elseif (str_ends_with($filterKeyWithPotentialSuffix, '__not_in')) {
                        $operatorSuffix = '__not_in';
                        $baseFilterKey = substr($filterKeyWithPotentialSuffix, 0, -8);
                    } elseif (str_ends_with($filterKeyWithPotentialSuffix, '__contains')) {
                        $operatorSuffix = '__contains';
                        $baseFilterKey = substr($filterKeyWithPotentialSuffix, 0, -10);
                    } elseif (str_ends_with($filterKeyWithPotentialSuffix, '__ne')) {
                        $operatorSuffix = '__ne';
                        $baseFilterKey = substr($filterKeyWithPotentialSuffix, 0, -4);
                    }
                    // Hinweis: _min und _max Suffixe wurden bereits oben zu strukturierten Filtern verarbeitet.
                    // $baseFilterKey ist hier der Schlüssel, wie er in $filterableFields definiert ist (z.B. 'status', 'size')

                    if (!isset($this->filterableFields[$baseFilterKey])) {
                        $this->logger->warning("[getAuctions] Unbekannter Basis-Filter-Schlüssel '$baseFilterKey' (abgeleitet von '$filterKeyWithPotentialSuffix') wurde ignoriert.");
                        continue;
                    }

                    $config = $this->filterableFields[$baseFilterKey];
                    $auctionField = $config['field'];
                    $configFilterType = $config['type'];
                    $valueType = $config['value_type'] ?? 'string';

                    if (!array_key_exists($auctionField, $auction) && $configFilterType !== 'minmax') { // Bei minmax könnte das Feld fehlen, aber trotzdem gefiltert werden (z.B. Auktion hat keine Leistung angegeben)
                        $this->logger->debug("[getAuctions] Filter '$filterKeyWithPotentialSuffix' übersprungen (Feld '$auctionField' nicht in Auktion vorhanden).", ['auction_id' => $auctionIdForLog, 'available_keys' => array_keys($auction)]);
                        // $include = false; // Nicht hier, $matchFound wird false bleiben und unten behandelt
                        $matchFound = false; // explizit setzen, damit die untere Prüfung greift
                        // break; // Nicht hier, die äußere Schleife soll weitermachen, aber dieser Filter schlägt fehl
                    } else {
                        $auctionValue = $auction[$auctionField] ?? null; // null, wenn Feld nicht existiert (relevant für minmax)
                    }

                    $actualFilterType = $configFilterType;
                    if ($operatorSuffix === '__in' || $operatorSuffix === '__not_in') {
                        $actualFilterType = 'in_or_not_in';
                    } elseif ($operatorSuffix === '__contains') {
                        $actualFilterType = 'contains';
                    } elseif ($operatorSuffix === '__ne') {
                        $actualFilterType = 'not_equal';
                    }

                    $this->logger->debug("[FilterLoop] Auktion: {$auctionIdForLog}, FilterKey: '$filterKeyWithPotentialSuffix', BaseKey: '$baseFilterKey', AuctionField: '$auctionField', AuctionValue: '" . (is_array($auctionValue) ? json_encode($auctionValue) : $auctionValue) . "' (Typ: " . gettype($auctionValue) . "), FilterValue: '" . (is_array($filterValue) ? json_encode($filterValue) : $filterValue) . "' (Typ: " . gettype($filterValue) . "), ActualType: '$actualFilterType'");

                    switch ($actualFilterType) {
                        case 'exact':
                        case 'lowercase_exact':
                            $isLowercase = ($actualFilterType === 'lowercase_exact');
                            $compareAuctionValue = $isLowercase && is_string($auctionValue) ? strtolower($auctionValue) : $auctionValue;

                            if ($baseFilterKey === 'focus') {
                                // $filterValue ist hier der Wert aus $structuredFilters['focus']
                                // Kann 'true', 'false' (als Strings oder bools) oder null/nicht gesetzt sein
                                if (isset($filterValue) && $filterValue !== '' && $filterValue !== null) { // Wenn 'focus' als Filter aktiv ist
                                    $parsedFilterFocusValue = filter_var($filterValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

                                    if ($parsedFilterFocusValue === true) {
                                        $matchFound = ($auctionValue === true);
                                    } elseif ($parsedFilterFocusValue === false) {
                                        // Explizit focus=false, filtere auf Auktionen, wo focus false ist
                                        $matchFound = ($auctionValue === false);
                                    } else {
                                        // Ungültiger Wert für focus (z.B. "abc"), Filter nicht anwenden.
                                        $matchFound = true;
                                        $this->logger->debug("[FilterDetail][{$baseFilterKey}] Focus Filter: Ungültiger übergebener Wert '$filterValue', Filter wirkt nicht einschränkend.");
                                    }
                                } else {
                                    // Filter 'focus' wurde nicht im $structuredFilters gefunden (d.h. nicht im Request)
                                    // In diesem Fall soll der Fokus-Filter keine Einschränkung bewirken.
                                    $matchFound = true;
                                }
                                $this->logger->debug("[FilterDetail][{$baseFilterKey}] Focus Filter Logic: auctionValue='".var_export($auctionValue, true)."', filterValueReceived='".var_export($filterValue, true)."', matchFound=".var_export($matchFound, true));
                            } else {
                                // Normale 'exact' Logik für andere Felder
                                $valuesToCompareAgainst = [];
                                if (is_string($filterValue) && str_contains($filterValue, ',')) {
                                    $valuesToCompareAgainst = explode(',', $filterValue);
                                } elseif (is_array($filterValue)) {
                                    $valuesToCompareAgainst = $filterValue;
                                } else {
                                    $valuesToCompareAgainst = [$filterValue];
                                }

                                // $matchFound wurde oben bereits auf false initialisiert. Setze es nur auf true, wenn ein Match existiert.
                                foreach ($valuesToCompareAgainst as $singleValue) {
                                    $currentCompareVal = trim((string)$singleValue);
                                    if ($isLowercase) {
                                        $currentCompareVal = strtolower($currentCompareVal);
                                    }

                                    $typedSingleValue = null;
                                    if ($valueType === 'bool') {
                                        // Für boolesche Felder (NICHT focus, da oben behandelt)
                                        $typedSingleValue = filter_var($currentCompareVal, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                                        if ($typedSingleValue === null && $currentCompareVal !== '') {
                                            $this->logger->debug("[FilterDetail][{$baseFilterKey}] Invalid boolean value '$currentCompareVal' for auction {$auctionIdForLog}");
                                            continue; // Nächsten Wert in $valuesToCompareAgainst prüfen
                                        }
                                    } elseif ($valueType === 'int') {
                                        $typedSingleValue = (int)$currentCompareVal;
                                    } elseif ($valueType === 'float') {
                                        $typedSingleValue = (float)$currentCompareVal;
                                    } else {
                                        $typedSingleValue = $currentCompareVal;
                                    }
                                    $this->logger->debug("[FilterDetail][{$baseFilterKey}] Comparing: AuctionCompVal='{$compareAuctionValue}' (Type: " . gettype($compareAuctionValue) . ") WITH TypedFilterSingleVal='{$typedSingleValue}' (Type: " . gettype($typedSingleValue) . ")");
                                    if ($compareAuctionValue == $typedSingleValue) { // Beachte: == für Typumwandlung bei Zahlen vs. Strings
                                        $matchFound = true;
                                        $this->logger->debug("[FilterDetail][{$baseFilterKey}] Match found.");
                                        break; // Ein Match reicht
                                    }
                                }
                                // Nach der Schleife ist $matchFound entweder true oder immer noch false.
                            }
                            break;

                        case 'not_equal':
                            $compareValueNE = $filterValue;
                            if ($valueType === 'bool' && !is_bool($compareValueNE)) {
                                $compareValueNE = filter_var($compareValueNE, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                            } elseif ($valueType === 'int' && !is_int($compareValueNE)) {
                                $compareValueNE = (int)$compareValueNE;
                            } elseif ($valueType === 'float' && !is_float($compareValueNE)) {
                                $compareValueNE = (float)$compareValueNE;
                            }
                            if ($auctionValue == $compareValueNE) {
                                $this->logger->debug("[getAuctions] Filter '$filterKeyWithPotentialSuffix' (not_equal) nicht erfüllt.", ['auction_id' => $auctionIdForLog, 'auction_value' => $auctionValue, 'filter_value' => $compareValueNE]);
                                $include = false;
                            }
                            break;

                        case 'minmax':
                            // $matchFound wurde oben bereits für diesen Filter auf false initialisiert.
                            $minMaxConditionsMet = true; // Lokale Variable für die Bedingungen dieses MinMax-Filters

                            if (!is_array($filterValue) || (!isset($filterValue['min']) && !isset($filterValue['max']))) {
                                $this->logger->warning("[FilterDetail][{$baseFilterKey}] Invalid minmax filter structure for auction {$auctionIdForLog}: " . json_encode($filterValue));
                                $minMaxConditionsMet = false;
                            }

                            if ($minMaxConditionsMet && $auctionValue === null) {
                                $this->logger->debug("[FilterDetail][{$baseFilterKey}] Skipped minmax for auction {$auctionIdForLog} as auction value for '$auctionField' is null.");
                                $minMaxConditionsMet = false; // Kann nicht matchen, wenn Auktionswert null ist
                            }

                            $numericAuctionValue = 0;
                            if ($minMaxConditionsMet) { // Nur fortfahren, wenn bisher alles OK
                                if ($valueType === 'int') {
                                    $numericAuctionValue = (int)$auctionValue;
                                } elseif ($valueType === 'float') {
                                    $numericAuctionValue = (float)$auctionValue;
                                } else {
                                    $this->logger->warning("[FilterDetail][{$baseFilterKey}] Minmax for auction {$auctionIdForLog} requires value_type 'int' or 'float'. Found '$valueType' for auction value '$auctionValue'.");
                                    $minMaxConditionsMet = false;
                                }
                            }

                            if ($minMaxConditionsMet) { // Nur fortfahren, wenn Typ OK
                                $minValueFromFilter = $filterValue['min'] ?? null;
                                $maxValueFromFilter = $filterValue['max'] ?? null;

                                $this->logger->debug("[FilterDetail][{$baseFilterKey}] MinMax Check for auction {$auctionIdForLog}, Field '$auctionField': AuctionVal='{$numericAuctionValue}' (Type: {$valueType}), FilterMin='{$minValueFromFilter}', FilterMax='{$maxValueFromFilter}'");

                                if ($minValueFromFilter !== null) {
                                    $minFilter = ($valueType === 'int') ? (int)$minValueFromFilter : (float)$minValueFromFilter;
                                    if ($numericAuctionValue < $minFilter) {
                                        $this->logger->debug("[FilterResult][{$baseFilterKey}] Min condition NOT MET for auction {$auctionIdForLog}: Val '{$numericAuctionValue}' < Min '{$minFilter}'.");
                                        $minMaxConditionsMet = false;
                                    } else {
                                        // $this->logger->debug("[FilterResult][{$baseFilterKey}] Min condition MET for auction {$auctionIdForLog}: Val '{$numericAuctionValue}' >= Min '{$minFilter}'.");
                                    }
                                }
                                if ($minMaxConditionsMet && $maxValueFromFilter !== null) { // Prüfe $minMaxConditionsMet erneut
                                    $maxFilter = ($valueType === 'int') ? (int)$maxValueFromFilter : (float)$maxValueFromFilter;
                                    if ($numericAuctionValue > $maxFilter) {
                                        $this->logger->debug("[FilterResult][{$baseFilterKey}] Max condition NOT MET for auction {$auctionIdForLog}: Val '{$numericAuctionValue}' > Max '{$maxFilter}'.");
                                        $minMaxConditionsMet = false;
                                    } else {
                                        // $this->logger->debug("[FilterResult][{$baseFilterKey}] Max condition MET for auction {$auctionIdForLog}: Val '{$numericAuctionValue}' <= Max '{$maxFilter}'.");
                                    }
                                }
                            }

                            if ($minMaxConditionsMet) {
                                $matchFound = true; // Dieser MinMax-Filter wurde insgesamt erfüllt
                                $this->logger->debug("[FilterResult][{$baseFilterKey}] MinMax conditions OVERALL MET for auction {$auctionIdForLog}. Setting matchFound=true.");
                            } else {
                                // $matchFound bleibt false (Standard von oben)
                                $this->logger->debug("[FilterResult][{$baseFilterKey}] MinMax conditions NOT MET for auction {$auctionIdForLog}. matchFound remains false.");
                            }
                            // Das direkte Setzen von $include = false; hier wird entfernt.
                            // Die allgemeine Logik `if (!$matchFound)` weiter unten kümmert sich darum.
                            break;

                        case 'in_or_not_in':
                            $listValues = is_array($filterValue) ? $filterValue : array_map('trim', explode(',', (string)$filterValue));
                            $foundInList = false;
                            // $isLowercase muss hier basierend auf dem configFilterType des baseFilterKey bestimmt werden
                            $isLowercase = ($config['type'] === 'lowercase_exact'); 

                            foreach ($listValues as $lv) {
                                $compareLv = $lv;
                                if ($valueType === 'bool') {
                                    $compareLv = filter_var($lv, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                                } elseif ($valueType === 'int') {
                                    $compareLv = (int)$lv;
                                } elseif ($valueType === 'float') {
                                    $compareLv = (float)$lv;
                                } elseif ($isLowercase) { // Annahme: lowercase_exact + __in
                                    $compareLv = strtolower((string)$lv);
                                }
                                $compareAuctionValForIn = ($isLowercase && is_string($auctionValue)) ? strtolower($auctionValue) : $auctionValue;

                                if ($compareAuctionValForIn == $compareLv) {
                                    $foundInList = true;
                                    break;
                                }
                            }

                            if ($operatorSuffix === '__in' && !$foundInList) {
                                $this->logger->debug("[getAuctions] Filter '$filterKeyWithPotentialSuffix' (__in) nicht erfüllt.", ['auction_id' => $auctionIdForLog, 'auction_val' => $auctionValue, 'list' => $listValues]);
                                $include = false;
                            } elseif ($operatorSuffix === '__not_in' && $foundInList) {
                                $this->logger->debug("[getAuctions] Filter '$filterKeyWithPotentialSuffix' (__not_in) nicht erfüllt.", ['auction_id' => $auctionIdForLog, 'auction_val' => $auctionValue, 'list' => $listValues]);
                                $include = false;
                            }
                            break;

                        case 'contains': // Annahme: $filterValue ist hier der Suchstring
                            if (!is_string($auctionValue) || stripos((string)$auctionValue, (string)$filterValue) === false) {
                                $this->logger->debug("[getAuctions] Filter '$filterKeyWithPotentialSuffix' (__contains) nicht erfüllt.", ['auction_id' => $auctionIdForLog, 'auction_val' => $auctionValue, 'needle' => $filterValue]);
                                $include = false;
                            }
                            break;

                        default:
                            $this->logger->warning("[getAuctions] Unbekannter Filtertyp '$actualFilterType' für Filter '$filterKeyWithPotentialSuffix'. Filter ignoriert.");
                            break;
                    }

                    // Allgemeine Prüfung, ob der aktuelle Filter die Auktion ausschließt
                    if (!$matchFound) {
                        $this->logger->debug("[FilterLoopResult] Auktion {$auctionIdForLog}: Filter '{$filterKeyWithPotentialSuffix}' (Base: '{$baseFilterKey}', Type: '{$actualFilterType}') NOT satisfied. AuctionValue: '".(is_array($auctionValue) ? json_encode($auctionValue) : strval($auctionValue))."', FilterValue: '".(is_array($filterValue) ? json_encode($filterValue) : strval($filterValue))."'. Excluding auction.");
                        $include = false;
                        break; 
                    } else {
                        $this->logger->debug("[FilterLoopResult] Auktion {$auctionIdForLog}: Filter '{$filterKeyWithPotentialSuffix}' (Base: '{$baseFilterKey}', Type: '{$actualFilterType}') satisfied.");
                    }
                }

                if ($include) {
                    $filteredAuctions[] = $auction;
                }
            }

            $auctions = $filteredAuctions;
            $this->logger->info('[getAuctions] Nach Filterung verbleiben ' . count($auctions) . ' Auktionen.');
        } else {
            $this->logger->info('[getAuctions] Keine Filter angewendet, ' . $initialCount . ' Auktionen werden zurückgegeben.');
        }

        // Mehrstufige Sortierung anwenden, falls sortRules vorhanden
        if (!empty($sortRules)) {
            $this->logger->info("[getAuctions] Wende mehrstufige Sortierung an mit " . count($sortRules) . " Regeln");
            
            usort($auctions, function ($a, $b) use ($sortRules) {
                foreach ($sortRules as $rule) {
                    $field = $rule['field'];
                    $direction = $rule['direction'];
                    $valueType = $rule['value_type'];
                    
                    $valA = $a[$field] ?? null;
                    $valB = $b[$field] ?? null;
                    
                    // Behandlung für numerische und String-Werte
                    if ($valueType === 'int' || $valueType === 'float') {
                        $valA = ($valueType === 'int') ? (int)$valA : (float)$valA;
                        $valB = ($valueType === 'int') ? (int)$valB : (float)$valB;
                    } else {
                        $valA = strtolower((string)$valA);
                        $valB = strtolower((string)$valB);
                    }
                    
                    if ($valA == $valB) {
                        continue; // Gleiche Werte, zur nächsten Sortierregel gehen
                    }
                    
                    // Rückgabe des Sortiervergleichs basierend auf der aktuellen Regel
                    if ($direction === 'asc') {
                        return ($valA < $valB) ? -1 : 1;
                    } else {
                        return ($valA > $valB) ? -1 : 1;
                    }
                }
                
                // Wenn alle Regeln gleiche Werte ergeben haben, sind die Elemente als gleich zu betrachten
                return 0;
            });
            
            $this->logger->debug("[getAuctions] Mehrstufige Sortierung abgeschlossen.");
        }
        // Einfache Sortierung für Abwärtskompatibilität, falls keine sortRules aber sortBy vorhanden
        elseif ($sortBy && isset($this->filterableFields[$sortBy])) {
            $sortFieldKey = $this->filterableFields[$sortBy]['field'];
            $valueType = $this->filterableFields[$sortBy]['value_type'] ?? 'string';
            $this->logger->info("[getAuctions] Sortiere Auktionen nach Feld: {$sortFieldKey} ({$valueType}), Richtung: {$sortDirection}");

            usort($auctions, function ($a, $b) use ($sortFieldKey, $sortDirection, $valueType) {
                $valA = $a[$sortFieldKey] ?? null;
                $valB = $b[$sortFieldKey] ?? null;

                // Behandlung für numerische und String-Werte
                if ($valueType === 'int' || $valueType === 'float') {
                    $valA = ($valueType === 'int') ? (int)$valA : (float)$valA;
                    $valB = ($valueType === 'int') ? (int)$valB : (float)$valB;
                } else {
                    $valA = strtolower((string)$valA);
                    $valB = strtolower((string)$valB);
                }

                if ($valA == $valB) {
                    return 0;
                }

                if ($sortDirection === 'asc') {
                    return ($valA < $valB) ? -1 : 1;
                }
                
                return ($valA > $valB) ? -1 : 1;
            });
        } elseif ($sortBy) {
            $this->logger->warning("[getAuctions] Sortierfeld '{$sortBy}' ist nicht in filterableFields konfiguriert oder Feld-Key fehlt. Sortierung übersprungen.");
        }

        return array_values($auctions); // Indizes zurücksetzen
    }

    /**
     * Öffentliche Methode zum Abrufen einer spezifischen Auktion per ID
     * Optimiert: Versucht zuerst den direkten API-Abruf für die ID.
     */
    public function getAuctionById(string $id): ?array
    {
        $this->logger->debug('[getAuctionById] Auktion mit ID wird abgerufen (Cache-First): ' . $id);

        // Primärer Weg: Alle Auktionen aus dem Cache (oder API-Fallback von getPublicAuctions) laden
        $auctions = $this->getPublicAuctions(); // Nutzt Cache/Basic Auth

        if (empty($auctions)) {
            $this->logger->warning('[getAuctionById] Keine Auktionen von getPublicAuctions erhalten, kann ID '.$id.' nicht finden.');
            return null;
        }

        // Suche in der Gesamtliste nach der ID
        $normalizedId = trim((string)$id);
        foreach ($auctions as $auction) {
            // Nur auf die gemappte 'id' prüfen
            if (isset($auction['id']) && (string)$auction['id'] === $normalizedId) {
                 $this->logger->debug('[getAuctionById] Auktion mit ID ' . $id . ' in der von getPublicAuctions gelieferten Liste gefunden.');
                return $auction;
            }
        }

        // Wenn die Auktion hier nicht gefunden wurde, bedeutet das, sie war nicht in der gecachten/allgemeinen Liste.
        // Gemäß Anforderung wird fetchAuctionRaw nicht mehr als Fallback für normale Frontend-Abrufe genutzt.
        $this->logger->warning('[getAuctionById] Keine Auktion mit ID ' . $id . ' in der von getPublicAuctions gelieferten Liste gefunden. Es erfolgt kein weiterer API-Versuch via fetchAuctionRaw.');
        return null;
    }

    /**
     * Alle verfügbaren Bundesländer abrufen
     */
    public function getAllBundeslaender(): array
    {
        // Auktionen abrufen (direkt die öffentliche, potenziell gecachte Liste)
        $auctions = $this->getPublicAuctions(); // Direkt auf die ungefilterte Liste zugreifen

        if (empty($auctions)) {
             $this->logger->warning('[getAllBundeslaender] Keine Auktionen von getPublicAuctions erhalten.');
             return [];
        }

        // Bundesländer extrahieren
        $bundeslaender = [];
        foreach ($auctions as $auction) {
            // Stelle sicher, dass das Feld existiert und nicht leer ist
            if (!empty($auction['bundesland'])) {
                 $bundeslandValue = $auction['bundesland'];
                 // Füge nur eindeutige Werte hinzu
                 if (!in_array($bundeslandValue, $bundeslaender)) {
                    $bundeslaender[] = $bundeslandValue;
                 }
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
        // Auktionen abrufen (direkt die öffentliche, potenziell gecachte Liste)
        $auctions = $this->getPublicAuctions(); // Sicherstellen, dass von ungefilterter Liste ausgegangen wird

        if (empty($auctions)) {
             $this->logger->warning('[getAllLandkreise] Keine Auktionen von getPublicAuctions erhalten.');
             return [];
        }

        // Landkreise extrahieren
        $landkreise = [];
        foreach ($auctions as $auction) {
            // Stelle sicher, dass district existiert und nicht leer ist
            if (!empty($auction['district']) &&
                // Filter auf Bundesland anwenden, falls gegeben
                ($bundesland === null || (isset($auction['bundesland']) && $auction['bundesland'] === $bundesland))
               )
            {
                $districtValue = $auction['district'];
                 // Füge nur eindeutige Werte hinzu
                 if (!in_array($districtValue, $landkreise)) {
                     $landkreise[] = $districtValue;
                 }
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
     * Beim Beenden der PHP-Ausführung aufräumen
     */
    public function __destruct()
    {
        // Cookie-Datei löschen, wenn sie existiert
        if (file_exists($this->cookieFile)) {
            @unlink($this->cookieFile);
        }
    }

    /**
     * Holt die Rohdaten einer einzelnen Beispielauktion.
     * Ideal für die Anzeige im Backend, um verfügbare Felder zu sehen.
     *
     * @return array|null Die Rohdaten der ersten gefundenen Auktion oder null.
     */
    public function getSampleAuctionRawData(): ?array
    {
        $this->logger->debug('[AuctionService] getSampleAuctionRawData() aufgerufen.');
        // ForceRefresh auf false setzen, um nicht bei jedem Backend-Aufruf die API neu zu belasten
        // getAuctions sollte idealerweise intern den Cache von getPublicAuctions nutzen.
        $allAuctions = $this->getAuctions([], false); 

        if (empty($allAuctions)) {
            $this->logger->warning('[AuctionService] Keine Auktionen für Sample Raw Data gefunden.');
            return null;
        }

        $sampleAuction = reset($allAuctions); 

        if (isset($sampleAuction['_raw_data']) && is_array($sampleAuction['_raw_data'])) {
            $this->logger->debug('[AuctionService] Sample Raw Data (aus _raw_data) gefunden.');
            return $sampleAuction['_raw_data'];
        }
        
        $this->logger->debug('[AuctionService] Sample Raw Data (aus gemapptem Array als Fallback) gefunden, da \'_raw_data\' nicht gesetzt war.', ['sampleAuctionKeys' => array_keys($sampleAuction)]);
        return $sampleAuction; 
    }

    /**
     * Parst einen Filter-String (eine Regel pro Zeile) in ein Array,
     * das von der getAuctions Methode verwendet werden kann.
     *
     * Beispiel String-Format:
     * bundesland = Bayern
     * leistung_mw > 10
     * status IN STARTED,FIRST_ROUND
     *
     * @param string $filterString Der zu parsende String.
     * @return array Die geparsten Filter im Format ['filterKey' => 'value'] oder ['filterKey' => ['min' => x, 'max' => y]]
     */
    public function parseFiltersFromString(string $filterString): array
    {
        $parsedFilters = [];
        if (empty(trim($filterString))) {
            return $parsedFilters;
        }

        $lines = preg_split('/\r\n|\r|\n/', $filterString);
        $supportedOperators = ['=', '!=', '<', '>', '<=', '>=', 'IN', 'NOT IN', 'CONTAINS'];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Versuche, Feld, Operator und Wert zu extrahieren
            // Regex, um Feld, Operator und den Rest als Wert zu erfassen
            if (preg_match('/^(\w+)\s*(' . implode('|', array_map('preg_quote', $supportedOperators)) . ')\s*(.+)$/i', $line, $matches)) {
                $fieldKey = trim($matches[1]);
                $operator = strtoupper(trim($matches[2]));
                $value = trim($matches[3]);

                if (!isset($this->filterableFields[$fieldKey])) {
                    $this->logger->warning("[parseFiltersFromString] Unbekanntes Filterfeld '{$fieldKey}' in Zeile: '{$line}'. Wird ignoriert.");
                    continue;
                }

                $config = $this->filterableFields[$fieldKey];
                $filterType = $config['type'];
                $valueType = $config['value_type'] ?? 'string';

                // Spezifische Behandlung für minmax-Filter, wenn Operatoren <, >, <=, >= verwendet werden
                if ($filterType === 'minmax') {
                    if (!isset($parsedFilters[$fieldKey])) {
                        $parsedFilters[$fieldKey] = ['min' => null, 'max' => null];
                    }
                    if (in_array($operator, ['>', '>='])) {
                        $parsedFilters[$fieldKey]['min'] = ($operator === '>') ? $value + 0.00001 : $value; // Kleine Anpassung für exklusives >
                    } elseif (in_array($operator, ['<', '<='])) {
                        $parsedFilters[$fieldKey]['max'] = ($operator === '<') ? $value - 0.00001 : $value; // Kleine Anpassung für exklusives <
                    }
                    // Sicherstellen, dass Werte korrekt typisiert sind
                    if (isset($parsedFilters[$fieldKey]['min'])) {
                        $parsedFilters[$fieldKey]['min'] = ($valueType === 'int') ? (int)$parsedFilters[$fieldKey]['min'] : (float)$parsedFilters[$fieldKey]['min'];
                    }
                    if (isset($parsedFilters[$fieldKey]['max'])) {
                        $parsedFilters[$fieldKey]['max'] = ($valueType === 'int') ? (int)$parsedFilters[$fieldKey]['max'] : (float)$parsedFilters[$fieldKey]['max'];
                    }
                     // Entferne null-Werte, falls nur ein Teil des minmax gesetzt wurde
                    if ($parsedFilters[$fieldKey]['min'] === null) unset($parsedFilters[$fieldKey]['min']);
                    if ($parsedFilters[$fieldKey]['max'] === null) unset($parsedFilters[$fieldKey]['max']);
                    if (empty($parsedFilters[$fieldKey])) unset($parsedFilters[$fieldKey]);

                } elseif ($operator === 'IN' || $operator === 'NOT IN') {
                    // Für IN und NOT IN erwarten wir eine kommaseparierte Liste
                    // Die getAuctions-Methode muss dies intern behandeln oder wir passen das hier an.
                    // Aktuell unterstützt getAuctions direkt Array-Werte für IN-Filter nicht.
                    // Wir müssen das Format anpassen oder getAuctions erweitern.
                    // Vorerst belassen wir es dabei, dass getAuctions dies handhaben muss, wenn wir einen Filter für 'IN' definieren.
                    // Für diese Demo: Wir gehen davon aus, dass 'getAuctions' einen einzelnen Wert oder ein Array für 'IN' verarbeiten kann.
                    // Wichtig: Der 'IN' Operator im String wird hier nicht direkt in ein Array umgewandelt,
                    // sondern der Wert bleibt ein String. getAuctions muss das bei der Verarbeitung des 'IN' Operators berücksichtigen.
                    $parsedFilters[$fieldKey . ($operator === 'NOT IN' ? '__not_in' : '__in')] = $value; // Spezialschlüssel für IN/NOT IN
                                    } elseif ($operator === 'CONTAINS') {
                    $parsedFilters[$fieldKey . '__contains'] = $value; // Spezialschlüssel für CONTAINS

                } elseif (in_array($operator, ['=', '!='])) {
                     $finalValue = $value;
                     if ($valueType === 'bool') {
                         $finalValue = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                         if ($finalValue === null) {
                            $this->logger->warning("[parseFiltersFromString] Ungültiger boolescher Wert '{$value}' für Feld '{$fieldKey}'. Filter ignoriert.");
                            continue;
                         }
                     } elseif ($valueType === 'int') {
                        $finalValue = (int)$value;
                     } elseif ($valueType === 'float') {
                        $finalValue = (float)$value;
                     }
                    $parsedFilters[$fieldKey . ($operator === '!=' ? '__ne' : '')] = $finalValue; // Spezialschlüssel für != oder Standard
                } else {
                    $this->logger->warning("[parseFiltersFromString] Nicht unterstützter Operator '{$operator}' für Feld '{$fieldKey}' in Zeile: '{$line}'. Wird ignoriert.");
                }
            } else {
                $this->logger->warning("[parseFiltersFromString] Ungültiges Filterformat in Zeile: '{$line}'. Wird ignoriert.");
            }
        }
        $this->logger->debug("[parseFiltersFromString] Geparste Filter aus String: ", $parsedFilters);
        return $parsedFilters;
    }

    /**
     * Parst einen Sortierregeln-String (eine Regel pro Zeile) in ein Array,
     * das von der getAuctions Methode verwendet werden kann.
     *
     * Beispiel String-Format:
     * leistung_mw asc
     * countDown desc
     * flaeche_ha asc
     *
     * @param string $sortRulesString Der zu parsende String.
     * @return array Die geparsten Sortierregeln im Format [['field' => 'feldname', 'direction' => 'asc|desc'], ...]
     */
    public function parseSortRulesFromString(string $sortRulesString): array
    {
        $parsedRules = [];
        if (empty(trim($sortRulesString))) {
            return $parsedRules;
        }

        $lines = preg_split('/\r\n|\r|\n/', $sortRulesString);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Regex, um Feldname und Sortierrichtung zu erfassen
            // Erwartet Format: "feldname richtung" oder nur "feldname" (Default: asc)
            if (preg_match('/^(\w+)(?:\s+(asc|desc))?$/i', $line, $matches)) {
                $fieldKey = trim($matches[1]);
                $direction = isset($matches[2]) ? strtolower(trim($matches[2])) : 'asc';

                if (!isset($this->filterableFields[$fieldKey])) {
                    $this->logger->warning("[parseSortRulesFromString] Unbekanntes Sortierfeld '{$fieldKey}' in Zeile: '{$line}'. Wird ignoriert.");
                    continue;
                }

                $config = $this->filterableFields[$fieldKey];
                $fieldName = $config['field'];

                $parsedRules[] = [
                    'field' => $fieldName,
                    'key' => $fieldKey, // Original-Schlüssel für Logging
                    'direction' => $direction,
                    'value_type' => $config['value_type'] ?? 'string'
                ];
            } else {
                $this->logger->warning("[parseSortRulesFromString] Ungültiges Sortierformat in Zeile: '{$line}'. Wird ignoriert.");
            }
        }
        
        $this->logger->debug("[parseSortRulesFromString] Geparste Sortierregeln aus String: ", $parsedRules);
        return $parsedRules;
    }

    /**
     * Ruft alle eindeutigen Werte für "Eigenschaft/Objektart" (property) aus den Auktionen ab.
     *
     * @return array Eine sortierte Liste eindeutiger Property-Werte.
     */
    public function getUniquePropertyValues(): array
    {
        $this->logger->debug('[getUniquePropertyValues] Rufe eindeutige Property-Werte ab.');
        $auctions = $this->getPublicAuctions(); // Ruft gemappte Auktionen ab (aus Cache oder API)

        if (empty($auctions)) {
            $this->logger->warning('[getUniquePropertyValues] Keine Auktionen von getPublicAuctions erhalten.');
            return [];
        }

        $propertyValues = [];
        foreach ($auctions as $auction) {
            // Greife auf das gemappte Feld 'property' zu
            if (isset($auction['property']) && $auction['property'] !== null && $auction['property'] !== '') {
                $propertyValue = $auction['property'];
                if (!in_array($propertyValue, $propertyValues, true)) {
                    $propertyValues[] = $propertyValue;
                }
            }
        }

        sort($propertyValues); // Sortiere die Werte alphabetisch
        $this->logger->info('[getUniquePropertyValues] Eindeutige Property-Werte gefunden: ' . implode(', ', $propertyValues));
        return $propertyValues;
    }
}
