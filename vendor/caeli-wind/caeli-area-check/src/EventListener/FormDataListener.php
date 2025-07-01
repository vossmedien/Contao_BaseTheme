<?php

declare(strict_types=1);

namespace CaeliWind\CaeliAreaCheckBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Database;
use Contao\Input;
use Contao\Form;
use Psr\Log\LoggerInterface;

/**
 * Event Listener für Formular-Submissions zur Aktualisierung der Flächencheck-Einträge
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
        $this->formIds = $formIds;
        $this->fieldMapping = $fieldMapping;
        
        $this->logger->debug('[FormDataListener] Konfigurierte Form-IDs: ' . json_encode($this->formIds));
        $this->logger->debug('[FormDataListener] Feld-Mapping: ' . json_encode($this->fieldMapping));
    }

    /**
     * Hook: processFormData
     * Wird nach dem Absenden eines Formulars aufgerufen
     */
    #[AsHook('processFormData')]
    public function onProcessFormData(array $submittedData, array $formData, ?array $files, array $labels, Form $form): void
    {
        $formId = $form->formID;
        
        // Debug: Alle Formular-Submissions loggen
        $this->logger->debug('[FormDataListener] Formular abgesendet - ID: ' . $formId . ', Konfigurierte IDs: ' . implode(', ', $this->formIds));
        
        // Prüfen ob das Formular zu den konfigurierten IDs gehört
        if (!in_array($formId, $this->formIds, true)) {
            $this->logger->debug('[FormDataListener] Formular ' . $formId . ' nicht in konfigurierten IDs, überspringe');
            return;
        }

        $this->logger->info('[FormDataListener] Formular ' . $formId . ' wurde abgesendet, verarbeite Daten');

        try {
            // Check-ID aus URL-Parameter abrufen - sowohl checkid als auch parkid unterstützen
            $checkId = Input::get('checkid') ?: Input::get('parkid');
            
            // Debug: Alle verfügbaren Input-Parameter loggen
            $this->logger->debug('[FormDataListener] Verfügbare URL-Parameter: ' . json_encode($_GET));
            $this->logger->debug('[FormDataListener] Verfügbare POST-Parameter: ' . json_encode(array_keys($_POST)));
            $this->logger->debug('[FormDataListener] Submitted data keys: ' . json_encode(array_keys($submittedData)));
            
            if (!$checkId) {
                $this->logger->warning('[FormDataListener] Keine checkid oder parkid im URL-Parameter gefunden');
                return;
            }

            // Sanitize checkid
            $checkId = trim($checkId);
            if (empty($checkId)) {
                $this->logger->warning('[FormDataListener] Leere checkid/parkid nach Sanitization');
                return;
            }

            $this->logger->debug('[FormDataListener] Verarbeite checkid/parkid: ' . $checkId);

            // Formulardaten extrahieren (mit konfigurierbaren Feldnamen)
            $lastname = trim($submittedData[$this->fieldMapping['lastname_field']] ?? '');
            $firstname = trim($submittedData[$this->fieldMapping['firstname_field']] ?? '');
            $phone = trim($submittedData[$this->fieldMapping['phone_field']] ?? '');
            $email = trim($submittedData[$this->fieldMapping['email_field']] ?? '');

            $this->logger->debug('[FormDataListener] === FELDEXTRAKTION ===');
            $this->logger->debug('[FormDataListener] Feld-Mapping: ' . json_encode($this->fieldMapping));
            $this->logger->debug('[FormDataListener] Extrahierte Daten - Name: "' . $lastname . '", Vorname: "' . $firstname . '", Phone: "' . $phone . '", Email: "' . $email . '"');

            // E-Mail Validierung
            if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->logger->warning('[FormDataListener] Ungültige E-Mail-Adresse: ' . $email);
                $email = ''; // Ungültige E-Mail nicht speichern
            }

            // Validierung
            if (empty($lastname) && empty($firstname) && empty($phone) && empty($email)) {
                $this->logger->warning('[FormDataListener] Keine relevanten Formulardaten gefunden');
                return;
            }

            // Datenbank-Update durchführen
            $this->updateFlächencheckEntry($checkId, $lastname, $firstname, $phone, $email);

        } catch (\Throwable $e) {
            $this->logger->error('[FormDataListener] Fehler beim Verarbeiten der Formulardaten: ' . $e->getMessage());
            $this->logger->error('[FormDataListener] Stack trace: ' . $e->getTraceAsString());
        }
    }

    /**
     * Aktualisiert den Flächencheck-Eintrag mit den Formulardaten
     */
    private function updateFlächencheckEntry(string $checkId, string $lastname, string $firstname, string $phone, string $email): void
    {
        $db = Database::getInstance();
        
        $this->logger->info('[FormDataListener] === DATENBANK-SUCHE START ===');
        $this->logger->info('[FormDataListener] Suche nach checkId: ' . $checkId . ' (Typ: ' . (is_numeric($checkId) ? 'numerisch' : 'alphanumerisch') . ')');
        
        // Datenbank-Entry finden - zuerst park_id, dann UUID, dann ID (Fallback)
        $result = null;
        
        // 1. Versuch: park_id (für erfolgreiche Parks) - ZUERST prüfen!
        if (!is_numeric($checkId)) {
            // Validierung: nur alphanumerische Zeichen und erlaubte Sonderzeichen für park_id
            if (preg_match('/^[a-zA-Z0-9_-]+$/', $checkId)) {
                $this->logger->info('[FormDataListener] Suche nach park_id: ' . $checkId);
                $result = $db->prepare("SELECT * FROM tl_flaechencheck WHERE park_id = ? ORDER BY tstamp DESC LIMIT 1")
                             ->execute($checkId);
                $this->logger->info('[FormDataListener] park_id Suche: ' . ($result && $result->numRows > 0 ? 'GEFUNDEN (' . $result->numRows . ' Zeilen)' : 'NICHT GEFUNDEN'));
            }
            
            // Wenn nicht als park_id gefunden und es UUID-Format hat, dann in uuid Spalte suchen
            if ((!$result || $result->numRows === 0) && 
                (preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $checkId) || 
                 preg_match('/^fc-\d+-[a-f0-9]{16}$/', $checkId))) {
                $this->logger->info('[FormDataListener] Suche nach UUID: ' . $checkId);
                $result = $db->prepare("SELECT * FROM tl_flaechencheck WHERE uuid = ?")
                             ->execute($checkId);
                $this->logger->info('[FormDataListener] UUID Suche: ' . ($result && $result->numRows > 0 ? 'GEFUNDEN (' . $result->numRows . ' Zeilen)' : 'NICHT GEFUNDEN'));
            }
        }
        // 2. Fallback: DB-ID (für alte fehlgeschlagene Parks)
        else {
            // Numerische checkid = DB-ID (für fehlgeschlagene Parks)
            // Zusätzliche Validierung: nur positive Integers
            $numericId = (int) $checkId;
            if ($numericId <= 0) {
                $this->logger->error('[FormDataListener] Ungültige numerische checkid: ' . $checkId);
                return;
            }
            $this->logger->info('[FormDataListener] Suche nach DB-ID: ' . $numericId);
            $result = $db->prepare("SELECT * FROM tl_flaechencheck WHERE id = ?")
                         ->execute($numericId);
            $this->logger->info('[FormDataListener] DB-ID Suche: ' . ($result && $result->numRows > 0 ? 'GEFUNDEN (' . $result->numRows . ' Zeilen)' : 'NICHT GEFUNDEN'));
        }

        if (!$result || $result->numRows === 0) {
            $this->logger->error('[FormDataListener] === FEHLER: Kein Flächencheck-Eintrag gefunden ===');
            $this->logger->error('[FormDataListener] Gesuchte checkId: ' . $checkId);
            
            // Debug: Alle Einträge der letzten 10 Minuten anzeigen
            $recent = $db->prepare("SELECT id, park_id, uuid, tstamp, status FROM tl_flaechencheck WHERE tstamp > ? ORDER BY tstamp DESC LIMIT 10")
                         ->execute(time() - 600); // Letzte 10 Minuten
            
            $recentEntries = [];
            while ($recent->next()) {
                $recentEntries[] = [
                    'id' => $recent->id,
                    'park_id' => $recent->park_id,
                    'uuid' => $recent->uuid,
                    'status' => $recent->status,
                    'tstamp' => $recent->tstamp,
                    'time' => date('H:i:s', $recent->tstamp)
                ];
            }
            $this->logger->error('[FormDataListener] Letzte 10 DB-Einträge: ' . json_encode($recentEntries));
            return;
        }

        $entry = $result->fetchAssoc();
        $entryId = $entry['id'];
        
        $this->logger->info('[FormDataListener] === EINTRAG GEFUNDEN ===');
        $this->logger->info('[FormDataListener] DB-ID: ' . $entryId . ', park_id: ' . ($entry['park_id'] ?: 'NULL') . ', uuid: ' . ($entry['uuid'] ?: 'NULL'));

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

        $this->logger->info('[FormDataListener] Update-Daten: ' . json_encode($updateData));

        // Update durchführen
        $db->prepare("UPDATE tl_flaechencheck %s WHERE id = ?")
           ->set($updateData)
           ->execute($entryId);

        $this->logger->info('[FormDataListener] === UPDATE ERFOLGREICH ===');
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

    /**
     * Hook: loadFormField  
     * Wird beim Laden von Formularfeldern aufgerufen - für Vorausfüllung
     */
    #[AsHook('loadFormField')]
    public function onLoadFormField($widget, string $formId, array $formData, $form)
    {
        // IMMER loggen um zu sehen ob Hook aufgerufen wird
        $this->logger->info('[FormDataListener] Hook aufgerufen: ' . $formId . ' -> ' . ($widget->name ?? 'NO_NAME'));
        
        // Debug: Konfigurierte Form-IDs anzeigen
        if ($formId === 'auto_flaechencheckSuccessDE' && ($widget->name ?? '') === 'zip') {
            $this->logger->info('[FormDataListener] Konfigurierte Form-IDs: ' . json_encode($this->formIds));
            $this->logger->info('[FormDataListener] Ist auto_flaechencheckSuccessDE enthalten? ' . (in_array($formId, $this->formIds, true) ? 'JA' : 'NEIN'));
        }
        
        // Nur für konfigurierte Formulare
        if (!in_array($formId, $this->formIds, true)) {
            return $widget;
        }
        
        // Debug nur für konfigurierte Formulare
        $this->logger->info('[FormDataListener] === FORMULAR-VORAUSFÜLLUNG START ===');
        $this->logger->info('[FormDataListener] Form-ID: ' . $formId . ', Widget: ' . ($widget->name ?? 'UNKNOWN'));

        // Debug URL-Parameter
        $checkId = Input::get('checkid') ?: Input::get('parkid');
        $this->logger->info('[FormDataListener] Check-ID aus URL: ' . ($checkId ?: 'NICHT GEFUNDEN'));
        
        try {
            if (!$checkId) {
                $this->logger->info('[FormDataListener] Keine Check-ID - Überspringe');
                return $widget;
            }

            // Flächencheck-Daten laden
            $checkData = $this->loadFlächencheckData($checkId);
            if (!$checkData) {
                $this->logger->warning('[FormDataListener] Keine Daten für Check-ID: ' . $checkId);
                return $widget;
            }

            // Felder basierend auf Widget-Name vorausfüllen
            $fieldName = $widget->name;
            
            switch ($fieldName) {
                case 'zip':
                    $zip = $this->extractPostalCode($checkData['searched_address'] ?? '');
                    if ($zip && !$widget->value) {
                        $widget->value = $zip;
                        $this->logger->info('[FormDataListener] ✅ PLZ vorausgefüllt: ' . $zip);
                    }
                    break;
                    
                case 'area_id':
                    $parkId = $checkData['park_id'] ?? '';
                    $this->logger->info('[FormDataListener] park_id aus DB: ' . $parkId);
                    if ($parkId && !$widget->value) {
                        $widget->value = $parkId;
                        $this->logger->info('[FormDataListener] ✅ area_id vorausgefüllt: ' . $parkId);
                    } else {
                        $this->logger->info('[FormDataListener] ❌ area_id nicht gesetzt (parkId=' . $parkId . ', widget->value=' . ($widget->value ?: 'NULL') . ')');
                    }
                    break;
                    
                case 'flaechenkoordinaten':
                    $geometry = $checkData['geometry'] ?? '';
                    if ($geometry && !$widget->value) {
                        $widget->value = $geometry;
                        $this->logger->info('[FormDataListener] ✅ Flächenkoordinaten vorausgefüllt (Länge: ' . strlen($geometry) . ')');
                    } else {
                        $this->logger->info('[FormDataListener] ❌ Flächenkoordinaten nicht gesetzt');
                    }
                    break;
                    
                case 'such_string':
                    $searchedAddress = $checkData['searched_address'] ?? '';
                    if ($searchedAddress && !$widget->value) {
                        $widget->value = $searchedAddress;
                        $this->logger->info('[FormDataListener] ✅ Such-String vorausgefüllt: ' . $searchedAddress);
                    } else {
                        $this->logger->info('[FormDataListener] ❌ Such-String nicht gesetzt');
                    }
                    break;
                    
                case 'flaechengroesse':
                    // Flächengröße aus Geometrie berechnen wenn verfügbar
                    $geometry = $checkData['geometry'] ?? '';
                    if ($geometry && !$widget->value) {
                        $areaSize = $this->calculateAreaFromGeometry($geometry);
                        if ($areaSize) {
                            // Für HubSpot: Englisches Zahlenformat mit Punkt (ohne "ha")
                            $areaSizeFormatted = (string) $areaSize;
                            $widget->value = $areaSizeFormatted;
                            $this->logger->info('[FormDataListener] ✅ Flächengröße vorausgefüllt: ' . $areaSizeFormatted);
                        } else {
                            $this->logger->info('[FormDataListener] ❌ Flächenberechnung fehlgeschlagen');
                        }
                    } else {
                        $this->logger->info('[FormDataListener] ❌ Flächengröße nicht gesetzt');
                    }
                    break;
                    
                case 'datum':
                    $timestamp = $checkData['tstamp'] ?? '';
                    if ($timestamp && !$widget->value) {
                        $widget->value = date('d.m.Y', $timestamp);
                        $this->logger->info('[FormDataListener] ✅ Datum vorausgefüllt: ' . date('d.m.Y', $timestamp));
                    } else {
                        $this->logger->info('[FormDataListener] ❌ Datum nicht gesetzt');
                    }
                    break;
                    
                case 'quelle___caeli':
                    // Immer als "caeli" markieren
                    if (!$widget->value) {
                        $widget->value = 'caeli';
                        $this->logger->info('[FormDataListener] ✅ Quelle als "caeli" gesetzt');
                    } else {
                        $this->logger->info('[FormDataListener] ❌ Quelle bereits gesetzt: ' . $widget->value);
                    }
                    break;
                    
                case 'flaechencheck_pdf':
                    $parkId = $checkData['park_id'] ?? '';
                    if ($parkId && !$widget->value) {
                        $pdfUrl = 'https://www.caeli-wind.de/start?getersteinschaetzung=' . $parkId;
                        $widget->value = $pdfUrl;
                        $this->logger->info('[FormDataListener] ✅ Flächencheck PDF URL vorausgefüllt: ' . $pdfUrl);
                    } else {
                        $this->logger->info('[FormDataListener] ❌ Flächencheck PDF nicht gesetzt (parkId=' . $parkId . ', widget->value=' . ($widget->value ?: 'NULL') . ')');
                    }
                    break;
                    
                default:
                    $this->logger->info('[FormDataListener] Feld nicht konfiguriert für Vorausfüllung: ' . $fieldName);
                    break;
            }

        } catch (\Throwable $e) {
            $this->logger->error('[FormDataListener] Fehler beim Vorausfüllen von Feld ' . ($widget->name ?? 'unknown') . ': ' . $e->getMessage());
            $this->logger->error('[FormDataListener] Stack trace: ' . $e->getTraceAsString());
        }

        $this->logger->info('[FormDataListener] === LOAD FORM FIELD HOOK ENDE ===');
        return $widget;
    }

    /**
     * Lädt Flächencheck-Daten aus der Datenbank
     */
    private function loadFlächencheckData(string $checkId): ?array
    {
        $db = Database::getInstance();
        
        // Gleiche Suchlogik wie in updateFlächencheckEntry
        $result = null;
        
        if (!is_numeric($checkId)) {
            if (preg_match('/^[a-zA-Z0-9_-]+$/', $checkId)) {
                $result = $db->prepare("SELECT * FROM tl_flaechencheck WHERE park_id = ? ORDER BY tstamp DESC LIMIT 1")
                             ->execute($checkId);
            }
            
            if ((!$result || $result->numRows === 0) && 
                (preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $checkId) || 
                 preg_match('/^fc-\d+-[a-f0-9]{16}$/', $checkId))) {
                $result = $db->prepare("SELECT * FROM tl_flaechencheck WHERE uuid = ?")
                             ->execute($checkId);
            }
        } else {
            $numericId = (int) $checkId;
            if ($numericId > 0) {
                $result = $db->prepare("SELECT * FROM tl_flaechencheck WHERE id = ?")
                             ->execute($numericId);
            }
        }

        if ($result && $result->numRows > 0) {
            return $result->fetchAssoc();
        }

        return null;
    }

    /**
     * Extrahiert Postleitzahl aus Adress-String (international)
     */
    private function extractPostalCode(string $address): ?string
    {
        if (empty($address)) {
            return null;
        }

        // Internationale PLZ-Patterns (analog zu caeli-area-check.js)
        $patterns = [
            'de' => '/\b(\d{5})\b/',                           // Deutschland: 12345
            'at' => '/\b(\d{4})\b/',                           // Österreich: 1234  
            'ch' => '/\b(\d{4})\b/',                           // Schweiz: 1234
            'fr' => '/\b(\d{5})\b/',                           // Frankreich: 12345
            'nl' => '/\b(\d{4}\s?[A-Z]{2})\b/',                // Niederlande: 1234 AB
            'be' => '/\b(\d{4})\b/',                           // Belgien: 1234
            'dk' => '/\b(\d{4})\b/',                           // Dänemark: 1234
            'se' => '/\b(\d{3}\s?\d{2})\b/',                   // Schweden: 123 45
            'no' => '/\b(\d{4})\b/',                           // Norwegen: 1234
            'fi' => '/\b(\d{5})\b/',                           // Finnland: 12345
            'it' => '/\b(\d{5})\b/',                           // Italien: 12345
            'es' => '/\b(\d{5})\b/',                           // Spanien: 12345
            'pt' => '/\b(\d{4}-?\d{3})\b/',                    // Portugal: 1234-123
            'pl' => '/\b(\d{2}-?\d{3})\b/',                    // Polen: 12-345
            'cz' => '/\b(\d{3}\s?\d{2})\b/',                   // Tschechien: 123 45
            'sk' => '/\b(\d{3}\s?\d{2})\b/',                   // Slowakei: 123 45
            'hu' => '/\b(\d{4})\b/',                           // Ungarn: 1234
            'gb' => '/\b([A-Z]{1,2}\d[A-Z\d]?\s?\d[A-Z]{2})\b/', // UK: M1 1AA
            'ie' => '/\b([A-Z]\d{2}\s?[A-Z0-9]{4})\b/',        // Irland: D02 XY45
        ];

        // 1. Versuche Länder-Erkennung aus Adresse
        $detectedCountry = $this->detectCountryFromAddress($address);
        if ($detectedCountry && isset($patterns[$detectedCountry])) {
            if (preg_match($patterns[$detectedCountry], $address, $matches)) {
                $this->logger->debug('[FormDataListener] PLZ extrahiert (' . $detectedCountry . '): ' . $matches[1]);
                return trim($matches[1]);
            }
        }

        // 2. Fallback: Alle Patterns durchprobieren (Deutschland zuerst, dann Rest)
        $orderedPatterns = ['de' => $patterns['de']] + $patterns;
        
        foreach ($orderedPatterns as $country => $pattern) {
            if (preg_match($pattern, $address, $matches)) {
                $this->logger->debug('[FormDataListener] PLZ extrahiert (Fallback ' . $country . '): ' . $matches[1]);
                return trim($matches[1]);
            }
        }

        // 3. Letzter Fallback: Flexible Erkennung für unbekannte Formate
        if (preg_match('/\b(\d{3,5}(\s?[A-Z]{0,3})?)\b/', $address, $matches)) {
            $this->logger->debug('[FormDataListener] PLZ extrahiert (flexibel): ' . $matches[1]);
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Versucht das Land aus der Adresse zu erkennen
     */
    private function detectCountryFromAddress(string $address): ?string
    {
        $address = strtolower($address);
        
        // Länder-Keywords (häufige Schreibweisen)
        $countryKeywords = [
            'de' => ['deutschland', 'germany', 'german', 'de'],
            'at' => ['österreich', 'austria', 'austrian', 'at'],
            'ch' => ['schweiz', 'switzerland', 'swiss', 'ch'],
            'fr' => ['frankreich', 'france', 'french', 'fr'],
            'nl' => ['niederlande', 'netherlands', 'holland', 'dutch', 'nl'],
            'be' => ['belgien', 'belgium', 'belgian', 'be'],
            'gb' => ['england', 'britain', 'uk', 'united kingdom', 'gb'],
            'it' => ['italien', 'italy', 'italian', 'it'],
            'es' => ['spanien', 'spain', 'spanish', 'es'],
            'pl' => ['polen', 'poland', 'polish', 'pl'],
            'cz' => ['tschechien', 'czech', 'czechia', 'cz'],
            'dk' => ['dänemark', 'denmark', 'danish', 'dk'],
            'se' => ['schweden', 'sweden', 'swedish', 'se'],
            'no' => ['norwegen', 'norway', 'norwegian', 'no'],
        ];

        foreach ($countryKeywords as $country => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($address, $keyword) !== false) {
                    return $country;
                }
            }
        }

        return null; // Kein Land erkannt
    }

    /**
     * Berechnet Flächengröße aus GeoJSON-Geometrie
     */
    private function calculateAreaFromGeometry(string $geometry): ?float
    {
        try {
            $geoData = json_decode($geometry, true);
            if (!$geoData || !isset($geoData['geometry']['coordinates'][0])) {
                return null;
            }

            $coordinates = $geoData['geometry']['coordinates'][0];
            if (count($coordinates) < 3) {
                return null;
            }

            // Vereinfachte Flächenberechnung mit Shoelace-Formel
            $area = 0;
            $n = count($coordinates) - 1; // Letzter Punkt ist Wiederholung

            for ($i = 0; $i < $n; $i++) {
                $j = ($i + 1) % $n;
                $area += $coordinates[$i][0] * $coordinates[$j][1];
                $area -= $coordinates[$j][0] * $coordinates[$i][1];
            }

            $area = abs($area) / 2;
            
            // Umrechnung von Grad² zu Hektar (grobe Approximation für Deutschland)
            $areaHectares = $area * 111320 * 111320 / 10000;
            
            return round($areaHectares, 2);

        } catch (\Throwable $e) {
            $this->logger->warning('[FormDataListener] Fehler bei Flächenberechnung: ' . $e->getMessage());
            return null;
        }
    }
} 