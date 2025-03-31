<?php

namespace CaeliWind\CaeliGoogleNewsFetch\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;
use Contao\Dbafs;
use Contao\FilesModel;
use Contao\System;
use Contao\CoreBundle\Framework\ContaoFramework;

/**
 * Service für die Bildverarbeitung und -extraktion
 */
class ImageService
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
     * @var string
     */
    private string $projectDir;
    
    /**
     * @var ContaoFramework
     */
    private ContaoFramework $framework;
    
    /**
     * Verschiedene User-Agents für Fallback-Strategien
     * @var array
     */
    private array $userAgents = [
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36 Edg/91.0.864.59',
        'Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1'
    ];
    
    /**
     * Konstruktor
     */
    public function __construct(
        HttpClientInterface $httpClient,
        string $projectDir,
        ContaoFramework $framework,
        ?LoggerInterface $logger = null
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->projectDir = $projectDir;
        $this->framework = $framework;
    }
    
    /**
     * Extrahiert das beste verfügbare Bild aus den SerpAPI-Ergebnissen
     * 
     * @param object $item SerpAPI-Nachrichtenartikel
     * @return string|null URL des besten verfügbaren Bildes oder null
     */
    public function getBestImage($item): ?string 
    {
        $newsTitle = $item->title ?? 'Unbekannt';
        
        // 1. Prüfen, ob es ein thumbnail gibt
        if (!empty($item->thumbnail)) {
            $thumbnailUrl = $item->thumbnail;
            
            // Wenn es eine SerpAPI-URL ist, versuchen wir größere Varianten
            if (strpos($thumbnailUrl, 'serpapi.com') !== false) {
                // Option 1: Direktes Ersetzen des Größenparameters
                if (strpos($thumbnailUrl, '=s92') !== false) {
                    $largeUrl = str_replace('=s92', '=s800', $thumbnailUrl);
                    return $largeUrl;
                }
                
                // Option 2: Versuche, das Original-Bild aus der Quell-URL zu extrahieren
                if (isset($item->link) && !empty($item->link)) {
                    $originalUrl = $this->extractImageUrlFromSource($item->link);
                    if ($originalUrl) {
                        return $originalUrl;
                    }
                }
                
                // Option 3: Umleitung verfolgen (manchmal führt SerpAPI zu größeren Bildern)
                try {
                    // Nur einen User-Agent verwenden statt mehrere zu versuchen
                    $response = $this->httpClient->request('HEAD', $thumbnailUrl, [
                        'max_redirects' => 5,
                        'timeout' => 5,
                        'headers' => [
                            'User-Agent' => $this->userAgents[0]
                        ]
                    ]);
                    
                    // Die finale URL nach Umleitungen ist möglicherweise ein größeres Bild
                    $finalUrl = $response->getInfo('url');
                    if ($finalUrl && $finalUrl !== $thumbnailUrl) {
                        return $finalUrl;
                    }
                } catch (\Exception $e) {
                    // Fehler leise ignorieren
                }
            }
            
            // Als Fallback verwenden wir das ursprüngliche Thumbnail
            return $thumbnailUrl;
        }
        
        // 2. Prüfen, ob es ein icon oder andere Quellen gibt
        if (isset($item->source) && isset($item->source->thumbnail)) {
            return $item->source->thumbnail;
        }
        
        if (isset($item->source) && isset($item->source->icon)) {
            return $item->source->icon;
        }
        
        // Keine Bilder gefunden
        return null;
    }
    
    /**
     * Versucht, ein Bild von der Quell-Website zu extrahieren
     * 
     * @param string $sourceUrl URL der Quell-Website
     * @return string|null Bild-URL oder null
     */
    public function extractImageUrlFromSource(string $sourceUrl): ?string
    {
        try {
            // Nur einen Versuch mit dem ersten User-Agent machen
            $response = $this->httpClient->request('GET', $sourceUrl, [
                'timeout' => 5,
                'max_redirects' => 3,
                'headers' => [
                    'User-Agent' => $this->userAgents[0],
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8'
                ]
            ]);
            
            if ($response->getStatusCode() !== 200) {
                return null;
            }
            
            $html = $response->getContent();
            
            // Open Graph Image (wird oft für große Featured Images verwendet)
            if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\'](https?:\/\/[^"\']+)["\'][^>]*>/i', $html, $matches)) {
                return $matches[1];
            }
            
            // Twitter Card Image (alternative zu OG)
            if (preg_match('/<meta[^>]+name=["\']twitter:image["\'][^>]+content=["\'](https?:\/\/[^"\']+)["\'][^>]*>/i', $html, $matches)) {
                return $matches[1];
            }
            
            // JSON-LD strukturierte Daten
            if (preg_match('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches)) {
                $jsonData = json_decode($matches[1], true);
                if ($jsonData && isset($jsonData['image'])) {
                    return is_array($jsonData['image']) ? $jsonData['image'][0] : $jsonData['image'];
                }
            }
            
            // Hero-Bereich / Featured Image Div
            if (preg_match('/<div[^>]+class=["\'][^"\']*hero[^"\']*["\'][^>]*>.*?<img[^>]+src=["\'](https?:\/\/[^"\']+)["\'][^>]*>/is', $html, $matches)) {
                return $matches[1];
            }
            
        } catch (\Exception $e) {
            // Fehler leise ignorieren
        }
        
        return null;
    }
    
    /**
     * Lädt ein Bild herunter und importiert es in die Contao-Dateiverwaltung
     * 
     * @param string $imageUrl URL des zu importierenden Bildes
     * @param int $newsId News-ID für die Benennung der Datei
     * @param string|null $fallbackUrl Optional: URL für Fallback-Bild (z.B. SerpAPI-Thumbnail)
     * @return string UUID des importierten Bildes oder leerer String bei Fehler
     */
    public function downloadAndImportImage(string $imageUrl, int $newsId = 0, ?string $fallbackUrl = null): string
    {
        // URL normalisieren, um unabhängig vom Protokoll zu sein
        $normalizedUrl = strtolower(preg_replace('#^https?://#', '', $imageUrl));
        
        // Spezielle Regel für problematische Domains und URL-Muster, die bekanntermaßen Zugriffsprobleme verursachen
        $problematicPatterns = [
            'handelsblatt.com',
            'channelizer.handelsblatt',
            'artikel.handelsblatt',
            'img.handelsblatt',
            'opengraph_default_logo',
            'formatOriginal.png'
        ];
        
        // Prüfen, ob die URL eine bekannte problematische Domain oder ein problematisches Muster enthält
        foreach ($problematicPatterns as $pattern) {
            if (strpos($normalizedUrl, $pattern) !== false) {
                $this->log("Bekannte problematische Domain/Muster entdeckt in URL: " . $imageUrl, 'warning');
                
                // Wenn ein Fallback vorhanden ist, direkt diesen verwenden
                if ($fallbackUrl && $fallbackUrl !== $imageUrl) {
                    $this->log("Verwende stattdessen SerpAPI-Bild: " . $fallbackUrl, 'warning');
                    return $this->tryDownloadImage($fallbackUrl, $newsId);
                }
                
                return ''; // Kein Fallback verfügbar
            }
        }
        
        // Zuerst prüfen, ob das Hauptbild zugänglich ist
        if (!$this->isImageAccessible($imageUrl)) {
            $this->log("Bild nicht zugänglich (forbidden/unauthorized): " . $imageUrl, 'warning');
            
            // Wenn ein Fallback vorhanden ist, direkt diesen verwenden
            if ($fallbackUrl && $fallbackUrl !== $imageUrl) {
                $this->log("Verwende Fallback-Bild: " . $fallbackUrl, 'warning');
                if ($this->isImageAccessible($fallbackUrl)) {
                    return $this->tryDownloadImage($fallbackUrl, $newsId);
                } else {
                    $this->log("Auch Fallback-Bild nicht zugänglich: " . $fallbackUrl, 'error');
                    return '';
                }
            }
            
            return '';
        }
        
        // Hauptbild ist zugänglich, versuchen herunterzuladen
        $result = $this->tryDownloadImage($imageUrl, $newsId);
        
        // Wenn der Download trotzdem fehlschlägt und ein Fallback vorhanden ist
        if ($result === '' && $fallbackUrl && $fallbackUrl !== $imageUrl) {
            $this->log("Hauptbild konnte nicht heruntergeladen werden, versuche Fallback: " . $fallbackUrl, 'warning');
            if ($this->isImageAccessible($fallbackUrl)) {
                $result = $this->tryDownloadImage($fallbackUrl, $newsId);
            }
        }
        
        return $result;
    }
    
    /**
     * Prüft, ob ein Bild zugänglich ist (keine 403, 401, etc. Fehler)
     * 
     * @param string $url Die zu prüfende Bild-URL
     * @return bool True wenn das Bild zugänglich ist, sonst false
     */
    private function isImageAccessible(string $url): bool
    {
        try {
            // Wir verwenden alle User-Agents für mehrere Versuche
            foreach ($this->userAgents as $userAgent) {
                try {
                    $response = $this->httpClient->request('HEAD', $url, [
                        'timeout' => 3,
                        'max_redirects' => 3,
                        'headers' => [
                            'User-Agent' => $userAgent,
                            'Accept' => 'image/*',
                            'Referer' => parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST) . '/'
                        ]
                    ]);
                    
                    $statusCode = $response->getStatusCode();
                    
                    // 2xx Status-Codes bedeuten Erfolg
                    if ($statusCode >= 200 && $statusCode < 300) {
                        // Überprüfen, ob es sich wirklich um ein Bild handelt
                        $contentType = $response->getHeaders()['content-type'][0] ?? '';
                        if (strpos($contentType, 'image/') === 0) {
                            return true;
                        }
                        
                        // Kein Bildformat erkannt
                        $this->log("URL liefert keinen Bild-Content-Type: " . $url . " (Typ: " . $contentType . ")", 'warning');
                    }
                    
                    // Fehler protokollieren
                    if ($statusCode == 403 || $statusCode == 401) {
                        $this->log("Zugriff auf Bild verweigert (HTTP " . $statusCode . "): " . $url, 'warning');
                    } else if ($statusCode >= 400) {
                        $this->log("Fehler beim Zugriff auf Bild (HTTP " . $statusCode . "): " . $url, 'warning');
                    }
                } catch (\Exception $e) {
                    // Fehler protokollieren, aber mit nächstem User-Agent fortfahren
                    continue;
                }
            }
            
            // Wenn wir hierher kommen, war kein Zugriff erfolgreich
            return false;
        } catch (\Exception $e) {
            $this->log("Fehler bei der Bildvalidierung: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Versucht, ein Bild herunterzuladen und zu importieren
     * 
     * @param string $imageUrl URL des zu importierenden Bildes
     * @param int $newsId News-ID für die Benennung der Datei
     * @return string UUID des importierten Bildes oder leerer String bei Fehler
     */
    private function tryDownloadImage(string $imageUrl, int $newsId = 0): string
    {
        try {
            // Versuche mit verschiedenen User-Agents, falls einer blockiert wird
            $exceptions = [];
            $imageContent = null;
            
            foreach ($this->userAgents as $userAgent) {
                try {
                    $response = $this->httpClient->request('GET', $imageUrl, [
                        'headers' => [
                            'User-Agent' => $userAgent,
                            'Accept' => 'image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
                            'Referer' => parse_url($imageUrl, PHP_URL_SCHEME) . '://' . parse_url($imageUrl, PHP_URL_HOST) . '/'
                        ],
                        'max_duration' => 5
                    ]);
                    
                    if ($response->getStatusCode() === 200) {
                        $imageContent = $response->getContent();
                        break;
                    }
                } catch (\Exception $e) {
                    $exceptions[] = $e->getMessage();
                    // Weiterversuchen mit dem nächsten User-Agent
                    continue;
                }
            }
            
            // Falls der Bild-Download fehlgeschlagen ist
            if (!$imageContent) {
                $errorMessage = "Bild konnte nicht heruntergeladen werden: " . $imageUrl;
                if (!empty($exceptions)) {
                    $errorMessage .= " - Fehler: " . implode(", ", array_unique($exceptions));
                }
                $this->log($errorMessage, 'error');
                return '';
            }
            
            // Temporäres Verzeichnis für Downloads
            $tempDir = $this->projectDir . '/system/tmp';
            if (!is_dir($tempDir)) {
                if (!mkdir($tempDir, 0755, true)) {
                    $this->log("Konnte temporäres Verzeichnis nicht erstellen: " . $tempDir, 'error');
                    return '';
                }
            }
            
            // Eindeutigen Dateinamen generieren
            $actualNewsId = $newsId > 0 ? $newsId : mt_rand(1000, 9999);
            $fileName = 'news_' . $actualNewsId . '_' . md5($imageUrl . time()) . '.jpg';
            $tempFilePath = $tempDir . '/' . $fileName;
            
            // Bild in temporäre Datei speichern
            if (!file_put_contents($tempFilePath, $imageContent)) {
                $this->log("Konnte Bild nicht in temporäre Datei speichern: " . $tempFilePath, 'error');
                return '';
            }
            
            // Prüfen, ob Datei erfolgreich gespeichert wurde und valide ist
            if (!file_exists($tempFilePath) || filesize($tempFilePath) === 0) {
                @unlink($tempFilePath); // Leere Datei löschen
                $this->log("Temporäre Bilddatei ist leer oder existiert nicht: " . $tempFilePath, 'error');
                return '';
            }
            
            // Prüfen, ob es sich um ein gültiges Bild handelt
            $imageInfo = @getimagesize($tempFilePath);
            if ($imageInfo === false) {
                @unlink($tempFilePath);
                $this->log("Heruntergeladene Datei ist kein gültiges Bild: " . $imageUrl, 'error');
                return '';
            }
            
            try {
                // Zielordner in der Contao-Dateiverwaltung
                $uploadFolder = 'files/news';
                $uploadPath = $this->projectDir . '/' . $uploadFolder;
                
                // Stellen Sie sicher, dass der Zielordner existiert
                if (!is_dir($uploadPath)) {
                    if (!mkdir($uploadPath, 0755, true)) {
                        @unlink($tempFilePath);
                        $this->log("Konnte Zielordner nicht erstellen: " . $uploadPath, 'error');
                        return '';
                    }
                }
                
                // Datei in den Zielordner kopieren
                $targetFile = $uploadFolder . '/' . $fileName;
                $targetPath = $this->projectDir . '/' . $targetFile;
                
                if (!copy($tempFilePath, $targetPath)) {
                    @unlink($tempFilePath);
                    $this->log("Konnte Bild nicht in Zielordner kopieren: " . $targetPath, 'error');
                    return '';
                }
                
                // Temporäre Datei löschen
                @unlink($tempFilePath);
                
                // Datei in die DBAFS eintragen
                $dbafs = $this->framework->getAdapter(Dbafs::class);
                $fileModel = $dbafs->addResource($targetFile);
                
                if (!$fileModel) {
                    // Aufräumen
                    @unlink($targetPath);
                    $this->log("Konnte Bild nicht in DBAFS eintragen: " . $targetFile, 'error');
                    return '';
                }
                
                return $fileModel->uuid;
            } catch (\Exception $e) {
                // Temporäre Datei löschen, falls noch vorhanden
                if (file_exists($tempFilePath)) {
                    @unlink($tempFilePath);
                }
                
                $this->log("Fehler beim Import des Bildes: " . $e->getMessage(), 'error');
                return '';
            }
        } catch (\Exception $e) {
            $this->log("Allgemeiner Fehler beim Bild-Import: " . $e->getMessage(), 'error');
            return '';
        }
    }
    
    /**
     * Hilfsfunktion für Logging - stark reduziert
     */
    private function log(string $message, string $level = 'error'): void
    {
        // Nur Fehler in error_log schreiben, keine Info-Meldungen
        if ($level === 'error') {
            error_log('CaeliGoogleNewsFetch [ImageService]: ' . $message);
        }
        
        // Nur Fehler und Warnungen an den Logger weitergeben
        if ($this->logger && ($level === 'error' || $level === 'warning')) {
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