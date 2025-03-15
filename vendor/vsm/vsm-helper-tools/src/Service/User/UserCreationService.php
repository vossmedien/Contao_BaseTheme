<?php

namespace Vsm\VsmHelperTools\Service\User;

use Psr\Log\LoggerInterface;
use Contao\MemberModel;
use Doctrine\DBAL\Connection;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\System;
use Vsm\VsmHelperTools\Service\Email\EmailService;
use Contao\CoreBundle\Monolog\ContaoContext;

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
            $member->stop = $endTime;
            
            // Mitgliedsgruppen setzen
            if (!empty($groups)) {
                $member->groups = serialize($groups);
            }
            
            // Mitgliedschaft-Ende Feld setzen
            $this->ensureMembershipExpiresField();
            if ($endTime > 0) {
                $member->membership_expires = date('Y-m-d', $endTime);
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
        try {
            if ($this->framework) {
                $this->framework->initialize();
            }
            
            // Logger aus dem Container holen (für die Contao-Context Integration)
            $container = System::getContainer();
            $contaoLogger = $container ? $container->get('monolog.logger.contao') : $this->logger;

            // Validierung der Eingabedaten
            if (empty($metadata['personalData'])) {
                throw new \Exception('Keine persönlichen Daten übermittelt');
            }

            $personalData = json_decode($metadata['personalData'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ungültiges JSON in personalData: ' . json_last_error_msg());
            }

            // Pflichtfelder überprüfen
            $requiredFields = ['username', 'email'];
            foreach ($requiredFields as $field) {
                if (empty($personalData[$field])) {
                    throw new \Exception("Pflichtfeld fehlt: $field");
            }
            }

            // E-Mail-Format prüfen
            if (!filter_var($personalData['email'], FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('Ungültiges E-Mail-Format');
            }

            // Überprüfe, ob Benutzer bereits existiert
            $existingUser = MemberModel::findByEmail($personalData['email']);
            if ($existingUser) {
                $contaoLogger->info('Benutzer mit dieser E-Mail existiert bereits', [
                    'context' => ContaoContext::GENERAL
                ]);
                return $existingUser->id;
            }

            $existingUsername = MemberModel::findByUsername($personalData['username']);
            if ($existingUsername) {
                throw new \Exception('Benutzer mit diesem Benutzernamen existiert bereits');
            }
            
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

            // Sicheres Passwort-Hashing
            $passwordHash = password_hash(
                !empty($personalData['password']) ? base64_decode($personalData['password']) : $this->generatePassword(),
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
        // Prüfen, ob ein Aktualisierungszeitraum angegeben wurde
        if (!empty($userData['subscription_duration']) && is_numeric($userData['subscription_duration'])) {
            $duration = (int)$userData['subscription_duration'];
            
            // Wenn das Mitglied bereits ein Ablaufdatum hat, dieses als Basis verwenden
            $startTime = time();
            if ($member->stop && $member->stop > time()) {
                $startTime = $member->stop;
            }
            
            // Neues Ablaufdatum berechnen
            $endTime = strtotime('+' . $duration . ' months', $startTime);
            
            // Mitglied aktualisieren
            $member->stop = $endTime;
            
            // Mitgliedschaft-Ende Feld aktualisieren
            $this->ensureMembershipExpiresField();
            $member->membership_expires = date('Y-m-d', $endTime);
            
            $this->logger->info('Mitgliedschaft für Benutzer ' . $member->username . ' verlängert', [
                'new_expiry' => date('Y-m-d', $endTime)
            ]);
        }
        
        // Gruppen aktualisieren, falls angegeben
        if (!empty($userData['groups'])) {
            $groups = $userData['groups'];
            if (is_array($groups)) {
                $member->groups = serialize(array_map('intval', $groups));
            }
        }
        
        $member->save();
        
        return $member->id;
    }
    
    /**
     * Erstellt einen neuen Benutzer
     */
    private function createNewMember(array $userData): int
    {
        // Benutzername generieren
        $username = $userData['email'];
        if (!empty($userData['username'])) {
            $username = $userData['username'];
        }
        
        // Passwort generieren oder verwenden
        $password = $this->generatePassword();
        if (!empty($userData['password'])) {
            // Wenn das Passwort Base64-codiert ist, decodieren
            if (preg_match('/^[a-zA-Z0-9+\/=]+$/', $userData['password'])) {
                try {
                    $decodedPassword = base64_decode($userData['password'], true);
                    if ($decodedPassword !== false) {
                        $password = $decodedPassword;
                    }
                } catch (\Exception $e) {
                    // Ignorieren, falls es nicht decodiert werden kann
                }
            } else {
                $password = $userData['password'];
            }
        }
        
        // Start und Ende der Mitgliedschaft berechnen
        $startTime = time();
        $endTime = 0;
        
        if (!empty($userData['subscription_duration']) && is_numeric($userData['subscription_duration'])) {
            $endTime = strtotime('+' . $userData['subscription_duration'] . ' months', $startTime);
        }
        
        // Benutzer erstellen
        $member = new MemberModel();
        $member->tstamp = time();
        $member->dateAdded = time();
        $member->username = $username;
        $member->password = password_hash($password, PASSWORD_DEFAULT);
        $member->email = $userData['email'];
        $member->firstname = $userData['firstname'] ?? '';
        $member->lastname = $userData['lastname'] ?? '';
        $member->gender = $this->mapSalutation($userData['salutation'] ?? '');
        $member->street = $userData['street'] ?? '';
        $member->postal = $userData['postal'] ?? '';
        $member->city = $userData['city'] ?? '';
        $member->country = $userData['country'] ?? 'de';
        $member->phone = $userData['phone'] ?? '';
        $member->company = $userData['company'] ?? '';
        
        // Login aktivieren
        $member->login = true;
        $member->start = $startTime;
        $member->stop = $endTime;
        
        // Mitgliedsgruppen setzen
        if (!empty($userData['groups'])) {
            $groups = $userData['groups'];
            if (is_array($groups)) {
                $member->groups = serialize(array_map('intval', $groups));
            }
        }
        
        // Mitgliedschaft-Ende Feld setzen
        $this->ensureMembershipExpiresField();
        if ($endTime > 0) {
            $member->membership_expires = date('Y-m-d', $endTime);
        }
        
        $member->save();
        
        $this->logger->info('Neuer Benutzer erstellt: ' . $username);
        
        // Wenn ein E-Mail-Service verfügbar ist, sende Registrierungs-E-Mail
        if ($this->emailService) {
            $this->emailService->sendRegistrationEmail($member);
        }
        
        return $member->id;
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