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
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\Template;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;
use CaeliWind\CaeliAuctionConnect\Service\AuctionService;

#[AsFrontendModule(category: 'caeli_wind', template: 'mod_auction_filter', name: 'auction_filter')]
class AuctionFilterController extends AbstractFrontendModuleController
{
    public const TYPE = 'auction_filter';

    protected ?PageModel $page;

    public function __construct(
        private readonly AuctionService $auctionService
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
        // Abrufen der Filter-Parameter aus der Anfrage
        $filters = [];
        
        // Bundesland-Filter
        if ($request->query->has('bundesland')) {
            $filters['bundesland'] = $request->query->get('bundesland');
        }
        
        // Landkreis-Filter
        if ($request->query->has('landkreis')) {
            $filters['landkreis'] = $request->query->get('landkreis');
        }
        
        // Größe-Filter
        if ($request->query->has('size_min') || $request->query->has('size_max')) {
            $filters['size'] = [
                'min' => $request->query->get('size_min', ''),
                'max' => $request->query->get('size_max', ''),
            ];
        }
        
        // Leistung-Filter
        if ($request->query->has('leistung_min') || $request->query->has('leistung_max')) {
            $filters['leistung'] = [
                'min' => $request->query->get('leistung_min', ''),
                'max' => $request->query->get('leistung_max', ''),
            ];
        }
        
        // Volllaststunden-Filter
        if ($request->query->has('volllaststunden_min') || $request->query->has('volllaststunden_max')) {
            $filters['volllaststunden'] = [
                'min' => $request->query->get('volllaststunden_min', ''),
                'max' => $request->query->get('volllaststunden_max', ''),
            ];
        }
        
        // Status-Filter
        if ($request->query->has('status')) {
            $filters['status'] = $request->query->get('status');
        }

        // Aktualisierung der Auktionsdaten bei Bedarf
        if ($request->query->has('refresh') && $request->query->get('refresh') === '1') {
            // Rufe Auktionen ab, was intern den Cache aktualisiert
            $this->auctionService->getAuctions([], true);
        }

        // Abrufen der verfügbaren Bundesländer und Landkreise für die Filter
        $bundeslaender = $this->auctionService->getAllBundeslaender();
        $landkreise = $this->auctionService->getAllLandkreise(false, $filters['bundesland'] ?? null);

        // Variablen an das Template übergeben
        $template->filters = $filters;
        $template->bundeslaender = $bundeslaender;
        $template->landkreise = $landkreise;

        return $template->getResponse();
    }
} 