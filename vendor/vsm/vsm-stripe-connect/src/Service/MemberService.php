<?php

declare(strict_types=1);

/*
 * This file is part of vsm-stripe-connect.
 *
 * (c) Christian Voss 2025 <christian@vossmedien.de>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/vsm/vsm-stripe-connect
 */

namespace Vsm\VsmStripeConnect\Service;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\MemberModel;
use Contao\System;
use Psr\Log\LoggerInterface;

class MemberService
{
    private ContaoFramework $framework;
    private LoggerInterface $logger;

    public function __construct(
        ContaoFramework $framework,
        LoggerInterface $logger
    ) {
        $this->framework = $framework;
        $this->logger = $logger;
    }

    /**
     * Benutzer anlegen oder aktualisieren
     */
    public function createOrUpdateMember(array $metadata): MemberModel
    {
        $this->framework->initialize();

        if (empty($metadata['personalData'])) {
            throw new \Exception('No personal data provided');
        }

        $personalData = json_decode($metadata['personalData'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON in personalData: ' . json_last_error_msg());
        }

        // Pflichtfelder überprüfen
        $requiredFields = ['username', 'password', 'email'];
        foreach ($requiredFields as $field) {
            if (empty($personalData[$field])) {
                throw new \Exception("Required field missing: $field");
            }
        }

        // E-Mail-Format prüfen
        if (!filter_var($personalData['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('Invalid email format');
        }

        // Überprüfen, ob Benutzer bereits existiert
        $existingUser = MemberModel::findByEmail($personalData['email']);
        if ($existingUser) {
            return $this->updateExistingMember($existingUser, $metadata);
        }

        $existingUsername = MemberModel::findByUsername($personalData['username']);
        if ($existingUsername) {
            throw new \Exception('User with this username already exists');
        }

        return $this->createNewMember($personalData, $metadata);
    }

    /**
     * Bestehenden Benutzer aktualisieren
     */
    private function updateExistingMember(MemberModel $member, array $metadata): MemberModel
    {
        $personalData = json_decode($metadata['personalData'], true);
        $productData = !empty($metadata['productData']) ? 
            json_decode($metadata['productData'], true) : [];

        // Abonnementdauer verwenden
        $duration = isset($productData['duration']) ? (int)$productData['duration'] : 0;

        // Mitgliedschaftsdauer verlängern wenn Dauer angegeben
        if ($duration > 0) {
            $currentEnd = (int)$member->stop;
            
            // Wenn das aktuelle Enddatum in der Zukunft liegt, dort weiterzählen
            // sonst ab heute
            if ($currentEnd > time()) {
                $newEnd = strtotime("+{$duration} months", $currentEnd);
            } else {
                $newEnd = strtotime("+{$duration} months", time());
            }
            
            $member->disable = 0;
            $member->start = time();
            $member->stop = $newEnd;
            $member->dateEnd = date('Y-m-d', $newEnd);
        }

        // Mitgliedsgruppen aktualisieren, wenn angegeben
        if (!empty($metadata['memberGroup']) && is_numeric($metadata['memberGroup'])) {
            // Bestehende Gruppen beibehalten und neue hinzufügen
            $currentGroups = deserialize($member->groups, true);
            if (!in_array((int)$metadata['memberGroup'], $currentGroups)) {
                $currentGroups[] = (int)$metadata['memberGroup'];
                $member->groups = serialize(array_unique($currentGroups));
            }
        }

        $member->tstamp = time();
        $member->save();

        $this->logger->info('Member updated successfully', [
            'context' => ContaoContext::GENERAL,
            'username' => $member->username,
            'validUntil' => $member->stop ? date('Y-m-d', $member->stop) : 'unlimited'
        ]);

        return $member;
    }

    /**
     * Neuen Benutzer anlegen
     */
    private function createNewMember(array $personalData, array $metadata): MemberModel
    {
        $productData = [];
        if (!empty($metadata['productData'])) {
            $productData = json_decode($metadata['productData'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->warning('Invalid JSON in productData', [
                    'context' => ContaoContext::GENERAL,
                    'error' => json_last_error_msg()
                ]);
                $productData = [];
            }
        }

        // Dauer aus productData holen
        $duration = isset($productData['duration']) ? (int)$productData['duration'] : 0;

        // Start ist jetzt
        $startTime = time();

        // Berechne stopTime
        if ($duration > 0) {
            $stopTime = strtotime("+{$duration} months", $startTime);
        } else {
            $stopTime = 0;
        }

        $this->logger->info('Creating new member', [
            'context' => ContaoContext::GENERAL,
            'username' => $personalData['username'],
            'start' => date('Y-m-d H:i:s', $startTime),
            'stop' => $stopTime ? date('Y-m-d H:i:s', $stopTime) : 'not set',
            'duration' => $duration
        ]);

        // Sicheres Passwort-Hashing
        $passwordHash = password_hash(
            base64_decode($personalData['password']), 
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
            $this->logger->warning('No valid member group provided', [
                'context' => ContaoContext::GENERAL,
                'memberGroup' => $metadata['memberGroup'] ?? 'not set'
            ]);
            // Standardgruppe verwenden, falls konfiguriert
            $defaultGroup = System::getContainer()->getParameter('vsm_stripe_connect.default_member_group') ?? null;
            if ($defaultGroup) {
                $member->groups = serialize([(int)$defaultGroup]);
            }
        }

        $member->save();

        $this->logger->info('User created successfully', [
            'context' => ContaoContext::GENERAL,
            'username' => $member->username
        ]);

        return $member;
    }

    /**
     * Übersetzt Anrede in das Contao-Format
     */
    private function mapSalutation(string $salutation): string
    {
        return match ($salutation) {
            'Herr' => 'male',
            'Frau' => 'female',
            default => 'other'
        };
    }

    /**
     * Überprüft, ob Benutzername und E-Mail-Adresse gültig sind
     */
    public function checkUserCredentials(string $email, string $username): array
    {
        $this->framework->initialize();

        $existingEmail = MemberModel::findByEmail($email);
        $existingUsername = MemberModel::findByUsername($username);

        // Wenn beide nicht existieren, ist alles OK
        if (!$existingEmail && !$existingUsername) {
            return [
                'valid' => true
            ];
        }

        // Wenn beide existieren, müssen sie zum gleichen Benutzer gehören
        if ($existingEmail && $existingUsername) {
            $valid = ($existingEmail->id === $existingUsername->id);
            return [
                'valid' => $valid,
                'message' => $valid ? 'ok' : 'Die E-Mail-Adresse und der Benutzername gehören zu verschiedenen Konten. Bitte verwenden Sie entweder beide Daten eines bestehenden Kontos oder geben Sie komplett neue Daten ein.'
            ];
        }

        // Wenn nur einer existiert, ist es nicht OK
        return [
            'valid' => false,
            'message' => 'Bitte verwenden Sie entweder beide Daten eines bestehenden Kontos oder geben Sie komplett neue Daten ein.'
        ];
    }
} 