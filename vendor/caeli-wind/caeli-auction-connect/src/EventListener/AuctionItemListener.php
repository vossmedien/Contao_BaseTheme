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

namespace CaeliWind\CaeliAuctionConnect\EventListener;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Input;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Symfony\Component\HttpFoundation\RequestStack;
use Psr\Log\LoggerInterface;

/**
 * Class AuctionItemListener
 * Verarbeitet URL-Parameter für Auction-Detailseiten.
 */
class AuctionItemListener
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly LoggerInterface $logger,
        private readonly RequestStack $requestStack,
        private readonly ScopeMatcher $scopeMatcher
    ) {
    }

    /**
     * Hook für getPageIdFromUrl
     */
    public function __invoke(array $fragments): ?int
    {
        $request = $this->requestStack->getCurrentRequest();

        // Nur für Frontend-Anfragen
        if (!$request || !$this->scopeMatcher->isFrontendRequest($request)) {
            return null;
        }

        // Früher Ausstieg: Nur bei Auction-URLs weitermachen
        $path = $request->getPathInfo();
        if (!preg_match('/(auction|auktion|detail)/i', $path)) {
            return null;
        }

        // Framework nur bei relevanten URLs initialisieren
        $this->framework->initialize();
        $inputAdapter = $this->framework->getAdapter(Input::class);



        // Die URL in Segmente teilen und nach IDs suchen
        $pathSegments = explode('/', trim($path, '/'));
        $lastSegment = end($pathSegments);

        // Prüfen, ob das letzte Segment eine potenzielle ID ist
        if ($lastSegment && $lastSegment !== 'detail' && preg_match('/^[a-zA-Z0-9_-]+$/', $lastSegment)) {
            $inputAdapter->setGet('auto_item', $lastSegment);
            $inputAdapter->setGet('auction_id', $lastSegment);


        }

        // Standard-Fragments-Verarbeitung (falls obige Methode nichts findet)
        if (count($fragments) > 1 && !empty($fragments[1]) && preg_match('/^[a-zA-Z0-9_-]+$/', $fragments[1])) {
            $inputAdapter->setGet('auto_item', $fragments[1]);
            $inputAdapter->setGet('auction_id', $fragments[1]);


        }

        // Wir geben null zurück, damit die normale Contao-Seitenauflösung fortgesetzt wird
        return null;
    }
}
