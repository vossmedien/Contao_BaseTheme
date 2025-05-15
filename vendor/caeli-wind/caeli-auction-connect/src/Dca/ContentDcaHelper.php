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

            $rawData = $auctionService->getSampleAuctionRawData();

            if (empty($rawData)) {
                return '<p class="tl_info">Keine Beispiel-Rohdaten für Auktionen verfügbar.</p>';
            }

            $jsonPreview = json_encode($rawData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            return '
                <div class="widget">
                    <h3><label>' . ($GLOBALS['TL_LANG']['tl_content']['auctionRawDataPreview_label'][0] ?? 'Vorschau Rohdaten') . '</label></h3>
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
