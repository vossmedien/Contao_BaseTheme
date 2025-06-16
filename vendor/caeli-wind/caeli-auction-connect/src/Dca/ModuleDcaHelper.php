<?php

declare(strict_types=1);

namespace CaeliWind\CaeliAuctionConnect\Dca;

use Contao\System;
use Contao\DataContainer;
use CaeliWind\CaeliAuctionConnect\Service\AuctionService;
use Symfony\Component\Finder\Finder;

class ModuleDcaHelper
{
    /**
     * Lädt die verfügbaren Auktion Item Templates.
     *
     * @return array
     */
    public function getAuctionItemTemplates(): array
    {
        $templates = [];
        try {
            $projectDir = System::getContainer()->getParameter('kernel.project_dir');
            // Pfad zu den Templates innerhalb deines Bundles
            // Passe diesen Pfad ggf. an, falls deine Templates woanders liegen.
            // Standardmäßig sucht Contao Bundle-Templates in 'templates' oder 'Resources/contao/templates'
            // Da die Controller @CaeliWindCaeliAuctionConnect/ nutzen, gehen wir davon aus,
            // dass die Templates im Bundle-Verzeichnis unter einem Standardpfad wie
            // vendor/caeli-wind/caeli-auction-connect/contao/templates/ oder
            // vendor/caeli-wind/caeli-auction-connect/templates/ liegen.
            // Für dieses Beispiel nehmen wir an, sie sind in /contao/templates/
            $templatePath = $projectDir . '/vendor/caeli-wind/caeli-auction-connect/contao/templates';

            if (!is_dir($templatePath)) {
                // Loggen, dass das Verzeichnis nicht gefunden wurde
                System::getContainer()->get('monolog.logger.contao.error')?->error(
                    'Template-Verzeichnis für Auction Items nicht gefunden: ' . $templatePath,
                    ['source' => 'CaeliAuctionConnect']
                );
                return ['' => 'Template-Verzeichnis nicht gefunden'];
            }

            $finder = new Finder();
            // Sucht nach allen .html.twig Dateien, die mit 'auction_item' beginnen
            $finder->files()->in($templatePath)->name('auction_item*.html.twig');

            if (!$finder->hasResults()) {
                 return ['' => 'Keine Item-Templates (auction_item*.html.twig) gefunden.'];
            }

            foreach ($finder as $file) {
                $fileName = $file->getRelativePathname();
                // Erzeuge einen lesbareren Namen für die Select-Option
                $displayName = str_replace(['auction_item_', '.html.twig'], ['', ''], $fileName);
                $displayName = ucfirst(str_replace('_', ' ', $displayName));
                $templates[$fileName] = $displayName . ' (' . $fileName . ')';
            }
            ksort($templates);

        } catch (\Exception $e) {
            System::getContainer()->get('monolog.logger.contao.error')?->error(
                'Fehler beim Laden der Auction Item Templates: ' . $e->getMessage(),
                ['exception' => $e, 'source' => 'CaeliAuctionConnect']
            );
            return ['' => 'Fehler beim Laden der Templates'];
        }
        return $templates;
    }

    /**
     * Zeigt eine Vorschau der Rohdaten einer Beispielauktion im Backend für Module an.
     *
     * @param DataContainer $dc
     * @return string
     */
    public function displayAuctionRawDataPreviewMod(DataContainer $dc): string
    {
        try {
            /** @var AuctionService $auctionService */
            $auctionService = System::getContainer()->get(AuctionService::class);

            if (!$auctionService) {
                return '<p class="tl_info">AuctionService nicht gefunden.</p>';
            }

            // URL-Parameter aus dem aktuellen Modul lesen
            $urlParams = null;
            
            // Debug: Mehrere Methoden versuchen
            $debugMethod = 'keine';
            $debugExtra = '';
            if ($dc) {
                // Debug ID-Check
                $debugExtra = ' | DC->id: ' . var_export($dc->id, true) . ' | empty: ' . (empty($dc->id) ? 'ja' : 'nein');
                
                // Methode 1: activeRecord
                if (isset($dc->activeRecord) && !empty($dc->activeRecord->auctionApiUrlParams)) {
                    $urlParams = $dc->activeRecord->auctionApiUrlParams;
                    $debugMethod = 'activeRecord';
                }
                // Methode 2: field value direkt
                elseif (!empty($dc->field) && $dc->field === 'auctionRawDataPreviewMod' && !empty($_POST['auctionApiUrlParams'])) {
                    $urlParams = $_POST['auctionApiUrlParams'];
                    $debugMethod = 'POST';
                }
                // Methode 3: Database lookup - Immer versuchen, auch wenn ID "leer" ist
                elseif ($dc->id) {
                    try {
                        $result = \Contao\Database::getInstance()->prepare("SELECT * FROM tl_module WHERE id=?")
                                                         ->execute($dc->id);
                        if ($result->numRows) {
                            $row = $result->row();
                            $debugMethod = 'database-found-fields: ' . implode(',', array_keys($row));
                            if (isset($row['auctionApiUrlParams']) && !empty($row['auctionApiUrlParams'])) {
                                $urlParams = $row['auctionApiUrlParams'];
                                $debugMethod = 'database-success';
                            } else {
                                $debugMethod = 'database-field-empty-or-missing';
                            }
                        } else {
                            $debugMethod = 'database-no-record';
                        }
                    } catch (\Exception $e) {
                        $debugMethod = 'database-error: ' . $e->getMessage();
                    }
                }
            }

            // Verwende getAuctions statt getSampleAuctionRawData, um URL-Parameter zu berücksichtigen
            $auctions = $auctionService->getAuctions([], false, null, 'asc', [], $urlParams);
            
            if (empty($auctions)) {
                $infoText = 'Keine Beispiel-Rohdaten für Auktionen verfügbar.';
                if (!empty($urlParams)) {
                    $infoText .= ' (Mit URL-Parametern: ' . htmlspecialchars($urlParams) . ')';
                }
                return '<p class="tl_info">' . $infoText . '</p>';
            }

            // Erste Auktion als Beispiel verwenden
            $sampleAuction = reset($auctions);
            $rawData = $sampleAuction['_raw_data'] ?? $sampleAuction;

            $jsonPreview = json_encode($rawData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            $apiInfo = '';
            $debugInfo = '';
            
            // Debug-Informationen sammeln
            if ($dc) {
                $debugInfo .= '<p class="tl_help" style="color: #666;"><small>';
                $debugInfo .= 'Debug - DC ID: ' . ($dc->id ?? 'null') . ' | ';
                $debugInfo .= 'Field: ' . ($dc->field ?? 'null') . ' | ';
                $debugInfo .= 'ActiveRecord: ' . (isset($dc->activeRecord) ? 'ja' : 'nein') . ' | ';
                $debugInfo .= 'Methode: ' . $debugMethod . $debugExtra . ' | ';
                $debugInfo .= 'URL-Params: ' . ($urlParams ?? 'null');
                $debugInfo .= '</small></p>';
            }
            
            if (!empty($urlParams)) {
                // Basis-URL aus der Konfiguration holen
                $baseUrl = System::getContainer()->getParameter('caeli_auction.marketplace_api_url');
                $finalUrl = rtrim($baseUrl, '/') . '/' . ltrim($urlParams, '/');
                
                $apiInfo = '<p class="tl_help"><strong>API-Aufruf mit URL-Parametern:</strong><br/>';
                $apiInfo .= 'Parameter: <code>' . htmlspecialchars($urlParams) . '</code><br/>';
                $apiInfo .= 'Finale URL: <code>' . htmlspecialchars($finalUrl) . '</code></p>';
            }

            return '
                <div class="widget">
                    <h3><label>' . ($GLOBALS['TL_LANG']['tl_module']['auctionRawDataPreviewMod_label'][0] ?? 'Vorschau Rohdaten (Modul)') . '</label></h3>
                    ' . $debugInfo . '
                    ' . $apiInfo . '
                    <pre style="max-height: 300px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; border-radius: 3px;">' .
                        htmlspecialchars($jsonPreview) .
                    '</pre>
                    <p class="tl_help">' . ($GLOBALS['TL_LANG']['tl_module']['auctionRawDataPreviewMod_label'][1] ?? 'Dies ist eine Vorschau der Rohdaten einer Beispielauktion. Nutzen Sie diese Felder für die Filter- und Sortierdefinition.') . '</p>
                </div>';

        } catch (\Exception $e) {
            // Log error if possible
            System::getContainer()->get('monolog.logger.contao.error')?->error(
                'Fehler beim Laden der Rohdaten-Vorschau (Modul): ' . $e->getMessage(),
                ['exception' => $e, 'source' => 'CaeliAuctionConnect']
            );
            return '<p class="tl_error">Fehler beim Laden der Rohdaten-Vorschau: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
    }
} 