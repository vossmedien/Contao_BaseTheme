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
use Contao\Environment;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\Template;
use Contao\System;
use CaeliWind\CaeliPinLogin\Session\PinLoginSessionManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
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
    private HttpClientInterface $httpClient;
    private RequestStack $requestStack;
    private ParameterBagInterface $parameterBag;

    public function __construct(
        PinLoginSessionManager $sessionManager,
        LoggerInterface $logger,
        ContaoFramework $framework,
        HttpClientInterface $httpClient,
        RequestStack $requestStack,
        ParameterBagInterface $parameterBag
    ) {
        $this->sessionManager = $sessionManager;
        $this->logger = $logger;
        $this->framework = $framework;
        $this->httpClient = $httpClient;
        $this->requestStack = $requestStack;
        $this->parameterBag = $parameterBag;
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
        $services['security.helper'] = AuthorizationCheckerInterface::class;
        $services['translator'] = TranslatorInterface::class;

        return $services;
    }

    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        // --- Logging beim Laden der Seite (GET Request) ---
        if ($request->isMethod('GET')) {
             $this->logger->debug('[PinLogin GET] Referrer aus Session:' . $this->sessionManager->getReferrer());
             $this->logger->debug('[PinLogin GET] TargetPageId aus Session:' . $this->sessionManager->getTargetPageId());
             $this->logger->debug('[PinLogin GET] ExpectedPin aus Session:' . $this->sessionManager->getExpectedPin());
        }

        // Template-Variablen für Bezeichnungen
        $template->pinLabel = $GLOBALS['TL_LANG']['PIN_LOGIN']['pin_label'] ?? 'PIN-Code';
        $template->emailLabel = $GLOBALS['TL_LANG']['PIN_LOGIN']['email_label'] ?? 'E-Mail-Adresse';
        $template->messageLabel = $GLOBALS['TL_LANG']['PIN_LOGIN']['message_label'] ?? 'Nachricht (optional)';
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
            $message = $request->request->get('message');

            // Debug-Protokollierung
            if ($template->isDevMode) {
                $this->logger->debug('Formular gesendet', ['pin' => $pin, 'email' => $email, 'message' => $message]);
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
                    'message' => $message,
                ];

                $this->sessionManager->authorizePageAccess($targetPageId, $additionalData);

                // HubSpot Integration
                $this->submitToHubspot($email, $message, $model);

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

    /**
     * Sends data to HubSpot if configured.
     */
    private function submitToHubspot(?string $email, ?string $message, ModuleModel $model): void
    {
        // Lese die verarbeiteten Parameter, die in services.yaml definiert wurden
        $portalId = $this->parameterBag->get('pin_hubspot_portal_id');
        $formId = $this->parameterBag->get('pin_hubspot_form_id');

        // Nur senden, wenn ENV-Variablen gesetzt sind (bzw. die resultierenden Parameter Werte haben)
        if (empty($portalId) || empty($formId)) {
            if ($model->requireEmail || $model->extraDataField) { // Nur loggen wenn Felder überhaupt aktiv sind
                $this->logger->info('HubSpot Übermittlung für PIN-Login übersprungen: PIN_HUBSPOT_PORTALID oder PIN_HUBSPOT_FORMID nicht konfiguriert.', [
                    'module_id' => $model->id,
                ]);
            }
            return;
        }

        $hubspotDataFields = [];

        // E-Mail hinzufügen, wenn nicht leer
        if (!empty($email)) {
            $hubspotDataFields[] = [
                'name' => 'email',
                'value' => $email,
            ];
        }

        // Nachricht hinzufügen, wenn Modul-Feld aktiv und Nachricht nicht leer
        if ($model->extraDataField && !empty($message)) {
            $hubspotDataFields[] = [
                'name' => 'message',
                'value' => $message,
            ];
        }

        // Wenn keine Felder zu senden sind, abbrechen (sollte nicht passieren, wenn E-Mail Pflicht ist)
        if (empty($hubspotDataFields)) {
            $this->logger->info('HubSpot Übermittlung für PIN-Login: Keine Daten zum Senden vorhanden (weder E-Mail noch Nachricht).', [
                'module_id' => $model->id,
            ]);
            return;
        }

        $request = $this->requestStack->getCurrentRequest();

        $hubspotContext = [
            'pageUri' => Environment::get('uri'),
            'pageName' => $GLOBALS['objPage']?->pageTitle ?? 'PIN Login Page',
        ];

        if ($request && $hutk = $request->cookies->get('hubspotutk')) {
            $hubspotContext['hutk'] = $hutk;
        }

        $hubspotPayload = [
            'fields' => $hubspotDataFields,
            'context' => $hubspotContext,
        ];

        $this->logger->debug('Sende Daten an HubSpot für PIN-Login', [
            'module_id' => $model->id,
            'portal_id' => $portalId,
            'form_id' => $formId,
            'payload' => $hubspotPayload, // Vorsicht: Kann sensible Daten enthalten
        ]);

        try {
            $response = $this->httpClient->request('POST', 'https://api.hsforms.com/submissions/v3/integration/submit/'.$portalId.'/'.$formId, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => $hubspotPayload,
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode >= 200 && $statusCode < 300) {
                $this->logger->info('HubSpot-Übermittlung für PIN-Login erfolgreich', [
                    'module_id' => $model->id,
                    'status_code' => $statusCode,
                ]);
            } else {
                $this->logger->error('HubSpot-Übermittlung für PIN-Login fehlgeschlagen', [
                    'module_id' => $model->id,
                    'status_code' => $statusCode,
                    'response' => $response->getContent(false),
                ]);
            }
        } catch (\Throwable $e) {
            $this->logger->error('Fehler bei der HubSpot-Übermittlung für PIN-Login: '.$e->getMessage(), [
                'module_id' => $model->id,
                'exception' => $e,
            ]);
        }
    }
}
