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

namespace CaeliWind\CaeliAuctionConnect\Service;

use Symfony\Component\HttpFoundation\Request;

class DeviceDetectionService
{
    /**
     * Prüft ob es sich um ein mobiles Gerät handelt
     */
    public function isMobileDevice(Request $request): bool
    {
        $userAgent = $request->headers->get('User-Agent', '');
        
        // Mobile User-Agent Patterns
        $mobilePatterns = [
            '/iPhone/i',
            '/iPad/i',
            '/Android/i',
            '/Mobile/i',
            '/webOS/i',
            '/BlackBerry/i',
            '/Windows Phone/i',
            '/Opera Mini/i',
            '/IEMobile/i',
            '/Mobile Safari/i',
        ];
        
        foreach ($mobilePatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Prüft ob es sich um ein Tablet handelt
     */
    public function isTabletDevice(Request $request): bool
    {
        $userAgent = $request->headers->get('User-Agent', '');
        
        $tabletPatterns = [
            '/iPad/i',
            '/Android(?!.*Mobile)/i', // Android ohne "Mobile" = meist Tablet
            '/Tablet/i',
        ];
        
        foreach ($tabletPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Gibt den Gerätetyp zurück
     */
    public function getDeviceType(Request $request): string
    {
        if ($this->isTabletDevice($request)) {
            return 'tablet';
        }
        
        if ($this->isMobileDevice($request)) {
            return 'mobile';
        }
        
        return 'desktop';
    }
} 