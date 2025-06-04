<?php

declare(strict_types=1);

namespace App\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Environment;
use Contao\PageModel;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Symfony\Component\HttpFoundation\RequestStack;

class PaginationCanonicalListener
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly ScopeMatcher $scopeMatcher,
        private readonly RequestStack $requestStack
    ) {
    }

    #[AsHook('generatePage')]
    public function onGeneratePage(PageModel $pageModel): void
    {
        $request = $this->requestStack->getCurrentRequest();
        
        if (!$request || !$this->scopeMatcher->isFrontendRequest($request)) {
            return;
        }

        $this->framework->initialize();
        
        // Prüfe auf Paginierungs-Parameter
        $paginationParams = $this->getPaginationParams($request);
        
        if (empty($paginationParams)) {
            return; // Keine Paginierung gefunden
        }

        // Canonical URL (immer zur ersten Seite)
        $canonicalUrl = $this->getCanonicalUrl($request, $pageModel);
        
        // Prev/Next URLs für Paginierung
        $prevNextUrls = $this->getPrevNextUrls($request, $paginationParams);
        
        // Head-Tags setzen
        $this->setCanonicalTag($canonicalUrl);
        $this->setPrevNextTags($prevNextUrls);
        $this->setMetaRobots($paginationParams);
    }

    private function getPaginationParams($request): array
    {
        $params = [];
        $query = $request->query->all();
        
        // Verschiedene Paginierungs-Parameter suchen
        foreach ($query as $key => $value) {
            if (preg_match('/^page(_n\d+)?$/', $key) && (int)$value > 1) {
                $params[$key] = (int)$value;
            }
        }
        
        return $params;
    }

    private function getCanonicalUrl($request, PageModel $pageModel): string
    {
        // Basis-URL ohne Paginierungs-Parameter
        $url = $request->getSchemeAndHttpHost() . $request->getPathInfo();
        
        // Query-Parameter ohne Paginierung
        $query = $request->query->all();
        foreach ($query as $key => $value) {
            if (preg_match('/^page(_n\d+)?$/', $key)) {
                unset($query[$key]);
            }
        }
        
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        
        return $url;
    }

    private function getPrevNextUrls($request, array $paginationParams): array
    {
        $urls = ['prev' => null, 'next' => null];
        
        foreach ($paginationParams as $paramName => $currentPage) {
            $baseUrl = $request->getSchemeAndHttpHost() . $request->getPathInfo();
            $query = $request->query->all();
            
            // Debug-Log
            error_log("PaginationCanonical: Processing param $paramName = $currentPage");
            
            // Previous Page
            if ($currentPage > 2) {
                $query[$paramName] = $currentPage - 1;
                $urls['prev'] = $baseUrl . '?' . http_build_query($query);
                error_log("PaginationCanonical: Prev URL (page > 2): " . $urls['prev']);
            } elseif ($currentPage === 2) {
                unset($query[$paramName]);
                $urls['prev'] = empty($query) ? $baseUrl : $baseUrl . '?' . http_build_query($query);
                error_log("PaginationCanonical: Prev URL (page = 2): " . $urls['prev']);
            }
            
            // Next Page - hier müssten wir die maximale Seitenzahl kennen
            // Das ist komplex, da wir nicht wissen wie viele Seiten es gibt
            // Besser: Im Template über JavaScript oder zusätzliche Logik
            
            break; // Nur den ersten Paginierungs-Parameter behandeln
        }
        
        return $urls;
    }

    private function setCanonicalTag(string $url): void
    {
        $GLOBALS['TL_HEAD'][] = '<link rel="canonical" href="' . $url . '">';
    }

    private function setPrevNextTags(array $urls): void
    {
        if ($urls['prev']) {
            $GLOBALS['TL_HEAD'][] = '<link rel="prev" href="' . $urls['prev'] . '">';
        }
        
        if ($urls['next']) {
            $GLOBALS['TL_HEAD'][] = '<link rel="next" href="' . $urls['next'] . '">';
        }
    }

    private function setMetaRobots(array $paginationParams): void
    {
        // Seite 2+ Meta-Robots setzen unter Berücksichtigung der Staging-Schutzregel
        if (!empty($paginationParams)) {
            // Bestehende robots-Meta-Tags entfernen
            if (isset($GLOBALS['TL_HEAD'])) {
                $GLOBALS['TL_HEAD'] = array_filter($GLOBALS['TL_HEAD'], function($tag) {
                    return !preg_match('/<meta[^>]+name=["\']robots["\'][^>]*>/i', $tag);
                });
            }
            
            // Gleiche Hostname-Prüfung wie im fe_page.html5 Template
            $hostname = $_SERVER['HTTP_HOST'] ?? '';
            
            if (strpos($hostname, 'www.') === 0) {
                // Produktionsumgebung: index,follow für paginierte Seiten
                $GLOBALS['TL_HEAD'][] = '<meta name="robots" content="index, follow">';
                error_log('PaginationCanonical: Meta robots gesetzt für Produktionsumgebung: index, follow');
            } else {
                // Staging-Umgebung: noindex,nofollow (wie im fe_page Template)
                $GLOBALS['TL_HEAD'][] = '<meta name="robots" content="noindex,nofollow">';
                error_log('PaginationCanonical: Meta robots gesetzt für Staging-Umgebung: noindex,nofollow');
            }
        }
    }
} 