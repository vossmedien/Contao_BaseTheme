<?php

declare(strict_types=1);

namespace App\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Template;
use Symfony\Component\HttpFoundation\RequestStack;

class PaginationCanonicalListener
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly RequestStack $requestStack
    ) {
    }

    #[AsHook('parseTemplate', priority: 100)]
    public function onParseTemplate(Template $template): void
    {
        $request = $this->requestStack->getCurrentRequest();
        
        if (!$request) {
            return;
        }

        // 1. Canonical URL Optimierung für Page Templates
        if (in_array($template->getName(), ['fe_page', 'fe_page_uk'])) {
            $this->optimizeCanonicalUrl($template, $request);
        }
        
        // 2. Link-Optimierung für alle Templates mit Links
        $this->optimizeLinks($template, $request);
    }

    private function optimizeCanonicalUrl(Template $template, $request): void
    {
        // Prüfe auf Kategorie-Parameter in der URL
        $currentPath = $request->getRequestUri();
        $hasCategories = $this->hasActiveCategories($request, $currentPath);
        
        if (!$hasCategories) {
            return;
        }

        // Canonical URL zur Hauptseite ohne Filter/Kategorien
        $canonicalUrl = $this->getCanonicalUrl($request);
        
        // Template-Variable überschreiben
        $template->canonical = $canonicalUrl;
        
        // Debug-Ausgabe
        $GLOBALS['TL_HEAD'][] = '<!-- PaginationCanonical: Template ' . $template->getName() . ' canonical überschrieben auf: ' . $canonicalUrl . ' -->';
    }

    private function optimizeLinks(Template $template, $request): void
    {
        // Nur bei Templates mit Inhalt, die Links haben könnten
        $contentProperties = ['text', 'html', 'content', 'body', 'main', 'articles'];
        
        foreach ($contentProperties as $property) {
            if (isset($template->$property) && is_string($template->$property)) {
                $template->$property = $this->enhanceLinks($template->$property, $request);
            }
        }
    }

    private function enhanceLinks(string $content, $request): string
    {
        // Regex für interne Links ohne title/aria-label
        $pattern = '/<a\s+([^>]*?)href=(["\'])([^"\']*)\2([^>]*?)>([^<]+)<\/a>/i';
        
        return preg_replace_callback($pattern, function ($matches) use ($request) {
            $beforeHref = $matches[1];
            $quote = $matches[2];
            $href = $matches[3];
            $afterHref = $matches[4];
            $linkText = $matches[5];
            
            // Nur interne Links bearbeiten
            if (!$this->isInternalLink($href, $request)) {
                return $matches[0];
            }
            
            // Prüfe ob bereits title oder aria-label vorhanden
            if (preg_match('/\b(title|aria-label)\s*=/i', $beforeHref . $afterHref)) {
                return $matches[0];
            }
            
            // Generiere optimierte Attribute
            $title = $this->generateLinkTitle($href, $linkText);
            $ariaLabel = $this->generateAriaLabel($href, $linkText);
            
            // Attribute hinzufügen
            $enhancedAttributes = $afterHref;
            if ($title) {
                $enhancedAttributes .= ' title="' . htmlspecialchars($title, ENT_QUOTES) . '"';
            }
            if ($ariaLabel) {
                $enhancedAttributes .= ' aria-label="' . htmlspecialchars($ariaLabel, ENT_QUOTES) . '"';
            }
            
            return '<a ' . trim($beforeHref) . ' href=' . $quote . $href . $quote . $enhancedAttributes . '>' . $linkText . '</a>';
            
        }, $content);
    }

    private function isInternalLink(string $href, $request): bool
    {
        // Alle Links bearbeiten - sowohl interne als auch externe
        return true;
    }

    private function generateLinkTitle(string $href, string $linkText): ?string
    {
        // Einfach den Linktext als title verwenden - vollständig sprachenunabhängig
        return trim($linkText) ?: null;
    }

    private function generateAriaLabel(string $href, string $linkText): ?string
    {
        // Aria-label auf den gleichen Wert wie title setzen für bessere Accessibility
        return trim($linkText) ?: null;
    }

    private function hasActiveCategories($request, string $currentPath): bool
    {
        // Prüfe URL-Pfad auf Kategorien
        if (preg_match('/\/(category|filter|tag|type|year|month)\/[^\/]+/', $currentPath)) {
            return true;
        }
        
        // Prüfe Query-Parameter
        $categoryParams = $this->getCategoryParams($request);
        return !empty($categoryParams);
    }

    private function getCategoryParams($request): array
    {
        $params = [];
        $query = $request->query->all();
        
        // Standard Kategorie-Parameter
        $categoryKeys = ['category', 'filter', 'tag', 'type', 'search', 'year', 'month'];
        foreach ($categoryKeys as $key) {
            if (isset($query[$key]) && $query[$key] !== '') {
                $params[$key] = $query[$key];
            }
        }
        
        // MAE Event Categories Parameter
        foreach ($query as $key => $value) {
            if (preg_match('/^mae_/', $key) && $value !== '') {
                $params[$key] = $value;
            }
        }
        
        return $params;
    }

    private function getCanonicalUrl($request): string
    {
        $scheme = $request->getScheme();
        $host = $request->getHost();
        $path = $request->getPathInfo();
        
        // Entferne Kategorie-Pfade
        $cleanPath = preg_replace('/\/(category|filter|tag|type|year|month)\/[^\/]+/', '', $path);
        
        // Query-Parameter ohne Filter
        $query = $request->query->all();
        unset($query['category'], $query['filter'], $query['tag'], $query['type'], $query['search'], $query['year'], $query['month']);
        
        // Entferne Paginierungs-Parameter und MAE Parameter
        foreach ($query as $key => $value) {
            if (preg_match('/^(page(_n\d+)?|mae_.*)$/', $key)) {
                unset($query[$key]);
            }
        }
        
        $canonicalUrl = $scheme . '://' . $host . $cleanPath;
        
        if (!empty($query)) {
            $canonicalUrl .= '?' . http_build_query($query);
        }
        
        return $canonicalUrl;
    }
} 