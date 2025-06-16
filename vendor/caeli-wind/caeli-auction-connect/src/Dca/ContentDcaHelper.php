<?php

declare(strict_types=1);

namespace CaeliWind\CaeliAuctionConnect\Dca;

use Contao\DataContainer;
use Contao\System;
use CaeliWind\CaeliAuctionConnect\Service\AuctionService;

class ContentDcaHelper
{
    public function displayAuctionRawDataPreview(DataContainer $dc): string
    {
        try {
            /** @var AuctionService $auctionService */
            $auctionService = System::getContainer()->get(AuctionService::class);

            if (!$auctionService) {
                return '<p class="tl_info">AuctionService nicht gefunden.</p>';
            }

            // URL-Parameter aus dem aktuellen Content Element lesen
            $urlParams = null;
            
            // Debug: Mehrere Methoden versuchen
            if ($dc) {
                // Methode 1: activeRecord
                if (isset($dc->activeRecord) && !empty($dc->activeRecord->auctionApiUrlParamsCE)) {
                    $urlParams = $dc->activeRecord->auctionApiUrlParamsCE;
                }
                // Methode 2: field value direkt
                elseif (!empty($dc->field) && $dc->field === 'auctionRawDataPreview' && !empty($_POST['auctionApiUrlParamsCE'])) {
                    $urlParams = $_POST['auctionApiUrlParamsCE'];
                }
                // Methode 3: Database lookup
                elseif (!empty($dc->id)) {
                    try {
                        $result = \Contao\Database::getInstance()->prepare("SELECT auctionApiUrlParamsCE FROM tl_content WHERE id=?")
                                                         ->execute($dc->id);
                        if ($result->numRows && !empty($result->auctionApiUrlParamsCE)) {
                            $urlParams = $result->auctionApiUrlParamsCE;
                        }
                    } catch (\Exception $e) {
                        // Fallback: ignore error
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
                $debugInfo .= 'URL-Params: ' . ($urlParams ?? 'null');
                $debugInfo .= '</small></p>';
            }
            
            if (!empty($urlParams)) {
                $apiInfo = '<p class="tl_help"><strong>API-Aufruf mit URL-Parametern:</strong> ' . htmlspecialchars($urlParams) . '</p>';
            }

            return '
                <div class="widget">
                    <h3><label>' . ($GLOBALS['TL_LANG']['tl_content']['auctionRawDataPreview_label'][0] ?? 'Vorschau Rohdaten') . '</label></h3>
                    ' . $debugInfo . '
                    ' . $apiInfo . '
                    <pre style="max-height: 300px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; border-radius: 3px;">' .
                        htmlspecialchars($jsonPreview) .
                    '</pre>
                    <p class="tl_help">' . ($GLOBALS['TL_LANG']['tl_content']['auctionRawDataPreview_label'][1] ?? 'Dies ist eine Vorschau der Rohdaten einer Beispielauktion. Nutzen Sie diese Felder für die Filterdefinition.') . '</p>
                </div>';

        } catch (\Exception $e) {
            // Log error if possible, e.g., System::getContainer()->get('monolog.logger.contao')->error()
            return '<p class="tl_error">Fehler beim Laden der Rohdaten-Vorschau: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
    }
}
