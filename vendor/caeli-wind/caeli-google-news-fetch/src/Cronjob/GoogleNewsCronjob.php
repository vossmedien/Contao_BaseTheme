<?php

declare(strict_types=1);

namespace CaeliWind\CaeliGoogleNewsFetch\Cronjob;

use Contao\CoreBundle\Framework\ContaoFramework;
use CaeliWind\CaeliGoogleNewsFetch\Model\CaeliGooglenewsModel;
use CaeliWind\CaeliGoogleNewsFetch\Service\GoogleNewsFeedService;
use Psr\Log\LoggerInterface;

/**
 * Cronjob für die automatische Aktualisierung der Google News über SerpAPI
 */
class GoogleNewsCronjob
{
    private const DEFAULT_UPDATE_INTERVAL = 86400; // 24 Stunden in Sekunden
    
    protected ContaoFramework $framework;
    protected GoogleNewsFeedService $newsFeedService;
    protected ?LoggerInterface $logger;
    
    /**
     * Konstruktor
     */
    public function __construct(
        ContaoFramework $framework,
        GoogleNewsFeedService $newsFeedService,
        ?LoggerInterface $logger = null
    ) {
        $this->framework = $framework;
        $this->newsFeedService = $newsFeedService;
        $this->logger = $logger;
    }
    
    /**
     * Führt den Cronjob aus
     */
    public function __invoke(): void
    {
        if ($this->logger) {
            $this->logger->info('GoogleNewsCronjob: Starte automatische Aktualisierung');
        }
        
        // Initialisiere das Contao-Framework
        $this->framework->initialize();
        
        // Alle Konfigurationen laden
        $configs = CaeliGooglenewsModel::findAll();
        
        if (null === $configs) {
            if ($this->logger) {
                $this->logger->info('GoogleNewsCronjob: Keine Konfigurationen gefunden');
            }
            return;
        }
        
        $now = time();
        $updatedConfigs = 0;
        
        foreach ($configs as $config) {
            // Prüfen, ob das Update-Intervall abgelaufen ist
            $lastUpdated = (int)$config->lastUpdated;
            $timeSinceLastUpdate = $now - $lastUpdated;
            
            if ($timeSinceLastUpdate < self::DEFAULT_UPDATE_INTERVAL) {
                // Letztes Update ist noch nicht lange genug her
                continue;
            }
            
            try {
                if ($this->logger) {
                    $this->logger->info('GoogleNewsCronjob: Aktualisiere Konfiguration ID ' . $config->id);
                }
                
                // SerpAPI-Konfiguration
                $searchQuery = $config->serpApiQuery;
                $apiKey = $config->serpApiKey;
                $numResults = (int)$config->serpApiNumResults ?: 100;
                $location = $config->serpApiLocation ?: 'Germany';
                $language = $config->serpApiLanguage ?: 'de';
                
                if (empty($searchQuery) || empty($apiKey)) {
                    if ($this->logger) {
                        $this->logger->error('GoogleNewsCronjob: Fehlende SerpAPI-Konfiguration für ID ' . $config->id);
                    }
                    continue;
                }
                
                // News über SerpAPI abrufen
                $newsItems = $this->newsFeedService->fetchNewsViaSerpApi(
                    $searchQuery,
                    $apiKey,
                    $numResults,
                    $location,
                    $language
                );
                
                if (empty($newsItems)) {
                    if ($this->logger) {
                        $this->logger->info('GoogleNewsCronjob: Keine News gefunden für ID ' . $config->id);
                    }
                    continue;
                }
                
                // Archivierte News laden
                $jsonDir = \Contao\System::getContainer()->getParameter('kernel.project_dir') . '/var/caeli_googlenews';
                $archivedFilePath = $jsonDir . '/news_' . $config->id . '_archived.json';
                
                $archivedNews = [];
                if (file_exists($archivedFilePath)) {
                    $jsonData = file_get_contents($archivedFilePath);
                    if ($jsonData !== false) {
                        $archivedNews = json_decode($jsonData, true) ?: [];
                    }
                }
                
                // Duplikate filtern (vereinfachte Version, nur GUID und Link prüfen)
                $filteredNews = [];
                $archivedGuids = array_column($archivedNews, 'guid');
                $archivedLinks = array_column($archivedNews, 'link');
                
                foreach ($newsItems as $item) {
                    $isDuplicate = false;
                    
                    // GUID-Prüfung
                    if (!empty($item['guid']) && in_array($item['guid'], $archivedGuids)) {
                        $isDuplicate = true;
                    }
                    
                    // Link-Prüfung
                    if (!$isDuplicate && !empty($item['link']) && in_array($item['link'], $archivedLinks)) {
                        $isDuplicate = true;
                    }
                    
                    if (!$isDuplicate) {
                        $filteredNews[] = $item;
                    }
                }
                
                // News in JSON-Datei speichern
                if (!is_dir($jsonDir)) {
                    mkdir($jsonDir, 0755, true);
                }
                
                $currentFilePath = $jsonDir . '/news_' . $config->id . '_current.json';
                $jsonData = json_encode($filteredNews, JSON_PRETTY_PRINT);
                
                if (file_put_contents($currentFilePath, $jsonData) !== false) {
                    // Update der Konfiguration
                    $config->lastUpdated = $now;
                    $config->save();
                    $updatedConfigs++;
                    
                    if ($this->logger) {
                        $this->logger->info('GoogleNewsCronjob: Konfiguration ID ' . $config->id . ' erfolgreich aktualisiert');
                    }
                }
            } catch (\Exception $e) {
                if ($this->logger) {
                    $this->logger->error('GoogleNewsCronjob: Fehler bei Konfiguration ID ' . $config->id . ': ' . $e->getMessage());
                }
            }
        }
        
        if ($this->logger) {
            $this->logger->info('GoogleNewsCronjob: Aktualisierung abgeschlossen. ' . $updatedConfigs . ' Konfiguration(en) aktualisiert.');
        }
    }
} 