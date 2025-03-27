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
     * @var ImageService
     */
    private ImageService $imageService;
    
    /**
     * Konstruktor
     */
    public function __construct(
        HttpClientInterface $httpClient,
        ImageService $imageService,
        ?LoggerInterface $logger = null
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->imageService = $imageService;
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
        // Log nur bei Fehlern, nicht für normale Abläufe

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
               "&nfpr=1" .  // Aktiviert "exakte Treffer", was zu größeren Bildern führen kann
               "&safe=active" . // Sicheren Modus aktivieren
               "&output=json" . // Explizites JSON-Format anfordern 
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
            
            // Keine vollständige JSON-Antwort mehr loggen
            
            if (!$data || !isset($data->news_results) || empty($data->news_results)) {
                $this->log('Keine News-Ergebnisse in SerpAPI-Antwort', 'error');
                return null;
            }
            
            $news = $data->news_results;
            
            // Ergebnisse in standardisiertes Format konvertieren
            $formattedNews = [];
            foreach ($news as $item) {
                // Datum in Timestamp umwandeln
                $pubDate = strtotime($item->date ?? 'now');
                
                // Bei SerpAPI sind die Snippets meist besser als in den RSS-Feeds
                $description = $item->snippet ?? $item->title;
                
                // Original-Thumbnail für Fallback speichern (dies ist wichtig für die Fallback-Strategie)
                $thumbnail = $item->thumbnail ?? null;
                
                // Besten verfügbaren Bild-URL ermitteln
                $selectedImage = $this->imageService->getBestImage($item);
                
                // Wenn kein besseres Bild gefunden wurde, nutze das Thumbnail als Hauptbild
                if (!$selectedImage) {
                    $selectedImage = $thumbnail;
                }
                
                // WICHTIG: Handelsblatt-URLs direkt ersetzen
                // Prüfen, ob die URL problematische Handelsblatt-Domains enthält - Fall-insensitive Prüfung
                if ($selectedImage && (
                    stripos($selectedImage, 'handelsblatt.com') !== false || 
                    stripos($selectedImage, 'channelizer.handelsblatt') !== false ||
                    stripos($selectedImage, 'opengraph_default_logo') !== false ||
                    stripos($selectedImage, 'formatOriginal.png') !== false
                )) {
                    // Direktes Ersetzen mit dem Thumbnail aus SerpAPI
                    $this->log("Handelsblatt-Bild erkannt und ersetzt: " . $selectedImage, 'warning');
                    $selectedImage = $thumbnail;
                }
                
                // News-Item zum Array hinzufügen
                $newsItem = [
                    'title' => $item->title,
                    'link' => $item->link,
                    'description' => $description,
                    'pubDate' => $pubDate,
                    'source' => $item->source,
                    'imageUrl' => $selectedImage,
                    'thumbnail' => $thumbnail, // Original-Thumbnail für Fallback-Zwecke, immer speichern
                    'guid' => md5($item->link . '-' . $item->title), // Eigene GUID generieren
                    'keyword' => $searchQuery,
                    // Zusätzliche Metadaten
                    'position' => $item->position ?? null,
                    'date' => $item->date ?? null
                ];
                
                $formattedNews[] = $newsItem;
            }
            
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
     * Schreibt Log-Nachrichten nur bei Fehlern
     */
    private function log(string $message, string $level = 'error'): void 
    {
        // Nur Fehler und Warnungen loggen
        if ($level === 'error') {
            error_log('CaeliGoogleNewsFetch: ' . $message);
        }
        
        if ($this->logger) {
            switch ($level) {
                case 'error':
                    $this->logger->error($message);
                            break;
                case 'warning':
                    $this->logger->warning($message);
                            break;
            }
        }
    }
} 