<?php

declare(strict_types=1);

namespace CaeliWind\CaeliPinLogin\Session;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class PinLoginSessionManager
{
    public const SESSION_PREFIX = 'caeli_pin_login';
    
    private SessionInterface $session;

    public function __construct(RequestStack $requestStack)
    {
        $this->session = $requestStack->getCurrentRequest()->getSession();
    }

    /**
     * Prüft, ob eine Seite bereits autorisiert ist und die Autorisierung nicht abgelaufen ist
     */
    public function isPageAuthorized(int $pageId, int $timeout = 1800): bool
    {
        $authorizedPages = $this->getAuthorizedPages();
        
        if (!isset($authorizedPages[$pageId])) {
            return false;
        }
        
        // Prüfen, ob die Autorisierung abgelaufen ist
        if (time() - $authorizedPages[$pageId]['timestamp'] > $timeout) {
            // Autorisierung abgelaufen, aus der Liste entfernen
            unset($authorizedPages[$pageId]);
            $this->setAuthorizedPages($authorizedPages);
            return false;
        }
        
        return true;
    }

    /**
     * Speichert die Referrer-URL in der Session
     */
    public function setReferrer(string $url): void
    {
        $this->session->set(self::SESSION_PREFIX . '_referrer', $url);
    }

    /**
     * Gibt die Referrer-URL aus der Session zurück
     */
    public function getReferrer(): ?string
    {
        return $this->session->get(self::SESSION_PREFIX . '_referrer');
    }

    /**
     * Entfernt die Referrer-URL aus der Session
     */
    public function clearReferrer(): void
    {
        $this->session->remove(self::SESSION_PREFIX . '_referrer');
    }

    /**
     * Speichert die Ziel-Seiten-ID in der Session
     */
    public function setTargetPageId(int $pageId): void
    {
        $this->session->set(self::SESSION_PREFIX . '_target_page_id', $pageId);
    }

    /**
     * Gibt die Ziel-Seiten-ID aus der Session zurück
     */
    public function getTargetPageId(): ?int
    {
        return $this->session->get(self::SESSION_PREFIX . '_target_page_id');
    }

    /**
     * Speichert den erwarteten PIN-Wert in der Session
     */
    public function setExpectedPin(string $pin): void
    {
        $this->session->set(self::SESSION_PREFIX . '_expected_pin', $pin);
    }

    /**
     * Gibt den erwarteten PIN-Wert aus der Session zurück
     */
    public function getExpectedPin(): ?string
    {
        return $this->session->get(self::SESSION_PREFIX . '_expected_pin');
    }

    /**
     * Speichert zusätzliche Daten für eine Seite in der Session
     */
    public function setPageData(int $pageId, array $data): void
    {
        $pageData = $this->session->get(self::SESSION_PREFIX . '_page_data', []);
        $pageData[$pageId] = $data;
        $this->session->set(self::SESSION_PREFIX . '_page_data', $pageData);
    }

    /**
     * Gibt zusätzliche Daten für eine Seite aus der Session zurück
     */
    public function getPageData(int $pageId): ?array
    {
        $pageData = $this->session->get(self::SESSION_PREFIX . '_page_data', []);
        return $pageData[$pageId] ?? null;
    }

    /**
     * Markiert eine Seite als autorisiert
     */
    public function authorizePageAccess(int $pageId, array $additionalData = []): void
    {
        $authorizedPages = $this->getAuthorizedPages();
        $authorizedPages[$pageId] = [
            'timestamp' => time(),
            'data' => $additionalData
        ];
        $this->setAuthorizedPages($authorizedPages);
    }

    /**
     * Entfernt die Autorisierung für eine Seite
     */
    public function deauthorizePageAccess(int $pageId): void
    {
        $authorizedPages = $this->getAuthorizedPages();
        unset($authorizedPages[$pageId]);
        $this->setAuthorizedPages($authorizedPages);
    }

    /**
     * Debug: Gibt alle autorisierten Seiten für Debug-Zwecke zurück
     */
    public function getDebugAuthorizedPages(): array
    {
        return $this->getAuthorizedPages();
    }

    /**
     * Gibt alle autorisierten Seiten zurück
     */
    private function getAuthorizedPages(): array
    {
        return $this->session->get(self::SESSION_PREFIX . '_authorized_pages', []);
    }

    /**
     * Speichert die Liste der autorisierten Seiten
     */
    private function setAuthorizedPages(array $pages): void
    {
        $this->session->set(self::SESSION_PREFIX . '_authorized_pages', $pages);
    }
} 