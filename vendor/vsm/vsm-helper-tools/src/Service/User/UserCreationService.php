<?php

namespace Vsm\VsmHelperTools\Service\User;

use Psr\Log\LoggerInterface;
use Contao\MemberModel;
use Doctrine\DBAL\Connection;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\System;
use Vsm\VsmHelperTools\Service\Email\EmailService;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Monolog\CoreLogger;

class UserCreationService
{
    private LoggerInterface $logger;
    private Connection $db;
    private ?ContaoFramework $framework;
    private ?EmailService $emailService;
    
    public function __construct(
        LoggerInterface $logger, 
        Connection $db, 
        ?ContaoFramework $framework = null,
        ?EmailService $emailService = null
    ) {
        $this->logger = $logger;
        $this->db = $db;
        $this->framework = $framework;
        $this->emailService = $emailService;
    }
    
    /**
     * Erstellt oder aktualisiert einen Benutzer basierend auf den übergebenen Daten
     */
    public function createOrUpdateUser(array $userData): ?int
    {
        try {
            if ($this->framework) {
                $this->framework->initialize();
            }
            
            // Detailliertes Logging für Debugging
            $this->logger->info('createOrUpdateUser aufgerufen mit Daten:', [
                'email' => $userData['email'] ?? 'nicht gesetzt',
                'groups' => isset($userData['groups']) ? json_encode($userData['groups']) : 'nicht gesetzt',
                'subscription_duration' => $userData['subscription_duration'] ?? 'nicht gesetzt',
                'available_keys' => array_keys($userData)
            ]);
            
            // Überprüfen, ob die erforderlichen Daten vorhanden sind
            if (empty($userData['email'])) {
                $this->logger->error('Keine E-Mail-Adresse für die Benutzer-Erstellung vorhanden');
                return null;
            }
            
            // Überprüfen, ob bereits ein Benutzer mit dieser E-Mail existiert
            $existingMember = MemberModel::findByEmail($userData['email']);
            
            if ($existingMember !== null) {
                $this->logger->info('Benutzer mit E-Mail ' . $userData['email'] . ' existiert bereits, aktualisiere Mitgliedschaft');
                return $this->updateExistingMember($existingMember, $userData);
            } else {
                return $this->createNewMember($userData);
            }
        } catch (\Exception $e) {
            $this->logger->error('Fehler bei der Benutzer-Erstellung: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }
    
    /**
     * Erstellt einen neuen Contao-Benutzer basierend auf persönlichen Daten
     */
    public function createUser(array $personalData, array $productData): ?int
    {
        try {
            // Überprüfen, ob die User-Erstellung aktiviert ist
            if (empty($productData['create_user'])) {
                return null;
            }
            
            // Überprüfen, ob eine Mitgliedergruppe angegeben wurde
            if (empty($productData['member_group'])) {
                $this->logger->error('Keine Mitgliedergruppe für die Benutzer-Erstellung angegeben');
                return null;
            }
            
            // Überprüfen, ob eine E-Mail-Adresse vorhanden ist
            if (empty($personalData['email'])) {
                $this->logger->error('Keine E-Mail-Adresse für die Benutzer-Erstellung vorhanden');
                return null;
            }
            
            // Überprüfen, ob bereits ein Benutzer mit dieser E-Mail existiert
            $existingMember = MemberModel::findByEmail($personalData['email']);
            
            if ($existingMember !== null) {
                $this->logger->info('Benutzer mit E-Mail ' . $personalData['email'] . ' existiert bereits');
                return $existingMember->id;
            }
            
            // Benutzername generieren
            $username = $personalData['email'];
            if (!empty($personalData['username'])) {
                $username = $personalData['username'];
            }
            
            // Passwort generieren oder verwenden
            $password = $this->generatePassword();
            $plainPassword = $password;
            
            if (!empty($personalData['password'])) {
                $password = $personalData['password'];
                $plainPassword = $password;
                
                // Wenn das Passwort Base64-codiert ist, decodieren
                if (preg_match('/^[a-zA-Z0-9+\/=]+$/', $password)) {
                    try {
                        $decodedPassword = base64_decode($password, true);
                        if ($decodedPassword !== false) {
                            $password = $decodedPassword;
                            $plainPassword = $decodedPassword;
                        }
                    } catch (\Exception $e) {
                        // Ignorieren, falls es nicht decodiert werden kann
                    }
                }
            }
            
            // Start und Ende der Mitgliedschaft berechnen
            $startTime = time();
            $endTime = 0;
            
            if (!empty($productData['subscription_duration']) && is_numeric($productData['subscription_duration'])) {
                $endTime = strtotime('+' . $productData['subscription_duration'] . ' months', $startTime);
            }
            
            // Member-Gruppen verarbeiten
            $groups = [];
            
            if (!empty($productData['member_group'])) {
                if (is_array($productData['member_group'])) {
                    $groups = array_map('intval', $productData['member_group']);
                } else if (is_string($productData['member_group']) && strpos($productData['member_group'], ',') !== false) {
                    $groups = array_map('intval', explode(',', $productData['member_group']));
                } else {
                    $groups = [intval($productData['member_group'])];
                }
            }
            
            // Benutzer erstellen
            $member = new MemberModel();
            $member->tstamp = time();
            $member->dateAdded = time();
            $member->username = $username;
            $member->password = password_hash($password, PASSWORD_DEFAULT);
            $member->email = $personalData['email'];
            $member->firstname = $personalData['firstname'] ?? '';
            $member->lastname = $personalData['lastname'] ?? '';
            $member->gender = $this->mapSalutation($personalData['salutation'] ?? '');
            $member->street = $personalData['street'] ?? '';
            $member->postal = $personalData['postal'] ?? '';
            $member->city = $personalData['city'] ?? '';
            $member->country = $personalData['country'] ?? 'de';
            $member->phone = $personalData['phone'] ?? '';
            $member->company = $personalData['company'] ?? '';
            
            // Login aktivieren
            $member->login = true;
            $member->start = $startTime;
            
            // Stop-Zeit nur setzen, wenn sie größer als 0 ist
            if ($endTime > 0) {
                $member->stop = $endTime;
                $this->logger->info('Mitgliedschaft endet am: ' . date('Y-m-d H:i:s', $endTime));
            } else {
                // Sicherstellen, dass stop nicht auf 0 gesetzt wird (01.01.1970)
                $member->stop = ''; // Leerer String = kein Enddatum in Contao
                $this->logger->info('Unbegrenzte Mitgliedschaft (kein Enddatum gesetzt)');
            }
            
            // Mitgliedsgruppen setzen
            if (!empty($groups)) {
                $member->groups = serialize($groups);
                $this->logger->info('Mitgliedsgruppen gesetzt: ' . json_encode($groups));
            }
            
            // Mitgliedschaft-Ende Feld setzen
            $this->ensureMembershipExpiresField();
            if ($endTime > 0) {
                $member->membership_expires = date('Y-m-d', $endTime);
                $this->logger->info('Membership-expires Feld gesetzt auf: ' . $member->membership_expires);
            } else {
                $member->membership_expires = ''; // Leerer String = kein Enddatum
            }
            
            $member->save();
            
            $this->logger->info('Neuer Benutzer erstellt: ' . $username);
            
            // Wenn ein E-Mail-Service verfügbar ist, sende Registrierungs-E-Mail
            if ($this->emailService) {
                $this->emailService->sendRegistrationEmail($member);
            }
            
            return $member->id;
        } catch (\Exception $e) {
            $this->logger->error('Fehler bei der Benutzer-Erstellung: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }
    
    /**
     * Erstellt einen neuen Contao-Benutzer aus Metadata (für WebhookController)
     */
    public function createContaoUser(array $metadata): ?int
    {
        if (empty($metadata['personalData'])) {
            $this->logger->error('Keine persönlichen Daten für die Benutzer-Erstellung gefunden');
            return null;
        }
        
        $this->framework->initialize();
        
        $contaoLogger = $this->framework->createInstance(CoreLogger::class);
        $contaoLogger->setContext(ContaoContext::GENERAL);
        
        $personalData = json_decode($metadata['personalData'], true);
        
        // Fehlerbehandlung für JSON-Decode
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error('Fehler beim Decodieren der persönlichen Daten: ' . json_last_error_msg());
            return null;
        }
        
        try {
            // Sicherstellen, dass ein Benutzername vorhanden ist
            if (empty($personalData['username'])) {
                if (!empty($personalData['email'])) {
                    $personalData['username'] = $personalData['email'];
                } else {
                    throw new \Exception('Kein Benutzername oder E-Mail für die Benutzer-Erstellung angegeben');
                }
            }
            
            // Überprüfen, ob bereits ein Benutzer mit diesem Benutzernamen oder dieser E-Mail existiert
            $existingMember = $this->framework->createInstance(MemberModel::class);
            $existingByUsername = $existingMember->findByUsername($personalData['username']);
            $existingByEmail = $existingMember->findByEmail($personalData['email']);
            
            // Wenn ein Benutzer bereits existiert, aktualisieren statt neu erstellen
            if ($existingByUsername !== null || $existingByEmail !== null) {
                $member = $existingByUsername ?: $existingByEmail;
                
                $contaoLogger->info('Aktualisiere vorhandenen Benutzer: ' . $member->username, [
                    'context' => ContaoContext::GENERAL
                ]);
                
                // Aktualisiere Benutzer mit neuen Daten
                $this->updateMember($member, $personalData, $metadata);
                
                return $member->id;
            }
            
            // Produktdaten extrahieren
            $productData = [];
            if (!empty($metadata['productData'])) {
                $productData = json_decode($metadata['productData'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $contaoLogger->warning('Ungültiges JSON in productData', [
                        'context' => ContaoContext::GENERAL,
                        'error' => json_last_error_msg()
                    ]);
                    $productData = [];
                }
            }

            // Verwende duration statt subscription_duration
            $duration = isset($productData['duration']) ? (int)$productData['duration'] : 0;

            // Start ist jetzt
            $startTime = time();

            // Berechne stopTime
            if ($duration > 0) {
                $stopTime = strtotime("+{$duration} months", $startTime);
            } else {
                $stopTime = 0;
            }

            $contaoLogger->info('Erstelle neuen Benutzer', [
                'context' => ContaoContext::GENERAL,
                'username' => $personalData['username'],
                'start' => date('Y-m-d H:i:s', $startTime),
                'stop' => $stopTime ? date('Y-m-d H:i:s', $stopTime) : 'nicht gesetzt',
                'duration' => $duration
            ]);

            // Passwort für die Benutzeranmeldung wird hier temporär aus den personalData entnommen,
            // jedoch nicht in der Datenbank gespeichert - es wird lediglich gehasht
            $password = !empty($personalData['password']) ? base64_decode($personalData['password']) : $this->generatePassword();

            // Stelle sicher, dass das Passwort nicht in den Metadaten gespeichert wird
            if (isset($personalData['password'])) {
                unset($personalData['password']);
            }

            // Sicheres Passwort-Hashing
            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT,
                ['cost' => 12]
            );

            // Neuen Benutzer anlegen
            $member = new MemberModel();
            $member->tstamp = time();
            $member->dateAdded = time();
            $member->username = $personalData['username'];
            $member->password = $passwordHash;
            $member->email = $personalData['email'];
            $member->firstname = $personalData['firstname'] ?? '';
            $member->lastname = $personalData['lastname'] ?? '';
            $member->gender = $this->mapSalutation($personalData['salutation'] ?? '');
            $member->street = $personalData['street'] ?? '';
            $member->postal = $personalData['postal'] ?? '';
            $member->city = $personalData['city'] ?? '';
            $member->phone = $personalData['phone'] ?? '';
            $member->company = $personalData['company'] ?? '';
            $member->login = true;
            $member->start = $startTime;
            $member->stop = $stopTime;
            $member->dateEnd = $stopTime ? date('Y-m-d', $stopTime) : null; // Für die E-Mail

            // Sichere Behandlung der Benutzergruppen
            if (!empty($metadata['memberGroup']) && is_numeric($metadata['memberGroup'])) {
                $member->groups = serialize([(int)$metadata['memberGroup']]);
            } else {
                $contaoLogger->warning('Keine gültige Mitgliedergruppe angegeben', [
                    'context' => ContaoContext::GENERAL,
                    'memberGroup' => $metadata['memberGroup'] ?? 'nicht gesetzt'
                ]);
                // Standardgruppe verwenden, falls konfiguriert
                $defaultGroup = $container ? $container->getParameter('vsm_helper_tools.default_member_group') ?? null : null;
                if ($defaultGroup) {
                    $member->groups = serialize([(int)$defaultGroup]);
                }
            }
            
            $member->save();
            
            // Nach erfolgreicher Speicherung E-Mail senden
            if ($this->emailService) {
                try {
                    $this->emailService->sendRegistrationEmail($member);
                    $contaoLogger->info('Registrierungs-Email erfolgreich gesendet', [
                        'context' => ContaoContext::GENERAL,
                        'username' => $member->username,
                        'email' => $member->email
                    ]);
                } catch (\Exception $e) {
                    $contaoLogger->error('Fehler beim Senden der Registrierungs-Email', [
                        'context' => ContaoContext::GENERAL,
                        'username' => $member->username,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $contaoLogger->info('Benutzer erfolgreich erstellt', [
                'context' => ContaoContext::GENERAL,
                'username' => $member->username
            ]);
            
            return $member->id;

        } catch (\Exception $e) {
            $this->logger->error('Fehler bei der Benutzer-Erstellung', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }
    
    /**
     * Aktualisiert einen bestehenden Benutzer mit neuen Daten
     */
    private function updateExistingMember(MemberModel $member, array $userData): int
    {
        // Detailliertes Logging für Debugging
        $this->logger->info('updateExistingMember aufgerufen für: ' . $member->username, [
            'subscription_duration' => $userData['subscription_duration'] ?? 'nicht gesetzt',
            'current_stop' => $member->stop ? date('Y-m-d H:i:s', $member->stop) : 'nicht gesetzt',
            'has_groups' => !empty($userData['groups'])
        ]);
        
        // Prüfen, ob ein Aktualisierungszeitraum angegeben wurde
        if (!empty($userData['subscription_duration']) && is_numeric($userData['subscription_duration'])) {
            $duration = (int)$userData['subscription_duration'];
            
            if ($duration <= 0) {
                $this->logger->warning('Ungültige Mitgliedschaftsdauer für update: ' . $duration);
                // Keine Änderung am Ablaufdatum
            } else {
                // Wenn das Mitglied bereits ein Ablaufdatum hat, dieses als Basis verwenden
                $startTime = time();
                if ($member->stop && $member->stop > time()) {
                    $startTime = $member->stop;
                    $this->logger->info('Verwende bestehendes Ablaufdatum als Startpunkt: ' . date('Y-m-d H:i:s', $startTime));
                } else {
                    $this->logger->info('Verwende aktuelles Datum als Startpunkt: ' . date('Y-m-d H:i:s', $startTime));
                }
                
                // Neues Ablaufdatum berechnen
                $endTime = strtotime('+' . $duration . ' months', $startTime);
                
                // Mitglied aktualisieren
                $member->stop = $endTime;
                
                // Mitgliedschaft-Ende Feld aktualisieren
                $this->ensureMembershipExpiresField();
                $member->membership_expires = date('Y-m-d', $endTime);
                
                $this->logger->info('Mitgliedschaft für Benutzer ' . $member->username . ' verlängert', [
                    'duration' => $duration,
                    'startTime' => date('Y-m-d H:i:s', $startTime),
                    'new_expiry' => date('Y-m-d H:i:s', $endTime)
                ]);
            }
        }
        
        // Gruppen aktualisieren, falls angegeben
        if (!empty($userData['groups'])) {
            $groups = $userData['groups'];
            if (is_array($groups)) {
                $member->groups = serialize(array_map('intval', $groups));
                $this->logger->info('Gruppen aktualisiert: ' . json_encode(array_map('intval', $groups)));
            } else if (is_string($groups) && strpos($groups, ',') !== false) {
                $groupArray = explode(',', $groups);
                $member->groups = serialize(array_map('intval', $groupArray));
                $this->logger->info('Gruppen aus String aktualisiert: ' . json_encode(array_map('intval', $groupArray)));
            } else {
                $member->groups = serialize([intval($groups)]);
                $this->logger->info('Einzelne Gruppe gesetzt: ' . intval($groups));
            }
        }
        
        // Aktualisiere andere Felder, falls vorhanden
        if (!empty($userData['firstname'])) $member->firstname = $userData['firstname'];
        if (!empty($userData['lastname'])) $member->lastname = $userData['lastname'];
        if (!empty($userData['street'])) $member->street = $userData['street'];
        if (!empty($userData['postal'])) $member->postal = $userData['postal'];
        if (!empty($userData['city'])) $member->city = $userData['city'];
        if (!empty($userData['phone'])) $member->phone = $userData['phone'];
        if (!empty($userData['company'])) $member->company = $userData['company'];
        
        $member->tstamp = time();
        $member->save();
        
        return $member->id;
    }
    
    /**
     * Erstellt einen neuen Benutzer
     */
    private function createNewMember(array $userData): ?int
    {
        try {
            $this->framework->initialize();
            
            // Username ist entweder direkt gesetzt oder wird aus der E-Mail-Adresse generiert
            $username = $userData['username'] ?? $userData['email'];
            
            // Persönliche Daten extrahieren
            $personalData = $userData;
            
            // Produktdaten auslesen, falls separat übergeben
            $productData = $userData['product_data'] ?? [];
            
            // Passwort entweder aus den Daten nehmen oder generieren
            $password = '';
            if (!empty($userData['password'])) {
                $password = $userData['password'];
                
                // Wenn das Passwort Base64-codiert ist, decodieren
                if (preg_match('/^[a-zA-Z0-9+\/=]+$/', $password)) {
                    try {
                        $decodedPassword = base64_decode($password, true);
                        if ($decodedPassword !== false) {
                            $password = $decodedPassword;
                            $plainPassword = $decodedPassword;
                        }
                    } catch (\Exception $e) {
                        // Ignorieren, falls es nicht decodiert werden kann
                    }
                }
            } else {
                // Generiere ein zufälliges Passwort
                $password = $this->generatePassword();
                $plainPassword = $password;
            }
            
            // Detailliertes Logging der Gruppen für Debugging
            $this->logger->info('Mitgliedergruppen in createNewMember:', [
                'groups_from_userData' => isset($userData['groups']) ? json_encode($userData['groups']) : 'nicht gesetzt'
            ]);
            
            // Start und Ende der Mitgliedschaft berechnen
            $startTime = time();
            $endTime = 0;
            
            if (!empty($userData['subscription_duration']) && is_numeric($userData['subscription_duration'])) {
                // Stelle sicher, dass subscription_duration größer als 0 ist
                $duration = intval($userData['subscription_duration']);
                if ($duration > 0) {
                    $endTime = strtotime('+' . $duration . ' months', $startTime);
                    $this->logger->info('Mitgliedschaft-Ende berechnet:', [
                        'duration' => $duration,
                        'startTime' => date('Y-m-d H:i:s', $startTime),
                        'endTime' => date('Y-m-d H:i:s', $endTime),
                        'endTime_timestamp' => $endTime
                    ]);
                } else {
                    $this->logger->warning('Ungültige Mitgliedschaftsdauer: ' . $duration);
                }
            } else {
                $this->logger->info('Keine Mitgliedschaftsdauer gesetzt, verwende unbegrenzte Mitgliedschaft');
            }
            
            // Member-Gruppen aus userData['groups'] verwenden
            $groups = [];
            
            if (!empty($userData['groups'])) {
                if (is_array($userData['groups'])) {
                    $groups = array_map('intval', $userData['groups']);
                    $this->logger->info('Gruppen aus Array übernommen: ' . json_encode($groups));
                } else if (is_string($userData['groups']) && strpos($userData['groups'], ',') !== false) {
                    $groups = array_map('intval', explode(',', $userData['groups']));
                    $this->logger->info('Gruppen aus komma-getrenntem String übernommen: ' . json_encode($groups));
                } else {
                    $groups = [intval($userData['groups'])];
                    $this->logger->info('Gruppe als Einzelwert übernommen: ' . json_encode($groups));
                }
            } else {
                // Standardgruppe verwenden, falls verfügbar
                $container = System::getContainer();
                if ($container && $container->hasParameter('vsm_helper_tools.default_member_group')) {
                    $defaultGroup = $container->getParameter('vsm_helper_tools.default_member_group');
                    if ($defaultGroup) {
                        $groups = [intval($defaultGroup)];
                        $this->logger->info('Standardgruppe verwendet: ' . json_encode($groups));
                    }
                }
            }
            
            if (empty($groups)) {
                $this->logger->warning('Keine Mitgliedergruppe gefunden und keine Standardgruppe konfiguriert');
            }
            
            // Benutzer erstellen
            $member = new MemberModel();
            $member->tstamp = time();
            $member->dateAdded = time();
            $member->username = $username;
            $member->password = password_hash($password, PASSWORD_DEFAULT);
            $member->email = $personalData['email'];
            $member->firstname = $personalData['firstname'] ?? '';
            $member->lastname = $personalData['lastname'] ?? '';
            $member->gender = $this->mapSalutation($personalData['salutation'] ?? '');
            $member->street = $personalData['street'] ?? '';
            $member->postal = $personalData['postal'] ?? '';
            $member->city = $personalData['city'] ?? '';
            $member->country = $personalData['country'] ?? 'de';
            $member->phone = $personalData['phone'] ?? '';
            $member->company = $personalData['company'] ?? '';
            
            // Login aktivieren
            $member->login = true;
            $member->start = $startTime;
            
            // Stop-Zeit nur setzen, wenn sie größer als 0 ist
            if ($endTime > 0) {
                $member->stop = $endTime;
                $this->logger->info('Mitgliedschaft endet am: ' . date('Y-m-d H:i:s', $endTime));
            } else {
                // Sicherstellen, dass stop nicht auf 0 gesetzt wird (01.01.1970)
                $member->stop = ''; // Leerer String = kein Enddatum in Contao
                $this->logger->info('Unbegrenzte Mitgliedschaft (kein Enddatum gesetzt)');
            }
            
            // Mitgliedsgruppen setzen
            if (!empty($groups)) {
                $member->groups = serialize($groups);
                $this->logger->info('Mitgliedsgruppen gesetzt: ' . json_encode($groups));
            }
            
            // Mitgliedschaft-Ende Feld setzen
            $this->ensureMembershipExpiresField();
            if ($endTime > 0) {
                $member->membership_expires = date('Y-m-d', $endTime);
                $this->logger->info('Membership-expires Feld gesetzt auf: ' . $member->membership_expires);
            } else {
                $member->membership_expires = ''; // Leerer String = kein Enddatum
            }
            
            $member->save();
            
            $this->logger->info('Neuer Benutzer erstellt: ' . $username);
            
            // Wenn ein E-Mail-Service verfügbar ist, sende Registrierungs-E-Mail
            if ($this->emailService) {
                $this->emailService->sendRegistrationEmail($member);
            }
            
            return $member->id;
        } catch (\Exception $e) {
            $this->logger->error('Fehler bei der Benutzer-Erstellung: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }
    
    /**
     * Generiert ein zufälliges Passwort
     */
    private function generatePassword(int $length = 12): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-+=';
        $password = '';
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $password;
    }
    
    /**
     * Stellt sicher, dass das membership_expires-Feld in tl_member existiert
     */
    public function ensureMembershipExpiresField(): void
    {
        try {
            // Prüfen, ob das Feld bereits existiert
            $schemaManager = $this->db->createSchemaManager();
            $columns = $schemaManager->listTableColumns('tl_member');
            
            if (!isset($columns['membership_expires'])) {
                // Feld hinzufügen
                $this->db->executeStatement("
                    ALTER TABLE tl_member 
                    ADD COLUMN membership_expires varchar(10) NOT NULL default ''
                ");
                
                $this->logger->info('membership_expires-Feld zu tl_member hinzugefügt');
                
                // DCA-Konfiguration hinzufügen
                $this->addMembershipExpiresFieldToDca();
            }
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Hinzufügen des membership_expires-Feldes: ' . $e->getMessage());
        }
    }
    
    /**
     * Fügt die DCA-Konfiguration für das membership_expires-Feld hinzu
     */
    private function addMembershipExpiresFieldToDca(): void
    {
        try {
            if ($this->framework) {
                $this->framework->initialize();
                
                // DCA laden
                $GLOBALS['TL_DCA']['tl_member']['fields']['membership_expires'] = [
                    'label' => ['Mitgliedschaft endet am', 'Datum, an dem die Mitgliedschaft endet'],
                    'exclude' => true,
                    'inputType' => 'text',
                    'eval' => ['rgxp' => 'date', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
                    'sql' => "varchar(10) NOT NULL default ''"
                ];
            }
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Hinzufügen der DCA-Konfiguration: ' . $e->getMessage());
        }
    }
    
    /**
     * Mappt eine Anrede auf den Contao-Gender-Wert
     */
    public function mapSalutation(string $salutation): string
    {
        return match (strtolower($salutation)) {
            'herr', 'mr', 'mister', 'male', 'm' => 'male',
            'frau', 'mrs', 'ms', 'miss', 'female', 'f' => 'female',
            default => 'other'
        };
    }
} 