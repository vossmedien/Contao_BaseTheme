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
use Contao\StringUtil;
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
        $this->logger->debug('[AuctionElementController] getResponse gestartet für CE ID ' . $model->id);

        $filters = [];
        if ($model->auctionElementFilters) {
            $filters = $this->auctionService->parseFiltersFromString((string)$model->auctionElementFilters);
            $this->logger->debug('[AuctionElementController] Filter aus auctionElementFilters geparst', ['parsedFilters' => $filters]);
        }

        // Sortieroptionen aus dem Content Element Model lesen
        $sortBy = $model->auctionSortByCE ?: null;
        $sortDirection = $model->auctionSortDirectionCE ?: 'asc';
        $this->logger->debug('[AuctionElementController] Alte Sortieroptionen aus CE', ['sortBy' => $sortBy, 'sortDirection' => $sortDirection]);

        // Neue mehrstufige Sortierregeln aus dem Content Element Model lesen
        $sortRules = [];
        if ($model->auctionSortRulesCE) {
            $sortRules = $this->auctionService->parseSortRulesFromString((string)$model->auctionSortRulesCE);
            $this->logger->debug('[AuctionElementController] Neue Sortierregeln aus auctionSortRulesCE geparst', ['sortRules' => $sortRules]);
        }

        // URL-Parameter aus CE-Einstellungen lesen
        $urlParams = $model->auctionApiUrlParamsCE ?: null;
        if (!empty($urlParams)) {
            $this->logger->debug('[AuctionElementController] URL-Parameter aus CE-Einstellungen: ' . $urlParams);
        }

        // Behandlung von auction_ids (für Abwärtskompatibilität, bis das Feld entfernt wird)
        $targetAuctionIds = array_filter(array_map('trim', explode(',', (string)($model->auction_ids ?? ''))));

        if (!empty($targetAuctionIds)) {
            $this->logger->debug('[AuctionElementController] Spezifische auction_ids gefunden. Diese werden priorisiert und zusätzlich gefiltert.', ['ids' => $targetAuctionIds]);
            
            if (!empty($filters) && isset($filters['id__in'])) {
                 $existingIdFilter = array_map('trim', explode(',', $filters['id__in']));
                 $targetAuctionIds = array_unique(array_merge($targetAuctionIds, $existingIdFilter));
            }
            $filters['id__in'] = implode(',', $targetAuctionIds);
            $this->logger->debug('[AuctionElementController] Kombinierte Filter mit id__in', $filters);
            
            $auctions = $this->auctionService->getAuctions($filters, false, $sortBy, $sortDirection, $sortRules, $urlParams);

        } else {
            $this->logger->debug('[AuctionElementController] Keine spezifischen auction_ids. Verwende Filter aus Textarea.');
            $auctions = $this->auctionService->getAuctions($filters, false, $sortBy, $sortDirection, $sortRules, $urlParams);
        }

        $this->logger->info('[AuctionElementController] ' . count($auctions) . ' Auktionen nach Filterung und Sortierung erhalten.');

        // Limit anwenden, falls gesetzt
        if ($model->auctionElementLimit > 0) {
            $auctions = array_slice($auctions, 0, (int)$model->auctionElementLimit);
            $this->logger->debug('[AuctionElementController] Limit von ' . $model->auctionElementLimit . ' angewendet. Neue Anzahl: ' . count($auctions));
        }

        $template->detailPage = null;
        if ($model->jumpTo) {
            $pageModel = PageModel::findById($model->jumpTo);
            if ($pageModel instanceof PageModel) {
                $template->detailPage = $pageModel;
                $template->detailPageUrl = $pageModel->getFrontendUrl();
                $this->logger->debug('Weiterleitungsseite gefunden', ['id' => $model->jumpTo, 'alias' => $pageModel->alias]);
            } else {
                $this->logger->warning('Weiterleitungsseite NICHT gefunden', ['id' => $model->jumpTo]);
                $template->detailPageUrl = null;
            }
        } else {
            $template->detailPageUrl = null;
        }

        if (count($auctions) === 1) {
            $template->auction = $auctions[0];
            $template->auctions = null;
            $template->multipleAuctions = false;
        } elseif (count($auctions) > 1) {
            $template->auctions = $auctions;
            $template->auction = null;
            $template->multipleAuctions = true;
        } else {
            $template->auction = null;
            $template->auctions = null;
            $template->multipleAuctions = false;
            $this->logger->warning('Keine Auktionen gefunden.', ['filters' => $filters, 'target_ids' => $targetAuctionIds]);
        }

        // Ausgewähltes Item-Template aus dem CE-Modell holen oder Default setzen
        $itemTemplateName = $model->auctionItemTemplateCE ?: 'auction_item.html.twig'; // Default, falls nichts ausgewählt
        $template->auctionItemTemplate = '@CaeliWindCaeliAuctionConnect/' . $itemTemplateName;
        $this->logger->debug('[AuctionElementController] Item-Template für CE gesetzt auf: ' . $template->auctionItemTemplate);

        // Headline deserialisieren falls vorhanden
        $headlineData = null;
        if ($model->headline) {
            $deserializedHeadline = StringUtil::deserialize($model->headline, true);
            if (is_array($deserializedHeadline)) {
                $headlineData = [
                    'unit' => $deserializedHeadline['unit'] ?? 'h2',
                    'value' => trim($deserializedHeadline['value'] ?? '')
                ];
            }
        }
        $template->headline = $headlineData;
        $template->cssClass = $model->cssID[1] ?? '';

        return $template->getResponse();
    }
}
