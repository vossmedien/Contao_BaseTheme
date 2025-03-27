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
use CaeliWind\CaeliGoogleNewsFetch\Service\ImageService;
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
    protected ImageService $imageService;
    
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
        ImageService $imageService,
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
        $this->imageService = $imageService;
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
                $this->addFlash('info', 'Keine neuen Artikel gefunden');
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
        $urls = [];
        $titles = [];
        
        foreach ($newsItems as $item) {
            $isDuplicate = false;
            
            // Prüfe GUID wenn vorhanden
            if (!empty($item['guid'])) {
                if (in_array($item['guid'], $guids)) {
                    $isDuplicate = true;
                } else {
                    $guids[] = $item['guid'];
                }
            }
            
            // Prüfe URL wenn vorhanden
            if (!$isDuplicate && !empty($item['link'])) {
                $url = $item['link'];
                if (in_array($url, $urls)) {
                    $isDuplicate = true;
                } else {
                    $urls[] = $url;
                }
            }
            
            // Prüfe Titel wenn vorhanden
            if (!$isDuplicate && !empty($item['title'])) {
                $title = $item['title'];
                if (in_array($title, $titles)) {
                $isDuplicate = true;
                } else {
                    $titles[] = $title;
                }
            }
            
            if (!$isDuplicate) {
                $uniqueItems[] = $item;
            }
        }
        
        return $uniqueItems;
    }
    
    /**
     * Filtert Duplikate aus den aktuellen Nachrichten und entfernt JavaScript-Code
     */
    private function filterDuplicateNews(array $currentNews, array $archivedNews): array
    {
        $filtered = [];
        
        foreach ($currentNews as $newsItem) {
            $isDuplicate = false;
            $itemUrl = $newsItem['link'] ?? '';
            
            // Prüfen, ob dieser Artikel bereits im Archiv vorhanden ist
            foreach ($archivedNews as $archivedItem) {
                $archivedUrl = $archivedItem['link'] ?? '';
                
                // Wenn URL identisch, dann als Duplikat betrachten
                if (!empty($itemUrl) && !empty($archivedUrl) && $itemUrl === $archivedUrl) {
                    $isDuplicate = true;
                    break;
                }
                
                // Oder wenn Titel identisch/sehr ähnlich
                if (!empty($newsItem['title']) && !empty($archivedItem['title'])) {
                    // Exakte Übereinstimmung
                    if ($newsItem['title'] === $archivedItem['title']) {
                        $isDuplicate = true;
                        break;
                    }
                    
                    // Ähnlichkeits-Check: Levenshtein-Distanz < 10% der Länge
                    $titleLength = strlen($newsItem['title']);
                    if ($titleLength > 10) { // Nur für längere Titel
                        $distance = levenshtein($newsItem['title'], $archivedItem['title']);
                        if ($distance / $titleLength < 0.1) { // Weniger als 10% Unterschied
                            $isDuplicate = true;
                            break;
                        }
                    }
                }
            }
            
            if (!$isDuplicate) {
                $filtered[] = $newsItem;
            }
        }
        
        return $filtered;
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
        
        // Konfiguration abrufen
        $configModel = CaeliGooglenewsModel::findById($id);
        
        if (null === $configModel) {
            $this->addFlash('error', 'Konfiguration nicht gefunden');
            return $this->redirectToContaoBackend($id);
        }
        
        // Prüfen, ob ein News-Archiv ausgewählt ist
        if (empty($configModel->newsArchive) || $configModel->newsArchive < 1) {
            $this->addFlash('error', 'Kein News-Archiv ausgewählt');
            return $this->redirectToContaoBackend($id);
        }
        
        try {
            // Prüfen, ob die Artikel aus dem Archiv oder der aktuellen Liste kommen
            $isArchiveSource = $request->query->has('source') && $request->query->get('source') === 'archive';
            $sourceType = $isArchiveSource ? 'archived' : 'current';
            
            // News-Items aus JSON-Datei laden
            $newsItems = $this->loadNewsFromJsonFile($id, $sourceType);
            
            if (empty($newsItems) || !isset($newsItems[$index])) {
                $this->addFlash('error', 'News-Artikel nicht gefunden');
                return $this->redirectToContaoBackend($id);
            }
            
            // Das ausgewählte News-Item
            $item = $newsItems[$index];
            
            // News-Eintrag im Contao News-Modul erstellen
            $newsModel = new \Contao\NewsModel();
            $newsModel->tstamp = time();
            $newsModel->pid = $configModel->newsArchive;
            $newsModel->headline = $item['title'];
            $newsModel->alias = StringUtil::generateAlias($item['title']);
            
            // Veröffentlichungsdetails
            if (!empty($item['pubDate'])) {
                try {
                    // Wenn pubDate bereits ein Timestamp ist
                    if (is_numeric($item['pubDate'])) {
                        $newsModel->date = $newsModel->time = (int)$item['pubDate'];
                    } else {
                        $pubDate = new \DateTime($item['pubDate']);
                        $newsModel->date = $newsModel->time = $pubDate->getTimestamp();
                    }
                } catch (\Exception $e) {
                    $newsModel->date = $newsModel->time = time();
                }
            } else {
                // Aktuelles Datum verwenden, wenn kein Datum vorhanden
                $newsModel->date = $newsModel->time = time();
            }
            
            $newsModel->published = 1;
            $newsModel->start = '';
            $newsModel->stop = '';
            
            // Quelle und URL
            $newsModel->source = 'external';
            $newsModel->url = $item['link'] ?? '';
            $newsModel->target = 1; // In neuem Fenster öffnen
            
            // Teaser und Inhalt
            $newsModel->teaser = $item['description'] ?? '';
            $newsModel->subheadline = $item['source'] ?? 'Google News';
            
            // Speichern
            $newsModel->save();
            
            // Bild-Import, wenn vorhanden
            if (!empty($item['imageUrl'])) {
                if ($this->logger) {
                    $this->logger->info(sprintf('Versuche Bild zu importieren: %s', $item['imageUrl']));
                }
                
                // Hole den SerpAPI-Thumbnail als Fallback
                $fallbackUrl = $item['thumbnail'] ?? null;
                
                // News speichern, um eine ID zu bekommen
                $newsModel->save();
                
                // Nutze den Fallback-Parameter für das SerpAPI-Thumbnail
                $imageId = $this->imageService->downloadAndImportImage($item['imageUrl'], $newsModel->id, $fallbackUrl);
                if ($imageId) {
                    $newsModel->addImage = '1';
                    $newsModel->singleSRC = $imageId;
                    if ($this->logger) {
                        $this->logger->info(sprintf('Bild erfolgreich importiert: %s', $imageId));
                    }
                } else if ($this->logger) {
                    $this->logger->warning('Konnte weder Hauptbild noch Fallback-Bild importieren');
                }
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
            
            // Teaser setzen - direkt aus der SerpAPI Beschreibung
            $news->teaser = $item['description'] ?? '';
            
            // Author setzen, falls vorhanden
            if (!empty($item['author'])) {
                $news->author = $item['author'];
            }
            
            // Bild herunterladen und setzen, falls vorhanden
            if (!empty($item['imageUrl'])) {
                if ($this->logger) {
                    $this->logger->info(sprintf('Versuche Bild zu importieren: %s', $item['imageUrl']));
                }
                
                // Hole den SerpAPI-Thumbnail als Fallback
                $fallbackUrl = $item['thumbnail'] ?? null;
                
                // News speichern, um eine ID zu bekommen
                $news->save();
                
                // Nutze den Fallback-Parameter für das SerpAPI-Thumbnail
                $imageId = $this->imageService->downloadAndImportImage($item['imageUrl'], $news->id, $fallbackUrl);
                if ($imageId) {
                    $news->addImage = '1';
                    $news->singleSRC = $imageId;
                    if ($this->logger) {
                        $this->logger->info(sprintf('Bild erfolgreich importiert: %s', $imageId));
                    }
                } else if ($this->logger) {
                    $this->logger->warning('Konnte weder Hauptbild noch Fallback-Bild importieren');
                }
            }
            
            // Markieren als aus Google News importiert mit benutzerdefiniertem Feld
            $news->caeli_googlenews_imported = '1';
            
            // Speichern (oder aktualisieren, falls bereits gespeichert)
            $news->save();
            
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
     * Setzt das Archiv zurück
     */
    #[Route('/reset/{id<\d+>}', name: 'reset_archive', methods: ['GET'])]
    public function resetArchiveAction(Request $request, int $id): Response
    {
        try {
            // Stelle sicher, dass das Verzeichnis existiert
            $dir = $this->getJsonStorageDir();
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            // Leere die Archiv-JSON-Datei (leerer Array)
            $archivedFilePath = $this->getJsonFilePath($id, 'archived');
            file_put_contents($archivedFilePath, '[]');
            
            // Zusätzlich auch die current.json leeren
            $currentFilePath = $this->getJsonFilePath($id, 'current');
            file_put_contents($currentFilePath, '[]');
            
            // Erfolgsmeldung
            $this->addFlash('confirm', 'Alle News erfolgreich zurückgesetzt');
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Zurücksetzen: ' . $e->getMessage());
            $this->addFlash('error', 'Fehler beim Zurücksetzen: ' . $e->getMessage());
        }
        
        // Zurück zum Backend mit Timestamp für Cache-Busting
        return $this->redirect('/contao?do=caeli_googlenews&act=edit&id=' . $id . '&rt=' . time());
    }

    /**
     * Veröffentlicht mehrere ausgewählte News-Artikel
     */
    #[Route('/publish-multiple/{id<\d+>}', name: 'publish_multiple', methods: ['POST'])]
    public function publishMultipleAction(Request $request, int $id): Response
    {
        $this->framework->initialize();
        
        // CSRF-Token prüfen
        $csrfToken = $request->query->get('_token');
        
        // Wenn kein gültiges Token gefunden wurde
        if (!$this->isCsrfTokenValid('contao_csrf_token', $csrfToken)) {
            $this->addFlash('error', 'Sicherheitstoken ist ungültig.');
            return $this->redirect('/contao?do=caeli_googlenews&act=edit&id=' . $id);
        }
        
        // Ausgewählte Artikel-Indizes aus dem Formular holen
        $selectedIndices = $request->request->all()['selected'] ?? [];
        
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
                
                // News-Eintrag importieren
                if ($this->publishNewsItem($item, $configModel)) {
                    $successCount++;
                } else {
                    $errorCount++;
            }
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
        
        // Zurück zum Backend mit Cache-Busting
        return $this->redirect('/contao?do=caeli_googlenews&act=edit&id=' . $id . '&rt=' . time());
    }

    /**
     * Veröffentlicht alle News-Artikel
     */
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
                // News-Eintrag importieren
                if ($this->publishNewsItem($item, $configModel)) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
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
        
        // Zurück zum Backend mit Cache-Busting
        return $this->redirect('/contao?do=caeli_googlenews&act=edit&id=' . $id . '&rt=' . time());
    }
} 