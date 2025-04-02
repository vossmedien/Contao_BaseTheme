<?php

declare(strict_types=1);

/*
 * This file is part of Caeli Auction Connect.
 *
 * (c) Caeli Wind - Christian Voss 2025 <christian.voss@caeli-wind.de>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/caeli-wind/caeli-auction-connect
 */

namespace CaeliWind\CaeliAuctionConnect\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\PageModel;
use Contao\Template;
use CaeliWind\CaeliAuctionConnect\Service\AuctionService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(category: 'caeli_wind', template: 'ce_auction_element', name: 'auction_element')]
class AuctionElementController extends AbstractContentElementController
{
    public const TYPE = 'auction_element';

    public function __construct(
        private readonly AuctionService $auctionService,
        private readonly LoggerInterface $logger
    ) {
    }

    protected function getResponse(Template $template, ContentModel $model, Request $request): Response
    {
        // Kommagetrennte IDs in ein Array umwandeln und leere Einträge entfernen
        $auctionIds = array_filter(array_map('trim', explode(',', $model->auction_ids)));
        $auctions = [];

        if (!empty($auctionIds)) {
             // Alle benötigten Auktionen auf einmal abrufen
             $auctions = $this->auctionService->getAuctionsByIds($auctionIds);
             $this->logger->debug('Auktionen für IDs abgerufen', ['ids' => $auctionIds, 'count' => count($auctions)]);
        } else {
             $this->logger->debug('Keine Auktions-IDs im Inhaltselement angegeben.');
        }

        // Weiterleitungsseite hinzufügen, falls angegeben
        $template->detailPage = null;
        if ($model->jumpTo) {
            $template->detailPage = \Contao\PageModel::findById($model->jumpTo);
            $this->logger->debug('Weiterleitungsseite gefunden', [
                'id' => $model->jumpTo,
                'alias' => $template->detailPage ? $template->detailPage->alias : 'nicht gefunden'
            ]);
        }

        // Detailseiten-URL für Links setzen
        $template->detailPageUrl = null;
        if ($template->detailPage) {
            $template->detailPageUrl = $template->detailPage->getFrontendUrl();
        }

        // Template-Variablen je nach Anzahl der Auktionen setzen
        if (count($auctions) === 1) {
            // Wenn nur eine Auktion gefunden wurde, setze sie als einzelne Auktion
            $template->auction = $auctions[0];
            $template->auctions = null;
            $template->multipleAuctions = false;
        } elseif (count($auctions) > 1) {
            // Wenn mehrere Auktionen gefunden wurden, setze das Array
            $template->auctions = $auctions;
            $template->auction = null;
            $template->multipleAuctions = true;
        } else {
            // Wenn keine Auktionen gefunden wurden
            $template->auction = null;
            $template->auctions = null;
            $template->multipleAuctions = false;
            $this->logger->warning('Keine Auktionen gefunden für IDs', ['ids' => $model->auction_ids]);
        }

        // Template-Variablen setzen
        $template->headline = $model->headline;
        $template->cssClass = $model->cssID[1] ?? '';

        return $template->getResponse();
    }
}
