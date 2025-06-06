<?php

declare(strict_types=1);

namespace CaeliWind\CaeliPinLogin\EventListener;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\PageFinder;
use Contao\PageModel;
use Contao\LayoutModel;
use Contao\PageRegular;
use CaeliWind\CaeliPinLogin\Session\PinLoginSessionManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

/**
 * @Hook("generatePage")
 */
class PinLoginCheckListener
{
    private RequestStack $requestStack;
    private PinLoginSessionManager $sessionManager;
    private RouterInterface $router;
    private ContaoFramework $framework;
    private PageFinder $pageFinder;
    private LoggerInterface $logger;

    public function __construct(
        RequestStack $requestStack,
        PinLoginSessionManager $sessionManager,
        RouterInterface $router,
        ContaoFramework $framework,
        PageFinder $pageFinder,
        LoggerInterface $logger
    ) {
        $this->requestStack = $requestStack;
        $this->sessionManager = $sessionManager;
        $this->router = $router;
        $this->framework = $framework;
        $this->pageFinder = $pageFinder;
        $this->logger = $logger;
    }

    /**
     * @Hook("generatePage")
     */
    public function __invoke(PageModel $pageModel, LayoutModel $layout, PageRegular $pageRegular): void
    {
        // Früher Ausstieg: Nur bei PIN-geschützten Seiten weitermachen
        if (!$pageModel->pin_protected || !$pageModel->pin_value) {
            return;
        }



        $this->framework->initialize();

        // PIN-Schutz ist aktiviert und PIN-Wert ist gesetzt
            $request = $this->requestStack->getCurrentRequest();
            if (null === $request) {
                return;
            }

            $currentUrl = $request->getUri();
            

            
            // Prüfen, ob diese spezifische Seite bereits autorisiert ist
            if (!$this->sessionManager->isPageAuthorized($pageModel->id, (int) $pageModel->pin_timeout)) {
                

                
                // PIN nicht korrekt oder nicht gesetzt oder abgelaufen - Weiterleitung zum Login
                $this->sessionManager->setReferrer($currentUrl);
                $this->sessionManager->setTargetPageId($pageModel->id);
                $this->sessionManager->setExpectedPin($pageModel->pin_value);
                
                // Ermitteln der Login-Seite
                $pageModelAdapter = $this->framework->getAdapter(PageModel::class);
                $loginPage = $pageModelAdapter->findByPk($pageModel->pin_login_page ?: $GLOBALS['TL_CONFIG']['pinLoginPage'] ?? 0);
                
                if ($loginPage) {
                    // URL-Generierung für Contao 5
                    $url = '';
                    
                    // In Contao 5 verwenden wir nicht mehr die Route 'contao_frontend'
                    // Stattdessen können wir die absolute URL direkt aus dem PageModel ableiten
                    if (method_exists($loginPage, 'getAbsoluteUrl')) {
                        $url = $loginPage->getAbsoluteUrl();
                    } else {
                        // Fallback für ältere Versionen
                        $url = '/' . $loginPage->alias . '.html';
                        if ($request->getHttpHost()) {
                            $scheme = $request->isSecure() ? 'https' : 'http';
                            $url = $scheme . '://' . $request->getHttpHost() . $url;
                        }
                    }
                    

                    
                    // Sicherstellen, dass noch keine Ausgabe erfolgt ist
                    if (!headers_sent()) {
                        header('Location: ' . $url);
                        exit;
                    } else {
                        // Wenn Header bereits gesendet wurden, logge einen Fehler.
                        // Dies sollte nach Entfernung der Echos nicht mehr passieren.
                        $this->logger->error("PinLoginCheckListener: Konnte nicht weiterleiten, da Header bereits gesendet wurden. Ausgabe startete bei: " . headers_sent($file, $line) ? $file.':'.$line : 'unbekannt');
                    }
                }
            } else {

            }
    }
} 