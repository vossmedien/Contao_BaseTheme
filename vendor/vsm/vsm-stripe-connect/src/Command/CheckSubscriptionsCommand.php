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

namespace Vsm\VsmStripeConnect\Command;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\MemberModel;
use Contao\System;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Vsm\VsmStripeConnect\Service\MemberService;
use Vsm\VsmStripeConnect\Service\StripePaymentService;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(
    name: 'vsm:stripe:check-subscriptions',
    description: 'Prüft und verlängert Stripe-Abonnements',
)]
class CheckSubscriptionsCommand extends Command
{
    private ContaoFramework $framework;
    private Connection $connection;
    private LoggerInterface $logger;
    private MemberService $memberService;
    private StripePaymentService $stripeService;

    public function __construct(
        ContaoFramework $framework,
        Connection $connection,
        LoggerInterface $logger,
        MemberService $memberService,
        StripePaymentService $stripeService
    ) {
        parent::__construct();
        $this->framework = $framework;
        $this->connection = $connection;
        $this->logger = $logger;
        $this->memberService = $memberService;
        $this->stripeService = $stripeService;
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Erzwingt die Verlängerung aller Abonnements, unabhängig vom Ablaufdatum'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->framework->initialize();
        
        $forceExtend = $input->getOption('force');
        
        $io->title('Prüfe Stripe-Abonnements');
        
        if ($forceExtend) {
            $io->info('Erzwungener Modus: Alle Abonnements werden verlängert');
        }

        // Aktive Abonnements aus der Datenbank holen
        $subscriptions = $this->getActiveSubscriptions();
        
        if (empty($subscriptions)) {
            $io->info('Keine aktiven Abonnements gefunden.');
            return Command::SUCCESS;
        }

        $io->info(sprintf('Gefundene aktive Abonnements: %d', count($subscriptions)));

        // Organisiere Abonnements nach E-Mail-Adressen, um Mehrfach-Abos zu identifizieren
        $emailToSubscriptions = [];
        foreach ($subscriptions as $subscription) {
            $email = $this->extractEmailFromCustomerData($subscription['customer_data']);
            if ($email) {
                if (!isset($emailToSubscriptions[$email])) {
                    $emailToSubscriptions[$email] = [];
                }
                $emailToSubscriptions[$email][] = $subscription;
            }
        }
        
        // Zeige Anzahl der Benutzer mit mehreren Abonnements
        $multipleSubscriptionUsers = array_filter($emailToSubscriptions, function($items) {
            return count($items) > 1;
        });
        
        if (count($multipleSubscriptionUsers) > 0) {
            $io->warning(sprintf('%d Benutzer haben mehrere Abonnements:', count($multipleSubscriptionUsers)));
            foreach ($multipleSubscriptionUsers as $email => $userSubscriptions) {
                $io->info(sprintf('  - %s: %d Abonnements', $email, count($userSubscriptions)));
                
                // Detaillierte Informationen zu jedem Abo anzeigen
                foreach ($userSubscriptions as $index => $sub) {
                    $productData = json_decode($sub['product_data'] ?? '{}', true);
                    $io->info(sprintf('    Abo %d: Session-ID: %s, Produkt: %s', 
                        $index + 1, 
                        $sub['session_id'], 
                        $productData['stripe_product_id'] ?? 'unbekannt'
                    ));
                    
                    // Bestimme die Duration für dieses Abo
                    $duration = $this->getSubscriptionDuration($sub);
                    $io->info(sprintf('    ↳ Duration: %d', $duration));
                }
            }
            
            $io->warning('Um zu vermeiden, dass Abos mehrfach verlängert werden, wird nur ein Abo pro E-Mail-Adresse verlängert.');
        }

        // Teste Stripe-Produkt-Abruf für Debug-Zwecke
        if (!empty($subscriptions)) {
            foreach ($subscriptions as $subscription) {
                $productData = json_decode($subscription['product_data'] ?? '{}', true);
                if (!empty($productData['stripe_product_id'])) {
                    $stripeProductId = $productData['stripe_product_id'];
                    $io->info(sprintf('Teste Stripe-Produkt-Abruf für: %s', $stripeProductId));
                    
                    try {
                        $stripeProduct = $this->stripeService->getProduct($stripeProductId);
                        if ($stripeProduct) {
                            $io->info(sprintf('Produkt gefunden: %s', $stripeProduct->name));
                            
                            // Metadata prüfen
                            if (isset($stripeProduct->metadata) && !empty($stripeProduct->metadata->toArray())) {
                                $io->info('Produktmetadaten:');
                                foreach ($stripeProduct->metadata->toArray() as $key => $value) {
                                    $io->info(sprintf('  - %s: %s', $key, $value));
                                }
                            } else {
                                $io->warning('Keine Metadaten gefunden');
                            }
                            
                            // Nach duration suchen
                            if (isset($stripeProduct->metadata->duration)) {
                                $io->success(sprintf('Duration gefunden: %s', $stripeProduct->metadata->duration));
                            } else {
                                $io->warning('Keine duration in Metadaten gefunden');
                            }
                        } else {
                            $io->error(sprintf('Produkt nicht gefunden: %s', $stripeProductId));
                        }
                    } catch (\Exception $e) {
                        $io->error(sprintf('Fehler beim Abrufen des Produkts: %s', $e->getMessage()));
                    }
                    
                    // Nur einen Test durchführen
                    break;
                }
            }
        }
        
        $io->progressStart(count($subscriptions));

        $extendedCount = 0;
        $errorCount = 0;
        $alreadyExtendedEmails = []; // Liste der bereits verlängerten E-Mail-Adressen

        foreach ($subscriptions as $subscription) {
            try {
                $io->progressAdvance();
                
                // Mitglied anhand der E-Mail-Adresse finden
                $email = $this->extractEmailFromCustomerData($subscription['customer_data']);
                if (!$email) {
                    $this->logger->warning('Keine E-Mail-Adresse gefunden', ['session_id' => $subscription['session_id']]);
                    continue;
                }
                
                // Prüfen, ob dieser Benutzer bereits verlängert wurde
                if (in_array($email, $alreadyExtendedEmails)) {
                    $this->logger->info('Benutzer wurde bereits in diesem Durchlauf verlängert', [
                        'email' => $email,
                        'session_id' => $subscription['session_id']
                    ]);
                    $io->text(sprintf(
                        '<comment>Überspringe Abo für %s:</comment> Benutzer wurde bereits verlängert',
                        $email
                    ));
                    continue;
                }
                
                $member = MemberModel::findByEmail($email);
                if (!$member) {
                    $this->logger->warning('Kein Mitglied mit dieser E-Mail gefunden', ['email' => $email]);
                    continue;
                }
                
                // Logge die Mitgliedsdaten für Debugging
                $this->logger->info('Mitgliedsdaten gefunden', [
                    'email' => $email,
                    'stop' => $member->stop ?: 'nicht gesetzt',
                    'membership_expires' => $member->membership_expires ?: 'nicht gesetzt',
                    'current_time' => date('Y-m-d', time())
                ]);
                
                // Prüfen, ob das Ablaufdatum überschritten wurde oder kein Ablaufdatum gesetzt ist
                $isExpired = false;
                $noExpiryDate = true; // Standardmäßig annehmen, dass kein Ablaufdatum gesetzt ist
                
                // Zuerst das membership_expires Feld prüfen (Format YYYY-MM-DD)
                if (!empty($member->membership_expires)) {
                    $noExpiryDate = false;
                    $expiryTimestamp = strtotime($member->membership_expires);
                    if ($expiryTimestamp && $expiryTimestamp <= time()) {
                        $isExpired = true;
                        $this->logger->info('Mitgliedschaft ist abgelaufen (membership_expires)', [
                            'email' => $email,
                            'expiry_date' => $member->membership_expires,
                            'expiry_timestamp' => $expiryTimestamp,
                            'current_time' => time()
                        ]);
                    }
                }
                
                // Dann die stop-Spalte prüfen (in Contao als Unix-Timestamp oder Y-m-d Format)
                if (!$isExpired && !empty($member->stop)) {
                    $noExpiryDate = false;
                    
                    // Prüfen, ob der stop-Wert ein Unix-Timestamp oder ein Datumsstring ist
                    $stopTimestamp = 0;
                    if (is_numeric($member->stop)) {
                        $stopTimestamp = (int)$member->stop;
                    } else {
                        $stopTimestamp = strtotime($member->stop);
                    }
                    
                    if ($stopTimestamp && $stopTimestamp <= time()) {
                        $isExpired = true;
                        $this->logger->info('Mitgliedschaft ist abgelaufen (stop)', [
                            'email' => $email,
                            'stop' => $member->stop,
                            'stop_timestamp' => $stopTimestamp,
                            'stop_date' => date('Y-m-d', $stopTimestamp),
                            'current_time' => time()
                        ]);
                    }
                }
                
                // Überprüfen, ob keine stop-Spalte vorhanden ist
                if (empty($member->stop)) {
                    $this->logger->info('Mitglied hat keinen stop-Wert', [
                        'email' => $email,
                        'member_id' => $member->id
                    ]);
                    
                    // Wenn keine Ablaufdaten gesetzt sind, werden wir das Membership auf jeden Fall aktualisieren
                    $noExpiryDate = true;
                }
                
                // Prüfen, ob der Benutzer aktuell deaktiviert ist
                if ($member->disable) {
                    $this->logger->info('Mitglied ist deaktiviert und wird aktiviert', [
                        'email' => $email,
                        'member_id' => $member->id,
                        'disable' => $member->disable
                    ]);
                }
                
                // Wenn abgelaufen oder kein Ablaufdatum gesetzt oder Force-Modus aktiviert, dann verlängern
                if ($isExpired || $noExpiryDate || $forceExtend) {
                    // Grund für die Verlängerung loggen
                    if ($isExpired) {
                        $this->logger->info('Grund für Verlängerung: Mitgliedschaft ist abgelaufen', ['email' => $email]);
                    } else if ($noExpiryDate) {
                        $this->logger->info('Grund für Verlängerung: Kein Ablaufdatum gesetzt', ['email' => $email]);
                    } else if ($forceExtend) {
                        $this->logger->info('Grund für Verlängerung: Force-Modus aktiviert', ['email' => $email]);
                    }
                
                    // Abo-Laufzeit aus Stripe oder Produktdaten ermitteln
                    $duration = $this->getSubscriptionDuration($subscription);
                    
                    // Ausgabe im Terminal für dieses spezifische Abonnement
                    $productData = json_decode($subscription['product_data'] ?? '{}', true);
                    $io->text(sprintf(
                        '<info>Abo-Prüfung für %s:</info> Session-ID: %s, Produkt: %s, Duration: %d', 
                        $email,
                        $subscription['session_id'],
                        $productData['stripe_product_id'] ?? 'unbekannt',
                        $duration
                    ));
                    
                    if ($duration <= 0) {
                        $this->logger->warning('Keine gültige Abo-Laufzeit in den Stripe-Metadaten gefunden, Verlängerung wird übersprungen', [
                            'session_id' => $subscription['session_id'],
                            'email' => $email,
                            'stripe_product_id' => $productData['stripe_product_id'] ?? 'unbekannt'
                        ]);
                        
                        $io->error(sprintf(
                            'Verlängerung nicht möglich: Keine gültige Duration im Stripe-Produkt gefunden. Bitte "duration" in den Metadaten des Produkts setzen.'
                        ));
                        
                        continue;
                    }
                    
                    $io->text(sprintf(
                        '<info>Verlängere Abo für %s:</info> Duration: %d Monate', 
                        $email,
                        $duration
                    ));
                    
                    try {
                        // Wir berechnen das neue Stop-Datum
                        $newStopTimestamp = time() + ($duration * 30 * 24 * 60 * 60);
                        
                        // Wenn bereits ein Stop-Datum existiert und in der Zukunft liegt, verlängern wir von dort
                        if (!empty($member->stop)) {
                            // Prüfen, ob es ein Unix-Timestamp oder ein Datum ist
                            $existingStopTimestamp = 0;
                            
                            if (is_numeric($member->stop)) {
                                $existingStopTimestamp = (int)$member->stop;
                            } else {
                                $existingStopTimestamp = strtotime($member->stop);
                            }
                            
                            if ($existingStopTimestamp && $existingStopTimestamp > time()) {
                                $this->logger->info('Verlängerung ab bestehendem Stop-Datum', [
                                    'email' => $email,
                                    'existing_stop' => $member->stop,
                                    'existing_stop_timestamp' => $existingStopTimestamp,
                                    'existing_stop_date' => date('Y-m-d H:i:s', $existingStopTimestamp)
                                ]);
                                $newStopTimestamp = $existingStopTimestamp + ($duration * 30 * 24 * 60 * 60);
                                
                                // Terminal-Ausgabe für die Verlängerung
                                $io->text(sprintf(
                                    '  <comment>Verlängerung ab bestehendem Stop-Datum:</comment> %s (+ %d Monate)',
                                    date('Y-m-d', $existingStopTimestamp),
                                    $duration
                                ));
                            } else {
                                $io->text(sprintf(
                                    '  <comment>Neues Stop-Datum wird ab heute berechnet</comment> (+ %d Monate)',
                                    $duration
                                ));
                            }
                        } else {
                            $io->text(sprintf(
                                '  <comment>Neues Stop-Datum wird ab heute berechnet</comment> (+ %d Monate)',
                                $duration
                            ));
                        }
                        
                        // Debug-Info über die berechneten Daten
                        $this->logger->info('Berechnete Zeitwerte', [
                            'email' => $email,
                            'current_time' => time(),
                            'current_time_date' => date('Y-m-d H:i:s', time()),
                            'duration_in_months' => $duration,
                            'duration_in_seconds' => $duration * 30 * 24 * 60 * 60,
                            'new_stop_timestamp' => $newStopTimestamp,
                            'new_stop_date' => date('Y-m-d H:i:s', $newStopTimestamp)
                        ]);
                        
                        // Neue Werte setzen
                        $member->stop = (string)$newStopTimestamp; // Als Unix-Timestamp (als String)
                        $member->membership_expires = date('Y-m-d', $newStopTimestamp); // Dieses Feld bleibt im Y-m-d Format
                        $member->disable = 0; // Deaktivierung aufheben
                        
                        // Start-Datum nur aktualisieren, wenn nicht gesetzt oder in der Vergangenheit
                        if (empty($member->start)) {
                            $member->start = (string)time(); // Heutiger Timestamp als String
                        } else {
                            // Prüfen, ob das Start-Datum in der Vergangenheit liegt
                            
                            // Erst prüfen, ob es ein Unix-Timestamp ist
                            if (is_numeric($member->start)) {
                                $startTimestamp = (int)$member->start;
                                if ($startTimestamp < time()) {
                                    $member->start = (string)time(); // Auf heute setzen
                                }
                            } else {
                                // Versuchen als Datum zu parsen
                                $startTimestamp = strtotime($member->start);
                                if (!$startTimestamp || $startTimestamp < time()) {
                                    $member->start = (string)time(); // Auf heute setzen
                                }
                            }
                        }
                        
                        // Änderungen speichern
                        $member->tstamp = time();
                        $member->save();
                        
                        $this->logger->info('Abonnement erfolgreich verlängert', [
                            'session_id' => $subscription['session_id'],
                            'email' => $email,
                            'duration' => $duration,
                            'neues_enddatum' => date('Y-m-d', $newStopTimestamp)
                        ]);
                        
                        // Terminal-Ausgabe des Ergebnisses
                        $io->text(sprintf(
                            '  <info>Verlängerung erfolgreich:</info> Neues Stop-Datum: %s, Disable: %s',
                            date('Y-m-d', $newStopTimestamp),
                            $member->disable ? 'Ja' : 'Nein'
                        ));
                        
                        // Zur Liste der bereits verlängerten E-Mails hinzufügen
                        $alreadyExtendedEmails[] = $email;
                        
                        $extendedCount++;
                    } catch (\Exception $e) {
                        $this->logger->error('Fehler beim Verlängern des Mitglieds: ' . $e->getMessage(), [
                            'email' => $email,
                            'exception' => $e
                        ]);
                        $errorCount++;
                    }
                }
            } catch (\Exception $e) {
                $this->logger->error('Fehler bei der Verarbeitung eines Abonnements: ' . $e->getMessage(), [
                    'session_id' => $subscription['session_id'] ?? 'unbekannt',
                    'exception' => $e
                ]);
                $errorCount++;
            }
        }

        $io->progressFinish();
        
        // Detaillierte Zusammenfassung ausgeben
        $io->section('Zusammenfassung');
        
        // Anzahl der Benutzer mit mehreren Abonnements nochmal anzeigen
        if (count($multipleSubscriptionUsers) > 0) {
            $io->warning(sprintf('%d Benutzer haben mehrere Abonnements.', count($multipleSubscriptionUsers)));
            $io->text('Diese Benutzer könnten mehrfach verlängert worden sein. Prüfe die Logs für Details.');
        }
        
        $io->success(sprintf(
            'Abonnement-Prüfung abgeschlossen: %d Abonnements verlängert, %d Fehler',
            $extendedCount,
            $errorCount
        ));

        return Command::SUCCESS;
    }

    /**
     * Holt aktive Abonnements aus der Datenbank
     */
    private function getActiveSubscriptions(): array
    {
        $qb = $this->connection->createQueryBuilder();
        
        $completedSessions = $qb->select('*')
            ->from('tl_stripe_payment_sessions')
            ->where($qb->expr()->eq('status', ':status'))
            ->setParameter('status', 'completed')
            ->execute()
            ->fetchAllAssociative();
            
        // Aus den abgeschlossenen Sessions die Abonnements filtern
        $subscriptions = [];
        foreach ($completedSessions as $session) {
            $productData = json_decode($session['product_data'] ?? '{}', true);
            
            // Als Abonnement markieren, wenn eines der Kriterien erfüllt ist:
            // 1. is_subscription = true ist gesetzt
            // 2. subscription_duration ist gesetzt und > 0
            // 3. duration ist gesetzt und > 0
            if (
                (isset($productData['is_subscription']) && $productData['is_subscription'] === true) ||
                (isset($productData['is_subscription']) && $productData['is_subscription'] === 'true') ||
                (isset($productData['subscription_duration']) && (int)$productData['subscription_duration'] > 0) ||
                (isset($productData['duration']) && (int)$productData['duration'] > 0)
            ) {
                $subscriptions[] = $session;
            }
        }
        
        return $subscriptions;
    }

    /**
     * Extrahiert die E-Mail-Adresse aus den JSON-codierten Kundendaten
     */
    private function extractEmailFromCustomerData(?string $customerData): ?string
    {
        if (!$customerData) {
            return null;
        }
        
        $data = json_decode($customerData, true);
        if (!$data || !isset($data['email'])) {
            return null;
        }
        
        return $data['email'];
    }

    /**
     * Ermittelt die Abo-Laufzeit aus den Produkt- oder Stripe-Daten
     */
    private function getSubscriptionDuration(array $subscription): int
    {
        // Produktdaten aus der Session extrahieren
        $productData = [];
        
        if (!empty($subscription['product_data'])) {
            if (is_string($subscription['product_data'])) {
                $productData = json_decode($subscription['product_data'], true) ?: [];
            } elseif (is_array($subscription['product_data'])) {
                $productData = $subscription['product_data'];
            }
        }
        
        // Vollständige Produktdaten loggen
        $this->logger->info('Produktdaten für Subscription', [
            'session_id' => $subscription['session_id'] ?? '',
            'product_data' => $productData
        ]);
        
        // Stripe-Produkt-ID aus den Produktdaten extrahieren
        if (empty($productData['stripe_product_id'])) {
            $this->logger->warning('Keine Stripe-Produkt-ID gefunden', [
                'session_id' => $subscription['session_id'] ?? ''
            ]);
            return 0;
        }
        
        // Stripe-Produkt abrufen und Duration aus den Metadaten auslesen
        $stripeProductId = $productData['stripe_product_id'];
        
        try {
            $this->logger->info('Rufe Stripe-Produkt ab', [
                'stripe_product_id' => $stripeProductId
            ]);
            
            $stripeProduct = $this->stripeService->getProduct($stripeProductId);
            
            if (!$stripeProduct) {
                $this->logger->warning('Stripe-Produkt nicht gefunden', [
                    'stripe_product_id' => $stripeProductId
                ]);
                return 0;
            }
            
            // Alle Metadaten-Felder abrufen und loggen
            $metadata = $stripeProduct->metadata ? $stripeProduct->metadata->toArray() : [];
            
            $this->logger->info('Stripe-Produkt gefunden', [
                'stripe_product_id' => $stripeProductId,
                'product_name' => $stripeProduct->name ?? 'unbekannt',
                'metadata' => $metadata
            ]);
            
            // Nur das 'duration' Feld aus den Metadaten verwenden
            if (isset($metadata['duration'])) {
                $duration = $this->parseIntValue($metadata['duration']);
                if ($duration > 0) {
                    $this->logger->info('Laufzeit aus Stripe-Metadata', [
                        'stripe_product_id' => $stripeProductId,
                        'field' => 'duration',
                        'duration' => $duration
                    ]);
                    return $duration;
                }
            }
            
            $this->logger->warning('Kein gültiges duration-Feld in Stripe-Metadaten gefunden', [
                'stripe_product_id' => $stripeProductId
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Abrufen der Stripe-Produktdaten: ' . $e->getMessage(), [
                'stripe_product_id' => $stripeProductId
            ]);
        }
        
        // Wenn kein gültiger Wert in den Stripe-Metadaten gefunden wurde, geben wir 0 zurück
        return 0;
    }
    
    /**
     * Konvertiert verschiedene Werttypen zu Integer
     */
    private function parseIntValue($value): int
    {
        if (is_int($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            // "true" und "false" in Anführungszeichen berücksichtigen
            if ($value === 'true') {
                return 1;
            }
            
            if ($value === 'false') {
                return 0;
            }
            
            if (is_numeric($value)) {
                return (int)$value;
            }
        }
        
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }
        
        return 0;
    }
} 