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
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use CaeliWind\CaeliAuctionConnect\Service\AuctionService;

#[AsFrontendModule(category: 'caeli_wind', template: 'mod_auction_filter', name: 'auction_filter')]
class AuctionFilterController extends AbstractFrontendModuleController
{
    public const TYPE = 'auction_filter';

    protected ?PageModel $page;

    public function __construct(
        private readonly AuctionService $auctionService,
        private readonly TranslatorInterface $translator
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

        return $services;
    }

    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        // --- Filterkonfiguration definieren ---
        $filterConfigs = [
            'bundesland' => [
                'label' => 'Bundesland',
                'type' => 'select',
                'options_key' => 'bundeslaender',
                'placeholder' => 'Alle Bundesländer',
            ],
            'landkreis' => [
                'label' => 'Landkreis',
                'type' => 'select',
                'options_key' => 'landkreise',
                'placeholder' => 'Alle Landkreise',
            ],
            'status' => [
                'label' => 'Status',
                'type' => 'select',
                'options_key' => 'status_values',
                'placeholder' => 'Alle Status',
            ],
            'size' => [
                'label' => 'Größe (ha)',
                'type' => 'range_slider',
                'min' => 0,
                'max' => 500,
                'step' => 10,
            ],
            'leistung' => [
                'label' => 'Leistung (MW)',
                'type' => 'range_slider',
                'min' => 0,
                'max' => 250,
                'step' => 5,
            ],
            'volllaststunden' => [
                'label' => 'Volllaststunden',
                'type' => 'range_slider',
                'min' => 0,
                'max' => 4000,
                'step' => 100,
            ],
        ];

        // --- Optionen für Select-Felder vorbereiten ---
        $bundeslaender = $this->auctionService->getAllBundeslaender();
        $selectedBundesland = $request->query->get('bundesland');
        $landkreise = $this->auctionService->getAllLandkreise(false, $selectedBundesland ?: null);

        // Status-Optionen definieren (mit Übersetzungen)
        $statusOptions = [
            'STARTED' => $this->translator->trans('filter.status.started', [], 'messages'),
            'OPEN_FOR_DIRECT_AWARDING' => $this->translator->trans('filter.status.open_for_direct_awarding', [], 'messages'),
            'DIRECT_AWARDING' => $this->translator->trans('filter.status.direct_awarding', [], 'messages'),
            'AWARDING' => $this->translator->trans('filter.status.awarding', [], 'messages'),
            'PRE_RELEASE' => $this->translator->trans('filter.status.pre_release', [], 'messages'),
        ];

        $options = [
            'bundeslaender' => array_combine($bundeslaender, $bundeslaender),
            'landkreise' => array_combine($landkreise, $landkreise),
            'status_values' => $statusOptions,
        ];

        // --- Aktuelle Filterwerte aus der Anfrage extrahieren (für Template) ---
        $templateFilters = [];
        foreach ($filterConfigs as $key => $config) {
            if ($config['type'] === 'select') {
                if ($request->query->has($key)) {
                    $templateFilters[$key] = $request->query->get($key);
                }
            } elseif ($config['type'] === 'range_slider') {
                if ($request->query->has($key . '_min')) {
                    $templateFilters[$key . '_min'] = $request->query->get($key . '_min');
                }
                if ($request->query->has($key . '_max')) {
                    $templateFilters[$key . '_max'] = $request->query->get($key . '_max');
                }
            }
        }

        // --- Aktionen durchführen ---
        if ($request->query->has('refresh') && $request->query->get('refresh') === '1') {
            $this->auctionService->getAuctions([], true);
        }

        // --- Variablen an das Template übergeben ---
        $template->filter_configs = $filterConfigs;
        $template->options = $options;
        $template->filters = $templateFilters;

        // --- Antwort generieren ---
        if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                return $template->getResponse();
        }

        return $template->getResponse();
    }
}
