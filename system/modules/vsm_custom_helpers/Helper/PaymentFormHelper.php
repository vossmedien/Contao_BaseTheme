<?php

namespace VSM_HelperFunctions;

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