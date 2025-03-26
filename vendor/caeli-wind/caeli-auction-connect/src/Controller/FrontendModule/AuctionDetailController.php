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
use Contao\Input;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\Template;
use CaeliWind\CaeliAuctionConnect\Service\AuctionService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Psr\Log\LoggerInterface;

#[AsFrontendModule(category: 'caeli_wind', template: 'mod_auction_detail', name: 'auction_detail')]
class AuctionDetailController extends AbstractFrontendModuleController
{
    public const TYPE = 'auction_detail';

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
        $services['contao.routing.scope_matcher'] = ScopeMatcher::class;
        $services['security.helper'] = AuthorizationCheckerInterface::class;
        $services['translator'] = TranslatorInterface::class;

        return $services;
    }

    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        // Explizite Log-Ausgabe, damit wir sehen, wenn der Controller aufgerufen wird
        $this->logger->info('AuctionDetailController wurde aufgerufen', [
            'request_uri' => $request->getRequestUri(),
            'auto_item' => Input::get('auto_item'),
            'auction_id' => Input::get('auction_id')
        ]);
        
        // Debug-Ausgabe für Entwicklung
        $template->debug = [
            'auto_item' => Input::get('auto_item'),
            'auction_id' => Input::get('auction_id'),
            'request_uri' => $request->getRequestUri(),
            'attributes' => $request->attributes->all(),
        ];
        
        // Die Auktions-ID aus dem Auto-Item oder dem speziellen Parameter abrufen
        $auctionId = Input::get('auction_id') ?: Input::get('auto_item');
        
        // Template-Variablen vor dem Laden der Auktion setzen
        $template->debug['extracted_id'] = $auctionId;
        $template->listingPage = $model->jumpTo ? PageModel::findById($model->jumpTo) : null;
        
        if (!$auctionId) {
            $translator = $this->container->get('translator');
            $template->error = $translator->trans('ERR.auctionNotFound', [], 'contao_default');
            $this->logger->error('Keine Auktions-ID gefunden (weder auto_item noch auction_id)', [
                'request_uri' => $request->getRequestUri()
            ]);
            return $template->getResponse();
        }
        
        // Auktionsdaten abrufen
        $auction = $this->auctionService->getAuctionById($auctionId);
        
        if (!$auction) {
            $translator = $this->container->get('translator');
            $template->error = $translator->trans('ERR.auctionNotFound', [], 'contao_default');
            $this->logger->error('Keine Auktion mit ID ' . $auctionId . ' gefunden', [
                'request_uri' => $request->getRequestUri()
            ]);
            
            // Versuchen Sie noch einmal mit einem Cache-Clear
            $this->logger->warning('Versuche es erneut mit Cache-Clear');
            $this->auctionService->clearCache();
            $auction = $this->auctionService->getAuctionById($auctionId);
            
            if (!$auction) {
                // Immer noch nichts gefunden
                $this->logger->error('Auch nach Cache-Clear keine Auktion mit ID ' . $auctionId . ' gefunden');
                return $template->getResponse();
            } else {
                $this->logger->info('Auktion nach Cache-Clear gefunden!');
            }
        }
        
        // Template-Variable setzen - WICHTIG: der Template-Name muss mit dem im HTML-Template übereinstimmen
        $template->auction = $auction;
        $template->auction_var = $auction; // Alternatives Template-Variable für Kompatibilität
        
        // Auch raw_data für das Template bereitstellen
        if (isset($auction['_raw_data'])) {
            $template->raw_data = $auction['_raw_data'];
        }
        
        // Log, dass alles erfolgreich war
        $this->logger->info('Auktion erfolgreich geladen', [
            'auction_id' => $auctionId,
            'auction_title' => $auction['title'] ?? 'unbekannt'
        ]);
        
        return $template->getResponse();
    }
} 