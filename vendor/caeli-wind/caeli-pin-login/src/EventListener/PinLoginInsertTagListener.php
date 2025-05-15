<?php

declare(strict_types=1);

namespace CaeliWind\CaeliPinLogin\EventListener;

use CaeliWind\CaeliPinLogin\Session\PinLoginSessionManager;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsHook('replaceInsertTags')]
class PinLoginInsertTagListener
{
    private PinLoginSessionManager $sessionManager;
    private RequestStack $requestStack;
    private ContaoFramework $framework;

    public function __construct(
        PinLoginSessionManager $sessionManager,
        RequestStack $requestStack,
        ContaoFramework $framework
    ) {
        $this->sessionManager = $sessionManager;
        $this->requestStack = $requestStack;
        $this->framework = $framework;
    }

    public function __invoke(string $tag, bool $useCache, $cacheValue, array $flags): string|false
    {
        $parts = explode('::', $tag);

        if ('pin_login' !== $parts[0]) {
            return false; // Nicht unser Tag
        }

        if (!isset($parts[1])) {
            return false; // Kein Datenfeld angefordert
        }

        $requestedField = $parts[1];

        // Contao Framework initialisieren, um Zugriff auf $objPage zu gewährleisten
        $this->framework->initialize();

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request || !$this->framework->isInitialized()) {
            return false; // Sollte nicht vorkommen im Frontend
        }
        
        // Aktuelle Seite holen
        global $objPage;
        if (!isset($objPage) || !$objPage instanceof \Contao\PageModel) {
             return false; // Keine aktuelle Seite gefunden
        }
        $currentPageId = (int) $objPage->id;

        // Prüfen, ob die Seite überhaupt autorisiert ist (optional, aber sinnvoll)
        // Die Timeout-Dauer hier sollte zur Sicherheit mit der im Listener übereinstimmen
        // oder wir lassen die Prüfung weg und geben einfach die Daten zurück, falls vorhanden.
        // if (!$this->sessionManager->isPageAuthorized($currentPageId, 1800)) {
        //    return ''; // Nicht autorisiert oder Timeout
        // }

        // Benutzerdaten holen
        $userData = $this->sessionManager->getUserData($currentPageId);

        if (null === $userData) {
            return ''; // Keine Daten für diese Seite gespeichert
        }

        // Gewünschtes Feld zurückgeben
        switch ($requestedField) {
            case 'email':
                return htmlspecialchars((string)($userData['email'] ?? ''));
            case 'message':
                return htmlspecialchars((string)($userData['message'] ?? ''));
            // Hier könnten weitere Felder hinzugefügt werden, falls nötig
            default:
                return ''; // Unbekanntes Feld
        }
    }
} 