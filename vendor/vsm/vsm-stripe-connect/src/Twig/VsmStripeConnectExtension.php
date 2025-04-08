<?php

declare(strict_types=1);

/*
 * This file is part of vsm-stripe-connect.
 *
 * (c) Christian Voss 2025 <christian@vossmedien.de>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/vsm/vsm-stripe-connect
 */

namespace Vsm\VsmStripeConnect\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Vsm\VsmStripeConnect\Helper\PaymentFormHelper;

class VsmStripeConnectExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            // PaymentFormHelper - aus dem Stripe-Connect-Modul
            new TwigFunction('vsm_payment_form', [PaymentFormHelper::class, 'generatePaymentFormHTML']),
        ];
    }

    public function getName(): string
    {
        return 'vsm_stripe_connect_extension';
    }
} 