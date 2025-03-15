<?php

declare(strict_types=1);

/*
 * This file is part of VSM Helper und Integrations.
 *
 * (c) Vossmedien - Christian Voss 2025 <christian@vossmedien.de>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/vsm/vsm-helper-tools
 */
namespace Vsm\VsmHelperTools\Helper;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\System;
use Contao\FrontendTemplate;

class PaymentFormHelper
{
    public static function generatePaymentFormHTML(
        $personalDataFields = [],
        $stripePublicKey = '',
        $successUrl = '',
        $stripeCurrency = 'eur',
        $productData = [],
        $elementId = '',
        $createUser = false,
        $showPrivacyNotice = false,
        $privacyPage = ''
    ) {
        $framework = System::getContainer()->get('contao.framework');
        $framework->initialize();

        $template = new FrontendTemplate('_stripe_form');

        $template->stripeKey = $stripePublicKey;
        $template->createUser = $createUser;
        $template->successUrl = $successUrl;
        $template->currency = $stripeCurrency;
        $template->productData = htmlspecialchars(json_encode($productData));
        $template->elementId = $elementId;
        $template->personalDataFields = $personalDataFields;
        $template->showPrivacyNotice = $showPrivacyNotice;
        $template->privacyPage = $privacyPage;

        return $template->parse();
    }
}