<?php

declare(strict_types=1);

namespace CaeliWind\CaeliAreaCheckBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Database;
use Contao\Input;
use Contao\Form;
use Psr\Log\LoggerInterface;

/**
 * Listener für Formular-Submissions zur Aktualisierung der Flächencheck-Einträge
 */
class FormDataListener
{
    private array $formIds = [];
    private array $fieldMapping = [];
    
    public function __construct(
        private readonly LoggerInterface $logger,
        array $formIds = [],
        array $fieldMapping = []
    ) {
        $this->formIds = $formIds ?: [
            // Fallback-IDs falls keine Konfiguration vorhanden
            'flaechencheckNotSuccessEN',
            'flaechencheckNotSuccessDE', 
            'flaechencheckSuccessEN',
            'flaechencheckSuccessDE'
        ];
        
        $this->fieldMapping = $fieldMapping ?: [
            'lastname_field' => 'lastname',
            'firstname_field' => 'firstname', 
            'phone_field' => 'phone',
            'email_field' => 'email'
        ];
    }

    /**
     * Hook: processFormData
     * Wird nach dem Absenden eines Formulars aufgerufen
     */
    #[AsHook('processFormData')]
    public function onProcessFormData(array $submittedData, array $formData, ?array $files, array $labels, Form $form): void
    {
        $formId = $form->formID;
        
        // Prüfen ob das Formular zu den konfigurierten IDs gehört
        if (!in_array($formId, $this->formIds, true)) {
            return;
        }

        $this->logger->debug('[FormDataListener] Formular ' . $formId . ' wurde abgesendet, verarbeite Daten');

        try {
            // Check-ID aus URL-Parameter abrufen
            $checkId = Input::get('checkid');
            
            if (!$checkId) {
                $this->logger->warning('[FormDataListener] Keine checkid im URL-Parameter gefunden');
                return;
            }

                         // Formulardaten extrahieren (mit konfigurierbaren Feldnamen)
            $lastname = $submittedData[$this->fieldMapping['lastname_field']] ?? '';
            $firstname = $submittedData[$this->fieldMapping['firstname_field']] ?? '';
            $phone = $submittedData[$this->fieldMapping['phone_field']] ?? '';
            $email = $submittedData[$this->fieldMapping['email_field']] ?? '';

            // Validierung
            if (empty($lastname) && empty($firstname) && empty($phone) && empty($email)) {
                $this->logger->warning('[FormDataListener] Keine relevanten Formulardaten gefunden');
                return;
            }

            // Datenbank-Update durchführen
            $this->updateFlächencheckEntry($checkId, $lastname, $firstname, $phone, $email);

        } catch (\Throwable $e) {
            $this->logger->error('[FormDataListener] Fehler beim Verarbeiten der Formulardaten: ' . $e->getMessage());
        }
    }

    /**
     * Aktualisiert den Flächencheck-Eintrag mit den Formulardaten
     */
    private function updateFlächencheckEntry(string $checkId, string $lastname, string $firstname, string $phone, string $email): void
    {
        $db = Database::getInstance();
        
        // Datenbank-Entry finden - zuerst versuchen über park_id, dann über ID
        $result = null;
        
        if (is_numeric($checkId)) {
            // Numerische checkid = DB-ID (für fehlgeschlagene Parks)
            $result = $db->prepare("SELECT * FROM tl_flaechencheck WHERE id = ?")
                         ->execute($checkId);
        } else {
            // String checkid = park_id (für erfolgreiche Parks)
            $result = $db->prepare("SELECT * FROM tl_flaechencheck WHERE park_id = ? ORDER BY tstamp DESC LIMIT 1")
                         ->execute($checkId);
        }

        if (!$result || $result->numRows === 0) {
            $this->logger->error('[FormDataListener] Kein Flächencheck-Eintrag gefunden für checkid: ' . $checkId);
            return;
        }

        $entry = $result->fetchAssoc();
        $entryId = $entry['id'];

        // Update-Daten vorbereiten (nur nicht-leere Werte)
        $updateData = [];
        
        if (!empty($lastname)) {
            $updateData['name'] = $lastname;
        }
        
        if (!empty($firstname)) {
            $updateData['vorname'] = $firstname;
        }
        
        if (!empty($phone)) {
            $updateData['phone'] = $phone;
        }
        
        if (!empty($email)) {
            $updateData['email'] = $email;
        }

        if (empty($updateData)) {
            $this->logger->info('[FormDataListener] Keine Daten zum Update für checkid: ' . $checkId);
            return;
        }

        // Update durchführen
        $db->prepare("UPDATE tl_flaechencheck %s WHERE id = ?")
           ->set($updateData)
           ->execute($entryId);

        $this->logger->info('[FormDataListener] Flächencheck-Eintrag ID ' . $entryId . ' erfolgreich aktualisiert für checkid: ' . $checkId);
    }

    /**
     * Setter für Formular-IDs (für Konfiguration)
     */
    public function setFormIds(array $formIds): void
    {
        $this->formIds = $formIds;
    }

    /**
     * Getter für Formular-IDs
     */
    public function getFormIds(): array
    {
        return $this->formIds;
    }
} 