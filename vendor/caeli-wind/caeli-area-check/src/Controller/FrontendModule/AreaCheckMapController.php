<?php

declare(strict_types=1);

namespace CaeliWind\CaeliAreaCheckBundle\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\Template;
use Contao\Database;
use Contao\Environment;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Psr\Log\LoggerInterface;
use CaeliWind\CaeliAreaCheckBundle\Service\MapImageGeneratorService;

#[AsFrontendModule(category: 'caeli_wind', template: 'mod_area_check_map', name: 'area_check_map')]
class AreaCheckMapController extends AbstractFrontendModuleController
{
    public const TYPE = 'area_check_map';
    
    private string $api_url;
    private string $api_user;
    private string $api_pass;
    private ?string $cached_token = null;
    private ?int $token_expires = null;

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly LoggerInterface $logger,
        private readonly TranslatorInterface $translator,
        private readonly MapImageGeneratorService $mapImageGenerator
    ) {
        // Flexiblere Environment Variable Erkennung - getenv() und $_ENV kombinieren
        $this->api_url = getenv('CAELI_INFRA_API_URL') ?: ($_ENV['CAELI_INFRA_API_URL'] ?? '');
        $this->api_user = getenv('CAELI_INFRA_API_USERNAME') ?: ($_ENV['CAELI_INFRA_API_USERNAME'] ?? '');
        $this->api_pass = getenv('CAELI_INFRA_API_PASSWORD') ?: ($_ENV['CAELI_INFRA_API_PASSWORD'] ?? '');
        
        // Validierung mit besserer Fehlermeldung
        if (empty($this->api_url)) {
            throw new \RuntimeException('CAELI_INFRA_API_URL environment variable is required. Prüfe deine .env/.env.local Datei.');
        }
        if (empty($this->api_user)) {
            throw new \RuntimeException('CAELI_INFRA_API_USERNAME environment variable is required. Prüfe deine .env/.env.local Datei.');
        }
        if (empty($this->api_pass)) {
            throw new \RuntimeException('CAELI_INFRA_API_PASSWORD environment variable is required. Prüfe deine .env/.env.local Datei.');
        }
    }

    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        $this->logger->debug('[AreaCheckMapController] Map-Controller aufgerufen');

        // POST-Verarbeitung - Park erstellen und weiterleiten
        if ($request->isMethod('POST')) {
            try {
                // Token-Validierung mit Fallback bei Fehlern
                $requestToken = $request->request->get('REQUEST_TOKEN');
                $framework = $this->framework;
                $framework->initialize();
                $tokenManager = $this->container->get('contao.csrf.token_manager');
                
                if (!$requestToken) {
                    $this->logger->warning('[AreaCheckMapController] REQUEST_TOKEN fehlt, aber fahre fort (Fallback)');
                    // NICHT abbrechen - für bessere UX bei Token-Problemen
                } else {
                    try {
                        // Korrekte Token-Validierung mit CsrfToken Objekt
                        $csrfToken = new \Symfony\Component\Security\Csrf\CsrfToken('contao.csrf_token', $requestToken);
                        if (!$tokenManager->isTokenValid($csrfToken)) {
                            $this->logger->warning('[AreaCheckMapController] Ungültiger REQUEST_TOKEN, aber fahre fort (Fallback)');
                        }
                    } catch (\Throwable $e) {
                        $this->logger->warning('[AreaCheckMapController] Token-Validierung fehlgeschlagen: ' . $e->getMessage() . ', fahre fort (Fallback)');
                    }
                }
                
                // Park erstellen versuchen
                                        // Exakt wie Original: $_POST direkt an createPark übergeben  
            $parkid = $this->createPark($request->request->all());
            
            // Exakt wie Original: Rating für jeden Park abrufen
            $parkData = null;
            if ($parkid != "-") {
                $parkData = $this->getPlotRating($parkid);
                $status = 'success';
                $isSuccess = true;
                $errorMessage = null;
            } else {
                // Wie NEdev-Modul: Bei fehlgeschlagenem Park trotzdem Rating versuchen
                $this->logger->info('[AreaCheckMapController] Park fehlgeschlagen - versuche Rating');
                try {
                    $geometry_raw = $request->request->get('geometry', '{}');
                    $ratingData = $this->getRatingAreaForFailedPark($geometry_raw);
                    if ($ratingData) {
                        $parkData = $ratingData;
                        $status = 'failed_with_rating';
                        $this->logger->info('[AreaCheckMapController] Rating für fehlgeschlagenen Park erhalten');
                    } else {
                        $status = 'failed';
                    }
                } catch (\Throwable $e) {
                    $this->logger->error('[AreaCheckMapController] Rating für fehlgeschlagenen Park fehlgeschlagen: ' . $e->getMessage());
                    $status = 'failed';
                }
                $isSuccess = false;
                $errorMessage = 'Park konnte nicht erstellt werden';
            }
            
            $this->logger->debug('[AreaCheckMapController] Park-Erstellung: success=' . ($isSuccess ? 'true' : 'false') . ', parkid=' . $parkid . ', error=' . $errorMessage);
                
                // Input-Daten validieren und sanitizen
                $name = trim($request->request->get('name', ''));
                $vorname = trim($request->request->get('vorname', ''));
                $phone = trim($request->request->get('phone', ''));
                $email = trim($request->request->get('email', ''));
                $searchedAddress = trim($request->request->get('searched_address', ''));
                $geometry = trim($request->request->get('geometry', ''));

                // E-Mail Validierung
                if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $this->logger->warning('[AreaCheckMapController] Ungültige E-Mail-Adresse: ' . $email);
                    $email = '';
                }

                // UUID für fehlgeschlagene Parks generieren
                $uuid = $this->generateUniqueUuid();

                // Daten in die Datenbank einfügen
                $set = [
                    'tstamp' => time(),
                    'name' => $name ?: 'Unbekannt',
                    'vorname' => $vorname ?: 'Unbekannt',
                    'phone' => $phone ?: '',
                    'email' => $email ?: '',
                    'searched_address' => $searchedAddress ?: '',
                    'geometry' => $geometry ?: '',
                    'park_id' => $parkid ?: '',
                    'park_rating' => $parkData ? json_encode($parkData) : null,
                    'status' => $status,
                    'error_message' => $errorMessage ?: '',
                    'uuid' => $uuid,
                ];

                // Insert ausführen
                $dbResult = Database::getInstance()
                    ->prepare("INSERT INTO tl_flaechencheck %s")
                    ->set($set)
                    ->execute();
                
                $insertId = $dbResult->insertId;
                $this->logger->info('[AreaCheckMapController] === DB-INSERT DEBUG ===');
                $this->logger->info('[AreaCheckMapController] DB-Insert erfolgreich, ID: ' . $insertId . ', isSuccess: ' . ($isSuccess ? 'true' : 'false'));
                $this->logger->info('[AreaCheckMapController] Eingefügte Daten: ' . json_encode($set));
                
                // Kartenbild für erfolgreiche Flächenchecks generieren
                if ($isSuccess && $insertId && $parkid && $parkid !== '-') {
                    try {
                        $this->mapImageGenerator->generateAndSaveMapImage($parkid, $geometry, $isSuccess);
                    } catch (\Throwable $e) {
                        // Bildgenerierung soll den Hauptflow nicht blockieren
                        $this->logger->warning('[AreaCheckMapController] Bildgenerierung fehlgeschlagen (Park: ' . $parkid . '): ' . $e->getMessage());
                    }
                }
                
                // Immer zur Detailseite weiterleiten - auch bei fehlgeschlagenen Parks
                if ($model->jumpTo) {
                    $framework = $this->framework;
                    $framework->initialize();
                    $detailPage = $framework->getAdapter(PageModel::class)->findById($model->jumpTo);
                    
                    if ($detailPage) {
                        // Bei erfolgreichen Parks: parkid verwenden (dauerhaft aufrufbar)
                        // Bei fehlgeschlagenen Parks: UUID verwenden (kryptisch, sicher)
                        if ($isSuccess) {
                            $detailUrl = $detailPage->getFrontendUrl() . '?parkid=' . urlencode($parkid);
                        } else {
                            $detailUrl = $detailPage->getFrontendUrl() . '?checkid=' . urlencode($uuid);
                        }
                        
                        $this->logger->info('[AreaCheckMapController] === REDIRECT DEBUG ===');
                        $this->logger->info('[AreaCheckMapController] URL-Parameter: ' . ($isSuccess ? 'parkid=' . $parkid : 'checkid=' . $uuid));
                        $this->logger->info('[AreaCheckMapController] detailUrl: ' . $detailUrl);
                        $this->logger->info('[AreaCheckMapController] Detailseite Frontend URL: ' . $detailPage->getFrontendUrl());
                        return new Response('', 302, ['Location' => $detailUrl]);
                    } else {
                        $this->logger->error('[AreaCheckMapController] Detail-Seite nicht gefunden für jumpTo: ' . $model->jumpTo);
                    }
                } else {
                    $this->logger->error('[AreaCheckMapController] Kein jumpTo konfiguriert.');
                }
                
                // Fallback: Fehler anzeigen
                $template->error = 'Keine Detailseite konfiguriert.';
                
            } catch (\Throwable $e) {
                $this->logger->error('[AreaCheckMapController] Schwerwiegender Fehler: ' . $e->getMessage());
                $template->error = 'Schwerwiegender Fehler bei der Verarbeitung: ' . $e->getMessage();
            }
        }

        // Google Maps Variablen setzen
        $googleMapsApiKey = getenv('GOOGLE_MAPS_API_KEY') ?: ($_ENV['GOOGLE_MAPS_API_KEY'] ?? '');
        $googleMapsMapId = getenv('GOOGLE_MAPS_MAP_ID') ?: ($_ENV['GOOGLE_MAPS_MAP_ID'] ?? '');
        
        // Google Maps API Key validieren
        if (empty($googleMapsApiKey)) {
            $this->logger->error('[AreaCheckMapController] Google Maps API Key fehlt');
            $template->error = 'Google Maps API Key ist nicht konfiguriert.';
        }
        
        $template->googleMapsApiKey = $googleMapsApiKey;
        $template->googleMapsMapId = $googleMapsMapId;
        
        // Contao Sprachdatei laden
        System::loadLanguageFile('default');
        
        // Übersetzungen aus Contao-Sprachdateien verwenden
        $translations = $GLOBALS['TL_LANG']['caeli_area_check'] ?? [];
        
        $template->translations = $translations;
        
        // CSRF Token setzen
        $framework = $this->framework;
        $framework->initialize();
        $tokenManager = $this->container->get('contao.csrf.token_manager');
        $template->request_token = $tokenManager->getDefaultTokenValue();
        
        // Detailseite für Form-Action
        $template->detailPage = null;
        if ($model->jumpTo) {
            $template->detailPage = $framework->getAdapter(PageModel::class)->findById($model->jumpTo);
        }
        
        // Länder-Beschränkung aus Modulkonfiguration verarbeiten
        $allowedCountries = [];
        if ($model->allowedCountries) {
            // Das listWizard Feld speichert serialisierte Arrays
            $allowedCountriesRaw = \Contao\StringUtil::deserialize($model->allowedCountries);
            if (is_array($allowedCountriesRaw)) {
                $allowedCountries = array_filter(array_map('trim', array_map('strtolower', $allowedCountriesRaw)));
            }
        }
        
        // Fallback auf Deutschland wenn keine Länder konfiguriert
        if (empty($allowedCountries)) {
            $allowedCountries = ['de'];
        }
        
        $template->allowedCountries = $allowedCountries;
        $this->logger->info('[AreaCheckMapController] Allowed Countries: ' . json_encode($allowedCountries));
        
        // AJAX-Konfiguration für JavaScript bereitstellen
        // URLs basierend auf aktueller Seite generieren
        $currentPath = rtrim($request->getPathInfo(), '/');
        $baseUrl = $request->getScheme() . '://' . $request->getHost() . $currentPath;
        
        $template->ajaxConfig = [
            'startUrl' => $baseUrl . '/ajax/start',
            'statusUrl' => $baseUrl . '/ajax/status',
            'detailPageUrl' => $template->detailPage ? $template->detailPage->getFrontendUrl() : null
        ];
        
        $this->logger->info('[AreaCheckMapController] AJAX Config generiert: ' . json_encode($template->ajaxConfig));
        $this->logger->info('[AreaCheckMapController] Current Path: ' . $currentPath);
        
        // Stelle sicher, dass error immer gesetzt ist
        if (!isset($template->error)) {
            $template->error = null;
        }

        return $template->getResponse();
    }

    private function getApiSessionId(): ?string
    {
        // Cached Token wiederverwenden falls noch gültig (5 Minuten Cache)
        if ($this->cached_token && $this->token_expires && time() < $this->token_expires) {
            $this->logger->debug('[AreaCheckMapController] Verwende cached API Token (noch gültig für ' . ($this->token_expires - time()) . ' Sekunden)');
            return $this->cached_token;
        }
        
        // DEBUG: Login-Call debuggen
        $rootDir = System::getContainer()->getParameter('kernel.project_dir');
        
        $fields = json_encode([
            "email" =>  $this->api_user,
            "password" => $this->api_pass,
        ]);

        $cookieFile = $this->getCookieFilePath();
        
        // Sicherstellen dass das tmp-Verzeichnis existiert und beschreibbar ist
        $tmpDir = dirname($cookieFile);
        if (!is_dir($tmpDir)) {
            if (!mkdir($tmpDir, 0755, true)) {
                $this->logger->error('[AreaCheckMapController] Tmp-Verzeichnis konnte nicht erstellt werden: ' . $tmpDir);
                return null;
            }
        }
        
        if (!is_writable($tmpDir)) {
            $this->logger->error('[AreaCheckMapController] Tmp-Verzeichnis ist nicht beschreibbar: ' . $tmpDir);
            return null;
        }
        
        // Cookie-File für cURL vorbereiten (auch wenn API keine HTTP-Cookies sendet)
        if (file_exists($cookieFile)) {
            unlink($cookieFile);
        }
        
        // Leeres Cookie-File für cURL erstellen (für COOKIEJAR/COOKIEFILE Optionen)
        if (file_put_contents($cookieFile, '# Netscape HTTP Cookie File' . PHP_EOL) === false) {
            $this->logger->warning('[AreaCheckMapController] Cookie-File konnte nicht erstellt werden: ' . $cookieFile);
            // Weitermachen ohne Cookie-File - API funktioniert trotzdem
        }
        
        $this->logger->info('[AreaCheckMapController] LOGIN CALL - Cookie File: ' . $cookieFile);
        $this->logger->info('[AreaCheckMapController] LOGIN CALL - API URL: ' . $this->api_url."auth/login");

        $curl_session = curl_init();
        if (!$curl_session) {
            $this->logger->error('[AreaCheckMapController] cURL-Initialisierung fehlgeschlagen');
            return null;
        }

        curl_setopt($curl_session ,CURLOPT_URL, $this->api_url."auth/login");
        curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl_session, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl_session, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($curl_session, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($curl_session, CURLOPT_COOKIEFILE, $cookieFile); // Auch COOKIEFILE setzen
        curl_setopt($curl_session, CURLOPT_TIMEOUT, 20); // Timeout reduziert
        curl_setopt($curl_session, CURLOPT_CONNECTTIMEOUT, 5); // Verbindungs-Timeout reduziert
        curl_setopt($curl_session, CURLOPT_FOLLOWLOCATION, true); // Redirects folgen
        curl_setopt($curl_session, CURLOPT_SSL_VERIFYPEER, false); // SSL-Verifikation für Tests deaktivieren
        curl_setopt($curl_session, CURLOPT_USERAGENT, 'Caeli-Area-Check/1.0'); // User-Agent setzen
        curl_setopt($curl_session, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        
        $this->logger->info('[AreaCheckMapController] Sende Login-Request an: ' . $this->api_url."auth/login");
        
        $result = curl_exec($curl_session);
        $httpCode = curl_getinfo($curl_session, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl_session);
        
        if ($curlError) {
            $this->logger->error('[AreaCheckMapController] cURL ERROR: ' . $curlError);
            curl_close($curl_session);
            return null;
        }
        
        if ($result === false) {
            $this->logger->error('[AreaCheckMapController] cURL exec failed');
            curl_close($curl_session);
            return null;
        }
        
        if ($httpCode !== 200) {
            $this->logger->error('[AreaCheckMapController] LOGIN HTTP Error: ' . $httpCode . ', Response: ' . $result);
            curl_close($curl_session);
            return null;
        }
        
        curl_close($curl_session);
        
        $this->logger->info('[AreaCheckMapController] LOGIN RESPONSE: ' . $result);
        
        // Cookie-File Status prüfen (informativ - API funktioniert auch ohne HTTP-Cookies)
        if (file_exists($cookieFile)) {
            $fileSize = filesize($cookieFile);
            $this->logger->debug('[AreaCheckMapController] Cookie-File vorhanden, Größe: ' . $fileSize . ' bytes');
            
            if ($fileSize > 30) { // Mehr als nur Header = echte Cookies
                $this->logger->debug('[AreaCheckMapController] HTTP-Cookies erhalten - Session wird wiederverwendet');
            }
        } else {
            $this->logger->debug('[AreaCheckMapController] Cookie-File nicht vorhanden - API sendet keine HTTP-Cookies');
        }
        
        // JSON-Parsing mit besserer Fehlerbehandlung
        $json = json_decode($result);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error('[AreaCheckMapController] JSON Parse Error: ' . json_last_error_msg());
            return null;
        }
        
        $token = $json->tokens->csrf_session_id ?? null;
        
        if (!$token) {
            $this->logger->error('[AreaCheckMapController] Kein CSRF Token in Response gefunden');
            return null;
        }
        
        $this->logger->info('[AreaCheckMapController] CSRF Token: ' . $token);
        
        // Token für 5 Minuten cachen (Caeli-API Sessions sind länger gültig)
        $this->cached_token = $token;
        $this->token_expires = time() + 300; // 5 Minuten
        $this->logger->debug('[AreaCheckMapController] API Token gecacht bis: ' . date('H:i:s', $this->token_expires));
        
        return $token;
    }

    private function createPark($data) {
        // Exakt wie Original-Modul
        $rootDir = System::getContainer()->getParameter('kernel.project_dir');
        $api_session_id = $this->getApiSessionId();

        // Exakt wie Original: json_decode auf geometry-String
        $postData = [
            'geometry' => json_decode($data['geometry']),
        ];

        $curl_session = curl_init();
        curl_setopt($curl_session ,CURLOPT_URL, $this->api_url."wind/caeli/park");
        curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl_session, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl_session, CURLOPT_COOKIEJAR, $this->getCookieFilePath());
        curl_setopt($curl_session, CURLOPT_COOKIEFILE, $this->getCookieFilePath());
        curl_setopt($curl_session, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($curl_session, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl_session, CURLOPT_HTTPHEADER, [
            'X-CSRF-TOKEN: '.$api_session_id,
            'Content-Type: application/json'
        ]);
        $result = curl_exec($curl_session);

        curl_close($curl_session);
        
        // Exakt wie Original: Check und Return
        if(json_decode($result)->status == 'success') {
            return str_replace(["[","]", "'"], ["","",""], json_decode($result)->parks->id);
        }else{
            return "-";
        }
    }

    private function createParkWithResult(array $data): array
    {
        // Get project root directory für Cookie-Speicherung
        $rootDir = System::getContainer()->getParameter('kernel.project_dir');
        
        $api_session_id = $this->getApiSessionId();
        if (!$api_session_id) {
            return [
                'success' => false,
                'parkid' => '-',
                'error' => 'API-Session konnte nicht erstellt werden.'
            ];
        }
        
        // Debug: Geometry-Struktur loggen
        $geometryData = json_decode($data['geometry'] ?? '{}', true);
        $this->logger->info('[AreaCheckMapController] Originale Geometry-Struktur: ' . json_encode($geometryData));
        
        // Die API erwartet direkt die Geometry, nicht nochmal eingepackt
        // Frontend sendet: {"geometry": {"type": "Polygon", "coordinates": [...]}}
        // API erwartet: {"geometry": {"type": "Polygon", "coordinates": [...]}}
        // Problem: Wir machen {"geometry": {"geometry": {"type": "Polygon", ...}}}
        $correctGeometry = isset($geometryData['geometry']) ? $geometryData['geometry'] : $geometryData;
        
        $postData = [
            'geometry' => $correctGeometry,
        ];
        
        $this->logger->info('[AreaCheckMapController] Korrigierte Geometry für API: ' . json_encode($postData));

        $curl_session = curl_init();
        curl_setopt($curl_session, CURLOPT_URL, $this->api_url."wind/caeli/park");
        curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl_session, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl_session, CURLOPT_COOKIEJAR, $this->getCookieFilePath());
        curl_setopt($curl_session, CURLOPT_COOKIEFILE, $this->getCookieFilePath());
        curl_setopt($curl_session, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($curl_session, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl_session, CURLOPT_HTTPHEADER, [
            'X-CSRF-TOKEN: '.$api_session_id,
            'Content-Type: application/json'
        ]);
        $result = curl_exec($curl_session);
        curl_close($curl_session);
        
        // Debug: Rohe API-Response loggen
        $this->logger->info('[AreaCheckMapController] === PARK API RESPONSE ===');
        $this->logger->info('[AreaCheckMapController] Raw Result: ' . $result);
        
        $json = json_decode($result);
        $this->logger->info('[AreaCheckMapController] Decoded JSON: ' . json_encode($json));
        
        if(isset($json->status) && $json->status == 'success') {
            $parkid = str_replace(["[", "]", "'"], ["", "", ""], $json->parks->id);
            $this->logger->debug('[AreaCheckMapController] Park erfolgreich erstellt: ' . $parkid);
            return [
                'success' => true,
                'parkid' => $parkid,
                'error' => ''
            ];
        } else {
            // Auch fehlgeschlagene API-Responses sind valide Ergebnisse
            $errorMessage = $json->message ?? $json->error ?? 'Unbekannter Fehler';
            $this->logger->info('[AreaCheckMapController] Park nicht geeignet: ' . $errorMessage);
            $this->logger->info('[AreaCheckMapController] Vollständige API-Response: ' . json_encode($json));
            return [
                'success' => false,
                'parkid' => '-',
                'error' => $errorMessage
            ];
        }
    }

    private function getParkEvaluation(string $parkid): ?array
    {
        // Get project root directory für Cookie-Speicherung
        $rootDir = System::getContainer()->getParameter('kernel.project_dir');
        
        $api_session_id = $this->getApiSessionId();
        if (!$api_session_id) {
            $this->logger->error('[AreaCheckMapController] Keine API-Session für Park-Bewertung');
            return null;
        }
        
        // Rating-Endpoint verwenden wie im Result-Controller
        $curl_session = curl_init();
        curl_setopt($curl_session, CURLOPT_URL, $this->api_url."wind/caeli/rating?".http_build_query([
            'area_id'=>$parkid
        ]));
        curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl_session, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($curl_session, CURLOPT_COOKIEJAR, $this->getCookieFilePath());
        curl_setopt($curl_session, CURLOPT_COOKIEFILE, $this->getCookieFilePath());
        curl_setopt($curl_session, CURLOPT_HTTPHEADER, [
            'X-CSRF-TOKEN: '.$api_session_id,
            'Content-Type: application/json'
        ]);
        $result = curl_exec($curl_session);
        curl_close($curl_session);
        
        $json = json_decode($result, true);
        
        // Rating-Endpoint gibt direkt die Bewertungsdaten zurück (ohne status-Wrapper)
        if ($json && isset($json['rating_cutdensity'])) {
            $this->logger->debug('[AreaCheckMapController] Park-Bewertung erfolgreich abgerufen für Park: ' . $parkid);
            return $json;
        } else {
            $this->logger->error('[AreaCheckMapController] Park-Bewertung konnte nicht abgerufen werden: ' . $result);
            return null;
        }
    }

    private function getRatingArea($longitude, $latitude, $size_ha = 3): ?array
    {
        // Get project root directory für Cookie-Speicherung
        $rootDir = System::getContainer()->getParameter('kernel.project_dir');
        
        $api_session_id = $this->getApiSessionId();
        if (!$api_session_id) {
            $this->logger->error('[AreaCheckMapController] Keine API-Session für Rating Area');
            return null;
        }

        // Geometrie aus Koordinaten erstellen (ähnlich wie im alten Modul)
        $coordinates = $this->getCoordinatesForMarker($longitude, $latitude, $size_ha);
        
        $postData = [
            'geometry' => $coordinates,
        ];

        $curl_session = curl_init();
        curl_setopt($curl_session, CURLOPT_URL, $this->api_url."wind/caeli/ratingArea");
        curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl_session, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl_session, CURLOPT_COOKIEJAR, $this->getCookieFilePath());
        curl_setopt($curl_session, CURLOPT_COOKIEFILE, $this->getCookieFilePath());
        curl_setopt($curl_session, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($curl_session, CURLOPT_HTTPHEADER, [
            'X-CSRF-TOKEN: '.$api_session_id,
            'Content-Type: application/json'
        ]);
        $result = curl_exec($curl_session);
        curl_close($curl_session);

        return json_decode($result, true);
    }

    private function getCoordinatesForMarker($longitude, $latitude, $size_ha = 3)
    {
        $api_session_id = $this->getApiSessionId();
        if (!$api_session_id) {
            return null;
        }

        $curl_session = curl_init();
        curl_setopt($curl_session, CURLOPT_URL, $this->api_url . "wind/caeli/buffer?" . http_build_query([
            'size_ha' => $size_ha,
            'longitude' => $longitude,
            'latitude' => $latitude
        ]));
        curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl_session, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($curl_session, CURLOPT_COOKIEJAR, $this->getCookieFilePath());
        curl_setopt($curl_session, CURLOPT_COOKIEFILE, $this->getCookieFilePath());
        curl_setopt($curl_session, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl_session, CURLOPT_HTTPHEADER, [
            'X-CSRF-TOKEN: ' . $api_session_id,
            'Content-Type: application/json'
        ]);
        $result = curl_exec($curl_session);
        
        if (curl_error($curl_session)) {
            $this->logger->error('[AreaCheckMapController] Buffer API Error: ' . curl_error($curl_session));
            curl_close($curl_session);
            return null;
        }
        
        curl_close($curl_session);
        
        $decoded = json_decode($result);
        if ($decoded && isset($decoded->featureCollection->features[0])) {
            return $decoded->featureCollection->features[0];
        }
        
        return null;
    }

    private function getRatingAreaWithGeometry($originalGeometry): ?array
    {
        $this->logger->info('[AreaCheckMapController] === RATING FÜR FEHLGESCHLAGENEN PARK ===');
        $this->logger->info('[AreaCheckMapController] Geometry: ' . substr(json_encode($originalGeometry), 0, 200) . '...');
        
        // Prüfen ob es ein String oder Array ist
        if (is_string($originalGeometry)) {
            $decoded = json_decode($originalGeometry, true);
            $this->logger->info('[AreaCheckMapController] Geometry decoded from string: ' . ($decoded ? 'erfolgreich' : 'fehlgeschlagen'));
            
            if (!$decoded || !isset($decoded['geometry'])) {
                $this->logger->warning('[AreaCheckMapController] Ungültige Geometry-Struktur');
                return null;
            }
            $geometry = $decoded['geometry'];
        } else {
            // Array ist bereits die Geometry selbst
            $this->logger->info('[AreaCheckMapController] Geometry ist bereits Array (direkte Geometry)');
            $geometry = $originalGeometry;
        }
        $this->logger->info('[AreaCheckMapController] Extrahierte Geometry: ' . json_encode($geometry));
        
        // Berechne Koordinaten für Buffer API wie im NEdev-Modul
        $coords = $geometry['coordinates'][0][0];
        $lon = $coords[0];
        $lat = $coords[1];
        $size_ha = 3; // Standard
        $this->logger->info('[AreaCheckMapController] Koordinaten: lon=' . number_format($lon, 12) . ', lat=' . number_format($lat, 12) . ', size_ha=' . $size_ha);
        
        // Hole Feature von Buffer API wie im NEdev-Modul
        $featureFromBuffer = $this->getCoordinatesForMarker($lon, $lat, $size_ha);
        if (!$featureFromBuffer) {
            $this->logger->warning('[AreaCheckMapController] Konnte kein Feature von Buffer API erhalten');
            return null;
        }
        
        $this->logger->info('[AreaCheckMapController] Buffer API Feature: ' . json_encode($featureFromBuffer));
        
        // Verwende exakt das NEdev-Format: geometry = Feature-Objekt
        $postData = [
            'geometry' => $featureFromBuffer
        ];
        
        $this->logger->info('[AreaCheckMapController] NEdev-Format für ratingArea API: ' . json_encode($postData));
        
        $api_session_id = $this->getApiSessionId();
        if (!$api_session_id) {
            $this->logger->error('[AreaCheckMapController] Keine API Session verfügbar');
            return null;
        }

        $curl_session = curl_init();
        curl_setopt($curl_session, CURLOPT_URL, $this->api_url."wind/caeli/ratingArea");
        curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl_session, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl_session, CURLOPT_COOKIEJAR, $this->getCookieFilePath());
        curl_setopt($curl_session, CURLOPT_COOKIEFILE, $this->getCookieFilePath());
        curl_setopt($curl_session, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($curl_session, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl_session, CURLOPT_HTTPHEADER, [
            'X-CSRF-TOKEN: ' . $api_session_id,
            'Content-Type: application/json'
        ]);
        $result = curl_exec($curl_session);
        
        if (curl_error($curl_session)) {
            $this->logger->error('[AreaCheckMapController] cURL Error: ' . curl_error($curl_session));
            curl_close($curl_session);
            return null;
        }
        
        curl_close($curl_session);
        
        $this->logger->info('[AreaCheckMapController] Rating response: ' . $result);
        
        $ratingData = json_decode($result, true);
        // ratingArea API gibt direkt die Rating-Daten zurück (ohne status-Wrapper)
        if ($ratingData && isset($ratingData['rating_cutdensity'])) {
            $this->logger->info('[AreaCheckMapController] ✅ ERFOLGREICHES RATING ERHALTEN!');
            return $ratingData;
        }
        
        $this->logger->warning('[AreaCheckMapController] Kein gültiges Rating erhalten: ' . $result);
        return null;
    }



    private function getPlotRating($id)
    {
        // Exakt wie Original-Modul: Rating-API MIT Cookies
        $rootDir = System::getContainer()->getParameter('kernel.project_dir');
        $api_session_id = $this->getApiSessionId();
        if (!$api_session_id) {
            throw new \RuntimeException('API-Session konnte nicht erstellt werden.');
        }
        $curl_session = curl_init();
        curl_setopt($curl_session ,CURLOPT_URL, $this->api_url."wind/caeli/rating?".http_build_query([
            'area_id'=>$id
        ]));
        curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl_session, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($curl_session, CURLOPT_COOKIEJAR, $this->getCookieFilePath());
        curl_setopt($curl_session, CURLOPT_COOKIEFILE, $this->getCookieFilePath());
        curl_setopt($curl_session, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl_session, CURLOPT_HTTPHEADER, [
            'X-CSRF-TOKEN: '.$api_session_id,
            'Content-Type: application/json'
        ]);
        $result = curl_exec($curl_session);
        curl_close($curl_session);
        return json_decode($result);
    }

    private function getRatingAreaForFailedPark($geometry_raw) 
    {
        // Wie NEdev-Modul: ratingArea API für fehlgeschlagene Parks
        $this->logger->info('[AreaCheckMapController] === RATING FÜR FEHLGESCHLAGENEN PARK ===');
        
        $api_session_id = $this->getApiSessionId();
        if (!$api_session_id) {
            $this->logger->error('[AreaCheckMapController] Keine API Session für Rating verfügbar');
            return null;
        }

        // Geometry wie im NEdev-Modul verarbeiten
        $postData = [
            'geometry' => json_decode($geometry_raw)
        ];

        $rootDir = System::getContainer()->getParameter('kernel.project_dir');
        $curl_session = curl_init();
        curl_setopt($curl_session, CURLOPT_URL, $this->api_url."wind/caeli/ratingArea");
        curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl_session, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl_session, CURLOPT_COOKIEJAR, $this->getCookieFilePath());
        curl_setopt($curl_session, CURLOPT_COOKIEFILE, $this->getCookieFilePath());
        curl_setopt($curl_session, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($curl_session, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl_session, CURLOPT_HTTPHEADER, [
            'X-CSRF-TOKEN: '.$api_session_id,
            'Content-Type: application/json'
        ]);
        $result = curl_exec($curl_session);
        curl_close($curl_session);

        $this->logger->info('[AreaCheckMapController] Rating response: ' . $result);

        $ratingData = json_decode($result);
        if ($ratingData) {
            return $ratingData;
        }
        
        return null;
    }

    /**
     * Generiert konsistenten Cookie-File-Pfad für API-Sessions
     */
    private function getCookieFilePath(): string
    {
        $rootDir = System::getContainer()->getParameter('kernel.project_dir');
        $sessionHash = md5($this->api_user . date('Y-m-d-H'));
        return $rootDir."/system/tmp/caeli_api_session_".$sessionHash.'.txt';
    }

    /**
     * Generiert eine eindeutige UUID für fehlgeschlagene Park-Checks
     */
    private function generateUniqueUuid(): string
    {
        $maxAttempts = 10;
        $attempts = 0;
        
        do {
            // UUID4 generieren (zufällig)
            $uuid = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            
            // Prüfen ob UUID bereits existiert
            $existing = Database::getInstance()
                ->prepare("SELECT id FROM tl_flaechencheck WHERE uuid = ?")
                ->execute($uuid);
            
            if ($existing->numRows === 0) {
                return $uuid;
            }
            
            $attempts++;
        } while ($attempts < $maxAttempts);
        
        // Fallback: Timestamp-basiert wenn UUID-Kollision
        return 'fc-' . time() . '-' . bin2hex(random_bytes(8));
    }

    /**
     * AJAX-Endpoint: Startet die asynchrone Flächenprüfung
     */
    #[Route('/flaechencheck/ajax/start', name: 'caeli_area_check_ajax_start', methods: ['POST'])]
    public function startAsync(Request $request, SessionInterface $session): JsonResponse
    {
        try {
            $this->logger->info('[AreaCheckMapController] === AJAX START REQUEST ===');
            $this->logger->info('[AreaCheckMapController] Request Method: ' . $request->getMethod());
            $this->logger->info('[AreaCheckMapController] Request URI: ' . $request->getRequestUri());
            $this->logger->info('[AreaCheckMapController] Request Headers: ' . json_encode($request->headers->all()));
            
            // Eindeutige Session-ID generieren
            $sessionId = 'check_' . uniqid();
            
            // Request-Daten in Session speichern
            $requestData = $request->request->all();
            $this->logger->info('[AreaCheckMapController] Request Data Keys: ' . json_encode(array_keys($requestData)));
            $session->set($sessionId, $requestData);
            
            $this->logger->info('[AreaCheckMapController] AJAX Check gestartet, sessionId: ' . $sessionId);
            
            return new JsonResponse([
                'status' => 'queued',
                'sessionId' => $sessionId,
                'statusUrl' => '/flaechencheck/ajax/status/' . $sessionId,
                'debug' => 'AJAX endpoint reached successfully'
            ]);
            
        } catch (\Throwable $e) {
            $this->logger->error('[AreaCheckMapController] AJAX Start Fehler: ' . $e->getMessage());
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Fehler beim Starten der Prüfung: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * AJAX-Endpoint: Prüft den Status der Flächenprüfung
     */
    #[Route('/flaechencheck/ajax/status/{sessionId}', name: 'caeli_area_check_ajax_status', methods: ['GET'])]
    public function checkStatus(string $sessionId, SessionInterface $session): JsonResponse
    {
        try {
            $this->logger->debug('[AreaCheckMapController] AJAX Status Check für sessionId: ' . $sessionId);
            
            // Optimiert: Erst auf completed prüfen, dann error, dann processing
            // Das verhindert unnötige Session-Abfragen
            
            // 1. Prüfen ob bereits verarbeitet (häufigster Fall nach Completion)
            if ($session->has($sessionId . '_result')) {
                $result = $session->get($sessionId . '_result');
                $this->logger->info('[AreaCheckMapController] AJAX Ergebnis gefunden für sessionId: ' . $sessionId);
                
                // Session-Cleanup sofort nach Abruf der Ergebnisse
                $session->remove($sessionId . '_result');
                $session->remove($sessionId . '_progress');
                $session->remove($sessionId);
                $session->remove($sessionId . '_processing');
                
                // SERVER-SEITIGES HTTP-REDIRECT statt JSON (funktioniert IMMER)
                if (isset($result['redirectUrl']) && $result['redirectUrl']) {
                    $this->logger->info('[AreaCheckMapController] Server-seitiges Redirect zu: ' . $result['redirectUrl']);
                    return new Response('', 302, ['Location' => $result['redirectUrl']]);
                }
                
                // Fallback: JSON Response
                return new JsonResponse([
                    'status' => 'completed',
                    'result' => $result
                ]);
            }
            
            // 2. Prüfen ob Fehler aufgetreten
            if ($session->has($sessionId . '_error')) {
                $error = $session->get($sessionId . '_error');
                $this->logger->error('[AreaCheckMapController] AJAX Fehler gefunden für sessionId: ' . $sessionId . ' - ' . $error);
                
                // Session-Cleanup auch bei Fehlern
                $session->remove($sessionId . '_error');
                $session->remove($sessionId . '_progress');
                
                return new JsonResponse([
                    'status' => 'error',
                    'message' => $error
                ]);
            }
            
            // 3. Verarbeitung starten wenn noch nicht begonnen
            if ($session->has($sessionId) && !$session->has($sessionId . '_processing')) {
                $this->logger->info('[AreaCheckMapController] Starte Verarbeitung für sessionId: ' . $sessionId);
                $session->set($sessionId . '_processing', true);
                
                // Verarbeitung in separatem Prozess starten (um Request nicht zu blockieren)
                // Die processAreaCheckAsync Methode läuft dann parallel
                try {
                $this->processAreaCheckAsync($sessionId, $session);
                } catch (\Throwable $e) {
                    // Fehler in Session speichern für nächsten Poll
                    $session->set($sessionId . '_error', 'Verarbeitungsfehler: ' . $e->getMessage());
                    $session->remove($sessionId . '_processing');
                }
            }
            
            // 4. Progress-Daten abrufen wenn verfügbar
            $progressData = $session->get($sessionId . '_progress', ['percentage' => 0, 'message' => 'Startet...']);
            
            return new JsonResponse([
                'status' => 'processing',
                'progress' => $progressData
            ]);
            
        } catch (\Throwable $e) {
            $this->logger->error('[AreaCheckMapController] AJAX Status Fehler: ' . $e->getMessage());
            
            // Session-Cleanup auch bei unerwarteten Fehlern
            $session->remove($sessionId);
            $session->remove($sessionId . '_processing');
            $session->remove($sessionId . '_progress');
            $session->remove($sessionId . '_result');
            $session->remove($sessionId . '_error');
            
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Unerwarteter Fehler beim Statuscheck: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Verarbeitet die Flächenprüfung asynchron (verwendet bestehende Logik)
     */
    private function processAreaCheckAsync(string $sessionId, SessionInterface $session): void
    {
        try {
            $requestData = $session->get($sessionId);
            if (!$requestData) {
                throw new \RuntimeException('Keine Request-Daten gefunden');
            }
            
            $this->logger->info('[AreaCheckMapController] AJAX Verarbeitung gestartet für sessionId: ' . $sessionId);
            
            // Schritt 1: API-Session vorbereiten (10%)
            $this->updateProgress($sessionId, $session, 10, 'Verbindung zur API herstellen...');
            
            // Bestehende Logik aus getResponse() wiederverwenden
            // AJAX: Sicherstellen dass alle Dependencies verfügbar sind
            if (!$this->framework) {
                throw new \RuntimeException('ContaoFramework nicht verfügbar');
            }
            
            $this->framework->initialize();
            
            // Schritt 2: Verarbeitung starten (30%)
            $this->updateProgress($sessionId, $session, 30, 'Fläche wird analysiert...');
            
            $parkid = $this->createPark($requestData);
            
            // Schritt 3: Park verarbeitet (60%)
            $this->updateProgress($sessionId, $session, 60, 'Windpotential wird bewertet...');
            
            $parkData = null;
            $status = 'failed';
            $isSuccess = false;
            $errorMessage = null;
            
            if ($parkid != "-") {
                // Schritt 4: Rating abrufen (80%)
                $this->updateProgress($sessionId, $session, 80, 'Bewertung wird abgerufen...');
                
                $parkData = $this->getPlotRating($parkid);
                $status = 'success';
                $isSuccess = true;
            } else {
                // Schritt 4: Fallback-Rating versuchen (80%)
                $this->updateProgress($sessionId, $session, 80, 'Alternative Bewertung wird erstellt...');
                
                // Fallback wie im synchronen Code
                try {
                    $geometry_raw = $requestData['geometry'] ?? '{}';
                    $ratingData = $this->getRatingAreaForFailedPark($geometry_raw);
                    if ($ratingData) {
                        $parkData = $ratingData;
                        $status = 'failed_with_rating';
                        $this->logger->info('[AreaCheckMapController] AJAX Rating für fehlgeschlagenen Park erhalten');
                    }
                } catch (\Throwable $e) {
                    $this->logger->error('[AreaCheckMapController] AJAX Rating für fehlgeschlagenen Park fehlgeschlagen: ' . $e->getMessage());
                }
                $errorMessage = 'Park konnte nicht erstellt werden';
            }
            
            // Schritt 5: Daten vorbereiten (90%)
            $this->updateProgress($sessionId, $session, 90, 'Ergebnis wird gespeichert...');
            
            // Input-Daten wie im synchronen Code
            $name = trim($requestData['name'] ?? '');
            $vorname = trim($requestData['vorname'] ?? '');
            $phone = trim($requestData['phone'] ?? '');
            $email = trim($requestData['email'] ?? '');
            $searchedAddress = trim($requestData['searched_address'] ?? '');
            $geometry = trim($requestData['geometry'] ?? '');

            // E-Mail Validierung
            if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->logger->warning('[AreaCheckMapController] AJAX Ungültige E-Mail-Adresse: ' . $email);
                $email = '';
            }

            // UUID für fehlgeschlagene Parks
            $uuid = $this->generateUniqueUuid();

            // DB-Insert wie im synchronen Code
            $set = [
                'tstamp' => time(),
                'name' => $name ?: 'Unbekannt',
                'vorname' => $vorname ?: 'Unbekannt',
                'phone' => $phone ?: '',
                'email' => $email ?: '',
                'searched_address' => $searchedAddress ?: '',
                'geometry' => $geometry ?: '',
                'park_id' => $parkid ?: '',
                'park_rating' => $parkData ? json_encode($parkData) : null,
                'status' => $status,
                'error_message' => $errorMessage ?: '',
                'uuid' => $uuid,
            ];
            
            $dbResult = Database::getInstance()
                ->prepare("INSERT INTO tl_flaechencheck %s")
                ->set($set)
                ->execute();
            
            $insertId = $dbResult->insertId;
            $this->logger->info('[AreaCheckMapController] AJAX DB-Insert erfolgreich für sessionId: ' . $sessionId . ', ID: ' . $insertId);
            
            // Kartenbild für erfolgreiche Flächenchecks generieren
            if ($isSuccess && $insertId && $parkid && $parkid !== '-') {
                try {
                    $this->mapImageGenerator->generateAndSaveMapImage($parkid, $geometry, $isSuccess);
                } catch (\Throwable $e) {
                    // Bildgenerierung soll den Hauptflow nicht blockieren
                    $this->logger->warning('[AreaCheckMapController] AJAX Bildgenerierung fehlgeschlagen (Park: ' . $parkid . '): ' . $e->getMessage());
                }
            }
            
            // Schritt 6: Abgeschlossen (100%)
            $this->updateProgress($sessionId, $session, 100, 'Verarbeitung abgeschlossen!');
            
            // Redirect-URL server-seitig berechnen (wie im synchronen Code)
            $redirectUrl = null;
            
            // Hier sollten wir das ModuleModel haben, aber da wir in async context sind...
            // Wir verwenden die Session-Daten für die jumpTo-Information
            $moduleId = $requestData['module_id'] ?? null;
            if ($moduleId) {
                $moduleModel = \Contao\ModuleModel::findById($moduleId);
                if ($moduleModel && $moduleModel->jumpTo) {
                    $detailPage = \Contao\PageModel::findById($moduleModel->jumpTo);
                    if ($detailPage) {
                        if ($isSuccess) {
                            $redirectUrl = $detailPage->getFrontendUrl() . '?parkid=' . urlencode($parkid);
                        } else {
                            $redirectUrl = $detailPage->getFrontendUrl() . '?checkid=' . urlencode($uuid);
                        }
                        $this->logger->info('[AreaCheckMapController] AJAX Redirect-URL berechnet: ' . $redirectUrl);
                    }
                }
            }
            
            // Ergebnis in Session speichern
            $result = [
                'isSuccess' => $isSuccess,
                'checkId' => $isSuccess ? $parkid : $uuid,
                'parkData' => $parkData,
                'status' => $status,
                'errorMessage' => $errorMessage,
                'redirectUrl' => $redirectUrl  // Server-seitige Redirect-URL hinzufügen
            ];
            
            $session->set($sessionId . '_result', $result);
            
            // Cleanup
            $session->remove($sessionId);
            $session->remove($sessionId . '_processing');
            $session->remove($sessionId . '_progress'); // Progress-Daten auch entfernen
            
            $this->logger->info('[AreaCheckMapController] AJAX Verarbeitung erfolgreich abgeschlossen für sessionId: ' . $sessionId);
            
        } catch (\Throwable $e) {
            $this->logger->error('[AreaCheckMapController] AJAX Verarbeitung Fehler: ' . $e->getMessage());
            $session->set($sessionId . '_error', $e->getMessage());
            $session->remove($sessionId);
            $session->remove($sessionId . '_processing');
            $session->remove($sessionId . '_progress'); // Progress-Daten auch bei Fehlern entfernen
        }
    }
    
    /**
     * Aktualisiert den Progress in der Session (nur vorwärts!)
     */
    private function updateProgress(string $sessionId, SessionInterface $session, int $percentage, string $message): void
    {
        // Prüfen ob bereits höherer Progress existiert (Race Condition vermeiden)
        $currentProgress = $session->get($sessionId . '_progress', ['percentage' => 0]);
        $currentPercentage = $currentProgress['percentage'] ?? 0;
        
        // Nur vorwärts gehen, nie rückwärts
        if ($percentage > $currentPercentage) {
        $progressData = [
            'percentage' => $percentage,
            'message' => $message,
            'timestamp' => time()
        ];
        
        $session->set($sessionId . '_progress', $progressData);
        $this->logger->debug('[AreaCheckMapController] AJAX Progress updated: ' . $percentage . '% - ' . $message);
        } else {
            $this->logger->debug('[AreaCheckMapController] AJAX Progress ignored (rückwärts): ' . $percentage . '% (aktuell: ' . $currentPercentage . '%)');
        }
    }
} 