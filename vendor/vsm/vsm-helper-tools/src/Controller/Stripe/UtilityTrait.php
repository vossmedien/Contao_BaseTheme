<?php

declare(strict_types=1);

/*
 * This file is part of VSM Helper und Integrations.
 *
 * (c) Vossmedien - Christian Voss 2025 <christian@vossmedien.de>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/vsm/vsm-helper-tools
 */
namespace Vsm\VsmHelperTools\Controller\Stripe;

/**
 * Trait mit Hilfsfunktionen für die Stripe-Controller
 */
trait UtilityTrait
{
    /**
     * Fügt Parameter zu einer URL hinzu und berücksichtigt dabei bestehende Parameter
     */
    protected function addParamsToUrl(string $url, array $params = []): string
    {
        if (empty($params)) {
            return $url;
        }
        
        $parsedUrl = parse_url($url);
        $query = [];
        
        // Bestehende Query-Parameter extrahieren, falls vorhanden
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $query);
        }
        
        // Neue Parameter hinzufügen oder bestehende überschreiben
        foreach ($params as $key => $value) {
            $query[$key] = $value;
        }
        
        // Basis-URL ohne Parameter erstellen
        $baseUrl = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : '';
        $baseUrl .= $parsedUrl['host'] ?? '';
        
        if (isset($parsedUrl['port'])) {
            $baseUrl .= ':' . $parsedUrl['port'];
        }
        
        $baseUrl .= $parsedUrl['path'] ?? '';
        
        // Alle Parameter als Query-String anhängen
        if (!empty($query)) {
            $baseUrl .= '?' . http_build_query($query);
        }
        
        // Fragment (Anker) anhängen, falls vorhanden
        if (isset($parsedUrl['fragment'])) {
            $baseUrl .= '#' . $parsedUrl['fragment'];
        }
        
        return $baseUrl;
    }
    
    /**
     * Formatiert einen Betrag für die Anzeige mit Währungssymbol
     */
    protected function formatAmount(int $amount, string $currency): string
    {
        // Währungsspezifische Formatierung
        switch (strtolower($currency)) {
            case 'eur':
                return number_format($amount / 100, 2, ',', '.') . ' €';
            case 'usd':
                return '$' . number_format($amount / 100, 2, '.', ',');
            case 'gbp':
                return '£' . number_format($amount / 100, 2, '.', ',');
            default:
                return number_format($amount / 100, 2, '.', ',') . ' ' . strtoupper($currency);
        }
    }
    
    /**
     * Validiert eine E-Mail-Adresse
     */
    protected function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Generiert eine eindeutige Referenznummer für Zahlungen
     */
    protected function generateReferenceNumber(): string
    {
        return 'REF-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));
    }
    
    /**
     * Maskiert eine Kreditkartennummer (z.B. für Logs oder Anzeige)
     */
    protected function maskCardNumber(string $cardNumber): string
    {
        // Nur die letzten 4 Ziffern behalten
        $length = strlen($cardNumber);
        if ($length <= 4) {
            return $cardNumber;
        }
        
        return str_repeat('*', $length - 4) . substr($cardNumber, -4);
    }
    
    /**
     * Hilfsfunktion: Baut eine URL aus ihren Teilen zusammen
     */
    protected function buildUrl(array $parts): string
    {
        $scheme   = isset($parts['scheme']) ? $parts['scheme'] . '://' : '';
        $host     = $parts['host'] ?? '';
        $port     = isset($parts['port']) ? ':' . $parts['port'] : '';
        $user     = $parts['user'] ?? '';
        $pass     = isset($parts['pass']) ? ':' . $parts['pass']  : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = $parts['path'] ?? '';
        $query    = isset($parts['query']) ? '?' . $parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';
        
        return "$scheme$user$pass$host$port$path$query$fragment";
    }
    
    /**
     * Sanitiert und verifiziert einen Dateipfad
     */
    protected function sanitizeAndVerifyFilePath(string $filePath): string
    {
        // Entferne möglicherweise gefährliche Pfadelemente
        $filePath = str_replace(['../', '..\\', './'], '', $filePath);
        $filePath = trim($filePath, '/\\');
        
        // Wenn es sich um einen UUID-Identifier handelt, versuche die Datei zu finden
        if (preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $filePath)) {
            // Es ist eine UUID, versuche sie in einen Dateipfad umzuwandeln
            $filePath = $this->getFilePathFromUuid($filePath);
        }
        
        // Stelle sicher, dass der Pfad existiert
        $absolutePath = $this->projectDir . '/' . $filePath;
        if (!file_exists($absolutePath)) {
            $this->logger->warning('Datei nicht gefunden: ' . $absolutePath);
        }
        
        return $filePath;
    }
    
    /**
     * Versucht, einen Dateipfad aus einer UUID zu erhalten
     */
    protected function getFilePathFromUuid(string $uuid): string
    {
        try {
            $this->framework->initialize();
            
            // Log für debugging
            $this->logger->debug('Versuche Datei mit UUID zu finden', [
                'uuid' => $uuid
            ]);
            
            // 1. Standard Contao-Funktion verwenden
            $file = \Contao\FilesModel::findByUuid($uuid);
            
            if ($file !== null) {
                $fullPath = $this->projectDir . '/' . $file->path;
                
                if (file_exists($fullPath)) {
                    if (is_dir($fullPath)) {
                        $this->logger->info('UUID führt zu einem Verzeichnis, suche nach Datei im Verzeichnis');
                        
                        // Suche nach der ersten Datei im Verzeichnis
                        $files = scandir($fullPath);
                        foreach ($files as $item) {
                            if ($item != '.' && $item != '..' && is_file($fullPath . '/' . $item)) {
                                $this->logger->info('Erste Datei im Verzeichnis gefunden: ' . $item);
                                return $file->path . '/' . $item;
                            }
                        }
                        
                        return $file->path; // Fallback: Verzeichnis zurückgeben
                    } else {
                        $this->logger->info('Datei direkt über UUID gefunden: ' . $file->path);
                        return $file->path;
                    }
                } else {
                    $this->logger->warning('Gefundener Pfad existiert nicht: ' . $fullPath);
                }
            } else {
                $this->logger->warning('Keine Datei für UUID gefunden: ' . $uuid);
            }
            
            // 2. Fallback: Einfach einen Standardpfad zurückgeben, der auf dem Server existieren sollte
            $fallbackPaths = [
                'files/downloads/test.pdf',
                'files/downloads/download.pdf',
                'files/content/download.pdf'
            ];
            
            foreach ($fallbackPaths as $path) {
                $fullPath = $this->projectDir . '/' . $path;
                if (file_exists($fullPath)) {
                    $this->logger->info('Fallback-Datei gefunden: ' . $path);
                    return $path;
                }
            }
            
            // Wenn alles fehlschlägt, leeren String zurückgeben
            return '';
            
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Auflösen der UUID: ' . $e->getMessage());
            return '';
        }
    }
    
    /**
     * Extrahiert die Mitgliedschaftsdauer aus den Produktdaten
     * Diese Methode versucht die Dauer aus verschiedenen Attributen zu extrahieren
     */
    protected function extractDurationFromProductData(array $productData): int
    {
        $possibleKeys = [
            'subscription_duration',
            'duration',
            'membership_duration',
            'data-duration',
            'data-subscription-duration'
        ];
        
        foreach ($possibleKeys as $key) {
            if (isset($productData[$key]) && !empty($productData[$key])) {
                $duration = intval($productData[$key]);
                if ($duration > 0) {
                    $this->logger->info('Mitgliedschaftsdauer aus ' . $key . ' extrahiert: ' . $duration);
                    return $duration;
                }
            }
        }
        
        // Versuche, aus data-Attributen zu extrahieren, die als JSON gespeichert sein könnten
        if (isset($productData['data']) && is_string($productData['data'])) {
            try {
                $decodedData = json_decode($productData['data'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decodedData)) {
                    foreach ($possibleKeys as $key) {
                        if (isset($decodedData[$key]) && !empty($decodedData[$key])) {
                            $duration = intval($decodedData[$key]);
                            if ($duration > 0) {
                                $this->logger->info('Mitgliedschaftsdauer aus JSON-Daten (data.' . $key . ') extrahiert: ' . $duration);
                                return $duration;
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->logger->warning('Fehler beim Dekodieren von JSON-Daten: ' . $e->getMessage());
            }
        }
        
        // Nichts gefunden
        return 0;
    }
    
    /**
     * Normalisiert Parameter-Namen für eine einheitliche Verwendung
     * Konvertiert z.B. 'data-create-invoice' zu 'create_invoice'
     */
    protected function normalizeParameterName(string $paramName): string
    {
        // Data-Attribute (data-xyz) normalisieren zu xyz
        if (strpos($paramName, 'data-') === 0) {
            $normalizedName = substr($paramName, 5); // Entferne 'data-'
            $normalizedName = str_replace('-', '_', $normalizedName); // Ersetze Bindestriche durch Unterstriche
            
            return $normalizedName;
        }
        
        // camelCase zu snake_case konvertieren
        if (preg_match('/[A-Z]/', $paramName)) {
            $normalizedName = preg_replace('/([a-z])([A-Z])/', '$1_$2', $paramName);
            return strtolower($normalizedName);
        }
        
        // Bindestriche durch Unterstriche ersetzen
        return str_replace('-', '_', $paramName);
    }
} 