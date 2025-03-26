<?php

declare(strict_types=1);

/*
 * This file is part of Caeli Google News Fetcher.
 *
 * (c) Caeli Wind - Christian Voss 2025 <christian.voss@caeli-wind.de>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/caeli-wind/caeli-google-news-fetch
 */

namespace CaeliWind\CaeliGoogleNewsFetch\Controller;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FilesModel;
use Contao\File;
use Contao\Folder;
use Contao\NewsModel;
use Contao\StringUtil;
use Contao\System;
use CaeliWind\CaeliGoogleNewsFetch\Model\CaeliGooglenewsModel;
use CaeliWind\CaeliGoogleNewsFetch\Service\GoogleNewsFeedService;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Controller für Google News Fetch-Funktionalität im Contao-Backend
 */
#[Route('/caeli_googlenews', name: 'caeli_googlenews_', defaults: ['_scope' => 'backend'])]
class GoogleNewsFetchController extends AbstractController
{
    protected ContaoFramework $framework;
    protected Connection $connection;
    protected GoogleNewsFeedService $newsFeedService;
    protected TranslatorInterface $translator;
    protected RequestStack $requestStack;
    protected CsrfTokenManagerInterface $csrfTokenManager;
    protected HttpClientInterface $httpClient;
    protected ?LoggerInterface $logger;
    
    /**
     * Constructor
     */
    public function __construct(
        ContaoFramework $framework,
        Connection $connection,
        GoogleNewsFeedService $newsFeedService,
        TranslatorInterface $translator,
        RequestStack $requestStack,
        CsrfTokenManagerInterface $csrfTokenManager,
        HttpClientInterface $httpClient,
        ?LoggerInterface $logger = null
    ) {
        $this->framework = $framework;
        $this->connection = $connection;
        $this->newsFeedService = $newsFeedService;
        $this->translator = $translator;
        $this->requestStack = $requestStack;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }
    
    /**
     * Speichert News-Daten in eine JSON-Datei
     */
    private function saveNewsToJsonFile(int $configId, string $type, array $data): bool
    {
        // Verzeichnis erstellen, falls es nicht existiert
        $jsonDir = $this->getJsonStorageDir();
        if (!is_dir($jsonDir)) {
            if (!mkdir($jsonDir, 0755, true)) {
                $this->addFlash('error', 'Konnte Verzeichnis nicht erstellen: ' . $jsonDir);
                error_log('CaeliGoogleNewsFetch: Konnte Verzeichnis nicht erstellen: ' . $jsonDir);
                return false;
            }
        }
        
        // Dateipfad erstellen
        $filePath = $this->getJsonFilePath($configId, $type);
        $jsonData = json_encode($data, JSON_PRETTY_PRINT);
        
        if (file_put_contents($filePath, $jsonData) === false) {
            $this->addFlash('error', 'Konnte Daten nicht in Datei speichern: ' . $filePath);
            error_log('CaeliGoogleNewsFetch: Konnte Daten nicht in Datei speichern: ' . $filePath);
            return false;
        }
        
        error_log('CaeliGoogleNewsFetch: Daten erfolgreich in Datei gespeichert: ' . $filePath);
        return true;
    }
    
    /**
     * Lädt News-Daten aus einer JSON-Datei
     */
    private function loadNewsFromJsonFile(int $configId, string $type): array
    {
        // Direkt aus Datei laden
        $filePath = $this->getJsonFilePath($configId, $type);
        
        if (!file_exists($filePath)) {
            error_log('CaeliGoogleNewsFetch: Datei nicht gefunden: ' . $filePath);
            return [];
        }
        
        $jsonData = file_get_contents($filePath);
        if ($jsonData === false) {
            error_log('CaeliGoogleNewsFetch: Konnte Datei nicht lesen: ' . $filePath);
            return [];
        }
        
        $data = json_decode($jsonData, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('CaeliGoogleNewsFetch: JSON-Fehler: ' . json_last_error_msg());
            return [];
        }
        
        error_log('CaeliGoogleNewsFetch: Daten aus Datei geladen: ' . $filePath);
        return $data ?: [];
    }
    
    /**
     * Erstellt den Pfad zur JSON-Datei
     */
    private function getJsonFilePath(int $configId, string $type): string
    {
        return $this->getJsonStorageDir() . '/news_' . $configId . '_' . $type . '.json';
    }
    
    /**
     * Gibt das Verzeichnis für die JSON-Dateien zurück
     */
    private function getJsonStorageDir(): string
    {
        return System::getContainer()->getParameter('kernel.project_dir') . '/var/caeli_googlenews';
    }
    
    /**
     * Holt News-Artikel aus dem Google News über SerpAPI
     */
    #[Route('/fetch/{id<\d+>}', name: 'fetch_news', methods: ['GET'])]
    public function fetchNewsAction(Request $request, int $id): Response
    {
        $this->framework->initialize();
        
        // Konfiguration abrufen
        $configModel = CaeliGooglenewsModel::findById($id);
        
        if (null === $configModel) {
            $this->addFlash('error', 'Konfiguration nicht gefunden');
            return $this->redirectToContaoBackend($id);
        }
        
        try {
            // SerpAPI konfigurieren
            $searchQuery = $configModel->serpApiQuery;
            $apiKey = $configModel->serpApiKey;
            $numResults = (int)$configModel->serpApiNumResults ?: 100;
            $location = $configModel->serpApiLocation ?: 'Germany';
            $language = $configModel->serpApiLanguage ?: 'de';
            
            // Filter-Optionen abrufen
            $filters = [];
            
            // Zeitraum-Filter
            if (!empty($configModel->dateRestrict)) {
                $filters['dateRestrict'] = $configModel->dateRestrict;
            }
            
            // Quellen-Filter
            if (!empty($configModel->newsSource)) {
                $filters['source'] = $configModel->newsSource;
            }
            
            // Nachrichtentyp-Filter
            if (!empty($configModel->newsType)) {
                $filters['newsType'] = $configModel->newsType;
            }
            
            if (empty($searchQuery) || empty($apiKey)) {
                $this->addFlash('error', 'SerpAPI: Fehlende Konfiguration (Suchanfrage oder API-Schlüssel)');
            return $this->redirectToContaoBackend($id);
        }
        
            // News-Items sammeln
            $allNewsItems = [];
            $pageCount = 1;
            
            // Pagination prüfen
            $usePagination = !empty($configModel->paginationEnabled);
            $maxPages = (int)$configModel->maxPages ?: 3;
            
            // Maximale Anzahl Seiten begrenzen
            if ($maxPages > 10) {
                $maxPages = 10;
            }
            
            // Erste Seite abrufen
            $newsItems = $this->newsFeedService->fetchNewsViaSerpApi(
                $searchQuery,
                $apiKey,
                $numResults,
                $location,
                $language,
                $filters,
                1
            );
            
            if (!$newsItems || empty($newsItems)) {
                $this->addFlash('error', 'Keine News gefunden via SerpAPI');
                return $this->redirectToContaoBackend($id);
            }
            
            $allNewsItems = array_merge($allNewsItems, $newsItems);
            
            // Weitere Seiten abrufen, wenn Pagination aktiviert ist
            if ($usePagination && count($newsItems) >= $numResults) {
                $this->logger->info('Pagination aktiviert, rufe weitere Seiten ab...');
                
                // Weitere Seiten abrufen
                for ($page = 2; $page <= $maxPages; $page++) {
                    $this->logger->info('Rufe Seite ' . $page . ' ab...');
                    
                    $moreItems = $this->newsFeedService->fetchNewsViaSerpApi(
                        $searchQuery,
                        $apiKey,
                        $numResults,
                        $location,
                        $language,
                        $filters,
                        $page
                    );
                    
                    // Wenn keine weiteren Ergebnisse, beenden
                    if (!$moreItems || empty($moreItems)) {
                        $this->logger->info('Keine weiteren Ergebnisse auf Seite ' . $page);
                        break;
                    }
                    
                    $allNewsItems = array_merge($allNewsItems, $moreItems);
                    
                    // Duplikate entfernen
                    $allNewsItems = $this->removeDuplicates($allNewsItems);
                    
                    // Wenn die maximale Anzahl erreicht ist, beenden
                    if (count($allNewsItems) >= $numResults * $maxPages) {
                        $this->logger->info('Maximale Anzahl an Ergebnissen erreicht');
                        break;
                    }
                }
            }
            
            // Blacklist Keywords verarbeiten
            $blacklistKeywords = $this->parseKeywords($configModel->blacklistKeywords ?? '');
            
            // Blacklist anwenden - Artikel entfernen, die Blacklist-Begriffe enthalten
            if (!empty($blacklistKeywords)) {
                $this->logger->info('Wende Blacklist-Filter an mit ' . count($blacklistKeywords) . ' Keywords');
                $allNewsItems = array_filter($allNewsItems, function($item) use ($blacklistKeywords) {
                    foreach ($blacklistKeywords as $keyword) {
                        $keyword = strtolower(trim($keyword));
                        if (empty($keyword)) {
                            continue;
                        }
                        
                        $title = strtolower($item['title'] ?? '');
                        $description = strtolower($item['description'] ?? '');
                        
                        if (strpos($title, $keyword) !== false || strpos($description, $keyword) !== false) {
                            return false; // Artikel enthält einen Blacklist-Begriff
                        }
                    }
                    return true; // Kein Blacklist-Begriff gefunden
                });
                
                $this->logger->info('Nach Blacklist-Filter: ' . count($allNewsItems) . ' Artikel übrig');
            }
            
            // Array reindexieren nach dem Filtern
            $allNewsItems = array_values($allNewsItems);
            
            // VERBESSERTE LOGIK FÜR DIE VERWALTUNG VON ARCHIV UND AKTUELLEN NEWS
            // 1. Aktuelle Artikel (falls vorhanden) ins Archiv verschieben
            $archivedNews = $this->loadNewsFromJsonFile($id, 'archived');
            $currentNews = $this->loadNewsFromJsonFile($id, 'current');
            
            // Wenn aktuelle News vorhanden sind, diese ins Archiv verschieben
            if (!empty($currentNews)) {
                $this->logger->info('Verschiebe ' . count($currentNews) . ' Artikel aus aktuellen News ins Archiv');
                $archivedNews = array_merge($archivedNews, $currentNews);
                
                // Duplikate aus dem Archiv entfernen
                $archivedNews = $this->removeDuplicates($archivedNews);
                
                // Archiv-JSON aktualisieren
                $this->saveNewsToJsonFile($id, 'archived', $archivedNews);
            }
            
            // 2. Neue Artikel filtern: Nur Artikel behalten, die nicht im Archiv sind
            $filteredNewArticles = [];
            foreach ($allNewsItems as $newsItem) {
                $isInArchive = false;
                
                // Prüfe, ob Artikel bereits im Archiv existiert (nach Titel und Link)
                foreach ($archivedNews as $archivedItem) {
                    // Vergleiche nach Titel (normalisiert)
                    $newTitle = strtolower(trim($newsItem['title'] ?? ''));
                    $archivedTitle = strtolower(trim($archivedItem['title'] ?? ''));
                    
                    // Vergleiche nach URL (normalisiert)
                    $newUrl = $this->normalizeUrl($newsItem['link'] ?? '');
                    $archivedUrl = $this->normalizeUrl($archivedItem['link'] ?? '');
                    
                    if (($newTitle && $archivedTitle && $newTitle === $archivedTitle) || 
                        ($newUrl && $archivedUrl && $newUrl === $archivedUrl)) {
                        $isInArchive = true;
                        break;
                    }
                }
                
                // Nur Artikel hinzufügen, die nicht im Archiv sind
                if (!$isInArchive) {
                    $filteredNewArticles[] = $newsItem;
                }
            }
            
            // 3. Gefilterte neue Artikel in current.json speichern
            $this->saveNewsToJsonFile($id, 'current', $filteredNewArticles);
            
            // Update Timestamp
            $configModel->lastUpdated = time();
            $configModel->save();
            
            // Erfolgsmeldung mit Anzahl der neuen Artikel
            if (count($filteredNewArticles) > 0) {
                $this->addFlash('success', count($filteredNewArticles) . ' neue Artikel gefunden');
            } else {
                $this->addFlash('info', 'Keine neuen Beiträge verfügbar');
            }
            
            return $this->redirectToContaoBackend($id);
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Abrufen der Google News: ' . $e->getMessage());
            $this->addFlash('error', 'Fehler beim Abrufen der Google News: ' . $e->getMessage());
            return $this->redirectToContaoBackend($id);
        }
    }
    
    /**
     * Parst Keywords aus einem Multiline-Feld
     */
    private function parseKeywords(string $keywordsText): array
    {
        if (empty($keywordsText)) {
            return [];
        }
        
        // Zeilenumbrüche normalisieren
        $keywordsText = str_replace(["\r\n", "\r"], "\n", $keywordsText);
        
        // Zuerst nach Zeilenumbrüchen trennen
        $keywords = explode("\n", $keywordsText);
        
        // Dann nach Kommas trennen (für den Fall, dass der Benutzer Kommas verwendet)
        $result = [];
        foreach ($keywords as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            
            if (strpos($line, ',') !== false) {
                $commaSeparated = explode(',', $line);
                foreach ($commaSeparated as $part) {
                    $part = trim($part);
                    if (!empty($part)) {
                        $result[] = $part;
                    }
                }
            } else {
                $result[] = $line;
            }
        }
        
        // Duplikate entfernen und Array bereinigen
        return array_values(array_unique(array_filter($result)));
    }
    
    /**
     * Entfernt Duplikate aus einem Array von News-Items.
     * Items mit gleicher URL oder GUID werden nur einmal behalten.
     */
    private function removeDuplicates(array $newsItems): array
    {
        $uniqueItems = [];
        $guids = [];
        $links = [];
        
        foreach ($newsItems as $item) {
            $guid = $item['guid'] ?? '';
            $link = $item['link'] ?? '';
            
            // Wenn weder GUID noch Link vorhanden sind, überspringen
            if (empty($guid) && empty($link)) {
                continue;
            }
            
            // Prüfen, ob das Item bereits in der Liste ist
            if ((!empty($guid) && in_array($guid, $guids)) || 
                (!empty($link) && in_array($link, $links))) {
                continue;
            }
            
            // Item zur Liste hinzufügen
            $uniqueItems[] = $item;
            
            if (!empty($guid)) {
                $guids[] = $guid;
            }
            
            if (!empty($link)) {
                $links[] = $link;
            }
        }
        
        return $uniqueItems;
    }
    
    /**
     * Filtert Duplikate aus den aktuellen Nachrichten und entfernt JavaScript-Code
     */
    private function filterDuplicateNews(array $currentNews, array $archivedNews): array
    {
        error_log('CaeliGoogleNewsFetch: Duplikatsprüfung gestartet. Aktuelle Nachrichten: ' . count($currentNews) . ', Archivierte: ' . count($archivedNews));
        
        // NOTFALL: Zuerst alle Beschreibungen auf JavaScript prüfen und bereinigen
        foreach ($currentNews as $key => $item) {
            // JavaScript und unerwünschte Inhalte aus Beschreibungen entfernen
            if (isset($item['description'])) {
                $description = $item['description'];
                
                // Prüfe auf JavaScript-Code oder andere unerwünschte Inhalte
                if (strpos($description, 'window.') !== false || 
                    strpos($description, 'WIZ_global_data') !== false ||
                    strpos($description, 'var ') !== false ||
                    strpos($description, 'function ') !== false ||
                    preg_match('/^\s*{/', $description) ||
                    strlen($description) > 3000) { // Zu lange Beschreibungen sind oft JavaScript
                    
                    error_log('CaeliGoogleNewsFetch: JavaScript in Beschreibung gefunden und entfernt für Artikel: ' . $item['title']);
                    // Ersetze die Beschreibung durch den Titel oder eine Standardnachricht
                    $currentNews[$key]['description'] = 'Artikel: ' . $item['title'];
                }
            }
        }
        
        if (empty($archivedNews)) {
            error_log('CaeliGoogleNewsFetch: Kein Archiv vorhanden, keine Duplikatsprüfung erforderlich.');
            return $currentNews;
        }
        
        // Extrahiere GUIDs, Links und Titel aus archivierten Nachrichten
        $archivedGuids = [];
        $archivedLinks = [];
        $archivedTitles = [];
        
        foreach ($archivedNews as $news) {
            if (!empty($news['guid'])) {
                $archivedGuids[] = $news['guid'];
            }
            
            if (!empty($news['link'])) {
                // Normalisiere URL für besseren Vergleich
                $normalizedLink = strtolower(preg_replace('/\?.*$/', '', $news['link']));
                $archivedLinks[] = $normalizedLink;
            }
            
            if (!empty($news['title'])) {
                $archivedTitles[] = $news['title'];
            }
        }
        
        $filteredNews = [];
        $duplicateCount = 0;
        
        foreach ($currentNews as $news) {
            $isDuplicate = false;
            $reason = '';
            
            // 1. GUID-Prüfung
            if (!empty($news['guid']) && in_array($news['guid'], $archivedGuids)) {
                $isDuplicate = true;
                $reason = 'GUID';
            }
            
            // 2. Link-Prüfung
            if (!$isDuplicate && !empty($news['link'])) {
                $normalizedLink = strtolower(preg_replace('/\?.*$/', '', $news['link']));
                if (in_array($normalizedLink, $archivedLinks)) {
                    $isDuplicate = true;
                    $reason = 'Link';
                }
            }
            
            // 3. Titelähnlichkeitsprüfung
            if (!$isDuplicate && !empty($news['title'])) {
                foreach ($archivedTitles as $archivedTitle) {
                    // Verwende Levenshtein-Distanz oder ähnliche Titel
                    $similarity = 0;
                    
                    // Für kurze Titel: Exakte Übereinstimmung prüfen
                    if ($news['title'] === $archivedTitle) {
                        $similarity = 100;
                    } else {
                        // Normalisiere Titel für besseren Vergleich
                        $title1 = strtolower(preg_replace('/[^a-z0-9äöüß ]/i', '', $news['title']));
                        $title2 = strtolower(preg_replace('/[^a-z0-9äöüß ]/i', '', $archivedTitle));
                        
                        // Berechne Ähnlichkeit mit similar_text
                        similar_text($title1, $title2, $similarity);
                    }
                    
                    // Bei 85% Ähnlichkeit oder mehr als Duplikat betrachten
                    if ($similarity >= 85) {
                        $isDuplicate = true;
                        $reason = 'Titel (Ähnlichkeit: ' . round($similarity, 2) . '%)';
                        break;
                    }
                }
            }
            
            if ($isDuplicate) {
                $duplicateCount++;
                error_log('CaeliGoogleNewsFetch: Artikel entfernt (Duplikat): ' . $news['title'] . ', Grund: ' . $reason);
            } else {
                $filteredNews[] = $news;
            }
        }
        
        error_log('CaeliGoogleNewsFetch: ' . $duplicateCount . ' Duplikate gefunden und entfernt. Verbleibende Artikel: ' . count($filteredNews));
        
        return $filteredNews;
    }
    
    /**
     * Normalisiert eine URL für den Vergleich (entfernt Parameter, Fragmente usw.)
     */
    private function normalizeUrl(string $url): string
    {
        // Parameter und Fragmente entfernen
        $url = preg_replace('/\?.*/', '', $url);
        $url = preg_replace('/#.*/', '', $url);
        
        // Protokoll entfernen (http:// oder https://)
        $url = preg_replace('/^https?:\/\//', '', $url);
        
        // Trailing slash entfernen
        $url = rtrim($url, '/');
        
        return strtolower($url);
    }
    
    /**
     * Veröffentlicht einen einzelnen News-Artikel
     */
    #[Route('/publish/{id<\d+>}/{index<\d+>}', name: 'publish_news', methods: ['GET'])]
    public function publishNewsAction(Request $request, int $id, int $index): Response
    {
        $this->framework->initialize();
        
        // CSRF-Token prüfen
        $csrfToken = $request->query->get('_token');
        
        if (!$this->isCsrfTokenValid('contao_csrf_token', $csrfToken)) {
            $this->addFlash('error', 'Sicherheitstoken ist ungültig.');
            return $this->redirect('/contao?do=caeli_googlenews&act=edit&id=' . $id);
        }
        
        // Prüfen, ob die Artikel aus dem Archiv oder der aktuellen Liste kommen
        $isArchiveSource = $request->query->has('source') && $request->query->get('source') === 'archive';
        $sourceType = $isArchiveSource ? 'archived' : 'current';
        
        // Aktuelle oder archivierte News-Items laden
        $newsItems = $this->loadNewsFromJsonFile($id, $sourceType);
        
        if (empty($newsItems) || !isset($newsItems[$index])) {
            $this->addFlash('error', 'News-Artikel nicht gefunden');
            return $this->redirect('/contao?do=caeli_googlenews&act=edit&id=' . $id);
        }
        
        $item = $newsItems[$index];
        
        // Konfiguration abrufen
        $configModel = CaeliGooglenewsModel::findById($id);
        
        if (null === $configModel) {
            $this->addFlash('error', 'Konfiguration nicht gefunden');
            return $this->redirect('/contao?do=caeli_googlenews&act=edit&id=' . $id);
        }
        
        // Prüfen, ob ein News-Archiv ausgewählt ist
        if (empty($configModel->newsArchive) || $configModel->newsArchive < 1) {
            $this->addFlash('error', 'Kein News-Archiv ausgewählt');
            return $this->redirect('/contao?do=caeli_googlenews&act=edit&id=' . $id);
        }
        
        try {
            // News-Modell erstellen
            $newsModel = new \Contao\NewsModel();
            $newsModel->pid = $configModel->newsArchive;
            $newsModel->tstamp = time();
            $newsModel->source = 'external';
            $newsModel->published = '1'; // Immer veröffentlicht
            
            // Titel und Alias
            $newsModel->headline = html_entity_decode($item['title']);
            $newsModel->alias = StringUtil::generateAlias($newsModel->headline);
            
            // URL
            $newsModel->url = $item['link'];
            
            // Veröffentlichungsdatum
            if (!empty($item['pubDate'])) {
                if (is_numeric($item['pubDate'])) {
                    $newsModel->date = $newsModel->time = (int)$item['pubDate'];
                } else {
                    try {
                        $date = new \DateTime($item['pubDate']);
                        $newsModel->date = $newsModel->time = $date->getTimestamp();
                    } catch (\Exception $e) {
                        $newsModel->date = $newsModel->time = time();
                    }
                }
            } else {
                $newsModel->date = $newsModel->time = time();
            }
            
            // Beschreibung
            $newsModel->teaser = $item['description'] ?? '';
            
            // Autor
            $newsModel->author = $item['author'] ?? $item['source'] ?? 'Google News';
            
            // Weitere Felder
            $newsModel->subheadline = $item['source'] ?? 'Google News';
            
            // Bild-Felder vorinitialisieren, aber noch nicht aktivieren
            $newsModel->addImage = 0;
            $newsModel->singleSRC = '';
            
            // News erst speichern, damit wir eine ID für den Bildimport haben
            $newsModel->save();
            
            $imagePath = '';
            // Bild-Import, wenn vorhanden
            if (!empty($item['imageUrl'])) {
                // Bild herunterladen und Pfad ermitteln
                $this->logger->info('Starte Bild-Import für Artikel ID ' . $newsModel->id . ': ' . $item['imageUrl']);
                $imagePath = $this->downloadAndImportImage($item['imageUrl'], $newsModel->id);
                
                if ($imagePath) {
                    // Bild-Referenz speichern
                    $newsModel->addImage = 1;
                    $newsModel->singleSRC = $imagePath;
                    $this->logger->info('Bild erfolgreich mit News-Artikel verknüpft: ' . $imagePath);
                } else {
                    $this->logger->warning('Bild-Import fehlgeschlagen, versuche es direkt von der Webseite');
                    
                    // Wenn das normale Bild fehlschlägt, versuchen wir es direkt von der Webseite
                    $websiteImageUrl = $this->extractImageFromWebsite($item['link']);
                    if ($websiteImageUrl) {
                        $this->logger->info('Alternatives Bild von Website gefunden: ' . $websiteImageUrl);
                        $imagePath = $this->downloadAndImportImage($websiteImageUrl, $newsModel->id);
                        
                        if ($imagePath) {
                            $newsModel->addImage = 1;
                            $newsModel->singleSRC = $imagePath;
                            $this->logger->info('Alternatives Bild erfolgreich mit News-Artikel verknüpft: ' . $imagePath);
                        } else {
                            $this->logger->error('Auch der alternative Bild-Import ist fehlgeschlagen');
                        }
                    }
                }
                
                // In jedem Fall nochmal speichern, entweder mit oder ohne Bild
                $newsModel->save();
            }
            
            // Bild-Verknüpfung prüfen und loggen
            if ($newsModel->addImage && !empty($newsModel->singleSRC)) {
                $this->logger->info('News-Artikel mit Bild gespeichert. News-ID: ' . $newsModel->id . ', Bild: ' . $newsModel->singleSRC);
            } else {
                $this->logger->warning('News-Artikel ohne Bild gespeichert. News-ID: ' . $newsModel->id);
            }
            
            // Item als veröffentlicht markieren und JSON aktualisieren (nur bei current)
            if (!$isArchiveSource) {
                $newsItems[$index]['published'] = true;
                $this->saveNewsToJsonFile($id, $sourceType, $newsItems);
            }
            
            $this->addFlash('success', 'News-Artikel erfolgreich veröffentlicht');
            
            return $this->redirectToContaoBackend($id);
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Veröffentlichen des News-Artikels: ' . $e->getMessage());
            $this->addFlash('error', 'Fehler beim Veröffentlichen: ' . $e->getMessage());
            return $this->redirectToContaoBackend($id);
        }
    }
    
    /**
     * Veröffentlicht einen News-Eintrag
     */
    protected function publishNewsItem(array $item, $configModel): bool
    {
        try {
            // News-Modell erstellen
            $news = new NewsModel();
            
            // News Archive ID setzen - Bei configModel handelt es sich um die ID des News-Archivs
            $news->pid = $configModel->newsArchive;
            
            // Titel und Alias setzen
            $news->headline = html_entity_decode($item['title']);
            $news->alias = StringUtil::generateAlias($news->headline);
            
            // Grunddaten setzen
            $news->source = 'external';
            $news->url = $item['link'] ?? '';
            
            // Veröffentlichungsdatum aus dem Feed
            if (!empty($item['pubDate'])) {
                try {
                    $date = strtotime($item['pubDate']);
                    if ($date) {
                        $news->date = $news->time = $date;
                    } else {
                        $news->date = $news->time = time();
                    }
                } catch (\Exception $e) {
                    $news->date = $news->time = time();
                }
            } else {
                // Aktuelles Datum verwenden, wenn kein Datum vorhanden
                $news->date = $news->time = time();
            }
            
            // Status: veröffentlicht
            $news->published = 1;
            
            // Teaser setzen
            $description = $this->extractDescription($item);
            $news->teaser = $description;
            
            // Author setzen, falls vorhanden
            if (!empty($item['author'])) {
                $news->author = $item['author'];
            }
            
            // Bild-Felder vorinitialisieren
            $news->addImage = 0;
            $news->singleSRC = '';
            
            // News speichern, um eine ID zu bekommen
            $news->save();
            
            // Bild herunterladen und setzen, falls vorhanden
            $imageSuccess = false;
            if (!empty($item['imageUrl'])) {
                if ($this->logger) {
                    $this->logger->info(sprintf('Versuche Bild zu importieren: %s', $item['imageUrl']));
                }
                
                $imageId = $this->downloadAndImportImage($item['imageUrl'], $news->id);
                if ($imageId) {
                    $news->addImage = '1';
                    $news->singleSRC = $imageId;
                    $imageSuccess = true;
                    if ($this->logger) {
                        $this->logger->info(sprintf('Bild erfolgreich importiert: %s', $imageId));
                    }
                }
            }
            
            // Wenn das Hauptbild nicht funktioniert, versuche es von der Original-URL zu extrahieren
            if (!$imageSuccess) {
                try {
                    // Verwende die tatsächliche Ziel-URL für die Bildextraktion
                    $urlForImageExtraction = $item['link'] ?? '';
                    if (!empty($urlForImageExtraction)) {
                        $this->logger->info('Versuche Bild direkt von Webseite zu extrahieren: ' . $urlForImageExtraction);
                        $imageFromUrl = $this->extractImageFromWebsite($urlForImageExtraction);
                        
                        if (!empty($imageFromUrl)) {
                            if ($this->logger) {
                                $this->logger->info(sprintf('Versuche alternatives Bild zu importieren: %s', $imageFromUrl));
                            }
                            
                            $imageId = $this->downloadAndImportImage($imageFromUrl, $news->id);
                            if ($imageId) {
                                $news->addImage = '1';
                                $news->singleSRC = $imageId;
                                $imageSuccess = true;
                                if ($this->logger) {
                                    $this->logger->info(sprintf('Alternatives Bild erfolgreich importiert: %s', $imageId));
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    if ($this->logger) {
                        $this->logger->warning('Fehler beim Extrahieren des alternativen Bildes: ' . $e->getMessage());
                    }
                }
            }
            
            // Markieren als aus Google News importiert mit benutzerdefiniertem Feld
            $news->caeli_googlenews_imported = '1';
            
            // Speichern (oder aktualisieren, falls bereits gespeichert)
            $news->save();
            
            // Bild-Zustand loggen
            if ($news->addImage && !empty($news->singleSRC)) {
                $this->logger->info('News-Artikel mit Bild gespeichert. News-ID: ' . $news->id . ', Bild: ' . $news->singleSRC);
            } else {
                $this->logger->warning('News-Artikel ohne Bild gespeichert. News-ID: ' . $news->id);
            }
            
            // Erfolgsmeldung loggen
            if ($this->logger) {
                $this->logger->info(sprintf(
                    'Artikel "%s" wurde erfolgreich importiert [ID: %s]',
                    $news->headline,
                    $news->id
                ));
            }
            
            return true;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Fehler beim Veröffentlichen: ' . $e->getMessage());
            }
            
            error_log('CaeliGoogleNewsFetch: Fehler beim Veröffentlichen: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Extrahiert die beste verfügbare Beschreibung für einen Artikel
     */
    private function extractDescription(array $item): string
    {
        error_log('CaeliGoogleNewsFetch: Extrahiere Beschreibung für Artikel: ' . $item['title']);
        
        // 1. Direkt aus dem RSS-Feed
        if (!empty($item['description']) && $item['description'] !== $item['title']) {
            // Erweiterter Check für JavaScript/CSS-Inhalte
            if (preg_match('/^(var|function|window|document|if|for|while|\{|\}|\[|\]|<script|WIZ_global_data)/i', $item['description']) ||
                strpos($item['description'], '.js') !== false ||
                strpos($item['description'], 'window.') !== false ||
                strpos($item['description'], '{') === 0 ||
                strpos($item['description'], 'WIZ_global_data') !== false ||
                strpos($item['description'], '<style') !== false ||
                strlen($item['description']) > 2000) {
                error_log('CaeliGoogleNewsFetch: Feed-Beschreibung enthält verdächtigen Code, wird ignoriert');
            } else {
                $description = strip_tags($item['description']);
                $description = preg_replace('/(https?:\/\/[^\s]+)/', '', $description);
                $description = preg_replace('/\s+/', ' ', $description);
                $description = trim($description);
                
                if (!empty($description) && strlen($description) > 50) {
                    error_log('CaeliGoogleNewsFetch: Beschreibung aus RSS Feed verwendet, Länge: ' . strlen($description));
                    return $description;
                }
            }
        }
        
        // 2. Versuche Meta-Description von der Original-URL zu holen
        if (!empty($item['link'])) {
            try {
                // Bei Google News URLs: Den tatsächlichen Link extrahieren
                $targetUrl = $this->resolveGoogleNewsRedirect($item['link']);
                if (!$targetUrl) {
                    $targetUrl = $item['link'];
                }
                
                error_log('CaeliGoogleNewsFetch: Versuche Meta-Description von URL zu holen: ' . $targetUrl);
                $metaDescription = $this->getMetaDescriptionFromUrl($targetUrl);
                if (!empty($metaDescription)) {
                    error_log('CaeliGoogleNewsFetch: Meta-Description von URL verwendet, Länge: ' . strlen($metaDescription));
                    return $metaDescription;
                }
            } catch (\Exception $e) {
                error_log('CaeliGoogleNewsFetch: Fehler beim Extrahieren der Meta-Description: ' . $e->getMessage());
                // Fehler ignorieren und nächste Methode versuchen
            }
        }
        
        // 3. Fallback: Titel und Quelle verwenden
        $source = $item['source'] ?? parse_url($item['link'], PHP_URL_HOST);
        if ($source) {
            $source = preg_replace('/^www\./', '', $source);
            $fallback = 'Artikel von ' . $source . ': ' . $item['title'];
            error_log('CaeliGoogleNewsFetch: Fallback-Beschreibung verwendet: ' . $fallback);
            return $fallback;
        }
        
        // 4. Letzter Fallback: Nur Titel
        error_log('CaeliGoogleNewsFetch: Letzter Fallback: Nur Titel als Beschreibung');
        return $item['title'];
    }
    
    /**
     * Folgt einem Google News Redirect und gibt die tatsächliche URL zurück
     */
    private function resolveGoogleNewsRedirect(string $url): ?string
    {
        try {
            // Überprüfen, ob es sich um eine Google News URL handelt
            if (strpos($url, 'news.google.com') !== false) {
                error_log('CaeliGoogleNewsFetch: Google News URL erkannt, folge Redirect: ' . $url);
                
                try {
                    // Keine 301/302 Redirects automatisch folgen, um die tatsächliche Ziel-URL zu erhalten
                    $response = $this->httpClient->request('GET', $url, [
                        'timeout' => 10,
                        'max_duration' => 15,
                        'max_redirects' => 0,
                        'verify_peer' => false, // SSL-Zertifikatsprüfung deaktivieren für mehr Kompatibilität
                        'verify_host' => false,
                        'headers' => [
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
                        ]
                    ]);
                } catch (\Exception $httpError) {
                    error_log('CaeliGoogleNewsFetch: HTTP-Fehler bei Redirect-Auflösung: ' . $httpError->getMessage());
                    
                    // Als Fallback: manuell extrahieren, wenn die URL ein bestimmtes Google-News-Format hat
                    // Format: https://news.google.com/rss/articles/CBMi...
                    if (preg_match('/news\.google\.com\/rss\/articles\/([^?]+)/', $url, $matches)) {
                        error_log('CaeliGoogleNewsFetch: Versuche direkt aus URL-Parameter zu extrahieren');
                        
                        // Versuche bei einigen bekannten News-Seiten direkt die URL zu rekonstruieren
                        $hosts = [
                            'handelsblatt.com', 
                            'windkraft-journal.de',
                            'deutsche-startups.de',
                            'kurierverlag.de',
                            'startup-insider.com',
                            'silicon-canals.de'
                        ];
                        
                        foreach ($hosts as $host) {
                            if (strpos($url, $host) !== false || 
                                (isset($item['source']) && strpos(strtolower($item['source']), $host) !== false)) {
                                $possibleUrl = 'https://www.' . $host . '/article/' . $matches[1];
                                error_log('CaeliGoogleNewsFetch: Mögliche rekonstruierte URL: ' . $possibleUrl);
                                return $possibleUrl;
                            }
                        }
                    }
                    
                    // Wenn das fehlschlägt, geben wir die Original-URL zurück
                    return $url;
                }
                
                // Wenn kein Redirect (unwahrscheinlich)
                if ($response->getStatusCode() === 200) {
                    return $url;
                }
                
                // Status-Codes für Redirects
                if ($response->getStatusCode() >= 300 && $response->getStatusCode() < 400) {
                    $headers = $response->getHeaders();
                    if (isset($headers['location'][0])) {
                        $targetUrl = $headers['location'][0];
                        error_log('CaeliGoogleNewsFetch: Redirect gefunden: ' . $targetUrl);
                        
                        // Manchmal zeigt der Redirect auf eine weitere Google-URL - 
                        // in diesem Fall folgen wir auch diesem Redirect
                        if (strpos($targetUrl, 'google.com') !== false) {
                            $secondLevelUrl = $this->resolveGoogleNewsRedirect($targetUrl);
                            if ($secondLevelUrl) {
                                return $secondLevelUrl;
                            }
                        }
                        
                        return $targetUrl;
                    }
                }
                
                // Versuche Link aus HTML zu extrahieren (falls kein HTTP-Redirect verwendet wird)
                try {
                    $html = $response->getContent(false);
                    
                    // Regulärer Ausdruck für das Finden eines Links in der Seite
                    if (preg_match('/<a[^>]+href=["\'](https?:\/\/[^"\']+)["\'][^>]*>/i', $html, $matches)) {
                        $targetUrl = $matches[1];
                        error_log('CaeliGoogleNewsFetch: Link aus HTML extrahiert: ' . $targetUrl);
                        return $targetUrl;
                    }
                    
                    // Suche nach URL in einem meta-refresh Tag
                    if (preg_match('/<meta[^>]+http-equiv=["\']refresh["\'][^>]+content=["\'].*?url=(https?:\/\/[^"\']+)["\'][^>]*>/i', $html, $matches)) {
                        $targetUrl = $matches[1];
                        error_log('CaeliGoogleNewsFetch: URL aus meta-refresh extrahiert: ' . $targetUrl);
                        return $targetUrl;
                    }
                    
                    // Als letzten Versuch: Suche nach einem prominent angezeigten Link
                    if (preg_match('/<div[^>]+class=["\'][^"\']*main[^"\']*["\']\s*>.*?<a[^>]+href=["\'](https?:\/\/[^"\']+)["\']/is', $html, $matches)) {
                        $targetUrl = $matches[1];
                        error_log('CaeliGoogleNewsFetch: Link aus Hauptbereich extrahiert: ' . $targetUrl);
                        return $targetUrl;
                    }
                } catch (\Exception $contentError) {
                    error_log('CaeliGoogleNewsFetch: Fehler beim Extrahieren des HTML-Inhalts: ' . $contentError->getMessage());
                    return $url; // Original-URL zurückgeben als Fallback
                }
            } else {
                // Wenn es keine Google News URL ist, geben wir die Original-URL zurück
                return $url;
            }
        } catch (\Exception $e) {
            error_log('CaeliGoogleNewsFetch: Fehler beim Auflösen des Google News Redirects: ' . $e->getMessage());
            return $url; // Original-URL zurückgeben im Fehlerfall
        }
        
        // Wenn wir keine Redirect-URL finden konnten, geben wir die Original-URL zurück
        return $url;
    }
    
    /**
     * Holt die Meta-Description von einer URL
     */
    private function getMetaDescriptionFromUrl(string $url): ?string
    {
        // Google News URLs bereinigen (die enthalten oft Tracking-Parameter)
        $url = preg_replace('/\?.*/', '', $url);
        
        try {
            error_log('CaeliGoogleNewsFetch: Versuche Meta-Description von URL zu holen: ' . $url);
            
            $response = $this->httpClient->request('GET', $url, [
                'timeout' => 5,
                'max_duration' => 10,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'
                ]
            ]);
            
            if ($response->getStatusCode() === 200) {
                $html = $response->getContent();
                
                // Frühzeitige Überprüfung auf JavaScript im ganzen Dokument
                if (strpos($html, 'WIZ_global_data') !== false || 
                    strpos($html, 'window.') === 0 || 
                    substr(trim($html), 0, 4) === 'var ' ||
                    substr(trim($html), 0, 9) === 'function ') {
                    error_log('CaeliGoogleNewsFetch: HTML sieht wie JavaScript aus, überspringe Extraktion');
                    return null;
                }
                
                error_log('CaeliGoogleNewsFetch: HTML-Inhalt erhalten, Größe: ' . strlen($html) . ' Bytes');
                
                $description = null;
                
                // NUR Meta-Tags und strukturierte Daten extrahieren, kein allgemeiner Text
                
                // 1. Meta Description
                if (preg_match('/<meta\s+name=["\']description["\']\s+content=["\'](.*?)["\']/si', $html, $matches) ||
                    preg_match('/<meta\s+content=["\'](.*?)["\']\s+name=["\']description["\']/si', $html, $matches)) {
                    $description = trim(html_entity_decode($matches[1]));
                    
                    // Beschreibung bereinigen: Länge begrenzen und JavaScript entfernen
                    $description = $this->cleanDescription($description);
                    
                    if (!empty($description)) {
                        error_log('CaeliGoogleNewsFetch: Meta-Description gefunden: ' . substr($description, 0, 100));
                        return $description;
                    }
                }
                
                // 2. Open Graph Description
                if (preg_match('/<meta\s+property=["\']og:description["\']\s+content=["\'](.*?)["\']/si', $html, $matches) ||
                    preg_match('/<meta\s+content=["\'](.*?)["\']\s+property=["\']og:description["\']/si', $html, $matches)) {
                    $description = trim(html_entity_decode($matches[1]));
                    
                    // Beschreibung bereinigen
                    $description = $this->cleanDescription($description);
                    
                    if (!empty($description)) {
                        error_log('CaeliGoogleNewsFetch: OG-Description gefunden: ' . substr($description, 0, 100));
                        return $description;
                    }
                }
                
                // 3. Twitter Description
                if (preg_match('/<meta\s+name=["\']twitter:description["\']\s+content=["\'](.*?)["\']/si', $html, $matches) ||
                    preg_match('/<meta\s+content=["\'](.*?)["\']\s+name=["\']twitter:description["\']/si', $html, $matches)) {
                    $description = trim(html_entity_decode($matches[1]));
                    
                    // Beschreibung bereinigen
                    $description = $this->cleanDescription($description);
                    
                    if (!empty($description)) {
                        error_log('CaeliGoogleNewsFetch: Twitter-Description gefunden: ' . substr($description, 0, 100));
                        return $description;
                    }
                }
                
                // 4. JSON-LD für strukturierte Daten
                if (preg_match('/<script\s+type=["\']application\/ld\+json["\']\s*>(.*?)<\/script>/si', $html, $matches)) {
                    try {
                        $jsonLd = json_decode($matches[1], true);
                        if ($jsonLd && isset($jsonLd['description'])) {
                            $description = trim($jsonLd['description']);
                            
                            // Beschreibung bereinigen
                            $description = $this->cleanDescription($description);
                            
                            if (!empty($description)) {
                            error_log('CaeliGoogleNewsFetch: JSON-LD Description gefunden: ' . substr($description, 0, 100));
                            return $description;
                            }
                        }
                    } catch (\Exception $e) {
                        error_log('CaeliGoogleNewsFetch: JSON-LD Fehler: ' . $e->getMessage());
                    }
                }
                
                // Keine zuverlässige Beschreibung gefunden
                error_log('CaeliGoogleNewsFetch: Keine Beschreibung in Meta-Tags gefunden.');
                return null;
            } else {
                error_log('CaeliGoogleNewsFetch: HTTP-Fehler ' . $response->getStatusCode() . ' für URL: ' . $url);
            }
        } catch (\Exception $e) {
            error_log('CaeliGoogleNewsFetch: Exception beim Abrufen der Meta-Description: ' . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Bereinigt eine Beschreibung von JavaScript-Code und beschränkt die Länge
     */
    private function cleanDescription(string $description): ?string
    {
        // Länge auf maximal 500 Zeichen beschränken
        if (strlen($description) > 500) {
            $description = substr($description, 0, 500);
        }
        
        // Beschreibung trimmen
        $description = trim($description);
        
        // Leere Beschreibung zurückgeben, wenn leer
        if (empty($description)) {
            return null;
        }

        // JavaScript-Inhalte prüfen
        if (
            // JavaScript-Schlüsselwörter oder Patterns
            preg_match('/^(var|function|window|document|if|for|while|\{|\}|\[|\]|<script|WIZ_global_data)/i', $description) ||
            strpos($description, '.js') !== false ||
            strpos($description, 'window.') !== false ||
            strpos($description, '{') === 0 ||
            strpos($description, 'WIZ_global_data') !== false ||
            strpos($description, '<style') !== false ||
            preg_match('/function\s*\(/i', $description) ||
            preg_match('/new\s+[A-Za-z0-9_]+\(/i', $description) ||
            preg_match('/\b(document\.write|innerHTML|getElementById|querySelector)\b/i', $description) ||
            // JavaScript-Objekte
            preg_match('/\{["\'][a-zA-Z0-9_]+["\']\s*:/i', $description) ||
            // HTML-Tags enthalten (außer einfache <br> oder <p> Tags)
            preg_match('/<(?!br|\/br|p|\/p)[a-zA-Z]/', $description) ||
            // Zu viele Sonderzeichen, Klammern oder andere technische Indikatoren
            substr_count($description, '{') > 2 ||
            substr_count($description, '}') > 2 ||
            substr_count($description, '=') > 5 ||
            substr_count($description, ';') > 5 ||
            // Zu lang für eine normale Beschreibung
            strlen($description) > 300
        ) {
            error_log('CaeliGoogleNewsFetch: Beschreibung enthält verdächtigen Code oder Format, ignoriere');
            return null;
        }

        // HTML-Tags entfernen
        $description = strip_tags($description);
        
        // URLs entfernen
        $description = preg_replace('/(https?:\/\/[^\s]+)/', '', $description);
        
        // Mehr als ein Leerzeichen durch ein einzelnes ersetzen
        $description = preg_replace('/\s+/', ' ', $description);
        
        // Beschreibung trimmen
        $description = trim($description);
        
        return $description;
    }
    
    /**
     * Lädt ein Bild herunter und importiert es in die Contao-Dateiverwaltung
     */
    private function downloadAndImportImage(string $imageUrl, int $newsId = 0): string
    {
        try {
            // Leere oder ungültige URLs abfangen
            if (empty($imageUrl) || !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                $this->logger->error('Ungültige Bild-URL: ' . $imageUrl);
                return '';
            }
            
            // Prüfen, ob es ein SerpAPI-Bild ist (typischerweise klein und von serpapi.com oder googleusercontent.com)
            $isSerpApiImage = false;
            if (strpos($imageUrl, 'serpapi.com') !== false || 
                strpos($imageUrl, 'googleusercontent.com') !== false || 
                strpos($imageUrl, 'gstatic.com') !== false) {
                $isSerpApiImage = true;
                $this->logger->info('SerpAPI Bild erkannt: ' . $imageUrl);
            }
            
            // Zuerst prüfen, ob es ein News-Modell mit dieser ID gibt
            $newsModel = null;
            if ($newsId > 0) {
                $newsModel = \Contao\NewsModel::findById($newsId);
                if (!$newsModel) {
                    $this->logger->warning('Kein News-Modell mit ID ' . $newsId . ' gefunden');
                }
            }
            
            // Temporär das Originalbild prüfen, um seine Größe zu ermitteln
            $originalImageSize = null;
            if ($isSerpApiImage) {
                try {
                    $originalResponse = $this->httpClient->request('GET', $imageUrl, ['timeout' => 5]);
                    if ($originalResponse->getStatusCode() === 200) {
                        // Temporäres Verzeichnis für Downloads
                        $projectDir = System::getContainer()->getParameter('kernel.project_dir');
                        $tempDir = $projectDir . '/system/tmp';
                        if (!is_dir($tempDir)) {
                            mkdir($tempDir, 0755, true);
                        }
                        
                        // Temporäre Datei für Größenprüfung
                        $tempCheckFile = $tempDir . '/size_check_' . md5($imageUrl . microtime()) . '.img';
                        file_put_contents($tempCheckFile, $originalResponse->getContent());
                        
                        if (file_exists($tempCheckFile) && filesize($tempCheckFile) > 0) {
                            $imageInfo = getimagesize($tempCheckFile);
                            if ($imageInfo) {
                                $width = $imageInfo[0] ?? 0;
                                $height = $imageInfo[1] ?? 0;
                                $originalImageSize = ['width' => $width, 'height' => $height];
                                $this->logger->info('Originalbild Größe: ' . $width . 'x' . $height . ' Pixel');
                            }
                            // Temporäre Datei aufräumen
                            @unlink($tempCheckFile);
                        }
                    }
                } catch (\Exception $e) {
                    $this->logger->warning('Fehler bei der Größenprüfung des Originalbildes: ' . $e->getMessage());
                }
            }
            
            // Wenn es ein kleines SerpAPI-Bild ist (<150px), versuchen wir ein besseres Bild zu bekommen
            $shouldReplaceImage = $isSerpApiImage && (
                $originalImageSize === null || 
                ($originalImageSize['width'] < 150 || $originalImageSize['height'] < 150)
            );
            
            $betterImageUrl = null;
            if ($shouldReplaceImage && $newsModel && !empty($newsModel->url)) {
                // News-Artikel URL verwenden, um Bild zu extrahieren
                $this->logger->info('Versuche größeres Bild direkt von der Webseite zu extrahieren: ' . $newsModel->url);
                
                // Versuchen, Bild von der Original-Website zu extrahieren
                $betterImageUrl = $this->extractImageFromWebsite($newsModel->url);
                
                if ($betterImageUrl && $this->isValidImageUrl($betterImageUrl)) {
                    $this->logger->info('Größeres Bild von Website extrahiert: ' . $betterImageUrl);
                    
                    // Vorläufige Größenprüfung des neuen Bildes durchführen
                    try {
                        $newResponse = $this->httpClient->request('GET', $betterImageUrl, ['timeout' => 5]);
                        if ($newResponse->getStatusCode() === 200) {
                            // Temporäre Datei für Größenprüfung
                            $tempDir = System::getContainer()->getParameter('kernel.project_dir') . '/system/tmp';
                            $tempNewFile = $tempDir . '/new_size_check_' . md5($betterImageUrl . microtime()) . '.img';
                            file_put_contents($tempNewFile, $newResponse->getContent());
                            
                            if (file_exists($tempNewFile)) {
                                $newImageInfo = getimagesize($tempNewFile);
                                @unlink($tempNewFile); // Aufräumen
                                
                                if ($newImageInfo) {
                                    $newWidth = $newImageInfo[0] ?? 0;
                                    $newHeight = $newImageInfo[1] ?? 0;
                                    $this->logger->info('Neues Bild Größe: ' . $newWidth . 'x' . $newHeight . ' Pixel');
                                    
                                    // Nur ersetzen, wenn das neue Bild tatsächlich größer ist
                                    if ($originalImageSize === null || 
                                        ($newWidth > $originalImageSize['width'] && $newHeight > $originalImageSize['height']) ||
                                        ($newWidth * $newHeight > $originalImageSize['width'] * $originalImageSize['height'] * 1.5)) {
                                        $imageUrl = $betterImageUrl; // Ersetze SerpAPI-Bild mit dem besseren Bild
                                        $this->logger->info('Bildquelle ersetzt: Neues Bild ist größer (' . $newWidth . 'x' . $newHeight . ' vs. ' . 
                                            ($originalImageSize ? $originalImageSize['width'] . 'x' . $originalImageSize['height'] : 'unbekannt') . ')');
                                    } else {
                                        $this->logger->info('Behalte originales SerpAPI-Bild: Neues Bild ist nicht wesentlich größer');
                                    }
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        $this->logger->warning('Fehler bei der Größenprüfung des neuen Bildes: ' . $e->getMessage());
                        // Bei Fehler trotzdem das neue Bild verwenden, da es wahrscheinlich besser ist
                        $imageUrl = $betterImageUrl;
                    }
                }
            }
            
            // Bild herunterladen
            try {
                $response = $this->httpClient->request('GET', $imageUrl, ['timeout' => 10, 'max_duration' => 30]);
                if ($response->getStatusCode() !== 200) {
                    $this->logger->error('Bild-Download fehlgeschlagen: HTTP-Status ' . $response->getStatusCode());
                    
                    // Wenn das Hauptbild fehlschlägt, aber ein alternatives Bild verfügbar ist
                    if ($betterImageUrl && $imageUrl !== $betterImageUrl) {
                        $this->logger->info('Versuche alternatives Bild: ' . $betterImageUrl);
                        $response = $this->httpClient->request('GET', $betterImageUrl, ['timeout' => 10]);
                        if ($response->getStatusCode() !== 200) {
                            return '';
                        }
                        $imageUrl = $betterImageUrl;
                    } else {
                        return '';
                    }
                }
            } catch (\Exception $e) {
                $this->logger->error('Fehler beim Bild-Download: ' . $e->getMessage());
                return '';
            }
            
            // Temporäres Verzeichnis für Downloads
            $projectDir = System::getContainer()->getParameter('kernel.project_dir');
            $tempDir = $projectDir . '/system/tmp';
            if (!is_dir($tempDir)) {
                if (!mkdir($tempDir, 0755, true)) {
                    $this->logger->error('Konnte temporäres Verzeichnis nicht erstellen: ' . $tempDir);
                    return '';
                }
            }
            
            // Eindeutigen Dateinamen generieren mit newsId für bessere Nachverfolgung
            $actualNewsId = $newsId > 0 ? $newsId : mt_rand(1000, 9999);
            $fileName = 'news_' . $actualNewsId . '_' . md5($imageUrl . time()) . '.jpg';
            $tempFilePath = $tempDir . '/' . $fileName;
            
            // Bild in temporäre Datei speichern
            $imageContent = $response->getContent();
            if (!file_put_contents($tempFilePath, $imageContent)) {
                $this->logger->error('Konnte Bild nicht in temporäre Datei speichern: ' . $tempFilePath);
                return '';
            }
            
            // Prüfen, ob Datei erfolgreich gespeichert wurde und valide ist
            if (!file_exists($tempFilePath) || filesize($tempFilePath) === 0) {
                $this->logger->error('Bild konnte nicht gespeichert werden oder ist leer: ' . $tempFilePath);
                @unlink($tempFilePath); // Leere Datei löschen
                return '';
            }
            
            // Prüfen, ob es sich um ein gültiges Bild handelt
            $imageInfo = @getimagesize($tempFilePath);
            if ($imageInfo === false) {
                $this->logger->error('Datei ist kein gültiges Bild: ' . $tempFilePath);
                @unlink($tempFilePath);
                return '';
            }
            
            // Bildgröße protokollieren
            $width = $imageInfo[0] ?? 0;
            $height = $imageInfo[1] ?? 0;
            $this->logger->info('Finale Bildgröße: ' . $width . 'x' . $height . ' Pixel');
            
            // Framework initialisieren
            $this->framework->initialize();
            
            try {
                // Zielordner in der Contao-Dateiverwaltung
                $uploadFolder = 'files/news';
                $uploadPath = $projectDir . '/' . $uploadFolder;
                
                // Stellen Sie sicher, dass der Zielordner existiert
                if (!is_dir($uploadPath)) {
                    if (!mkdir($uploadPath, 0755, true)) {
                        $this->logger->error('Konnte Upload-Verzeichnis nicht erstellen: ' . $uploadPath);
                        @unlink($tempFilePath);
                        return '';
                    }
                }
                
                // Datei in den Zielordner kopieren
                $targetFile = $uploadFolder . '/' . $fileName;
                $targetPath = $projectDir . '/' . $targetFile;
                
                if (!copy($tempFilePath, $targetPath)) {
                    $this->logger->error('Konnte Bild nicht in Zielordner kopieren: ' . $targetPath);
                    @unlink($tempFilePath);
                    return '';
                }
                
                // Temporäre Datei löschen
                @unlink($tempFilePath);
                
                // Datei in die DBAFS eintragen
                $dbafs = System::getContainer()->get('contao.framework')->getAdapter('Contao\Dbafs');
                $fileModel = $dbafs->addResource($targetFile);
                
                if (!$fileModel) {
                    $this->logger->error('Konnte Bild nicht in die Dateiverwaltung importieren: ' . $targetFile);
                    // Aufräumen
                    @unlink($targetPath);
                    return '';
                }
                
                // Prüfen, ob die UUID korrekt gesetzt wurde
                if (empty($fileModel->uuid)) {
                    $this->logger->error('Keine UUID für das importierte Bild gefunden: ' . $targetFile);
                    return '';
                }
                
                $this->logger->info('Bild erfolgreich importiert: ' . $targetFile . ' (UUID: ' . $fileModel->uuid . ')');
                
                // Prüfen, ob das News-Modell existiert und aktualisieren Sie es direkt
                if ($newsModel) {
                    $newsModel->addImage = 1;
                    $newsModel->singleSRC = $fileModel->uuid;
                    $newsModel->save();
                    $this->logger->info('News-Eintrag direkt mit Bild aktualisiert (ID: ' . $newsId . ')');
                }
                
                return $fileModel->uuid;
            } catch (\Exception $e) {
                $this->logger->error('Fehler beim Bild-Import: ' . $e->getMessage(), ['exception' => $e]);
                
                // Temporäre Datei löschen, falls noch vorhanden
                if (file_exists($tempFilePath)) {
                    @unlink($tempFilePath);
                }
                
                return '';
            }
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Bild-Import: ' . $e->getMessage(), ['exception' => $e]);
            return '';
        }
    }
    
    /**
     * Bestimmt die Dateierweiterung anhand des MIME-Types
     */
    private function getImageExtensionFromMimeType(string $mimeType): string
    {
        return match ($mimeType) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            default => 'jpg', // Fallback auf JPG
        };
    }
    
    /**
     * Leitet zur Contao-Backend-Seite weiter
     */
    private function redirectToContaoBackend(int $id): Response
    {
        // Der richtige Pfad entsprechend dem Backend DCA
        $backendUrl = sprintf(
            '/contao?do=caeli_googlenews&act=edit&id=%s&rt=%s', 
            $id, 
            $this->csrfTokenManager->getToken('contao_backend')->getValue()
        );
        
        $this->logger->info('Redirect zu URL: ' . $backendUrl);
        
        // Einfache Weiterleitung ohne JavaScript
        return new RedirectResponse($backendUrl);
    }
    
    /**
     * Übersetzt einen Schlüssel mit dem Translator
     */
    private function trans(string $key, array $parameters = []): string
    {
        return $this->translator->trans($key, $parameters, 'CaeliGoogleNewsFetch');
    }
    
    /**
     * Extrahiert ein Bild von einer Website
     */
    private function extractImageFromWebsite(string $url): ?string
    {
        // Google News URLs bereinigen (die enthalten oft Tracking-Parameter)
        $url = preg_replace('/\?.*/', '', $url);
        
        try {
            $response = $this->httpClient->request('GET', $url, [
                'timeout' => 5,
                'max_duration' => 10,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'
                ]
            ]);
            
            if ($response->getStatusCode() === 200) {
                $html = $response->getContent();
                
                $imageUrl = null;
                
                // 1. Open Graph Image (höchste Priorität)
                if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\'](.*?)["\']/si', $html, $matches) || 
                    preg_match('/<meta[^>]+content=["\'](.*?)["\']\s+property=["\']og:image["\']/si', $html, $matches)) {
                    $imageUrl = trim($matches[1]);
                    error_log('CaeliGoogleNewsFetch: OG-Image gefunden: ' . $imageUrl);
                    
                    if ($this->isValidImageUrl($imageUrl)) {
                        return $this->normalizeImageUrl($imageUrl, $url);
                    }
                }
                
                // 2. Twitter Image
                if (preg_match('/<meta[^>]+name=["\']twitter:image["\'][^>]+content=["\'](.*?)["\']/si', $html, $matches) || 
                    preg_match('/<meta[^>]+content=["\'](.*?)["\']\s+name=["\']twitter:image["\']/si', $html, $matches)) {
                    $imageUrl = trim($matches[1]);
                    error_log('CaeliGoogleNewsFetch: Twitter-Image gefunden: ' . $imageUrl);
                    
                    if ($this->isValidImageUrl($imageUrl)) {
                        return $this->normalizeImageUrl($imageUrl, $url);
                    }
                }
                
                // 3. JSON-LD Structured Data
                if (preg_match('/<script\s+type=["\']application\/ld\+json["\']\s*>(.*?)<\/script>/si', $html, $matches)) {
                    try {
                        $jsonLd = json_decode($matches[1], true);
                        
                        // NewsArticle mit Bild
                        if (isset($jsonLd['@type']) && 
                            ($jsonLd['@type'] === 'NewsArticle' || $jsonLd['@type'] === 'Article') && 
                            isset($jsonLd['image'])) {
                                
                            // Kann String oder Objekt sein
                            if (is_string($jsonLd['image'])) {
                                $imageUrl = $jsonLd['image'];
                            } elseif (is_array($jsonLd['image']) && isset($jsonLd['image']['url'])) {
                                $imageUrl = $jsonLd['image']['url'];
                            } elseif (is_array($jsonLd['image']) && !empty($jsonLd['image'][0])) {
                                if (is_string($jsonLd['image'][0])) {
                                    $imageUrl = $jsonLd['image'][0];
                                } elseif (is_array($jsonLd['image'][0]) && isset($jsonLd['image'][0]['url'])) {
                                    $imageUrl = $jsonLd['image'][0]['url'];
                                }
                            }
                            
                            if ($imageUrl) {
                                error_log('CaeliGoogleNewsFetch: JSON-LD Image gefunden: ' . $imageUrl);
                                if ($this->isValidImageUrl($imageUrl)) {
                                    return $this->normalizeImageUrl($imageUrl, $url);
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        error_log('CaeliGoogleNewsFetch: Fehler beim Parsen von JSON-LD: ' . $e->getMessage());
                    }
                }
                
                // 4. Artikel-Hauptbild finden
                // a) Bilder mit bestimmten Klassen, die typischerweise für Hauptbilder verwendet werden
                if (preg_match('/<img[^>]+class=["\']([^"\']*featured[^"\']*|[^"\']*hero[^"\']*|[^"\']*main[^"\']*|[^"\']*thumbnail[^"\']*)["\'][^>]+src=["\'](.*?)["\']/si', $html, $matches)) {
                    $imageUrl = trim($matches[2]);
                    error_log('CaeliGoogleNewsFetch: Hauptbild mit relevanter Klasse gefunden: ' . $imageUrl);
                    
                    if ($this->isValidImageUrl($imageUrl)) {
                        return $this->normalizeImageUrl($imageUrl, $url);
                    }
                }
                
                // b) Bilder mit großen Abmessungen (oft Hauptbilder)
                $bigImagePattern = '/<img[^>]+(width=["\'](1\d\d\d|[5-9]\d\d|[1-9]\d{3,})["\']|height=["\'](1\d\d\d|[5-9]\d\d|[1-9]\d{3,})["\'])[^>]+src=["\'](.*?)["\']/si';
                if (preg_match($bigImagePattern, $html, $matches)) {
                    $imageUrl = trim($matches[5]);
                    error_log('CaeliGoogleNewsFetch: Großes Bild gefunden: ' . $imageUrl);
                    
                    if ($this->isValidImageUrl($imageUrl)) {
                        return $this->normalizeImageUrl($imageUrl, $url);
                    }
                }
                
                // 5. Erstes Bild im Content-Bereich (falls vorhanden)
                if (preg_match('/<div[^>]+class=["\']([^"\']*content[^"\']*|[^"\']*article[^"\']*)["\'][^>]*>(.*?)<\/div>/si', $html, $contentMatches)) {
                    $contentHtml = $contentMatches[2];
                    if (preg_match('/<img[^>]+src=["\'](.*?)["\']/si', $contentHtml, $imgMatches)) {
                        $imageUrl = trim($imgMatches[1]);
                        error_log('CaeliGoogleNewsFetch: Bild im Content-Bereich gefunden: ' . $imageUrl);
                        
                        if ($this->isValidImageUrl($imageUrl)) {
                            return $this->normalizeImageUrl($imageUrl, $url);
                        }
                    }
                }
                
                // 6. Letzter Versuch: Erstes vernünftiges Bild (keine Icons oder sehr kleine Bilder)
                preg_match_all('/<img[^>]+src=["\'](.*?)["\']/si', $html, $matches);
                if (!empty($matches[1])) {
                    foreach ($matches[1] as $imgSrc) {
                        // Kleine Icons und Spacer ausfiltern
                        if (!preg_match('/(icon|logo|spacer|pixel|tracking|banner|advertisement|ad\.|1x1|transparent)/i', $imgSrc)) {
                            $imageUrl = trim($imgSrc);
                            error_log('CaeliGoogleNewsFetch: Reguläres Bild gefunden: ' . $imageUrl);
                            
                            if ($this->isValidImageUrl($imageUrl)) {
                                return $this->normalizeImageUrl($imageUrl, $url);
                            }
                        }
                    }
                }
            } else {
                error_log('CaeliGoogleNewsFetch: HTTP-Fehler ' . $response->getStatusCode() . ' für URL: ' . $url);
                }
            } catch (\Exception $e) {
                if ($this->logger) {
                    $this->logger->warning('Fehler beim Extrahieren des Bildes: ' . $e->getMessage());
            }
        }
        
        return null;
    }
    
    /**
     * Prüft, ob eine Bild-URL gültig ist
     */
    private function isValidImageUrl(string $url): bool
    {
        // Leere URLs oder Data-URLs ablehnen
        if (empty($url) || strpos($url, 'data:') === 0) {
            return false;
        }
        
        // Bilder mit bekannten Bildendungen akzeptieren
        if (preg_match('/\.(jpe?g|png|gif|webp|avif)$/i', $url)) {
            return true;
        }
        
        // URLs mit Bildparametern akzeptieren (z.B. image.php?id=123)
        if (strpos($url, 'image') !== false || strpos($url, 'media') !== false || strpos($url, 'photo') !== false) {
            return true;
        }
        
        // URLs mit unüblichen Endungen (aber dennoch Bilder) überprüfen
        if (preg_match('/\.(ashx|aspx|php|cgi)\?(.*)(image|img|photo|media)/i', $url)) {
            return true;
        }
        
        // Bestimmte bekannte Bilderdienste akzeptieren
        if (
            strpos($url, 'cloudinary.com') !== false || 
            strpos($url, 'imgix.net') !== false ||
            strpos($url, 'res.cloudinary.com') !== false ||
            strpos($url, 'img.youtube.com') !== false ||
            strpos($url, 'i.ytimg.com') !== false ||
            strpos($url, 'media.tenor.com') !== false
        ) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Normalisiert eine Bild-URL (macht relative URLs absolut)
     */
    private function normalizeImageUrl(string $imageUrl, string $baseUrl): string
    {
        // Wenn die URL bereits absolut ist
        if (strpos($imageUrl, 'http://') === 0 || strpos($imageUrl, 'https://') === 0) {
            return $imageUrl;
        }
        
        // Wenn es eine URL ist, die mit // beginnt (Schema-relatives URL)
        if (strpos($imageUrl, '//') === 0) {
            $scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https';
            return $scheme . ':' . $imageUrl;
        }
        
        // Basis-URL parsen
        $parsedUrl = parse_url($baseUrl);
        $scheme = $parsedUrl['scheme'] ?? 'https';
        $host = $parsedUrl['host'] ?? '';
        $path = $parsedUrl['path'] ?? '';
        
        // Wenn die URL mit einem Slash beginnt, ist sie relativ zum Root
        if (strpos($imageUrl, '/') === 0) {
            return $scheme . '://' . $host . $imageUrl;
        }
        
        // Ansonsten ist sie relativ zum aktuellen Pfad
        // Pfad auf Verzeichnis abschneiden
        $path = substr($path, 0, strrpos($path, '/') + 1);
        
        return $scheme . '://' . $host . $path . $imageUrl;
    }

    /**
     * Setzt das Archiv zurück
     */
    #[Route('/reset/{id<\d+>}', name: 'reset_archive', methods: ['GET'])]
    public function resetArchiveAction(Request $request, int $id): Response
    {
        try {
            // Einfache und direkte Lösung: Direkt einen leeren Array als JSON in die Datei schreiben
            $filePath = $this->getJsonFilePath($id, 'archived');
            
            // Stelle sicher, dass das Verzeichnis existiert
            $dir = dirname($filePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            // Leere die JSON-Datei direkt (leerer Array)
            file_put_contents($filePath, '[]');
            
            // Erfolgsmeldung
            $this->addFlash('confirm', 'Archiv erfolgreich zurückgesetzt');
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Zurücksetzen des Archivs: ' . $e->getMessage());
            $this->addFlash('error', 'Fehler beim Zurücksetzen des Archivs: ' . $e->getMessage());
        }
        
        // Zurück zum Backend mit Timestamp für Cache-Busting
        return $this->redirect('/contao?do=caeli_googlenews&act=edit&id=' . $id . '&rt=' . time());
    }
    
    /**
     * Erstellt einen Access-Denied-Response
     */
    private function createAccessDeniedResponse(): Response
    {
        return new Response('Access Denied', Response::HTTP_FORBIDDEN);
    }

    /**
     * Veröffentlicht mehrere ausgewählte News-Artikel
     */
    #[Route('/publish-multiple/{id<\d+>}', name: 'publish_multiple', methods: ['POST'])]
    public function publishMultipleAction(Request $request, int $id): Response
    {
        $this->framework->initialize();
        
        // Debug: Prüfe, welche Daten im Request ankommen
        $allData = $request->request->all();
        $debug = "Debug - Request-Daten: " . print_r($allData, true);
        $this->logger->error($debug); // Schreibe in Logs
        
        // CSRF-Token prüfen
        $csrfToken = $request->query->get('_token');
        
        // Wenn kein gültiges Token gefunden wurde
        if (!$this->isCsrfTokenValid('contao_csrf_token', $csrfToken)) {
            $this->addFlash('error', 'Sicherheitstoken ist ungültig.');
            return $this->redirect('/contao?do=caeli_googlenews&act=edit&id=' . $id);
        }
        
        // Ausgewählte Artikel-Indizes aus dem Formular holen
        $selectedIndices = $request->request->all()['selected'] ?? [];
        
        // Debug: Ausgewählte Indices
        $debugIndices = "Debug - Ausgewählte Indices: " . print_r($selectedIndices, true);
        $this->logger->error($debugIndices);
        
        if (empty($selectedIndices)) {
            $this->addFlash('error', 'Keine Artikel zum Veröffentlichen ausgewählt');
            return $this->redirect('/contao?do=caeli_googlenews&act=edit&id=' . $id);
        }
        
        // Prüfen, ob die Artikel aus dem Archiv oder der aktuellen Liste kommen
        $isArchiveSource = $request->query->has('source') && $request->query->get('source') === 'archive';
        $sourceType = $isArchiveSource ? 'archived' : 'current';
        
        // Aktuelle oder archivierte News-Items laden
        $newsItems = $this->loadNewsFromJsonFile($id, $sourceType);
        
        if (empty($newsItems)) {
            $this->addFlash('error', 'Keine News-Artikel gefunden');
            return $this->redirect('/contao?do=caeli_googlenews&act=edit&id=' . $id);
        }
        
        // Konfiguration abrufen
        $configModel = CaeliGooglenewsModel::findById($id);
            
            if (null === $configModel) {
            $this->addFlash('error', 'Konfiguration nicht gefunden');
            return $this->redirect('/contao?do=caeli_googlenews&act=edit&id=' . $id);
        }
        
        // Prüfen, ob ein News-Archiv ausgewählt ist
        if (empty($configModel->newsArchive) || $configModel->newsArchive < 1) {
            $this->addFlash('error', 'Kein News-Archiv ausgewählt');
            return $this->redirect('/contao?do=caeli_googlenews&act=edit&id=' . $id);
        }
        
        // Erfolgreich importierte Artikel zählen
        $successCount = 0;
        $errorCount = 0;
        
        // Jeden ausgewählten Artikel direkt importieren
        foreach ($selectedIndices as $index) {
            try {
                // Bereinige den Index (falls aus Archiv)
                if (strpos($index, 'archive_') === 0) {
                    $index = (int)str_replace('archive_', '', $index);
                } else {
                    $index = (int)$index;
                }
                
                // Prüfen, ob Artikel existiert
                if (!isset($newsItems[$index])) {
                    $errorCount++;
                    continue;
                }
                
                $item = $newsItems[$index];
                
                // News-Eintrag im Contao News-Modul erstellen
                $news = new \Contao\NewsModel();
                $news->pid = $configModel->newsArchive;
                $news->tstamp = time();
                $news->headline = html_entity_decode($item['title']);
                $news->alias = StringUtil::generateAlias($news->headline);
                
                // Quelle und URL
                $news->source = 'external';
                $news->url = $item['link'] ?? '';
                
                // Veröffentlichungsdatum
                if (!empty($item['pubDate'])) {
                    if (is_numeric($item['pubDate'])) {
                        $news->date = $news->time = (int)$item['pubDate'];
                    } else {
                        try {
                            $date = new \DateTime($item['pubDate']);
                            $news->date = $news->time = $date->getTimestamp();
        } catch (\Exception $e) {
                            $news->date = $news->time = time();
                        }
                    }
                } else {
                    $news->date = $news->time = time();
                }
                
                // Beschreibung
                $news->teaser = $item['description'] ?? '';
                
                // Autor
                if (!empty($item['author'])) {
                    $news->author = $item['author'];
                }
                
                // Immer veröffentlicht
                $news->published = '1';
                
                // Speichern
                $news->save();
                
                // Bild-Import, wenn URL vorhanden
                if (!empty($item['imageUrl'])) {
                    $imageUuid = $this->downloadAndImportImage($item['imageUrl'], $news->id);
                    if ($imageUuid) {
                        $news->addImage = '1';
                        $news->singleSRC = $imageUuid;
                        $news->save();
                    }
                }
                
                // Als erfolgreich zählen
                $successCount++;
            } catch (\Exception $e) {
                // Fehler loggen
                $this->logger->error('Fehler beim Import: ' . $e->getMessage());
                $errorCount++;
            }
        }
        
        // Feedback geben
        if ($successCount > 0) {
            $this->addFlash('confirm', $successCount . ' Artikel erfolgreich importiert' . ($errorCount > 0 ? ' (' . $errorCount . ' Fehler)' : ''));
        } else {
            $this->addFlash('error', 'Keine Artikel importiert' . ($errorCount > 0 ? ' (' . $errorCount . ' Fehler)' : ''));
        }
        
        // Zurück zum Backend - explizites Refresh-Parameter für Browser-Cache-Umgehung
        $timestamp = time();
        return $this->redirect('/contao?do=caeli_googlenews&act=edit&id=' . $id . '&refresh=' . $timestamp);
    }

    #[Route('/publish-all/{id<\d+>}', name: 'publish_all', methods: ['GET'])]
    public function publishAllAction(Request $request, int $id): Response
    {
        $this->framework->initialize();
        
        // CSRF-Token prüfen
        $csrfToken = $request->query->get('_token');
        
        if (!$this->isCsrfTokenValid('contao_csrf_token', $csrfToken)) {
            $this->addFlash('error', 'Sicherheitstoken ist ungültig.');
            return $this->redirect('/contao?do=caeli_googlenews&act=edit&id=' . $id);
        }
        
        // Prüfen, ob die Artikel aus dem Archiv oder der aktuellen Liste kommen
        $isArchiveSource = $request->query->has('source') && $request->query->get('source') === 'archive';
        $sourceType = $isArchiveSource ? 'archived' : 'current';
        
        // Aktuelle oder archivierte News-Items laden
        $newsItems = $this->loadNewsFromJsonFile($id, $sourceType);
        
        if (empty($newsItems)) {
            $this->addFlash('error', 'Keine News-Artikel gefunden');
            return $this->redirect('/contao?do=caeli_googlenews&act=edit&id=' . $id);
        }
        
        // Konfiguration abrufen
        $configModel = CaeliGooglenewsModel::findById($id);
        
        if (null === $configModel) {
            $this->addFlash('error', 'Konfiguration nicht gefunden');
            return $this->redirect('/contao?do=caeli_googlenews&act=edit&id=' . $id);
        }
        
        // Prüfen, ob ein News-Archiv ausgewählt ist
        if (empty($configModel->newsArchive) || $configModel->newsArchive < 1) {
            $this->addFlash('error', 'Kein News-Archiv ausgewählt');
            return $this->redirect('/contao?do=caeli_googlenews&act=edit&id=' . $id);
        }
        
        // Erfolgreich importierte Artikel zählen
        $successCount = 0;
        $errorCount = 0;
        
        // Jeden Artikel direkt importieren
        foreach ($newsItems as $index => $item) {
            try {
                // Prüfen, ob der Artikel bereits importiert wurde (wir könnten hier mehr Logik hinzufügen)
                // z.B. isNewsAlreadyImported(), aber das ist jetzt vereinfacht
                
                // News-Eintrag im Contao News-Modul erstellen
                $news = new \Contao\NewsModel();
                $news->pid = $configModel->newsArchive;
                $news->tstamp = time();
                $news->headline = html_entity_decode($item['title']);
                $news->alias = StringUtil::generateAlias($news->headline);
                
                // Quelle und URL
                $news->source = 'external';
                $news->url = $item['link'] ?? '';
                
                // Veröffentlichungsdatum
                if (!empty($item['pubDate'])) {
                    if (is_numeric($item['pubDate'])) {
                        $news->date = $news->time = (int)$item['pubDate'];
                    } else {
                        try {
                            $date = new \DateTime($item['pubDate']);
                            $news->date = $news->time = $date->getTimestamp();
                        } catch (\Exception $e) {
                            $news->date = $news->time = time();
                        }
                    }
                } else {
                    $news->date = $news->time = time();
                }
                
                // Beschreibung
                $news->teaser = $item['description'] ?? '';
                
                // Autor
                if (!empty($item['author'])) {
                    $news->author = $item['author'];
                }
                
                // Immer veröffentlicht
                $news->published = '1';
                
                // Speichern
                $news->save();
                
                // Bild-Import, wenn URL vorhanden
                if (!empty($item['imageUrl'])) {
                    $imageUuid = $this->downloadAndImportImage($item['imageUrl'], $news->id);
                    if ($imageUuid) {
                        $news->addImage = '1';
                        $news->singleSRC = $imageUuid;
                        $news->save();
                    }
                }
                
                // Als erfolgreich zählen
                $successCount++;
            } catch (\Exception $e) {
                // Fehler loggen
                $this->logger->error('Fehler beim Import: ' . $e->getMessage());
                $errorCount++;
            }
        }
        
        // Feedback geben
        if ($successCount > 0) {
            $this->addFlash('confirm', $successCount . ' Artikel erfolgreich importiert' . ($errorCount > 0 ? ' (' . $errorCount . ' Fehler)' : ''));
        } else {
            $this->addFlash('error', 'Keine Artikel importiert' . ($errorCount > 0 ? ' (' . $errorCount . ' Fehler)' : ''));
        }
        
        // Zurück zum Backend mit Timestamp für Cache-Busting
        $timestamp = time();
        return $this->redirect('/contao?do=caeli_googlenews&act=edit&id=' . $id . '&refresh=' . $timestamp);
    }
} 
} 