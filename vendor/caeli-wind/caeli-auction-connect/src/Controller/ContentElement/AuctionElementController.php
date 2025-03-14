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
        // Debug-Info
        $this->logger->debug('AuctionElementController aufgerufen', [
            'id' => $model->id,
            'auction_ids' => $model->auction_ids,
            'jumpTo' => $model->jumpTo
        ]);

        // Kommagetrennte IDs in ein Array umwandeln
        $auctionIds = array_map('trim', explode(',', $model->auction_ids));
        $auctions = [];

        // Für jede ID die entsprechende Auktion abrufen
        foreach ($auctionIds as $id) {
            if (!empty($id)) {
                $auction = $this->auctionService->getAuctionById($id);
                if ($auction) {
                    $auctions[] = $auction;
                    $this->logger->debug('Auktion gefunden', ['id' => $id]);
                } else {
                    $this->logger->warning('Auktion nicht gefunden', ['id' => $id]);
                }
            }
        }

        // Wenn keine Auktionen gefunden wurden, Debug-Info anzeigen
        if (empty($auctions)) {
            $this->logger->warning('Keine Auktionen gefunden für IDs', ['ids' => $model->auction_ids]);
            $template->noAuctions = true;
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

        // Template-Variablen setzen
        $template->auctions = $auctions;
        $template->headline = $model->headline;
        $template->cssClass = $model->cssID[1] ?? '';

        return $template->getResponse();
    }
}
