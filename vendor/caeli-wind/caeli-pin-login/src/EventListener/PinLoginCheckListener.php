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
        // Debug-Ausgabe
        if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'dev') {
            $debugMessages = [
                "PIN-Login Debug for PageId: " . $pageModel->id,
                "PIN Protected: " . ($pageModel->pin_protected ? 'Ja' : 'Nein'),
            ];
            if ($pageModel->pin_protected) {
                $debugMessages[] = "PIN Value: " . $pageModel->pin_value;
                $debugMessages[] = "PIN Login Page: " . $pageModel->pin_login_page;
                $debugMessages[] = "isPageAuthorized: " . ($this->sessionManager->isPageAuthorized($pageModel->id, (int) $pageModel->pin_timeout) ? 'Ja' : 'Nein');
                $debugMessages[] = "AuthorizedPages: " . print_r($this->sessionManager->getDebugAuthorizedPages(), true);
            }
            foreach ($debugMessages as $message) {
                $this->logger->debug($message);
            }
        }

        $this->framework->initialize();

        // Prüfen, ob für diese Seite ein PIN-Schutz aktiviert ist und ein PIN-Wert gesetzt ist
        if ($pageModel->pin_protected && $pageModel->pin_value) {
            $request = $this->requestStack->getCurrentRequest();
            if (null === $request) {
                return;
            }

            $currentUrl = $request->getUri();
            
            // Debug-Info zur Session
            if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'dev') {
                file_put_contents(
                    dirname(__DIR__, 4) . '/pin-login-debug.log',
                    date('Y-m-d H:i:s') . ' - Check Auth: Page ' . $pageModel->id . 
                    ' | Authorized: ' . ($this->sessionManager->isPageAuthorized($pageModel->id, (int) $pageModel->pin_timeout) ? 'Yes' : 'No') . PHP_EOL,
                    FILE_APPEND
                );
            }
            
            // Prüfen, ob diese spezifische Seite bereits autorisiert ist
            if (!$this->sessionManager->isPageAuthorized($pageModel->id, (int) $pageModel->pin_timeout)) {
                
                // Debug-Info zur Session für fehlgeschlagene Auth
                if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'dev') {
                    file_put_contents(
                        dirname(__DIR__, 4) . '/pin-login-debug.log',
                        date('Y-m-d H:i:s') . ' - Auth Failed: Setting session data and redirecting | ' . 
                        'Current URL: ' . $currentUrl . ' | Target ID: ' . $pageModel->id . ' | PIN: ' . $pageModel->pin_value . PHP_EOL,
                        FILE_APPEND
                    );
                }
                
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
                    
                    // Debug-Info zur Weiterleitung
                    if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'dev') {
                        file_put_contents(
                            dirname(__DIR__, 4) . '/pin-login-debug.log',
                            date('Y-m-d H:i:s') . ' - Redirecting to: ' . $url . PHP_EOL,
                            FILE_APPEND
                        );
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
                // Debug-Info für erfolgreiche Auth
                if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'dev') {
                    file_put_contents(
                        dirname(__DIR__, 4) . '/pin-login-debug.log',
                        date('Y-m-d H:i:s') . ' - Auth Successful: Page ' . $pageModel->id . ' is authorized.' . PHP_EOL,
                        FILE_APPEND
                    );
                }
            }
        }
    }
} 