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
use Symfony\Component\Security\Core\Security;
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
        $services['security.helper'] = Security::class;
        $services['translator'] = TranslatorInterface::class;

        return $services;
    }

    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        // Filter aus Request oder ModuleModel extrahieren
        $filters = [];
        
        // Beispiel: Größenfilter
        if ($request->query->has('size_min') && $request->query->has('size_max')) {
            $filters['size'] = [
                'min' => (int) $request->query->get('size_min'),
                'max' => (int) $request->query->get('size_max')
            ];
        }
        
        // Beispiel: Bundesland-Filter
        if ($request->query->has('bundesland')) {
            $filters['bundesland'] = $request->query->get('bundesland');
        }
        
        // Beispiel: Landkreis-Filter
        if ($request->query->has('landkreis')) {
            $filters['landkreis'] = $request->query->get('landkreis');
        }
        
        // Beispiel: Status-Filter
        if ($request->query->has('status')) {
            $filters['status'] = $request->query->get('status');
        }
        
        // Beispiel: Leistungs-Filter
        if ($request->query->has('leistung_min') && $request->query->has('leistung_max')) {
            $filters['leistung'] = [
                'min' => (int) $request->query->get('leistung_min'),
                'max' => (int) $request->query->get('leistung_max')
            ];
        }
        
        // Volllaststunden-Filter
        if ($request->query->has('volllaststunden_min') && $request->query->has('volllaststunden_max')) {
            $filters['volllaststunden'] = [
                'min' => (int) $request->query->get('volllaststunden_min'),
                'max' => (int) $request->query->get('volllaststunden_max')
            ];
        }
        
        // Auktionen abrufen (gefiltert oder alle)
        // Prüfen, ob Daten neu geladen werden sollen
        $forceRefresh = $request->query->has('refresh') && $request->query->get('refresh') === '1';
        $auctions = $this->auctionService->getAuctions($filters, $forceRefresh);
        
        // DEBUG: Erste Auktion überprüfen
        if (!empty($auctions)) {
            $firstAuction = reset($auctions);
            $this->logger->debug('Erste Auktion ID: ' . ($firstAuction['id'] ?? 'nicht vorhanden'), [
                'auction_keys' => array_keys($firstAuction),
                'auction_id' => $firstAuction['id'] ?? 'nicht vorhanden',
                'auction_auction_id' => $firstAuction['auction_id'] ?? 'nicht vorhanden',
                'complete_auction' => $firstAuction
            ]);
        }
        
        // Template-Variablen setzen
        $template->auctions = $auctions;
        $template->filters = $filters;
        $template->bundeslaender = $this->auctionService->getAllBundeslaender();
        $template->landkreise = $this->auctionService->getAllLandkreise(false, $filters['bundesland'] ?? null);
        
        // Detailseite für Links einrichten
        $template->detailPage = $model->jumpTo ? PageModel::findById($model->jumpTo) : null;
        
        return $template->getResponse();
    }
}
