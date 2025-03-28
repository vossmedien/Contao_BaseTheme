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

namespace CaeliWind\CaeliHubspotConnect\EventSubscriber;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\FormModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class HubspotIntegrationSubscriber implements EventSubscriberInterface
{
    private ScopeMatcher $scopeMatcher;
    
    public function __construct(ScopeMatcher $scopeMatcher)
    {
        $this->scopeMatcher = $scopeMatcher;
    }
    
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }
    
    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        
        // Nur im Frontend ausführen
        if (!$this->scopeMatcher->isFrontendRequest($request)) {
            return;
        }
        
        $response = $event->getResponse();
        $content = $response->getContent();
        
        // Nur HTML-Antworten verarbeiten
        if (!$response->headers->contains('Content-Type', 'text/html') 
            && !$response->headers->contains('Content-Type', 'text/html; charset=UTF-8')) {
            return;
        }
        
        // Prüfen, ob </body> im Content vorhanden ist
        if (!is_string($content) || false === strpos($content, '</body>')) {
            return;
        }
        
        // Alle HubSpot-aktivierten Formulare laden
        $hubspotForms = FormModel::findBy(['enableHubspot=?'], [1]);
        
        if (!$hubspotForms) {
            return;
        }
        
        // JavaScript für die HubSpot-Integration vorbereiten
        $javascript = $this->generateJavaScript($hubspotForms);
        
        // JavaScript vor dem schließenden </body>-Tag einfügen
        $content = str_replace('</body>', $javascript . '</body>', $content);
        $response->setContent($content);
    }
    
    private function generateJavaScript($hubspotForms): string
    {
        $script = '<script>';
        $script .= 'document.addEventListener("DOMContentLoaded", function() {';
        
        // Konfiguration für jedes Formular erstellen
        $script .= 'const hubspotConfig = {';
        
        while ($hubspotForms->next()) {
            $formId = $hubspotForms->id;
            $script .= '"' . $formId . '": {';
            $script .= '"portalId": "' . $hubspotForms->hubspotPortalId . '",';
            $script .= '"formId": "' . $hubspotForms->hubspotFormId . '",';
            
            // Feldmappings laden
            $script .= '"fields": {';
            
            $formFields = \Contao\FormFieldModel::findByPid($formId);
            if ($formFields) {
                $mappings = [];
                while ($formFields->next()) {
                    if ($formFields->hubspotFieldName) {
                        $mappings[] = '"' . $formFields->name . '": "' . $formFields->hubspotFieldName . '"';
                    }
                }
                $script .= implode(',', $mappings);
            }
            
            $script .= '}';
            $script .= '},';
        }
        
        $script .= '};';
        
        // Formular-Erkennung und Datenübermittlung
        $script .= <<<JS
        // Alle Formulare auf der Seite prüfen
        document.querySelectorAll('form').forEach(function(form) {
            // Nach FORM_SUBMIT-Feld suchen
            const formSubmitField = form.querySelector('input[name="FORM_SUBMIT"]');
            if (!formSubmitField) return;
            
            // ID aus auto_form_X extrahieren
            const value = formSubmitField.value;
            if (!value.startsWith('auto_form_')) return;
            
            const formId = value.replace('auto_form_', '');
            
            // Prüfen, ob dieses Formular HubSpot-aktiviert ist
            const config = hubspotConfig[formId];
            if (!config) return;
            
   
            
            // Submit-Handler hinzufügen
            form.addEventListener('submit', function(e) {
                const portalId = config.portalId;
                const hubspotFormId = config.formId;
                const fieldMappings = config.fields;
                
                
                // Formulardaten sammeln
                const formData = {
                    fields: [],
                    context: {
                        pageUri: window.location.href,
                        pageName: document.title
                    }
                };
                
                // Formularfelder verarbeiten
                this.querySelectorAll('input, textarea, select').forEach(function(field) {
                    // Ignoriere spezielle Felder
                    if (!field.name || field.type === 'submit' || field.name === 'FORM_SUBMIT' || field.name === 'REQUEST_TOKEN') {
                        return;
                    }
                    
                    // Ignoriere unchecked Checkboxen
                    if (field.type === 'checkbox' && !field.checked) {
                        return;
                    }
                    
                    // HubSpot-Feldname ermitteln
                    const hubspotFieldName = fieldMappings[field.name] || field.name;
                    
            
                    // Feld hinzufügen
                    formData.fields.push({
                        name: hubspotFieldName,
                        value: field.value
                    });
                });
                
                
                // An HubSpot senden
                fetch('https://api.hsforms.com/submissions/v3/integration/submit/' + portalId + '/' + hubspotFormId, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                })
                .then(function(response) {
                    if (!response.ok) {
                        console.error('❌ HubSpot API Error:', response.status, response.statusText);
                        return response.text().then(function(text) {
                            throw new Error('HubSpot API Error: ' + text);
                        });
                    }
                    return response.json();
                })
                .then(function(data) {
                    console.log('✅ HubSpot-Übermittlung erfolgreich:', data);
                })
                .catch(function(error) {
                    console.error('❌ HubSpot-Übermittlung fehlgeschlagen:', error);
                });
            });
        });
JS;
        
        $script .= '});';
        $script .= '</script>';
        
        return $script;
    }
} 