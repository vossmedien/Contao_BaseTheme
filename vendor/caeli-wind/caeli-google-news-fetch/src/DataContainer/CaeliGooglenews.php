<?php

declare(strict_types=1);

/*
 * This file is part of Caeli Google News Fetcher.
 *
 * (c) Caeli Wind - Christian Voss 2025 <christian.voss@caeli-wind.de>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/caeli-wind/caeli-google-news-fetch
 */

namespace CaeliWind\CaeliGoogleNewsFetch\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Contao\Input;
use Contao\System;

#[AsCallback(table: 'tl_caeli_googlenews', target: 'edit.buttons', priority: 100)]
class CaeliGooglenews
{

    public function __construct(
        private readonly ContaoFramework $framework,
    ) {
    }

    public function __invoke(array $arrButtons, DataContainer $dc): array
    {
        $inputAdapter = $this->framework->getAdapter(Input::class);
        $systemAdapter = $this->framework->getAdapter(System::class);

        $systemAdapter->loadLanguageFile('tl_caeli_googlenews');

        if ('edit' === $inputAdapter->get('act')) {
            $arrButtons['customButton'] = '<button type="submit" name="customButton" id="customButton" class="tl_submit customButton" accesskey="x">'.$GLOBALS['TL_LANG']['tl_caeli_googlenews']['customButton'].'</button>';
        }

        return $arrButtons;
    }
}
