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
        'bundesland' => ['field' => 'bundesland', 'type' => 'exact', 'access_raw' => false], // Frontend-Key, greift auf gemapptes 'bundesland' zu
        'landkreis' => ['field' => 'district', 'type' => 'lowercase_exact'], // Beachte: Feld heißt 'district' im Mapping, dieser Key eher für Backend?
        'status' => ['field' => 'status', 'type' => 'exact', 'access_raw' => false], // Greift auf gemapptes 'status' zu (Rohdatenfeld heißt auch status, aber Konsistenz)
        'size' => ['field' => 'flaeche_ha', 'type' => 'minmax', 'value_type' => 'float', 'access_raw' => false], // Greift auf gemapptes 'flaeche_ha' zu
        'areaSize' => ['field' => 'flaeche_ha', 'type' => 'minmax', 'value_type' => 'float', 'access_raw' => false], // Greift auf gemapptes 'flaeche_ha' zu
        'leistung' => ['field' => 'leistung_mw', 'type' => 'minmax', 'value_type' => 'float', 'access_raw' => false], // Greift auf gemapptes 'leistung_mw' zu
        'power' => ['field' => 'leistung_mw', 'type' => 'minmax', 'value_type' => 'float', 'access_raw' => false], // Greift auf gemapptes 'leistung_mw' zu
        'volllaststunden' => ['field' => 'volllaststunden', 'type' => 'minmax', 'value_type' => 'int', 'access_raw' => false], // Greift auf gemapptes 'volllaststunden' zu
        'property' => ['field' => 'property', 'type' => 'exact', 'access_raw' => false], // Greift auf gemapptes 'property' zu
        'focus' => ['field' => 'focus', 'type' => 'exact', 'value_type' => 'bool', 'access_raw' => false], // Explizit auf gemapptes Feld zugreifen
        'isAuctionInFocus' => ['field' => 'focus', 'type' => 'exact', 'value_type' => 'bool', 'access_raw' => false], // Backend-Key, greift auf gemapptes 'focus' zu
        'irr' => ['field' => 'internalRateOfReturnBeforeRent', 'type' => 'minmax', 'value_type' => 'float', 'access_raw' => false], // Greift auf gemapptes 'internalRateOfReturnBeforeRent' zu
        // Weitere Felder können hier hinzugefügt werden, z.B.:
        // 'some_bool_field' => ['field' => 'is_active', 'type' => 'exact', 'value_type' => 'bool'],
        // 'some_exact_string' => ['field' => 'projekt_name', 'type' => 'exact'],
    ];

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ParameterBagInterface $params
    ) {
        // Cache-Verzeichnis
        $this->cacheDir = $_SERVER['DOCUMENT_ROOT'] . '/../' . self::CACHE_DIR;
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }

        $this->logger->debug('AuctionService initialisiert, Cache: ' . $this->cacheDir);
    }

    /**
     * Implementierung analog zu getPublicMarketplace() aus Cronjobs.php
     * Verwendet Basic Authentication statt Login/CSRF
     *
     * @param bool $useCache Gibt an, ob der Cache verwendet werden soll
     * @param string|null $urlParams Zusätzliche URL-Parameter die an die API-URL angehängt werden
     * @return array|null
     */
    private function getPublicAuctions(bool $useCache = true, ?string $urlParams = null): ?array
    {
        // Cache-Dateinamen basierend auf URL-Parametern generieren
        $paramHash = !empty($urlParams) ? '_' . md5($urlParams) : '';
        $rawCacheFile = $this->cacheDir . '/' . str_replace('.json', $paramHash . '.json', self::RAW_AUCTIONS_CACHE_FILE);
        $mappedCacheFile = $this->cacheDir . '/' . str_replace('.json', $paramHash . '.json', self::MAPPED_AUCTIONS_CACHE_FILE);

        $this->logger->debug('[getPublicAuctions] Prüfe Caches. Raw: ' . $rawCacheFile . ', Mapped: ' . $mappedCacheFile . ' | useCache=' . ($useCache ? 'true' : 'false') . ' | urlParams=' . ($urlParams ?: 'null'));

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
                    $data = json_decode($cachedMappedData, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $this->logger->info('[getPublicAuctions] Erfolgreich gemappte Auktionen aus Cache geladen: ' . $mappedCacheFile);
                        return $data;
                    }
                    $this->logger->warning('[getPublicAuctions] Gemappte Cache-Datei ist kein valides JSON: ' . $mappedCacheFile);
                }
            } else {
                $this->logger->info('[getPublicAuctions] Gemappte Cache-Datei ist abgelaufen: ' . $mappedCacheFile);
            }
        } else {
            $this->logger->info('[getPublicAuctions] Keine gemappte Cache-Datei gefunden oder Cache deaktiviert.');
        }

        // 2. Versuche, Rohdaten aus dem Cache zu laden (wenn gemappte nicht verfügbar/abgelaufen)
        $rawAuctions = null;
        if ($useCache && file_exists($rawCacheFile)) {
            $rawCacheAge = time() - filemtime($rawCacheFile);
            $this->logger->debug('[getPublicAuctions] Rohdaten-Cache-Datei gefunden, Alter: ' . $rawCacheAge . 's');

            if ($rawCacheAge < self::CACHE_LIFETIME) {
                $this->logger->info('[getPublicAuctions] Versuche, gültigen Rohdaten-Cache zu verwenden: ' . $rawCacheFile);
                $cachedRawData = @file_get_contents($rawCacheFile);
                if ($cachedRawData === false) {
                    $this->logger->error('[getPublicAuctions] FEHLER beim Lesen der Rohdaten-Cache-Datei: ' . $rawCacheFile);
                } else {
                    $data = json_decode($cachedRawData, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $this->logger->info('[getPublicAuctions] Erfolgreich Rohdaten aus Cache geladen: ' . $rawCacheFile);
                        $rawAuctions = $data; // Rohdaten für späteres Mapping verwenden
                    } else {
                        $this->logger->warning('[getPublicAuctions] Rohdaten-Cache-Datei ist kein valides JSON: ' . $rawCacheFile);
                    }
                }
            } else {
                $this->logger->info('[getPublicAuctions] Rohdaten-Cache-Datei ist abgelaufen: ' . $rawCacheFile);
            }
        } else {
            $this->logger->info('[getPublicAuctions] Keine Rohdaten-Cache-Datei gefunden oder Cache deaktiviert.');
        }


        // 3. Wenn keine gültigen Rohdaten aus dem Cache, von API holen
        if ($rawAuctions === null) {
            $this->logger->info('[getPublicAuctions] Keine gültigen Rohdaten im Cache, versuche API-Abruf.');
            $apiUrl = rtrim($this->params->get('caeli_auction.marketplace_api_url'), '/');
            $apiAuth = $this->params->get('caeli_auction.marketplace_api_auth');

            // URL-Parameter anhängen, falls vorhanden
            if (!empty($urlParams)) {
                // Sicherstellen, dass der Parameter korrekt formatiert ist
                $urlParams = ltrim($urlParams, '/');
                if (!empty($urlParams)) {
                    $apiUrl .= '/' . $urlParams;
                }
                $this->logger->debug('[getPublicAuctions] URL-Parameter hinzugefügt: ' . $urlParams);
                $this->logger->info('[getPublicAuctions] Finale API-URL: ' . $apiUrl);
            }

            if (empty($apiUrl) || empty($apiAuth)) {
                $this->logger->error('[getPublicAuctions] Marketplace API URL oder Auth Token nicht konfiguriert. API-Abruf übersprungen.');
                return null;
            }

            $this->logger->debug('[getPublicAuctions] Rufe öffentliche Auktionen von API ab: ' . $apiUrl);

            // Basic Auth Header vorbereiten
            $authHeader = 'Basic ' . $apiAuth; // $apiAuth direkt verwenden

            $contextOptions = [
                'http' => [
                    'method' => 'GET',
                    'header' => "Authorization: " . $authHeader . "\r\n",
                    'timeout' => 30 // Timeout in Sekunden
                ]
            ];
            $context = stream_context_create($contextOptions);

            try {
                $response = @file_get_contents($apiUrl, false, $context);

                if ($response === false) {
                    $error = error_get_last();
                    $this->logger->error('[getPublicAuctions] API-Anfrage fehlgeschlagen: ' . ($error['message'] ?? 'Unbekannter Fehler'));
                    // HTTP-Statuscode aus den Headern extrahieren, falls möglich (erfordert PHP 7.1+ für $http_response_header)
                    if (isset($http_response_header)) {
                        foreach ($http_response_header as $header) {
                            if (preg_match('{^HTTP/\d\.\d\s+(\d+)\s*(.*)$}', $header, $matches)) {
                                $statusCode = (int)$matches[1];
                                $this->logger->error('[getPublicAuctions] API-Antwort Status Code: ' . $statusCode);
                                break;
                            }
                        }
                    }
                    return null;
                }

                $this->logger->debug('[getPublicAuctions] API-Antwort erhalten. Länge: ' . strlen($response));
                $decodedResponse = json_decode($response, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->logger->error('[getPublicAuctions] API-Antwort konnte nicht als JSON geparst werden: ' . json_last_error_msg());
                    $this->logger->debug('[getPublicAuctions] Rohe API-Antwort: ' . substr($response, 0, 500)); // Logge einen Teil der Antwort
                    return null;
                }

                $rawAuctions = $decodedResponse; // API-Antwort sind die Rohdaten

                // Rohdaten im Cache speichern
                if (!is_dir(dirname($rawCacheFile))) {
                    @mkdir(dirname($rawCacheFile), 0755, true);
                }
                if (@file_put_contents($rawCacheFile, json_encode($rawAuctions)) === false) {
                    $this->logger->error('[getPublicAuctions] FEHLER beim Schreiben der Rohdaten in Cache-Datei: ' . $rawCacheFile);
                } else {
                    $this->logger->info('[getPublicAuctions] Rohdaten erfolgreich in Cache geschrieben: ' . $rawCacheFile);
                }

            } catch (\Exception $e) {
                $this->logger->error('[getPublicAuctions] Ausnahme beim API-Abruf: ' . $e->getMessage());
                return null;
            }
        }


        // 4. Rohdaten mappen (entweder aus Cache oder frisch von API)
        if (empty($rawAuctions)) {
            $this->logger->warning('[getPublicAuctions] Keine Rohdaten zum Mappen vorhanden.');
            return []; // Leeres Array zurückgeben, wenn keine Daten vorhanden sind
        }

        $this->logger->debug('[getPublicAuctions] Beginne Mapping von ' . count($rawAuctions) . ' Roh-Auktionen.');
        $mappedAuctions = [];
        $itemCounter = 0;
        foreach ($rawAuctions as $auction) {
            $itemCounter++;
            if (!is_array($auction)) {
                $this->logger->warning('[getPublicAuctions] Element ' . $itemCounter . ' in Rohdaten ist kein Array, wird übersprungen.');
                continue;
            }
            $mappedAuction = $this->mapPublicAuctionToInternalFormat($auction);
            if ($mappedAuction) { // Nur hinzufügen, wenn das Mapping erfolgreich war und nicht null ist
                $mappedAuctions[] = $mappedAuction;
            }
        }
        $this->logger->debug('[getPublicAuctions] Mapping abgeschlossen. ' . count($mappedAuctions) . ' Auktionen gemappt.');

        // Gemappte Daten im Cache speichern
        if (!is_dir(dirname($mappedCacheFile))) {
            @mkdir(dirname($mappedCacheFile), 0755, true);
        }
        if (@file_put_contents($mappedCacheFile, json_encode($mappedAuctions)) === false) {
            $this->logger->error('[getPublicAuctions] FEHLER beim Schreiben der gemappten Auktionen in Cache-Datei: ' . $mappedCacheFile);
        } else {
            $this->logger->info('[getPublicAuctions] Gemappte Auktionen erfolgreich in Cache geschrieben: ' . $mappedCacheFile);
        }

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

            if (empty($apiBaseUrl) || empty($BasicAuth)) {
                $this->logger->error('Marketplace API URL oder Auth Token nicht konfiguriert. Download für Bild ' . $filename . ' übersprungen.');
                return '';
            }

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
     * Löscht den Cache für die Auktionsdaten (alle Varianten)
     */
    public function clearCache(): bool
    {
        $success = true;
        
        // Cache-Verzeichnis durchsuchen und alle Auction-Cache-Dateien löschen
        if (is_dir($this->cacheDir)) {
            $files = glob($this->cacheDir . '/auctions*.json');
            foreach ($files as $file) {
                if (file_exists($file)) {
                    $deleteSuccess = @unlink($file);
                    $success = $success && $deleteSuccess;
                    $this->logger->info('Cache-Datei gelöscht: ' . $file . ' (Erfolg: ' . ($deleteSuccess ? 'ja' : 'nein') . ')');
                }
            }
        }
        
        return $success;
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
     * @param string|null $urlParams Zusätzliche URL-Parameter die an die API-URL angehängt werden
     * @return array Die gefilterten und sortierten Auktionen
     */
    public function getAuctions(array $filters = [], bool $forceRefresh = false, ?string $sortBy = null, string $sortDirection = 'asc', array $sortRules = [], ?string $urlParams = null): array
    {
        $this->logger->debug('[getAuctions] Auktionen werden abgerufen', [ // Präfix [TESTLOG-ERROR] entfernt
            'raw_filters' => $filters,
            'forceRefresh' => $forceRefresh,
            'sortBy' => $sortBy,
            'sortDirection' => $sortDirection,
            'sortRules' => $sortRules,
        ]);

        $auctions = $this->getPublicAuctions(!$forceRefresh, $urlParams);
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
        $this->logger->debug('[getAuctions] Strukturierte Filter für Verarbeitung:', $structuredFilters); // $structuredFilters ist jetzt $parsedFilters von parseFiltersFromString

        if (!empty($structuredFilters)) { // $structuredFilters ist die Ausgabe von parseFiltersFromString
            $this->logger->debug('[getAuctions] Wende geparste Filter an: ' . json_encode($structuredFilters));
            $finalFilteredAuctions = []; // Umbenannt von $filteredAuctions

            foreach ($auctions as $auction) {
                $includeAuction = true; // Umbenannt von $include
                $auctionIdForLog = $auction['id'] ?? 'unbekannte_ID';

                foreach ($structuredFilters as $filterFieldKey => $filterRule) {
                    $auctionValue = null;
                    if ($filterRule['access_raw']) {
                        $auctionValue = $auction['_raw_data'][$filterFieldKey] ?? null;
                    } else {
                        $auctionValue = $auction[$filterFieldKey] ?? null;
                    }

                    $filterValueFromRule = $filterRule['value'];
                    $operatorType = $filterRule['operator_type'];
                    $valueTypeHint = $filterRule['value_type_hint'];

                    $matchThisRule = false;

                    $this->logger->debug("[FilterLoop] Auktion: {$auctionIdForLog}, FieldKey: '{$filterFieldKey}', Operator: '{$operatorType}', AccessRaw: " . ($filterRule['access_raw']?'true':'false') . ", AuctionValue: '" . (is_array($auctionValue) ? json_encode($auctionValue) : $auctionValue) . "' (Typ: " . gettype($auctionValue) . "), FilterValueFromRule: '" . (is_array($filterValueFromRule) ? json_encode($filterValueFromRule) : $filterValueFromRule) . "', ValueTypeHint: '{$valueTypeHint}'");

                    switch ($operatorType) {
                        case 'equal':
                        case 'not_equal':
                            $compareAuctionVal = $this->coerceTypeForComparison($auctionValue, $filterValueFromRule, $valueTypeHint);
                            $compareFilterVal = $this->coerceTypeForComparison($filterValueFromRule, $auctionValue, $valueTypeHint);

                            if ($valueTypeHint === 'bool' && $compareFilterVal === null && is_string($filterValueFromRule) && $filterValueFromRule !== ''){
                                // Ungültiger boolescher Wert im Filter, Regel nicht anwendbar
                                $this->logger->warning("[FilterLoop] Ungültiger boolescher Filterwert '{$filterValueFromRule}' für Feld {$filterFieldKey}. Regel ignoriert.");
                                $matchThisRule = true; // Ignorierte Regel schließt nicht aus
                                break;
                            }

                            $currentMatch = ($compareAuctionVal == $compareFilterVal);
                            $matchThisRule = ($operatorType === 'equal') ? $currentMatch : !$currentMatch;
                            break;

                        case 'minmax':
                            // auctionValue muss numerisch sein für minmax
                            if (!is_numeric($auctionValue)) {
                                $this->logger->debug("[FilterLoop][{$filterFieldKey}] MinMax nicht anwendbar, Auktionswert '{$auctionValue}' nicht numerisch.");
                                $matchThisRule = false; // Oder true, wenn nicht-numerische einfach ignoriert werden sollen? Aktuell: schließt aus.
                                break;
                            }
                            $numericAuctionValue = (float)$auctionValue;
                            $minMaxConditionsMet = true;

                            $minValueFromFilter = $filterValueFromRule['min'] ?? null;
                            $maxValueFromFilter = $filterValueFromRule['max'] ?? null;

                            if ($minValueFromFilter !== null) {
                                if ($numericAuctionValue < (float)$minValueFromFilter) {
                                    $minMaxConditionsMet = false;
                                }
                            }
                            if ($minMaxConditionsMet && $maxValueFromFilter !== null) {
                                if ($numericAuctionValue > (float)$maxValueFromFilter) {
                                    $minMaxConditionsMet = false;
                                }
                            }
                            $matchThisRule = $minMaxConditionsMet;
                            break;

                        case 'in':
                        case 'not_in':
                            $listValues = array_map('trim', explode(',', (string)$filterValueFromRule));
                            $foundInList = false;
                            foreach ($listValues as $listItem) {
                                $compareAuctionVal = $this->coerceTypeForComparison($auctionValue, $listItem, $valueTypeHint);
                                $compareListItem = $this->coerceTypeForComparison($listItem, $auctionValue, $valueTypeHint);
                                if ($compareAuctionVal == $compareListItem) {
                                    $foundInList = true;
                                    break;
                                }
                            }
                            $matchThisRule = ($operatorType === 'in') ? $foundInList : !$foundInList;
                            break;

                        case 'contains': // Annahme: String-Vergleich
                            $matchThisRule = (is_string($auctionValue) && is_string($filterValueFromRule) && stripos($auctionValue, $filterValueFromRule) !== false);
                            break;

                        default:
                            $this->logger->warning("[FilterLoop] Unbekannter operator_type '{$operatorType}' für Feld {$filterFieldKey}. Regel ignoriert.");
                            $matchThisRule = true; // Unbekannte Regel schließt nicht aus
                            break;
                    }

                    if (!$matchThisRule) {
                        $this->logger->debug("[FilterLoopResult] Auktion {$auctionIdForLog}: Filter '{$filterFieldKey}' NICHT erfüllt. Ausschluss.");
                        $includeAuction = false;
                        break; // Nächste Auktion, da eine Regel nicht erfüllt wurde
                    }
                    $this->logger->debug("[FilterLoopResult] Auktion {$auctionIdForLog}: Filter '{$filterFieldKey}' ERFÜLLT.");
                }

                if ($includeAuction) {
                    $finalFilteredAuctions[] = $auction;
                }
            }

            $auctions = $finalFilteredAuctions;
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
                    $accessRaw = $rule['access_raw'] ?? false; // Default auf false, falls nicht gesetzt

                    $valA = $accessRaw ? ($a['_raw_data'][$field] ?? null) : ($a[$field] ?? null);
                    $valB = $accessRaw ? ($b['_raw_data'][$field] ?? null) : ($b[$field] ?? null);

                    if ($valueType === 'attempt_dynamic') {
                        // Beide Werte numerisch? Dann numerisch sortieren.
                        if (is_numeric($valA) && is_numeric($valB)) {
                            $valA = (float)$valA;
                            $valB = (float)$valB;
                        }
                        // Beide Werte boolesch interpretierbar (auch als Strings 'true'/'false')? Dann boolesch.
                        // FILTER_NULL_ON_FAILURE ist wichtig, um nicht-boolesche Strings nicht fälschlich als false zu werten.
                        else {
                            $boolValA = filter_var($valA, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                            $boolValB = filter_var($valB, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                            if ($boolValA !== null && $boolValB !== null) {
                                $valA = $boolValA;
                                $valB = $boolValB;
                            } else {
                                // Fallback: Als String sortieren
                                $valA = strtolower((string)$valA);
                                $valB = strtolower((string)$valB);
                            }
                        }
                    } elseif ($valueType === 'int' || $valueType === 'float') {
                        $valA = ($valueType === 'int') ? (int)$valA : (float)$valA;
                        $valB = ($valueType === 'int') ? (int)$valB : (float)$valB;
                    } elseif ($valueType === 'bool') {
                        $valA = filter_var($valA, FILTER_VALIDATE_BOOLEAN);
                        $valB = filter_var($valB, FILTER_VALIDATE_BOOLEAN);
                    } else { // Default 'string' oder explizit 'string'
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
        // if (isset($this->cookieFile) && file_exists($this->cookieFile)) { // Entfernt, da cookieFile nicht mehr existiert
        //     @unlink($this->cookieFile);
        // }
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

            if (preg_match('/^(\w+)\s*(' . implode('|', array_map('preg_quote', $supportedOperators)) . ')\s*(.+)$/i', $line, $matches)) {
                $fieldKey = trim($matches[1]);
                $operator = strtoupper(trim($matches[2]));
                $value = trim($matches[3]);

                $targetFieldName = $fieldKey;
                $valueType = 'attempt_dynamic';
                $accessRaw = true; // Standard: Zugriff auf Rohdaten annehmen
                // Standard-Filtertyp für die Operator-Interpretation, wenn nicht in filterableFields bekannt
                // Wenn der Operator >, <, >=, <= ist, impliziert das einen Min/Max-artigen Vergleich.
                $filterParseType = 'exact'; // Default
                if (in_array($operator, ['<', '>', '<=', '>='])) {
                    $filterParseType = 'minmax'; // Damit die min/max Logik unten greift
                }

                if (isset($this->filterableFields[$fieldKey])) {
                    $config = $this->filterableFields[$fieldKey];
                    $targetFieldName = $config['field'] ?? $fieldKey;
                    $valueType = $config['value_type'] ?? 'attempt_dynamic';
                    $filterParseType = $config['type'] ?? $filterParseType; // Config 'type' überschreibt abgeleiteten Typ

                    if (isset($config['access_raw'])) {
                        $accessRaw = (bool)$config['access_raw'];
                    } else {
                        $accessRaw = ($targetFieldName === $fieldKey);
                    }
                } else {
                    // fieldKey nicht in filterableFields: dynamischer Zugriff auf Rohdatenfeld fieldKey
                    // $targetFieldName bleibt $fieldKey
                    // $valueType bleibt 'attempt_dynamic'
                    // $accessRaw bleibt true
                    // $filterParseType wurde oben basierend auf Operator ggf. schon auf minmax gesetzt
                }

                // Die eigentliche Weiterverarbeitung des Filters basierend auf $filterParseType, $operator, $value
                // und Speicherung von targetFieldName, accessRaw, valueType.

                // Spezifische Behandlung für minmax-Filter, wenn Operatoren <, >, <=, >= verwendet werden
                // oder der filterParseType explizit 'minmax' ist.
                if ($filterParseType === 'minmax') {
                    $currentFilter = ['min' => null, 'max' => null]; // Temporäres Array für diesen Min/Max-Filter
                    if (in_array($operator, ['>', '>='])) {
                        $currentFilter['min'] = ($operator === '>') ? $this->convertToType($value, $valueType, 0.00001) : $this->convertToType($value, $valueType);
                    } elseif (in_array($operator, ['<', '<='])) {
                        $currentFilter['max'] = ($operator === '<') ? $this->convertToType($value, $valueType, -0.00001) : $this->convertToType($value, $valueType);
                    }
                    // Entferne null-Werte, falls nur ein Teil des minmax gesetzt wurde
                    if ($currentFilter['min'] === null) unset($currentFilter['min']);
                    if ($currentFilter['max'] === null) unset($currentFilter['max']);

                    if (!empty($currentFilter)) {
                         // Wenn bereits ein Min/Max-Filter für dieses Feld existiert, merge ihn.
                         // Wichtig für den Fall, dass der Benutzer z.B. "feld > 10" UND "feld < 20" eingibt.
                        if (isset($parsedFilters[$targetFieldName]) && is_array($parsedFilters[$targetFieldName]['value'])) {
                            $existingMinMax = $parsedFilters[$targetFieldName]['value'];
                            $currentFilter = array_merge($existingMinMax, $currentFilter);
                        }
                        $parsedFilters[$targetFieldName] = [
                            'value' => $currentFilter,
                            'operator_type' => 'minmax', // Eindeutiger Typ für die spätere Verarbeitung
                            'value_type_hint' => $valueType,
                            'access_raw' => $accessRaw
                        ];
                    }
                } elseif ($operator === 'IN' || $operator === 'NOT IN') {
                    $parsedFilters[$targetFieldName] = [
                        'value' => $value, // Bleibt als kommaseparierter String, wird in getAuctions behandelt
                        'operator_type' => ($operator === 'NOT IN' ? 'not_in' : 'in'),
                        'value_type_hint' => $valueType,
                        'access_raw' => $accessRaw
                    ];
                } elseif ($operator === 'CONTAINS') {
                    $parsedFilters[$targetFieldName] = [
                        'value' => $this->convertToType($value, $valueType),
                        'operator_type' => 'contains',
                        'value_type_hint' => $valueType,
                        'access_raw' => $accessRaw
                    ];
                } elseif (in_array($operator, ['=', '!='])) {
                    $parsedFilters[$targetFieldName] = [
                        'value' => $this->convertToType($value, $valueType),
                        'operator_type' => ($operator === '!=' ? 'not_equal' : 'equal'),
                        'value_type_hint' => $valueType,
                        'access_raw' => $accessRaw
                    ];
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
     * Hilfsmethode zur Typkonvertierung basierend auf valueType und attempt_dynamic Logik.
     * $offset wird für exklusive Min/Max-Vergleiche verwendet.
     */
    private function convertToType($value, string $valueTypeHint, $offset = 0)
    {
        if ($value === null || $value === '') return $value; // Leere Werte nicht ändern

        if ($valueTypeHint === 'int') {
            return (int)$value + (int)$offset;
        }
        if ($valueTypeHint === 'float') {
            return (float)$value + (float)$offset;
        }
        if ($valueTypeHint === 'bool') {
            // Offset hier nicht sinnvoll
            return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }
        // Für 'string' oder 'attempt_dynamic' (wenn keine spezifische Konvertierung zutrifft)
        // Bei attempt_dynamic wird der Wert erstmal als String belassen, die Vergleichslogik muss dynamisch sein.
        // Offset für Strings nicht sinnvoll im allgemeinen Fall, es sei denn es wäre eine numerische Operation.
        // Da hier aber der Filterwert konvertiert wird, und der Auktionswert später dynamisch verglichen wird,
        // ist eine Offset-Anwendung auf den Filterwert hier riskant, wenn der Typ nicht fest numerisch ist.
        // Für Min/Max, wo Offset genutzt wird, sollte valueTypeHint bereits numerisch sein.
        if (is_numeric($offset) && $offset != 0 && is_numeric($value)) { // Nur Offset anwenden, wenn Wert numerisch
             return (float)$value + (float)$offset;
        }
        return (string)$value;
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

            if (preg_match('/^(\w+)(?:\s+(asc|desc))?$/i', $line, $matches)) {
                $fieldKey = trim($matches[1]); // Der vom User eingegebene Feldname
                $direction = isset($matches[2]) ? strtolower(trim($matches[2])) : 'asc';

                $finalFieldNameForAccess = $fieldKey;
                $valueType = 'attempt_dynamic';
                $accessRaw = true; // Standard: Zugriff auf Rohdaten annehmen

                if (isset($this->filterableFields[$fieldKey])) {
                    $config = $this->filterableFields[$fieldKey];
                    $finalFieldNameForAccess = $config['field'] ?? $fieldKey;
                    $valueType = $config['value_type'] ?? 'attempt_dynamic';

                    // Bestimme accessRaw basierend auf expliziter Konfig oder Abgleich von fieldKey und targetField
                    if (isset($config['access_raw'])) {
                        $accessRaw = (bool)$config['access_raw'];
                    } else {
                        // Wenn 'field' explizit gesetzt und anders als fieldKey, ist es wahrscheinlich ein Alias zu einem gemappten Feld
                        // Wenn 'field' nicht gesetzt oder gleich fieldKey, gehen wir von Rohdatenzugriff aus für diesen Schlüssel
                        $accessRaw = ($finalFieldNameForAccess === $fieldKey);
                    }
                } else {
                    // fieldKey nicht in filterableFields: dynamischer Zugriff auf Rohdatenfeld fieldKey
                    // $finalFieldNameForAccess bleibt $fieldKey
                    // $valueType bleibt 'attempt_dynamic'
                    // $accessRaw bleibt true
                }

                $parsedRules[] = [
                    'field' => $finalFieldNameForAccess,
                    'key' => $fieldKey,
                    'direction' => $direction,
                    'value_type' => $valueType,
                    'access_raw' => $accessRaw,
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

    /**
     * Ruft alle eindeutigen Status-Werte aus den Auktionen ab.
     *
     * @return array Eine sortierte Liste eindeutiger Status-Werte.
     */
    public function getUniqueStatusValues(): array
    {
        $this->logger->debug('[getUniqueStatusValues] Rufe eindeutige Status-Werte ab.');
        $auctions = $this->getPublicAuctions(); // Ruft gemappte Auktionen ab (aus Cache oder API)

        if (empty($auctions)) {
            $this->logger->warning('[getUniqueStatusValues] Keine Auktionen von getPublicAuctions erhalten.');
            return [];
        }

        $statusValues = [];
        foreach ($auctions as $auction) {
            // Greife auf das gemappte Feld 'status' zu
            if (isset($auction['status']) && $auction['status'] !== null && $auction['status'] !== '') {
                $statusValue = $auction['status'];
                if (!in_array($statusValue, $statusValues, true)) {
                    $statusValues[] = $statusValue;
                }
            }
        }

        sort($statusValues); // Sortiere die Werte alphabetisch
        $this->logger->info('[getUniqueStatusValues] Eindeutige Status-Werte gefunden: ' . implode(', ', $statusValues));
        return $statusValues;
    }

    private function coerceTypeForComparison($value1, $value2ForHint, string $valueTypeHint)
    {
        if ($value1 === null) return null;

        if ($valueTypeHint === 'int') return (int)$value1;
        if ($valueTypeHint === 'float') return (float)$value1;
        if ($valueTypeHint === 'bool') {
            return filter_var($value1, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        // attempt_dynamic oder string
        if ($valueTypeHint === 'attempt_dynamic') {
            if (is_numeric($value1) && is_numeric($value2ForHint)) return (float)$value1;

            $boolVal1 = filter_var($value1, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            // Prüfe, ob value2ForHint auch boolesch interpretiert werden KÖNNTE, um einen Typ-Mismatch zu vermeiden
            $boolVal2Hint = filter_var($value2ForHint, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($boolVal1 !== null && $boolVal2Hint !== null) return $boolVal1;
        }

        return (string)$value1; // Fallback auf String
    }

    /**
     * Wandelt ein einfaches Key-Value-Array von Request-Parametern
     * in das strukturierte Filterregel-Format um, das getAuctions erwartet.
     */
    public function structureRequestFilters(array $requestParams): array
    {
        $structuredRequestFilters = [];
        $minMaxPairs = []; // Sammelbehälter für Min/Max-Paare

        // Vorverarbeitung: Min/Max-Paare identifizieren und aus $requestParams entfernen
        foreach ($requestParams as $fieldKey => $rawValue) {
            if (str_ends_with($fieldKey, '_min')) {
                $baseKey = substr($fieldKey, 0, -4);
                $minMaxPairs[$baseKey]['min'] = $rawValue;
                unset($requestParams[$fieldKey]);
            } elseif (str_ends_with($fieldKey, '_max')) {
                $baseKey = substr($fieldKey, 0, -4);
                $minMaxPairs[$baseKey]['max'] = $rawValue;
                unset($requestParams[$fieldKey]);
            }
        }

        // Verarbeite Min/Max-Paare zuerst
        foreach ($minMaxPairs as $baseKey => $values) {
            $targetFieldName = $baseKey;
            $valueType = 'attempt_dynamic';
            $accessRaw = true;
            // Für Min/Max ist der filterInteractionType immer 'minmax'

            if (isset($this->filterableFields[$baseKey])) {
                $config = $this->filterableFields[$baseKey];
                $targetFieldName = $config['field'] ?? $baseKey;
                $valueType = $config['value_type'] ?? 'attempt_dynamic';
                // $filterInteractionType aus config['type'] hier nicht relevant, da es minmax ist
                if (isset($config['access_raw'])) {
                    $accessRaw = (bool)$config['access_raw'];
                } else {
                    $accessRaw = ($targetFieldName === $baseKey);
                }
            }

            $minVal = $values['min'] ?? null;
            $maxVal = $values['max'] ?? null;

            $filterValue = [];
            if ($minVal !== null && $minVal !== '') {
                $filterValue['min'] = $this->convertToType((string)$minVal, $valueType);
            }
            if ($maxVal !== null && $maxVal !== '') {
                $filterValue['max'] = $this->convertToType((string)$maxVal, $valueType);
            }

            if (!empty($filterValue)) {
                $structuredRequestFilters[$targetFieldName] = [
                    'value'           => $filterValue,
                    'operator_type'   => 'minmax',
                    'value_type_hint' => $valueType,
                    'access_raw'      => $accessRaw,
                    'original_key'    => $baseKey
                ];
            }
        }

        // Verarbeite die restlichen (nicht Min/Max) Parameter
        foreach ($requestParams as $fieldKey => $rawValue) {
            if ($rawValue === null || $rawValue === '' || in_array($fieldKey, ['page', 'refresh', 'token'])) {
                continue;
            }

            $targetFieldName = $fieldKey;
            $valueType = 'attempt_dynamic';
            $accessRaw = true;
            $filterInteractionType = 'exact';
            $operatorTypeToUse = 'equal';

            if (isset($this->filterableFields[$fieldKey])) {
                $config = $this->filterableFields[$fieldKey];
                $targetFieldName = $config['field'] ?? $fieldKey;
                $valueType = $config['value_type'] ?? 'attempt_dynamic';
                $filterInteractionType = $config['type'] ?? $filterInteractionType;
                if (isset($config['access_raw'])) {
                    $accessRaw = (bool)$config['access_raw'];
                } else {
                    $accessRaw = ($targetFieldName === $fieldKey);
                }
            }

            // Wenn der rawValue Kommas enthält und der Typ nicht explizit was anderes sagt,
            // gehen wir von einem 'IN'-Operator aus.
            if (is_string($rawValue) && str_contains($rawValue, ',') && $filterInteractionType !== 'exact_string_with_comma') { // Beispiel für eine Ausnahme
                $operatorTypeToUse = 'in';
            }
            // Wenn der filterInteractionType aus config 'exact', 'lowercase_exact' etc. ist, bleibt es 'equal'
            // (oder ggf. später 'contains', falls wir das auch aus Request-Parametern ableiten wollen)

            $structuredRequestFilters[$targetFieldName] = [
                'value'           => ($operatorTypeToUse === 'in') ? (string)$rawValue : $this->convertToType((string)$rawValue, $valueType),
                'operator_type'   => $operatorTypeToUse,
                'value_type_hint' => $valueType,
                'access_raw'      => $accessRaw,
                'original_key'    => $fieldKey
            ];
        }
        $this->logger->debug('[structureRequestFilters] Strukturierte Request-Filter: ', $structuredRequestFilters);
        return $structuredRequestFilters;
    }
}
