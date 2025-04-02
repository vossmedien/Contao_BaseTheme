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
        $this->logger->debug('[AuctionListingController] getResponse gestartet.');
        // Filter aus Request oder ModuleModel extrahieren
        $filters = [];
        
        // Beispiel: Größenfilter
        if ($request->query->has('size_min') || $request->query->has('size_max')) {
            $filters['size'] = [
                'min' => $request->query->get('size_min'),
                'max' => $request->query->get('size_max')
            ];
             $this->logger->debug('[AuctionListingController] Größenfilter aus Request erkannt', $filters['size']);
        }
        
        // Beispiel: Bundesland-Filter
        if ($request->query->has('bundesland')) {
            $filters['bundesland'] = $request->query->get('bundesland');
             $this->logger->debug('[AuctionListingController] Bundeslandfilter aus Request erkannt', ['bundesland' => $filters['bundesland']]);
        }
        
        // Beispiel: Landkreis-Filter
        if ($request->query->has('landkreis')) {
            $filters['landkreis'] = $request->query->get('landkreis');
             $this->logger->debug('[AuctionListingController] Landkreisfilter aus Request erkannt', ['landkreis' => $filters['landkreis']]);
        }
        
        // Beispiel: Status-Filter
        if ($request->query->has('status')) {
            $filters['status'] = $request->query->get('status');
             $this->logger->debug('[AuctionListingController] Statusfilter aus Request erkannt', ['status' => $filters['status']]);
        }
        
        // Beispiel: Leistungs-Filter
        if ($request->query->has('leistung_min') || $request->query->has('leistung_max')) {
            $filters['leistung'] = [
                'min' => $request->query->get('leistung_min'),
                'max' => $request->query->get('leistung_max')
            ];
             $this->logger->debug('[AuctionListingController] Leistungsfilter aus Request erkannt', $filters['leistung']);
        }
        
        // Volllaststunden-Filter
        if ($request->query->has('volllaststunden_min') || $request->query->has('volllaststunden_max')) {
            $filters['volllaststunden'] = [
                'min' => $request->query->get('volllaststunden_min'),
                'max' => $request->query->get('volllaststunden_max')
            ];
             $this->logger->debug('[AuctionListingController] Volllaststundenfilter aus Request erkannt', $filters['volllaststunden']);
        }
        
        // Auktionen abrufen (gefiltert oder alle)
        // Prüfen, ob Daten neu geladen werden sollen
        $forceRefresh = $request->query->has('refresh') && $request->query->get('refresh') === '1';
        $this->logger->debug('[AuctionListingController] Rufe auctionService->getAuctions auf', ['filters' => $filters, 'forceRefresh' => $forceRefresh]);
        $auctions = $this->auctionService->getAuctions($filters, $forceRefresh);
        $this->logger->info('[AuctionListingController] ' . count($auctions) . ' Auktionen vom Service erhalten.');
        
        // Template-Variablen setzen
        $template->auctions = $auctions;
        $template->filters = $filters;
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
