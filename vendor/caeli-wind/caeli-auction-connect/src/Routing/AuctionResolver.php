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

namespace CaeliWind\CaeliAuctionConnect\Routing;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Content\ContentUrlResolverInterface;
use Contao\CoreBundle\Routing\Content\ContentUrlResult;
use Contao\PageModel;

class AuctionResolver implements ContentUrlResolverInterface
{
    public function __construct(
        private readonly ContaoFramework $framework
    ) {
    }

    public function resolve(object $content): ContentUrlResult|null
    {
        // Hier könnten wir einen eigenen Content-Typ für Auktionen definieren
        // Im Moment führen wir aber kein Objekt-Mapping durch, daher immer null
        return null;
    }

    public function getParametersForContent(object $content, PageModel $pageModel): array
    {
        // Hier könnten wir einen eigenen Content-Typ für Auktionen definieren
        // Aktuell geben wir immer leere Parameter zurück
        return [];
    }
} 