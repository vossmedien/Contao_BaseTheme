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

            $rawData = $auctionService->getSampleAuctionRawData();

            if (empty($rawData)) {
                return '<p class="tl_info">Keine Beispiel-Rohdaten für Auktionen verfügbar.</p>';
            }

            $jsonPreview = json_encode($rawData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            return '
                <div class="widget">
                    <h3><label>' . ($GLOBALS['TL_LANG']['tl_module']['auctionRawDataPreviewMod_label'][0] ?? 'Vorschau Rohdaten (Modul)') . '</label></h3>
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