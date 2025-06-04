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

namespace CaeliWind\CaeliAuctionConnect\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\Date;
use Contao\FrontendUser;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\Template;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use CaeliWind\CaeliAuctionConnect\Service\AuctionService;
use Psr\Log\LoggerInterface;

#[AsFrontendModule(category: 'caeli_wind', template: 'mod_auction_listing', name: 'auction_listing')]
class AuctionListingController extends AbstractFrontendModuleController
{
    public const TYPE = 'auction_listing';

    protected ?PageModel $page;

    public function __construct(
        private readonly AuctionService $auctionService,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * This method extends the parent __invoke method,
     * its usage is usually not necessary.
     */
    public function __invoke(Request $request, ModuleModel $model, string $section, array $classes = null, PageModel $page = null): Response
    {
        // Get the page model
        $this->page = $page;

        $scopeMatcher = $this->container->get('contao.routing.scope_matcher');

        if ($this->page instanceof PageModel && $scopeMatcher->isFrontendRequest($request)) {
            $this->page->loadDetails();
        }

        return parent::__invoke($request, $model, $section, $classes);
    }

    /**
     * Lazyload services.
     */
    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();

        $services['contao.framework'] = ContaoFramework::class;
        $services['database_connection'] = Connection::class;
        $services['contao.routing.scope_matcher'] = ScopeMatcher::class;
        $services['security.helper'] = AuthorizationCheckerInterface::class;
        $services['translator'] = TranslatorInterface::class;
        $services['logger'] = LoggerInterface::class;

        return $services;
    }

    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        $this->logger->debug('[AuctionListingController] getResponse gestartet für Modul ID ' . $model->id);

        // Paginierungs-Parameter
        $page = max(1, (int)$request->query->get('page', 1));
        $itemsPerPage = (int)($model->perPage ?: 12); // Default 12 Items pro Seite

        // 1. Filter aus dem Request lesen und STRUKTURIEREN (für z.B. externes Filter-Modul)
        $rawRequestFilters = $request->query->all();
        // Entferne Parameter, die keine direkten Filter für den AuctionService sind oder separat behandelt werden
        // Die structureRequestFilters-Methode wird 'refresh' und 'page' intern überspringen.
        // unset($rawRequestFilters['refresh']); 
        // unset($rawRequestFilters['page']);

        $structuredRequestFilters = $this->auctionService->structureRequestFilters($rawRequestFilters);
        if (!empty($structuredRequestFilters)){
            $this->logger->debug('[AuctionListingController] Strukturierte Filter aus Request erhalten', $structuredRequestFilters);
        }

        // 2. Filter aus den Moduleinstellungen lesen und parsen (sind bereits strukturiert)
        $moduleFilters = [];
        if ($model->auctionListingFilters) {
            $moduleFilters = $this->auctionService->parseFiltersFromString((string)$model->auctionListingFilters);
            $this->logger->debug('[AuctionListingController] Strukturierte Filter aus Moduleinstellungen geparst', $moduleFilters);
        }

        // 3. Filter zusammenführen (Request-Filter überschreiben Modul-Filter bei gleichen ZIEL-Feldnamen)
        // Beide Arrays ($moduleFilters, $structuredRequestFilters) sollten jetzt das gleiche strukturierte Format haben.
        $finalFilters = array_merge($moduleFilters, $structuredRequestFilters);
        if (!empty($finalFilters)){
             $this->logger->info('[AuctionListingController] Finale strukturierte Filter nach Merge', $finalFilters);
        }

        // 4. Sortieroptionen aus dem Modul lesen
        $sortBy = $model->auctionSortBy ?: null;
        $sortDirection = $model->auctionSortDirection ?: 'asc';
        $this->logger->debug('[AuctionListingController] Alte Sortieroptionen', ['sortBy' => $sortBy, 'sortDirection' => $sortDirection]);

        // 4.1 Neue mehrstufige Sortierregeln aus dem Modul lesen
        $sortRules = [];
        if ($model->auctionSortRules) {
            $sortRules = $this->auctionService->parseSortRulesFromString((string)$model->auctionSortRules);
            $this->logger->debug('[AuctionListingController] Neue Sortierregeln aus auctionSortRules geparst', ['sortRules' => $sortRules]);
        }

        // Prüfen, ob Daten neu geladen werden sollen (aus Request)
        $forceRefresh = $request->query->has('refresh') && $request->query->get('refresh') === '1';

        // 5. Alle Auktionen mit finalen Filtern und Sortierung abrufen
        $allAuctions = $this->auctionService->getAuctions($finalFilters, $forceRefresh, $sortBy, $sortDirection, $sortRules);
        $this->logger->info('[AuctionListingController] ' . count($allAuctions) . ' Auktionen vom Service erhalten.');
        
        // 6. Paginierung berechnen
        $totalItems = count($allAuctions);
        $totalPages = (int)ceil($totalItems / $itemsPerPage);
        $page = min($page, max(1, $totalPages)); // Page korrigieren falls zu hoch
        
        $offset = ($page - 1) * $itemsPerPage;
        $auctions = array_slice($allAuctions, $offset, $itemsPerPage);
        
        // Paginierungs-Informationen
        $pagination = [
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'itemsPerPage' => $itemsPerPage,
            'hasNextPage' => $page < $totalPages,
            'hasPrevPage' => $page > 1,
            'nextPage' => $page < $totalPages ? $page + 1 : null,
            'prevPage' => $page > 1 ? $page - 1 : null,
            'startItem' => $totalItems > 0 ? $offset + 1 : 0,
            'endItem' => min($offset + $itemsPerPage, $totalItems)
        ];
        
        // Template-Variablen setzen
        $template->auctions = $auctions;
        $template->pagination = $pagination;
        $template->filters = $finalFilters;

        // Ausgewähltes Item-Template aus dem Modul-Modell holen oder Default setzen
        $itemTemplateName = $model->auctionItemTemplate ?: 'auction_item.html.twig'; // Default, falls nichts ausgewählt
        // Wichtig: Hier muss der Twig-Namespace vorangestellt werden!
        $template->auctionItemTemplate = '@CaeliWindCaeliAuctionConnect/' . $itemTemplateName;
        $this->logger->debug('[AuctionListingController] Item-Template gesetzt auf: ' . $template->auctionItemTemplate);

        $this->logger->debug('[AuctionListingController] Setze Template-Variablen.');
        
        // Detailseite für Links einrichten
        $template->detailPage = null;
        if ($model->jumpTo) {
            // Framework nutzen, um PageModel zu holen
            $framework = $this->container->get('contao.framework');
            $framework->initialize();
            $template->detailPage = $framework->getAdapter(PageModel::class)->findById($model->jumpTo);
            if ($template->detailPage) {
                $this->logger->debug('[AuctionListingController] Detailseite gefunden', ['id' => $model->jumpTo, 'alias' => $template->detailPage->alias]);
                $template->detailPageUrl = $template->detailPage->getFrontendUrl();
            } else {
                $this->logger->warning('[AuctionListingController] Detailseite NICHT gefunden', ['id' => $model->jumpTo]);
                $template->detailPageUrl = null;
            }
        } else {
            $template->detailPageUrl = null;
        }

        $this->logger->debug('[AuctionListingController] Gebe Response zurück.');
        return $template->getResponse();
    }
}
