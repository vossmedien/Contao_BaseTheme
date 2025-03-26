<?php

namespace CaeliWind\CaeliGoogleNewsFetch\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Service zum Abrufen von Google News über SerpAPI
 */
class GoogleNewsFeedService
{
    /**
     * @var HttpClientInterface
     */
    private HttpClientInterface $httpClient;
    
    /**
     * @var LoggerInterface|null
     */
    private ?LoggerInterface $logger;
    
    /**
     * Konstruktor
     */
    public function __construct(
        HttpClientInterface $httpClient,
        ?LoggerInterface $logger = null
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }
    
    /**
     * Abruf von Google News über SerpAPI
     * @param string $searchQuery Suchanfrage
     * @param string $apiKey SerpAPI-Schlüssel
     * @param int $numResults Maximale Anzahl Ergebnisse
     * @param string $location Standort für die Suche
     * @param string $language Sprache (Format: 'de')
     * @param array $filters Zusätzliche Filter wie 'dateRestrict', 'source' etc.
     * @param int $page Seitennummer für Pagination (1-basiert)
     * @return array|null Array mit Nachrichtenartikeln oder null bei Fehler
     */
    public function fetchNewsViaSerpApi(
        string $searchQuery, 
        string $apiKey, 
        int $numResults = 100, 
        string $location = "Germany", 
        string $language = "de",
        array $filters = [],
        int $page = 1
    ): ?array {
        $this->log('Starte News-Abruf via SerpAPI für: ' . $searchQuery . ' (Seite ' . $page . ')');
        
        // Domain entsprechend der Sprache anpassen
        $googleDomain = "google.de";
        if ($language === "en") {
            $googleDomain = "google.com";
        } elseif ($language === "fr") {
            $googleDomain = "google.fr";
        }
        
        // SerpAPI URL zusammenbauen
        $url = "https://serpapi.com/search.json?engine=google" .
               "&q=" . urlencode($searchQuery) .
               "&location=" . urlencode($location) .
               "&google_domain=" . $googleDomain .
               "&gl=" . strtoupper($language) .
               "&hl=" . $language .
               "&tbm=nws" .
               "&num=" . $numResults;
        
        // Pagination Parameter
        if ($page > 1) {
            $url .= "&start=" . (($page - 1) * $numResults);
        }
        
        // Filter anwenden
        if (!empty($filters)) {
            // Zeitraum-Filter
            if (!empty($filters['dateRestrict'])) {
                $url .= "&tbs=qdr:" . $filters['dateRestrict']; // h (Stunde), d (Tag), w (Woche), m (Monat), y (Jahr)
            }
            
            // Nachrichtentyp-Filter
            if (!empty($filters['newsType'])) {
                $url .= "&tbm=nws&tbs=cdr:1," . $filters['newsType'];
            }
            
            // Quellen-Filter
            if (!empty($filters['source'])) {
                $url .= "&as_sitesearch=" . urlencode($filters['source']);
            }
        }
        
        $url .= "&api_key=" . $apiKey;
        
        $this->log('SerpAPI URL: ' . $url);
        
        try {
            // SerpAPI API aufrufen
            $response = $this->httpClient->request('GET', $url, [
                'timeout' => 15,
                'max_duration' => 20,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
                ]
            ]);
            
            if ($response->getStatusCode() !== 200) {
                $this->log('HTTP-Fehler ' . $response->getStatusCode() . ' bei SerpAPI-Abfrage', 'error');
                return null;
            }
            
            $content = $response->getContent();
            $data = json_decode($content);
            
            if (!$data || !isset($data->news_results) || empty($data->news_results)) {
                $this->log('Keine News-Ergebnisse in SerpAPI-Antwort', 'error');
                return null;
            }
            
            $news = $data->news_results;
            $this->log(count($news) . ' Artikel via SerpAPI gefunden');
            
            // Ergebnisse in standardisiertes Format konvertieren
            $formattedNews = [];
            foreach ($news as $item) {
                // Datum in Timestamp umwandeln
                $pubDate = strtotime($item->date ?? 'now');
                
                // Bei SerpAPI sind die Snippets meist besser als in den RSS-Feeds
                $description = $item->snippet ?? $item->title;
                
                // News-Item zum Array hinzufügen
                $newsItem = [
                    'title' => $item->title,
                    'link' => $item->link,
                    'description' => $description,
                    'pubDate' => $pubDate,
                    'source' => $item->source,
                    'imageUrl' => $this->getBestImage($item),
                    'guid' => md5($item->link . '-' . $item->title), // Eigene GUID generieren
                    'keyword' => $searchQuery,
                    // Zusätzliche Metadaten
                    'position' => $item->position ?? null,
                    'date' => $item->date ?? null
                ];
                
                $formattedNews[] = $newsItem;
            }
            
            $this->log('Erfolgreich ' . count($formattedNews) . ' News-Artikel via SerpAPI verarbeitet');
            return $formattedNews;
            
        } catch (\Exception $e) {
            $this->log('Fehler beim SerpAPI-Aufruf: ' . $e->getMessage(), 'error');
            if ($this->logger) {
                $this->logger->error('SerpAPI-Fehler: ' . $e->getMessage(), [
                    'exception' => $e,
                    'query' => $searchQuery
                ]);
            }
            return null;
        }
    }
    
    /**
     * Schreibt Log-Nachrichten sowohl in die PHP-Fehlerprotokollierung als auch in den Logger
     */
    private function log(string $message, string $level = 'info'): void 
    {
        error_log('CaeliGoogleNewsFetch: ' . $message);
        
        if ($this->logger) {
            switch ($level) {
                case 'error':
                    $this->logger->error($message);
                            break;
                case 'warning':
                    $this->logger->warning($message);
                            break;
                default:
                    $this->logger->info($message);
            }
        }
    }

    /**
     * Extrahiert das beste verfügbare Bild aus den SerpAPI-Ergebnissen
     * 
     * SerpAPI bietet mehrere mögliche Bildquellen in verschiedenen Teilen der JSON-Antwort:
     * - thumbnail: Standard-Vorschaubild
     * - thumbnail_small: Kleineres Vorschaubild
     * - image: Hauptbild (wenn verfügbar)
     * - source.icon: Icon der Quelle (sehr klein)
     * - related_media: Kann größere Bilder enthalten
     * 
     * Wir extrahieren das beste Bild und bevorzugen größere Bilder.
     * 
     * @param object $item SerpAPI-Nachrichtenartikel
     * @return string|null URL des besten verfügbaren Bildes oder null, wenn kein Bild gefunden wurde
     */
    private function getBestImage($item): ?string 
    {
        // 1. Sammle alle verfügbaren Bilder mit ihrer erwarteten Größe (Schätzwert)
        $images = [];
        
        // Hauptbilder (priorisiert)
        if (!empty($item->thumbnail)) {
            $images['thumbnail'] = ['url' => $item->thumbnail, 'size' => 150]; // Standard-Thumbnail ~150px
        }
        
        if (!empty($item->thumbnail_small)) {
            $images['thumbnail_small'] = ['url' => $item->thumbnail_small, 'size' => 80]; // Kleineres Thumbnail ~80px
        }
        
        // Source-Icon (sehr klein, niedrige Priorität)
        if (isset($item->source) && isset($item->source->icon)) {
            $images['source_icon'] = ['url' => $item->source->icon, 'size' => 32]; // Icon ist meist sehr klein
        }
        
        // Author-Thumbnail (eher klein, niedrige Priorität)
        if (isset($item->author) && isset($item->author->thumbnail)) {
            $images['author_thumbnail'] = ['url' => $item->author->thumbnail, 'size' => 60]; // Author-Bilder oft klein
        }
        
        // Highlight-Bereich kann große Bilder enthalten
        if (isset($item->highlight) && isset($item->highlight->thumbnail)) {
            $images['highlight_thumbnail'] = ['url' => $item->highlight->thumbnail, 'size' => 200]; // Highlight-Bilder sind oft größer
        }
        
        // Stories können eigene Bilder haben
        if (isset($item->stories) && is_array($item->stories)) {
            foreach ($item->stories as $index => $story) {
                if (isset($story->thumbnail)) {
                    $images['story_' . $index . '_thumbnail'] = ['url' => $story->thumbnail, 'size' => 180];
                }
            }
        }
        
        // Related media kann größere Bilder enthalten
        if (isset($item->related_media) && is_array($item->related_media)) {
            foreach ($item->related_media as $index => $media) {
                if (isset($media->image)) {
                    $images['related_media_' . $index] = ['url' => $media->image, 'size' => 250]; // Oft größere Bilder
                }
            }
        }
        
        // Originalbild, wenn explizit verfügbar
        if (isset($item->image)) {
            $images['image'] = ['url' => $item->image, 'size' => 300]; // Hauptbild, hohe Priorität
        }
        
        // Nachrichtenbilder (oft größer)
        if (isset($item->news_results) && is_array($item->news_results)) {
            foreach ($item->news_results as $index => $newsItem) {
                if (isset($newsItem->thumbnail)) {
                    $images['news_' . $index . '_thumbnail'] = ['url' => $newsItem->thumbnail, 'size' => 280]; // News-Thumbnails sind oft größer
                }
                
                // Manchmal gibt es unterschiedliche Bildgrößen in SerpAPI
                if (isset($newsItem->original_thumbnail)) {
                    $images['news_' . $index . '_original'] = ['url' => $newsItem->original_thumbnail, 'size' => 350]; // Original-Bilder sind größer
                }
                
                if (isset($newsItem->large_thumbnail)) {
                    $images['news_' . $index . '_large'] = ['url' => $newsItem->large_thumbnail, 'size' => 400]; // Große Thumbnails sind bevorzugt
                }
            }
        }
        
        // Zusätzliche Prüfung auf generische Attribute wie images oder media_items
        if (isset($item->images) && is_array($item->images)) {
            foreach ($item->images as $index => $img) {
                if (is_string($img)) {
                    $images['image_direct_' . $index] = ['url' => $img, 'size' => 300];
                } elseif (is_object($img) && isset($img->url)) {
                    $images['image_object_' . $index] = ['url' => $img->url, 'size' => 300];
                    
                    // Wenn explizite Größenangaben vorhanden sind, verwenden wir diese für bessere Sortierung
                    if (isset($img->width) && isset($img->height)) {
                        // Berechne eine "Größenbewertung" basierend auf Breite und Höhe
                        $imgSize = $img->width * $img->height / 10000; // Normalisierung
                        $images['image_object_' . $index]['size'] = min(500, max(100, $imgSize)); // Begrenze zwischen 100 und 500
                    }
                }
            }
        }
        
        // Prüfe auf media_items (manchmal von SerpAPI verwendet)
        if (isset($item->media_items) && is_array($item->media_items)) {
            foreach ($item->media_items as $index => $media) {
                if (isset($media->url)) {
                    $images['media_' . $index] = ['url' => $media->url, 'size' => 320]; // Media-Items sind oft größer
                }
            }
        }
        
        // 2. Wenn keine Bilder gefunden wurden, return null
        if (empty($images)) {
            $this->log('Keine Bilder für Artikel gefunden: ' . ($item->title ?? 'Unbekannt'));
            return null;
        }
        
        // 3. Sortiere die Bilder nach geschätzter Größe (absteigend)
        uasort($images, function($a, $b) {
            return $b['size'] <=> $a['size']; // Sortiere nach Größe absteigend
        });
        
        // 4. Das erste Bild nach der Sortierung sollte das beste/größte sein
        $bestImage = reset($images);
        $bestImageKey = key($images);
        
        $this->log('Bestes Bild ausgewählt: ' . $bestImageKey . ' (erwartete Größe: ' . $bestImage['size'] . 'px) für Artikel: ' . ($item->title ?? 'Unbekannt'));
        
        return $bestImage['url'];
    }
} 