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
namespace Vsm\VsmHelperTools\Controller\Stripe;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller für die Abbruchbehandlung von Stripe-Checkout
 */
class CancelHandlerController extends BaseStripeController
{
    use UtilityTrait;
    
    /**
     * Verarbeitet den Abbruch eines Stripe-Checkout
     * Leitet auf eine Abbruchseite weiter, die in den Produktdaten angegeben wurde
     */
    #[Route('/checkout/cancel', name: 'stripe_checkout_cancel', methods: ['GET'])]
    public function handleCancel(Request $request): Response
    {
        // Session-ID aus dem Request extrahieren
        $sessionId = $request->query->get('session_id');
        
        // Abbruch-URL aus der Session holen, falls vorhanden
        $cancelUrl = '/';
        if ($sessionId) {
            $sessionData = $this->sessionManager->getSessionData($sessionId);
            if ($sessionData && isset($sessionData['product_data']['cancel_url'])) {
                $cancelUrl = $sessionData['product_data']['cancel_url'];
            }
        }
        
        // Auf Abbruchseite umleiten
        return $this->redirect($cancelUrl);
    }
} 