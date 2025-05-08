<?php

declare(strict_types=1);

/*
 * This file is part of VSM News und Event Enhancements.
 *
 * (c) Vossmedien - Christian Voss 2025 <christian@vossmedien.de>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/vsm/vsm-news-events-enhancements
 */

namespace Vsm\VsmNewsEventsEnhancements\ContaoManager;

use Vsm\VsmNewsEventsEnhancements\VsmVsmNewsEventsEnhancements;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CalendarBundle\ContaoCalendarBundle;
// Contao\NewsBundle\ContaoNewsBundle; // Entfernen, da News-Anpassungen im caeli-wind Bundle sind
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;

class Plugin implements BundlePluginInterface
{
    /**
     * @return array
     */
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create(VsmVsmNewsEventsEnhancements::class)
                ->setLoadAfter([
                    ContaoCoreBundle::class,
                    ContaoCalendarBundle::class
                    // ContaoNewsBundle::class // Entfernen
                ]),
        ];
    }
}
