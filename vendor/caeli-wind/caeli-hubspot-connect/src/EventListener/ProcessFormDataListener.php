<?php

declare(strict_types=1);

/*
 * This file is part of Caeli Hubspot Connect.
 *
 * (c) Caeli Wind - Christian Voss 2025 <christian.voss@caeli-wind.de>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/caeli-wind/caeli-hubspot-connect
 */

namespace CaeliWind\CaeliHubspotConnect\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;
use Contao\FormFieldModel;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsHook('processFormData')]
class ProcessFormDataListener
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;

    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    /**
     * Verarbeitet die Formulardaten und sendet sie an HubSpot.
     *
     * @param array $submittedData Die übermittelten Formulardaten
     * @param array $formData Die Konfiguration des Formulars
     * @param array|null $files Hochgeladene Dateien (falls vorhanden)
     * @param array $labels Labels der Formularfelder
     * @param Form $form Das Formular-Objekt
     */
    public function __invoke(array $submittedData, array $formData, ?array $files, array $labels, Form $form): void
    {
        // Prüfen, ob HubSpot für dieses Formular aktiviert ist
        if (!isset($formData['enableHubspot']) || !$formData['enableHubspot']) {
            return;
        }

        // Prüfen, ob die Portal-ID und Formular-ID gesetzt sind
        if (empty($formData['hubspotPortalId']) || empty($formData['hubspotFormId'])) {
            $this->logger->warning('HubSpot-Integration aktiviert, aber Portal-ID oder Formular-ID fehlen.', [
                'form_id' => $form->id,
                'form_title' => $formData['title'] ?? 'Unbekannt'
            ]);
            return;
        }

        $portalId = $formData['hubspotPortalId'];
        $hubspotFormId = $formData['hubspotFormId'];

        // Formularfelder und ihre HubSpot-Mappings laden
        $formFields = FormFieldModel::findByPid($form->id);
        $fieldMappings = [];

        if ($formFields) {
            while ($formFields->next()) {
                if ($formFields->hubspotFieldName) {
                    $fieldMappings[$formFields->name] = $formFields->hubspotFieldName;
                }
            }
        }

        // Daten für HubSpot aufbereiten
        $hubspotData = [
            'fields' => [],
            'context' => [
                'pageUri' => \Contao\Environment::get('uri'),
                'pageName' => $GLOBALS['objPage']->pageTitle ?? 'Formular-Seite'
            ]
        ];

        // Formularfelder gemäß den Mappings hinzufügen
        foreach ($submittedData as $fieldName => $value) {
            // Spezielle Felder überspringen
            if (in_array($fieldName, ['FORM_SUBMIT', 'REQUEST_TOKEN'])) {
                continue;
            }

            // HubSpot-Feldname aus dem Mapping holen oder Feldname verwenden
            $hubspotFieldName = $fieldMappings[$fieldName] ?? $fieldName;

            $hubspotData['fields'][] = [
                'name' => $hubspotFieldName,
                'value' => $value
            ];
        }

        // Debug-Log der zu sendenden Daten
        $this->logger->debug('HubSpot-Daten werden gesendet', [
            'form_id' => $form->id,
            'portal_id' => $portalId,
            'hubspot_form_id' => $hubspotFormId,
            'data' => $hubspotData
        ]);

        try {
            // Daten an HubSpot senden
            $response = $this->httpClient->request('POST', 'https://api.hsforms.com/submissions/v3/integration/submit/' . $portalId . '/' . $hubspotFormId, [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'json' => $hubspotData
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode >= 200 && $statusCode < 300) {
                $this->logger->info('HubSpot-Übermittlung erfolgreich', [
                    'form_id' => $form->id,
                    'status_code' => $statusCode
                ]);
            } else {
                $this->logger->error('HubSpot-Übermittlung fehlgeschlagen', [
                    'form_id' => $form->id,
                    'status_code' => $statusCode,
                    'response' => $response->getContent(false)
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('HubSpot-Übermittlung fehlgeschlagen: ' . $e->getMessage(), [
                'form_id' => $form->id,
                'exception' => get_class($e)
            ]);
        }
    }
} 