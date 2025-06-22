<?php

declare(strict_types=1);

namespace CaeliWind\CaeliAreaCheckBundle\Service;

use Psr\Log\LoggerInterface;

/**
 * Service für die Generierung von Kartenbildern mit eingezeichneten Polygonen
 * für erfolgreiche Flächenchecks. Speichert die Bilder als WebP im Dateisystem.
 */
class MapImageGeneratorService
{
    private string $googleMapsApiKey;
    private string $targetDirectory;
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger, string $projectDir)
    {
        $this->logger = $logger;
        $this->googleMapsApiKey = getenv('GOOGLE_MAPS_API_KEY') ?: ($_ENV['GOOGLE_MAPS_API_KEY'] ?? '');
        
        // Contao-spezifisches files/ Verzeichnis
        $this->targetDirectory = $projectDir . '/files/area-check';
        
        // Verzeichnis erstellen falls nicht vorhanden
        if (!is_dir($this->targetDirectory)) {
            if (!mkdir($this->targetDirectory, 0755, true)) {
                throw new \RuntimeException('Konnte Verzeichnis nicht erstellen: ' . $this->targetDirectory);
            }
        }
    }

    /**
     * Generiert und speichert ein Kartenbild mit Polygon für einen Flächencheck
     * 
     * @param string $parkId Die Park-ID (UUID) für den Dateinamen
     * @param string $geometryJson JSON-String der Polygon-Koordinaten
     * @param bool $isSuccess Ob der Flächencheck erfolgreich war
     * @return string|null Pfad zur gespeicherten Datei oder null bei Fehler
     */
    public function generateAndSaveMapImage(string $parkId, string $geometryJson, bool $isSuccess): ?string
    {
        try {
            $this->logger->info('[MapImageGenerator] === BILDGENERIERUNG GESTARTET ===');
            $this->logger->info('[MapImageGenerator] Park ID: ' . $parkId);
            $this->logger->info('[MapImageGenerator] Is Success: ' . ($isSuccess ? 'true' : 'false'));
            $this->logger->info('[MapImageGenerator] API Key vorhanden: ' . (empty($this->googleMapsApiKey) ? 'NEIN' : 'JA'));
            $this->logger->info('[MapImageGenerator] Target Directory: ' . $this->targetDirectory);
            $this->logger->info('[MapImageGenerator] System verfügbar: ' . ($this->isAvailable() ? 'JA' : 'NEIN'));
            
            if (empty($this->googleMapsApiKey)) {
                $this->logger->warning('[MapImageGenerator] Google Maps API Key fehlt - Bildgenerierung übersprungen');
                return null;
            }

            if (!$isSuccess) {
                $this->logger->debug('[MapImageGenerator] Flächencheck nicht erfolgreich - Bildgenerierung übersprungen für Park: ' . $parkId);
                return null;
            }
            
            if (!$this->isAvailable()) {
                $this->logger->error('[MapImageGenerator] System nicht verfügbar - Bildgenerierung übersprungen');
                return null;
            }

            // Polygon-Koordinaten aus Geometry extrahieren
            $this->logger->info('[MapImageGenerator] Geometry JSON (ersten 200 Zeichen): ' . substr($geometryJson, 0, 200));
            $coordinates = $this->extractPolygonCoordinates($geometryJson);
            $this->logger->info('[MapImageGenerator] Extrahierte Koordinaten: ' . count($coordinates) . ' Punkte');
            if (empty($coordinates)) {
                $this->logger->warning('[MapImageGenerator] Keine gültigen Polygon-Koordinaten gefunden für Park: ' . $parkId);
                return null;
            }

            // Optimalen Kartenausschnitt berechnen
            $mapConfig = $this->calculateOptimalMapConfig($coordinates);
            $this->logger->info('[MapImageGenerator] Map Config: ' . json_encode($mapConfig));
            
            // Google Maps Static API URL erstellen
            $staticMapUrl = $this->buildStaticMapUrl($coordinates, $mapConfig);
            $this->logger->info('[MapImageGenerator] Static Map URL: ' . $staticMapUrl);
            
            // Bild herunterladen
            $this->logger->info('[MapImageGenerator] Starte Download...');
            $imageData = $this->downloadImage($staticMapUrl);
            if (!$imageData) {
                $this->logger->error('[MapImageGenerator] Konnte Bild nicht herunterladen für Park: ' . $parkId);
                return null;
            }
            $this->logger->info('[MapImageGenerator] Bild erfolgreich heruntergeladen, Größe: ' . strlen($imageData) . ' Bytes');

            // Als PNG speichern
            $this->logger->info('[MapImageGenerator] Starte PNG-Speicherung...');
            $filePath = $this->saveImageAsPng($parkId, $imageData);
            
            if ($filePath) {
                $this->logger->info('[MapImageGenerator] Kartenbild erfolgreich gespeichert für Park: ' . $parkId . ' -> ' . $filePath);
            }

            return $filePath;

        } catch (\Throwable $e) {
            $this->logger->error('[MapImageGenerator] Fehler bei Bildgenerierung für Park ' . $parkId . ': ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Extrahiert Polygon-Koordinaten aus Geometry JSON
     */
    private function extractPolygonCoordinates(string $geometryJson): array
    {
        try {
            $data = json_decode($geometryJson, true);
            $this->logger->info('[MapImageGenerator] Parsed JSON structure: ' . json_encode(array_keys($data)));
            
            if (!$data) {
                $this->logger->error('[MapImageGenerator] JSON decode fehlgeschlagen');
                return [];
            }

            $coordinates = [];
            
            // Format 1: Direkte Geometry mit coordinates
            if (isset($data['geometry']['coordinates'])) {
                $this->logger->info('[MapImageGenerator] Format 1: Direkte Geometry erkannt');
                $coords = $data['geometry']['coordinates'];
                
                if (is_array($coords) && isset($coords[0]) && is_array($coords[0])) {
                    // Erstes Polygon verwenden (äußere Grenze)
                    foreach ($coords[0] as $coord) {
                        if (is_array($coord) && count($coord) >= 2) {
                            $coordinates[] = [
                                'lat' => (float) $coord[1],
                                'lng' => (float) $coord[0]
                            ];
                        }
                    }
                }
            }
            // Format 2: GeoJSON FeatureCollection
            elseif (isset($data['features']) && is_array($data['features'])) {
                $this->logger->info('[MapImageGenerator] Format 2: GeoJSON FeatureCollection erkannt');
                foreach ($data['features'] as $feature) {
                    if (isset($feature['geometry']['type']) && $feature['geometry']['type'] === 'Polygon') {
                        if (isset($feature['geometry']['coordinates'][0]) && is_array($feature['geometry']['coordinates'][0])) {
                            // Erstes Polygon verwenden (äußere Grenze)
                            foreach ($feature['geometry']['coordinates'][0] as $coord) {
                                if (is_array($coord) && count($coord) >= 2) {
                                    $coordinates[] = [
                                        'lat' => (float) $coord[1],
                                        'lng' => (float) $coord[0]
                                    ];
                                }
                            }
                            break; // Nur erstes Polygon verwenden
                        }
                    }
                }
            } else {
                $this->logger->warning('[MapImageGenerator] Unbekanntes Geometry JSON Format');
            }
            
            $this->logger->info('[MapImageGenerator] Extrahierte ' . count($coordinates) . ' Koordinaten-Punkte');
            if (count($coordinates) > 0) {
                $this->logger->info('[MapImageGenerator] Erstes Koordinaten-Paar: ' . json_encode($coordinates[0]));
                $this->logger->info('[MapImageGenerator] Letztes Koordinaten-Paar: ' . json_encode(end($coordinates)));
            }

            return $coordinates;

        } catch (\Throwable $e) {
            $this->logger->error('[MapImageGenerator] Fehler beim Extrahieren der Koordinaten: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Berechnet optimale Karten-Konfiguration basierend auf Polygon-Koordinaten
     */
    private function calculateOptimalMapConfig(array $coordinates): array
    {
        if (empty($coordinates)) {
            return ['center' => ['lat' => 51.0, 'lng' => 9.0], 'zoom' => 6];
        }

        // Bounding Box berechnen
        $minLat = min(array_column($coordinates, 'lat'));
        $maxLat = max(array_column($coordinates, 'lat'));
        $minLng = min(array_column($coordinates, 'lng'));
        $maxLng = max(array_column($coordinates, 'lng'));

        // Zentrum berechnen
        $centerLat = ($minLat + $maxLat) / 2;
        $centerLng = ($minLng + $maxLng) / 2;

        // Zoom-Level basierend auf Bounding Box Größe berechnen (2 Stufen rauszoomen)
        $latRange = $maxLat - $minLat;
        $lngRange = $maxLng - $minLng;
        $maxRange = max($latRange, $lngRange);
        
        // Zoom-Level Mapping (2 Stufen weiter raus als vorher)
        $zoom = 13; // Default (war 15)
        if ($maxRange > 0.5) $zoom = 8;  // war 10
        elseif ($maxRange > 0.2) $zoom = 10; // war 12
        elseif ($maxRange > 0.1) $zoom = 11; // war 13
        elseif ($maxRange > 0.05) $zoom = 12; // war 14
        elseif ($maxRange > 0.02) $zoom = 13; // war 15
        else $zoom = 14; // war 16

        return [
            'center' => ['lat' => $centerLat, 'lng' => $centerLng],
            'zoom' => $zoom
        ];
    }

    /**
     * Erstellt Google Maps Static API URL mit Polygon
     */
    private function buildStaticMapUrl(array $coordinates, array $mapConfig): string
    {
        $baseUrl = 'https://maps.googleapis.com/maps/api/staticmap';
        
        // Polygon-Pfad für Static API formatieren
        $pathCoords = array_map(function($coord) {
            return $coord['lat'] . ',' . $coord['lng'];
        }, $coordinates);
        $pathString = implode('|', $pathCoords);
        
        $params = [
            'center' => $mapConfig['center']['lat'] . ',' . $mapConfig['center']['lng'],
            'zoom' => $mapConfig['zoom'],
            'size' => '800x600',
            'maptype' => 'satellite',
            'format' => 'png',
            'scale' => '2', // Retina-Qualität
            // Polygon: Gelber Rand wie im JS, dunkles Grün-Grau Füllung (transparenter)
            'path' => 'color:0xffff00ff|weight:3|fillcolor:0x11363433|' . $pathString,
            'style' => 'feature:all|element:labels|visibility:off', // Labels ausblenden für sauberes Bild
            'key' => $this->googleMapsApiKey
        ];

        return $baseUrl . '?' . http_build_query($params);
    }

    /**
     * Lädt Bild von URL herunter
     */
    private function downloadImage(string $url): ?string
    {
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 30,
                    'user_agent' => 'CaeliWind/1.0'
                ]
            ]);

            $imageData = file_get_contents($url, false, $context);
            
            if ($imageData === false) {
                $this->logger->error('[MapImageGenerator] file_get_contents fehlgeschlagen für URL: ' . $url);
                return null;
            }

            return $imageData;

        } catch (\Throwable $e) {
            $this->logger->error('[MapImageGenerator] Fehler beim Download: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Speichert Bilddaten als PNG-Datei
     */
    private function saveImageAsPng(string $parkId, string $imageData): ?string
    {
        try {
            $this->logger->info('[MapImageGenerator] Target dir exists: ' . (is_dir($this->targetDirectory) ? 'JA' : 'NEIN'));
            $this->logger->info('[MapImageGenerator] Target dir writable: ' . (is_writable($this->targetDirectory) ? 'JA' : 'NEIN'));
            
            // PNG-Datei direkt speichern
            $pngFileName = $parkId . '.png';
            $pngPath = $this->targetDirectory . '/' . $pngFileName;
            
            $this->logger->info('[MapImageGenerator] PNG Ziel-Pfad: ' . $pngPath);
            
            $success = file_put_contents($pngPath, $imageData);
            
            if ($success === false) {
                $this->logger->error('[MapImageGenerator] Konnte PNG-Datei nicht speichern: ' . $pngPath);
                return null;
            }
            
            $this->logger->info('[MapImageGenerator] PNG erfolgreich gespeichert: ' . $success . ' Bytes');

            // Relative Pfad für Contao zurückgeben
            return 'files/area-check/' . $pngFileName;

        } catch (\Throwable $e) {
            $this->logger->error('[MapImageGenerator] Fehler beim Speichern als PNG: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Prüft ob die benötigten Funktionen verfügbar sind
     */
    public function isAvailable(): bool
    {
        return function_exists('file_put_contents') && 
               is_callable('file_get_contents');
    }
} 