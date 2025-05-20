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
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

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
                'label' => 'filter.label.bundesland',
                'type' => 'select',
                'options_key' => 'bundeslaender',
                'placeholder' => 'filter.placeholder.bundeslaender',
            ],
            'status' => [
                'label' => 'filter.label.status',
                'type' => 'select',
                'options_key' => 'status_values',
                'placeholder' => 'filter.placeholder.status_values',
            ],
            'property' => [
                'label' => 'filter.label.property',
                'type' => 'select',
                'options_key' => 'property_values',
                'placeholder' => 'filter.placeholder.property_values',
            ],
            'size' => [
                'label' => 'filter.label.size',
                'type' => 'range_slider',
                'min' => 0,
                'max' => 500,
                'step' => 10,
            ],
            'leistung' => [
                'label' => 'filter.label.leistung',
                'type' => 'range_slider',
                'min' => 0,
                'max' => 250,
                'step' => 5,
            ],
            'volllaststunden' => [
                'label' => 'filter.label.volllaststunden',
                'type' => 'range_slider',
                'min' => 0,
                'max' => 4000,
                'step' => 100,
            ],
            'irr' => [
                'label' => 'filter.label.irr',
                'type' => 'range_slider',
                'min' => 0,
                'max' => 20,
                'step' => 0.5,
            ],
        ];

        // --- Optionen für Select-Felder vorbereiten ---
        $bundeslaender = $this->auctionService->getAllBundeslaender();
        $selectedBundesland = $request->query->get('bundesland');

        // Status-Optionen definieren (mit Übersetzungen)
        $statusOptions = [
            'STARTED' => $this->translator->trans('filter.status.STARTED', [], 'messages'),
            'OPEN_FOR_DIRECT_AWARDING' => $this->translator->trans('filter.status.OPEN_FOR_DIRECT_AWARDING', [], 'messages'),
            'DIRECT_AWARDING' => $this->translator->trans('filter.status.DIRECT_AWARDING', [], 'messages'),
            'AWARDING' => $this->translator->trans('filter.status.AWARDING', [], 'messages'),
            'PRE_RELEASE' => $this->translator->trans('filter.status.PRE_RELEASE', [], 'messages'),
        ];

        // Eigentum-Optionen dynamisch abrufen und übersetzen
        $propertyValues = $this->auctionService->getUniquePropertyValues();
        $propertyOptions = [];
        foreach ($propertyValues as $value) {
            // Erstelle einen Übersetzungsschlüssel, z.B. filter.property.PRIVATE
            // Stelle sicher, dass die Werte aus der API (z.B. 'PRIVATE') hier korrekt als Key verwendet werden.
            // Die Übersetzung muss dann in der messages.de.yaml etc. existieren.
            $translationKey = 'filter.property.' . strtoupper($value);
            $propertyOptions[$value] = $this->translator->trans($translationKey, [], 'messages');
        }

        $options = [
            'bundeslaender' => array_combine($bundeslaender, $bundeslaender),
            'status_values' => $statusOptions,
            'property_values' => $propertyOptions,
        ];

        // --- Aktuelle Filterwerte aus der Anfrage extrahieren (für Template) ---
        $templateFilters = [];

        // Explizit den 'focus'-Parameter hinzufügen, Default auf 'false' wenn nicht im Request.
        // Der Wert aus dem Request ist ein String ('true' oder 'false') oder null, wenn er fehlt.
        // get('focus', 'false') stellt sicher, dass es immer 'true' oder 'false' als String ist.
        $templateFilters['focus'] = $request->query->get('focus', 'false');

        foreach ($filterConfigs as $key => $config) {
            if ($config['type'] === 'select') {
                if ($request->query->has($key)) {
                    $templateFilters[$key] = $request->query->get($key);
                } else {
                    $templateFilters[$key] = null; // Explizit null setzen, wenn nicht im Request, um sicherzustellen, dass der Key existiert
                }
            } elseif ($config['type'] === 'range_slider') {
                $minKey = $key . '_min';
                $maxKey = $key . '_max';

                if ($request->query->has($minKey)) {
                    $templateFilters[$minKey] = $request->query->get($minKey);
                } else {
                    // Default auf den konfigurierten Minimalwert des Sliders setzen
                    $templateFilters[$minKey] = $config['min'] ?? null;
                }

                if ($request->query->has($maxKey)) {
                    $templateFilters[$maxKey] = $request->query->get($maxKey);
                } else {
                    // Default auf den konfigurierten Maximalwert des Sliders setzen
                    $templateFilters[$maxKey] = $config['max'] ?? null;
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
        // Die spezielle AJAX-Behandlung wird entfernt.
        // Das Frontend-JS parst die komplette Antwort und extrahiert die benötigten Teile.
        // if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
        //     return new Response($template->parse()); // <-- Entfernt
        // }

        // Immer die Standard-Antwort zurückgeben
        return $template->getResponse();
    }
}
