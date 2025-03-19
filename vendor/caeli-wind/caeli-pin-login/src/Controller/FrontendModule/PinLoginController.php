<?php

declare(strict_types=1);

/*
 * This file is part of Caeli PIN-Login.
 *
 * (c) Caeli Wind - Christian Voss 2025 <christian.voss@caeli-wind.de>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/caeli-wind/caeli-pin-login
 */

namespace CaeliWind\CaeliPinLogin\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\Template;
use Contao\System;
use CaeliWind\CaeliPinLogin\Session\PinLoginSessionManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;
use Psr\Log\LoggerInterface;

#[AsFrontendModule(category: 'caeli_wind', template: 'mod_pin_login', name: 'pin_login')]
class PinLoginController extends AbstractFrontendModuleController
{
    public const TYPE = 'pin_login';

    protected ?PageModel $page;
    
    private PinLoginSessionManager $sessionManager;
    private LoggerInterface $logger;
    private readonly ContaoFramework $framework;
    
    public function __construct(
        PinLoginSessionManager $sessionManager,
        LoggerInterface $logger,
        ContaoFramework $framework
    ) {
        $this->sessionManager = $sessionManager;
        $this->logger = $logger;
        $this->framework = $framework;
    }

    /**
     * This method extends the parent __invoke method,
     * its usage is usually not necessary.
     */
    public function __invoke(Request $request, ModuleModel $model, string $section, array $classes = null, PageModel $page = null): Response
    {
        // Get the page model
        $this->page = $page;

        $scopeMatcher = $this->container->get('contao.routing.scope_matcher');

        if ($this->page instanceof PageModel && $scopeMatcher->isFrontendRequest($request)) {
            $this->page->loadDetails();
        }

        return parent::__invoke($request, $model, $section, $classes);
    }

    /**
     * Lazyload services.
     */
    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();

        $services['contao.framework'] = ContaoFramework::class;
        $services['contao.routing.scope_matcher'] = ScopeMatcher::class;
        $services['security.helper'] = Security::class;
        $services['translator'] = TranslatorInterface::class;

        return $services;
    }

    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        // Template-Variablen für Bezeichnungen
        $template->formTitle = $GLOBALS['TL_LANG']['PIN_LOGIN']['form_title'] ?? 'PIN-Eingabe erforderlich';
        $template->pinLabel = $GLOBALS['TL_LANG']['PIN_LOGIN']['pin_label'] ?? 'PIN-Code';
        $template->emailLabel = $GLOBALS['TL_LANG']['PIN_LOGIN']['email_label'] ?? 'E-Mail-Adresse';
        $template->extraDataLabel = $GLOBALS['TL_LANG']['PIN_LOGIN']['extra_data_label'] ?? 'Zusätzliche Informationen';
        $template->submitButton = $GLOBALS['TL_LANG']['PIN_LOGIN']['submit_button'] ?? 'Login';
        
        // Debug-Ausgabe für das Formular
        if ($request->server->get('APP_ENV') === 'dev') {
            $template->isDevMode = true;
            $this->logger->debug('PIN-Login Formular wird geladen.');
        } else {
            $template->isDevMode = false;
        }
        
        // Prüfen, ob eine Referrer-URL gesetzt ist
        $referrer = $this->sessionManager->getReferrer();
        
        if (empty($referrer)) {
            // Wenn keine Referrer-URL gesetzt ist, zur Startseite weiterleiten
            $this->logger->notice('Keine Referrer-URL gesetzt, leite zur Startseite weiter.');
            return new RedirectResponse('/');
        }
        
        $targetPageId = $this->sessionManager->getTargetPageId();
        $expectedPin = $this->sessionManager->getExpectedPin();
        
        // Debug-Informationen
        if ($template->isDevMode) {
            $template->expectedPin = $expectedPin;
            $template->targetPageId = $targetPageId;
            $this->logger->debug('Target Page ID: ' . $targetPageId . ', Expected PIN: ' . $expectedPin);
        }
        
        // Template-Variablen initialisieren
        $template->hasError = false;
        $template->errorMessage = '';
        $template->successMessage = '';
        
        // Modell-Variablen an das Template weitergeben
        $template->requireEmail = (bool)$model->requireEmail;
        $template->extraDataField = (bool)$model->extraDataField;
        
        // Formularverarbeitung
        if ($request->isMethod('POST') && $request->request->get('FORM_SUBMIT') === 'pin_login_form') {
            $pin = $request->request->get('pin');
            $email = $request->request->get('email');
            $extraData = $request->request->get('extra_data');
            
            // Debug-Protokollierung
            if ($template->isDevMode) {
                $this->logger->debug('Formular gesendet: PIN=' . $pin . ', Email=' . $email);
            }
            
            // Validierung
            $isValid = true;
            
            if (empty($pin)) {
                $template->hasError = true;
                $template->errorMessage = $GLOBALS['TL_LANG']['PIN_LOGIN']['error_no_pin'] ?? 'Bitte geben Sie einen PIN-Code ein.';
                $isValid = false;
            }
            
            // E-Mail nur validieren, wenn requireEmail aktiviert ist
            if ($template->requireEmail && empty($email)) {
                $template->hasError = true;
                $template->errorMessage = $GLOBALS['TL_LANG']['PIN_LOGIN']['error_no_email'] ?? 'Bitte geben Sie eine E-Mail-Adresse ein.';
                $isValid = false;
            }
            
            // PIN-Wert prüfen, wenn gültige Eingaben vorhanden sind
            if ($isValid && $pin !== $expectedPin) {
                $template->hasError = true;
                $template->errorMessage = $GLOBALS['TL_LANG']['PIN_LOGIN']['error_invalid_pin'] ?? 'Der eingegebene PIN ist nicht korrekt.';
                $isValid = false;
            }
            
            // Bei erfolgreicher Validierung: Autorisierung speichern und weiterleiten
            if ($isValid) {
                $additionalData = [
                    'email' => $email,
                    'extra_data' => $extraData,
                ];
                
                $this->sessionManager->authorizePageAccess($targetPageId, $additionalData);
                
                // Debug-Protokollierung
                if ($template->isDevMode) {
                    $this->logger->debug('Login erfolgreich, Weiterleitung zu: ' . $referrer);
                }
                
                // Erfolgreiche Validierung - direkt zum Referrer weiterleiten
                return new RedirectResponse($referrer);
            } else {
                // Debug-Protokollierung
                if ($template->isDevMode) {
                    $this->logger->error('Login fehlgeschlagen: ' . $template->errorMessage);
                }
            }
        }
        
        return $template->getResponse();
    }
} 