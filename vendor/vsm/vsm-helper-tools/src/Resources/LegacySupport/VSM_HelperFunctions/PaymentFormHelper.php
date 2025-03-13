<?php

namespace VSM_HelperFunctions;

/**
 * Legacy-Unterstützung für den alten Namespace
 * Diese Datei dient als Delegation zur neuen Klasse
 */
class PaymentFormHelper
{
    /**
     * Delegiert alle statischen Methodenaufrufe an die neue Klasse
     */
    public static function __callStatic($name, $arguments)
    {
        return \Vsm\VsmHelperTools\Helper\PaymentFormHelper::$name(...$arguments);
    }

    /**
     * Explizit die wichtigsten Methoden definieren, um IDE-Unterstützung zu gewährleisten
     */
    public static function generateForm($amount, $currency = 'eur', $description = '', $redirectUrl = '')
    {
        return \Vsm\VsmHelperTools\Helper\PaymentFormHelper::generateForm($amount, $currency, $description, $redirectUrl);
    }
} 