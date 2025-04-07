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
     * Entfernt Duplikate aus der übergebenen Liste von News-Items.
     * Items mit gleicher URL oder Titel werden nur einmal behalten.
     */
    protected function removeDuplicates(array $items): array
    {
        $urls = [];
        $titles = [];
        $filtered = [];

        foreach ($items as $item) {
            $isNewItem = true;

            // URL-Prüfung für Duplikate
            if (!empty($item['link'])) {
                $normalizedUrl = $this->normalizeUrl($item['link']);
                if (in_array($normalizedUrl, $urls)) {
                    $isNewItem = false;
                } else {
                    $urls[] = $normalizedUrl;
                }
            }

            // Titel-Prüfung als Fallback
            if ($isNewItem && !empty($item['title'])) {
                $title = strtolower(trim($item['title']));
                if (in_array($title, $titles)) {
                    $isNewItem = false;
                } else {
                    $titles[] = $title;
                }
            }

            // Wenn das Item einzigartig ist, behalten
            if ($isNewItem) {
                $filtered[] = $item;
            }
        }

        return $filtered;
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
     * Veröffentlicht einen einzelnen News-Artikel mit verbessertem Logging
     */
    #[Route('/publish/{id<\d+>}/{index<\d+>}', name: 'publish_news', methods: ['GET'])]
    public function publishNewsAction(Request $request, int $id, int $index): Response
    {
        // Ausführliches Logging für AJAX-Anfragen aktivieren
        error_log('CaeliGoogleNewsFetch [START]: Artikel-Veröffentlichung gestartet - ID ' . $id . ', Index ' . $index);

        $this->framework->initialize();

        // Konfiguration abrufen
        $configModel = CaeliGooglenewsModel::findById($id);

        if (null === $configModel) {
            error_log('CaeliGoogleNewsFetch [ERROR]: Konfiguration nicht gefunden');
            $this->addFlash('error', 'Konfiguration nicht gefunden');
            return $this->redirectToContaoBackend($id);
        }

        // Prüfen, ob ein News-Archiv ausgewählt ist
        if (empty($configModel->newsArchive) || $configModel->newsArchive < 1) {
            error_log('CaeliGoogleNewsFetch [ERROR]: Kein News-Archiv ausgewählt');
            $this->addFlash('error', 'Kein News-Archiv ausgewählt');
            return $this->redirectToContaoBackend($id);
        }

        try {
            // Aktiver Benutzer-ID ermitteln
            $authorId = $this->getBackendUserId();
            error_log('CaeliGoogleNewsFetch [INFO]: Aktiver Backend-Benutzer: ID ' . $authorId);

            // Prüfen, ob die Artikel aus dem Archiv oder der aktuellen Liste kommen
            $isArchiveSource = $request->query->has('source') && $request->query->get('source') === 'archive';
            $sourceType = $isArchiveSource ? 'archived' : 'current';

            error_log('CaeliGoogleNewsFetch [INFO]: Quelle der News: ' . $sourceType);

            // News-Items aus JSON-Datei laden
            $newsItems = $this->loadNewsFromJsonFile($id, $sourceType);

            if (empty($newsItems)) {
                error_log('CaeliGoogleNewsFetch [ERROR]: Keine News-Items gefunden in JSON-Datei');
                $this->addFlash('error', 'Keine News-Artikel gefunden');
                return $this->redirectToContaoBackend($id);
            }

            if (!isset($newsItems[$index])) {
                error_log('CaeliGoogleNewsFetch [ERROR]: News-Item mit Index ' . $index . ' nicht gefunden');
                $this->addFlash('error', 'News-Artikel mit Index ' . $index . ' nicht gefunden');
                return $this->redirectToContaoBackend($id);
            }

            // Das ausgewählte News-Item
            $item = $newsItems[$index];
            error_log('CaeliGoogleNewsFetch [INFO]: Verarbeite News-Artikel: ' . ($item['title'] ?? 'Ohne Titel'));

            // Prüfen, ob der Artikel bereits importiert wurde
            $db = $this->connection;
            $existingArticles = $db->fetchAllAssociative(
                "SELECT id FROM tl_news WHERE pid = ? AND url = ?",
                [
                    $configModel->newsArchive,
                    $item['link'] ?? ''
                ]
            );

            if (!empty($existingArticles)) {
                error_log('CaeliGoogleNewsFetch [INFO]: Artikel existiert bereits in der Datenbank (übersprungen)');
                $this->addFlash('info', 'Artikel existiert bereits und wurde als verarbeitet markiert');
                return $this->redirectToContaoBackend($id);
            }

            // News-Eintrag im Contao News-Modul erstellen
            $newsModel = new \Contao\NewsModel();
            $newsModel->tstamp = time();
            $newsModel->pid = $configModel->newsArchive;
            $newsModel->headline = html_entity_decode($item['title'] ?? '', ENT_QUOTES, 'UTF-8');
            $newsModel->alias = StringUtil::generateAlias($newsModel->headline);

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

            // Author explizit setzen
            $newsModel->author = $authorId;
            error_log('CaeliGoogleNewsFetch [INFO]: Backend-Benutzer-ID ' . $authorId . ' als Autor gesetzt');

            // Markieren als aus Google News importiert mit benutzerdefiniertem Feld
            $newsModel->caeli_googlenews_imported = '1';

            // Speichern
            $newsModel->save();
            error_log('CaeliGoogleNewsFetch [SUCCESS]: News-Artikel gespeichert, ID: ' . $newsModel->id);

            // Bild-Import, wenn vorhanden
            if (!empty($item['imageUrl'])) {
                error_log('CaeliGoogleNewsFetch [INFO]: Versuche Bild zu importieren: ' . $item['imageUrl']);

                // Hole den SerpAPI-Thumbnail als Fallback
                $fallbackUrl = $item['thumbnail'] ?? null;

                // Nutze den Fallback-Parameter für das SerpAPI-Thumbnail
                $imageId = $this->imageService->downloadAndImportImage($item['imageUrl'], $newsModel->id, $fallbackUrl);
                if ($imageId) {
                    $newsModel->addImage = '1';
                    $newsModel->singleSRC = $imageId;
                    $newsModel->save(); // Erneut speichern mit dem Bild
                    error_log('CaeliGoogleNewsFetch [SUCCESS]: Bild erfolgreich importiert: ' . $imageId);
                } else {
                    error_log('CaeliGoogleNewsFetch [WARNING]: Konnte Bild nicht importieren');
                }
            }

            error_log('CaeliGoogleNewsFetch [INFO]: Artikel als veröffentlicht markiert in JSON');

            // Erfolgs-Log
            error_log('CaeliGoogleNewsFetch [SUCCESS]: News-Artikel erfolgreich veröffentlicht');
            $this->addFlash('success', 'News-Artikel erfolgreich veröffentlicht');

            return $this->redirectToContaoBackend($id);
        } catch (\Exception $e) {
            error_log('CaeliGoogleNewsFetch [CRITICAL]: Fehler bei Veröffentlichung: ' . $e->getMessage() . "\n" . $e->getTraceAsString());

            if ($this->logger) {
                $this->logger->error('Fehler beim Veröffentlichen des News-Artikels: ' . $e->getMessage(), [
                    'exception' => $e,
                    'trace' => $e->getTraceAsString()
                ]);
            }

            $this->addFlash('error', 'Fehler beim Veröffentlichen: ' . $e->getMessage());
            return $this->redirectToContaoBackend($id);
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
     * Veröffentlicht einen News-Eintrag
     */
    protected function publishNewsItem(array $item, $configModel): bool
    {
        error_log('CaeliGoogleNewsFetch: publishNewsItem aufgerufen');
        try {
            // Author-ID vor der Erstellung ermitteln
            $authorId = $this->getBackendUserId();
            error_log('CaeliGoogleNewsFetch: [publishNewsItem] Backend-Benutzer-ID: ' . $authorId);

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

            // Author explizit setzen - WICHTIG!
            $news->author = $authorId;
            error_log('CaeliGoogleNewsFetch: [publishNewsItem] Setze author auf: ' . $authorId);

            // Markieren als aus Google News importiert mit benutzerdefiniertem Feld
            $news->caeli_googlenews_imported = '1';

            // Speichern vor dem Bild-Import
            $news->save();
            error_log('CaeliGoogleNewsFetch: [publishNewsItem] News-Artikel gespeichert, ID: ' . $news->id);

            // Bild herunterladen und setzen, falls vorhanden
            if (!empty($item['imageUrl'])) {
                error_log('CaeliGoogleNewsFetch: [publishNewsItem] Versuche Bild zu importieren: ' . $item['imageUrl']);

                // Hole den SerpAPI-Thumbnail als Fallback
                $fallbackUrl = $item['thumbnail'] ?? null;

                // Nutze den Fallback-Parameter für das SerpAPI-Thumbnail
                $imageId = $this->imageService->downloadAndImportImage($item['imageUrl'], $news->id, $fallbackUrl);
                if ($imageId) {
                    $news->addImage = '1';
                    $news->singleSRC = $imageId;
                    $news->save(); // Erneut speichern mit dem Bild
                    error_log('CaeliGoogleNewsFetch: [publishNewsItem] Bild erfolgreich importiert: ' . $imageId);
                } else {
                    error_log('CaeliGoogleNewsFetch: [publishNewsItem] Konnte weder Hauptbild noch Fallback-Bild importieren');
                }
            }

            // Nochmals explizit speichern
            $news->save();

            // Erfolgs-Log
            error_log('CaeliGoogleNewsFetch: [publishNewsItem] Artikel erfolgreich importiert: ' . $news->headline . ' [ID: ' . $news->id . ']');

            return true;
        } catch (\Exception $e) {
            error_log('CaeliGoogleNewsFetch: [publishNewsItem] Fehler beim Veröffentlichen: ' . $e->getMessage());
            if ($this->logger) {
                $this->logger->error('Fehler beim Veröffentlichen: ' . $e->getMessage());
            }

            throw $e;
        }
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
     * Ermittelt die ID des aktuell angemeldeten Backend-Benutzers
     */
    private function getBackendUserId(): int
    {
        // Initialisiere das Framework für Contao-Zugriffe
        $this->framework->initialize();

        try {
            // Über Contao Backend User Objekt den aktuellen Benutzer ermitteln
            $backendUser = $this->framework->getAdapter(\Contao\BackendUser::class)->getInstance();

            if ($backendUser && $backendUser->id > 0) {
                error_log('CaeliGoogleNewsFetch [INFO]: Backend-Benutzer gefunden: ID ' . $backendUser->id);
                return (int)$backendUser->id;
            }

            // Alternativ über die Datenbank den aktiven Benutzer ermitteln
            $db = $this->connection;
            $activeUser = $db->fetchAssociative(
                "SELECT id FROM tl_user WHERE disable='' AND (start='' OR start<=?) AND (stop='' OR stop>?) ORDER BY lastLogin DESC LIMIT 1",
                [time(), time()]
            );

            if ($activeUser && isset($activeUser['id'])) {
                error_log('CaeliGoogleNewsFetch [INFO]: Backend-Benutzer über DB gefunden: ID ' . $activeUser['id']);
                return (int)$activeUser['id'];
            }

            // Wenn kein Benutzer gefunden wurde, Fallback auf den ersten Admin-Benutzer
            $adminUser = $db->fetchAssociative(
                "SELECT id FROM tl_user WHERE admin='1' AND disable='' ORDER BY id ASC LIMIT 1"
            );

            if ($adminUser && isset($adminUser['id'])) {
                error_log('CaeliGoogleNewsFetch [WARNING]: Fallback auf Admin-Benutzer: ID ' . $adminUser['id']);
                return (int)$adminUser['id'];
            }

            // Als letztes Resort, den ersten aktiven Benutzer verwenden
            $anyUser = $db->fetchAssociative(
                "SELECT id FROM tl_user WHERE disable='' ORDER BY id ASC LIMIT 1"
            );

            if ($anyUser && isset($anyUser['id'])) {
                error_log('CaeliGoogleNewsFetch [WARNING]: Fallback auf ersten aktiven Benutzer: ID ' . $anyUser['id']);
                return (int)$anyUser['id'];
            }
        } catch (\Exception $e) {
            error_log('CaeliGoogleNewsFetch [ERROR]: Fehler beim Ermitteln der Backend-Benutzer-ID: ' . $e->getMessage());
        }

        // Wenn alles fehlschlägt, den Admin-Account mit ID 1 verwenden
        error_log('CaeliGoogleNewsFetch [WARNING]: Fallback auf Standard-Admin (ID 1)');
        return 1;
    }

    /**
     * Veröffentlicht mehrere News-Artikel auf einmal
     */
    #[Route('/publishMultiple/{id<\d+>}', name: 'publish_multiple_news', methods: ['POST'])]
    public function publishMultipleAction(Request $request, int $id): Response
    {
        $this->framework->initialize();

        // Konfiguration abrufen
        $configModel = CaeliGooglenewsModel::findById($id);

        if (null === $configModel) {
            $this->addFlash('error', 'Konfiguration nicht gefunden');
            return $this->redirectToContaoBackend($id);
        }

        if (empty($configModel->newsArchive) || $configModel->newsArchive < 1) {
            $this->addFlash('error', 'Kein News-Archiv ausgewählt');
            return $this->redirectToContaoBackend($id);
        }

        // Indizes der zu veröffentlichenden News-Items aus dem POST-Request holen
        $indices = $request->request->all('items') ?? [];
        $sourceType = $request->request->get('source') === 'archive' ? 'archived' : 'current';

        if (empty($indices)) {
            $this->addFlash('error', 'Keine News-Artikel ausgewählt');
            return $this->redirectToContaoBackend($id);
        }

        // News-Items aus JSON-Datei laden
        $newsItems = $this->loadNewsFromJsonFile($id, $sourceType);

        if (empty($newsItems)) {
            $this->addFlash('error', 'Keine News-Artikel gefunden');
            return $this->redirectToContaoBackend($id);
        }

        // Autor ermitteln
        $authorId = $this->getBackendUserId();

        // Ausgewählte Items verarbeiten
        $successCount = 0;
        $failureCount = 0;

        // Für jeden ausgewählten Index den entsprechenden Artikel importieren
        foreach ($indices as $index) {
            if (!isset($newsItems[$index])) {
                $failureCount++;
                continue;
            }

            $item = $newsItems[$index];

            try {
                // Prüfen, ob der Artikel bereits existiert
                $db = $this->connection;
                $existingArticles = $db->fetchAllAssociative(
                    "SELECT id FROM tl_news WHERE pid = ? AND url = ?",
                    [
                        $configModel->newsArchive,
                        $item['link'] ?? ''
                    ]
                );

                if (!empty($existingArticles)) {
                    // Artikel bereits importiert, NICHTS tun (kein 'published' Flag mehr setzen)
                    continue;
                }

                // News-Eintrag im Contao News-Modul erstellen
                $newsModel = new \Contao\NewsModel();
                $newsModel->tstamp = time();
                $newsModel->pid = $configModel->newsArchive;
                $newsModel->headline = html_entity_decode($item['title'] ?? '', ENT_QUOTES, 'UTF-8');
                $newsModel->alias = StringUtil::generateAlias($newsModel->headline);

                // Veröffentlichungsdetails
                if (!empty($item['pubDate'])) {
                    try {
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

                // Author setzen
                $newsModel->author = $authorId;

                // Markieren als aus Google News importiert
                $newsModel->caeli_googlenews_imported = '1';

                // Speichern
                $newsModel->save();

                // Bild-Import, wenn vorhanden
                if (!empty($item['imageUrl'])) {
                    // Hole den SerpAPI-Thumbnail als Fallback
                    $fallbackUrl = $item['thumbnail'] ?? null;

                    $imageId = $this->imageService->downloadAndImportImage($item['imageUrl'], $newsModel->id, $fallbackUrl);
                    if ($imageId) {
                        $newsModel->addImage = '1';
                        $newsModel->singleSRC = $imageId;
                        $newsModel->save(); // Erneut speichern mit dem Bild
                    }
                }

                $successCount++;
            } catch (\Exception $e) {
                if ($this->logger) {
                    $this->logger->error('Fehler beim Veröffentlichen eines News-Artikels: ' . $e->getMessage(), [
                        'exception' => $e
                    ]);
                }
                $failureCount++;
            }
        }

        // JSON-Dateien aktualisieren - NUR wenn etwas erfolgreich war?
        // Überlegung: Soll die JSON überhaupt noch gespeichert werden?
        // Da 'published' entfernt wurde, ist das Speichern hier evtl. nicht mehr sinnvoll.
        // Vorerst auskommentiert:
        // $this->saveNewsToJsonFile($id, $sourceType, $newsItems);

        // Benachrichtigung an Benutzer
        if ($successCount > 0) {
            $this->addFlash('success', $successCount . ' News-Artikel erfolgreich veröffentlicht');
        }

        if ($failureCount > 0) {
            $this->addFlash('error', $failureCount . ' News-Artikel konnten nicht veröffentlicht werden');
        }

        return $this->redirectToContaoBackend($id);
    }
}
