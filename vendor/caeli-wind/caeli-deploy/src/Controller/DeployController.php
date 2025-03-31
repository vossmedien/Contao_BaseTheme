<?php

namespace CaeliWind\CaeliDeploy\Controller;

use Contao\BackendModule;
use Contao\BackendTemplate;
use Contao\Input;
use Contao\Message;
use Contao\System;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class DeployController extends BackendModule
{
    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'be_deploy_to_live';
    
    /**
     * Generate the module
     *
     * @return string
     */
    public function generate(): string
    {
        // Backend-Template erstellen
        $template = new BackendTemplate($this->strTemplate);
        $template->ausgerollt = "";
        $template->isError = false;
        $template->showBackups = true;
        $template->success = false;
        
        $projectDir = System::getContainer()->getParameter('kernel.project_dir');
        
        // Lade Pfade aus der Umgebungskonfiguration
        $backupPath = $this->getEnvConfig('DEPLOY_BACKUP_PATH', '/usr/home/caelif/public_html/relaunch_live_autodeploy_versions');
        $stagingPath = $this->getEnvConfig('DEPLOY_STAGING_PATH', '/usr/home/caelif/public_html/staging.caeli');
        $livePath = $this->getEnvConfig('DEPLOY_LIVE_PATH', '/usr/home/caelif/public_html/relaunch.caeli');
        
        $backups = [];
        
        // Aktuelles Backup - aus dem Staging-Pfad
        $currentBackupFiles = $stagingPath . '/x_live_files.tar.gz';
        $currentBackupDb = $stagingPath . '/x_live_db.sql';
        
        // Prüfe, ob die Backup-Dateien existieren
        if (file_exists($currentBackupFiles) && file_exists($currentBackupDb)) {
            $filesTime = date("d.m.Y H:i:s", filemtime($currentBackupFiles));
            $dbTime = date("d.m.Y H:i:s", filemtime($currentBackupDb));
            
            // Prüfen, ob eine Info-Datei existiert
            $currentInfo = '';
            $currentInfoFile = $stagingPath . '/backup_info.txt';
            if (file_exists($currentInfoFile)) {
                $currentInfo = file_get_contents($currentInfoFile);
            }
            
            $backups[] = [
                'id' => 'current',
                'name' => 'Aktuelles Backup',
                'files' => 'x_live_files.tar.gz (' . $filesTime . ')',
                'db' => 'x_live_db.sql (' . $dbTime . ')',
                'info' => $currentInfo,
                'time' => filemtime($currentBackupFiles),
                'current' => true,
                'path_files' => $currentBackupFiles,
                'path_db' => $currentBackupDb
            ];
        }
        
        // Im Backup-Verzeichnis nach weiteren Backups suchen
        if (is_dir($backupPath)) {
            $fileBackups = glob($backupPath . '/*_live_files.tar.gz');
            
            if (!empty($fileBackups)) {
                foreach ($fileBackups as $file) {
                    $basename = basename($file);
                    $basePart = str_replace('_files.tar.gz', '', $basename);
                    $dbFile = $backupPath . '/' . $basePart . '_db.sql';
                    
                    // Backup-Info zurücksetzen für jedes Backup
                    $infoFile = null;
                    $backupInfo = '';
                    
                    // Priorität 1: Exakte Übereinstimmung prüfen
                    $exactInfoFile = $backupPath . '/' . $basePart . '_info.txt';
                    if (file_exists($exactInfoFile)) {
                        $infoFile = $exactInfoFile;
                        $backupInfo = file_get_contents($infoFile);
                    }
                    // Priorität 2: Zeitstempelbasierte Suche - nur wenn bisher keine Info gefunden wurde
                    else {
                        // Hole den Zeitstempel aus dem Dateinamen (YYYY-MM-DD_HH-MM-SS)
                        if (preg_match('/^([\d]{4}-[\d]{2}-[\d]{2}_[\d]{2}-[\d]{2}-[\d]{2})/', $basePart, $matches)) {
                            $timestampPart = $matches[1];
                            $timeBasedInfoFile = $backupPath . '/' . $timestampPart . '_info.txt';
                            
                            if (file_exists($timeBasedInfoFile)) {
                                $infoFile = $timeBasedInfoFile;
                                $backupInfo = file_get_contents($infoFile);
                            }
                        }
                    }
                    
                    if (file_exists($dbFile)) {
                        $backupTime = filemtime($file);
                        $backupId = 'backup_' . $backupTime;
                        
                        $backups[] = [
                            'id' => $backupId,
                            'name' => 'Backup vom ' . date("d.m.Y H:i:s", $backupTime),
                            'files' => $basename . ' (' . date("d.m.Y H:i:s", filemtime($file)) . ')',
                            'db' => basename($dbFile) . ' (' . date("d.m.Y H:i:s", filemtime($dbFile)) . ')',
                            'info' => $backupInfo,
                            'time' => $backupTime,
                            'current' => false,
                            'path_files' => $file,
                            'path_db' => $dbFile
                        ];
                    }
                }
            }
        }
        
        // Sortiere Backups nach Zeit absteigend
        if (!empty($backups)) {
            usort($backups, function($a, $b) {
                return $b['time'] - $a['time'];
            });
        }
        
        $template->backups = $backups;
        $template->backupCount = count($backups);
        
        // Für Contao 5.5 mit Turbo: Prüfen ob Anfrage per XHR (AJAX) kam
        $requestStack = System::getContainer()->get('request_stack');
        $request = $requestStack->getCurrentRequest();
        $isXhr = $request && $request->isXmlHttpRequest();
        
        // Session über RequestStack holen (Contao 5.5)
        $session = $requestStack->getSession();
        
        // Prüfen, ob ein Formular abgesendet wurde
        if (Input::post('FORM_SUBMIT') === 'deploy_to_live_form' || Input::post('TL_SUBMIT') !== null) {
            $action = Input::post('ausrollen') ?: 'ausrollen';  // Standardmäßig ausrollen, wenn kein Wert gesetzt ist
            
            $redirectUrl = System::getContainer()->get('router')->generate('contao_backend', [
                'do' => 'deploy_to_live'
            ]);
            
            // Ausgewähltes Backup (falls vorhanden)
            $selectedBackup = Input::post('selected_backup') ?: 'current';
            
            // Backup-Info (falls angegeben)
            $backupInfo = Input::post('backup_info') ?: '';
            
            if ($action === 'ausrollen') {
                try {
                    // Backup-Info speichern
                    if (!empty($backupInfo)) {
                        file_put_contents($stagingPath . '/backup_info.txt', $backupInfo);
                    }
                    
                    // Deployment-Script ausführen - im Projekt-Root-Verzeichnis
                    $process = new Process(['bash', $projectDir . '/xdeploystagingtolive.sh']);
                    $process->setWorkingDirectory($projectDir);
                    $process->setTimeout(600); // Erhöhe Timeout auf 10 Minuten
                    $process->mustRun();
                    
                    // Log-Datei auslesen, falls vorhanden
                    $logFile = $stagingPath . '/deploy_log.txt';
                    
                    // Lokale Datei prüfen, falls Remote-Pfad nicht funktioniert
                    if (!file_exists($logFile)) {
                        $logFile = $projectDir . '/deploy_log.txt';
                    }
                    
                    if (file_exists($logFile)) {
                        // Nur wichtige Teile des Logs anzeigen
                        $logContent = file_get_contents($logFile);
                        $filteredLog = $this->filterLogContent($logContent);
                        
                        // In Session speichern für nach dem Redirect
                        $session->set('deploy_log', $filteredLog);
                        $session->set('deploy_success', true);
                        
                        // Contao Meldung setzen
                        Message::addConfirmation('Deployment erfolgreich durchgeführt.');
                    } else {
                        // In Session speichern für nach dem Redirect
                        $session->set('deploy_log', "Erfolgreich ausgerollt, aber keine Log-Datei gefunden.");
                        $session->set('deploy_success', true);
                        
                        Message::addConfirmation('Deployment erfolgreich durchgeführt.');
                    }
                    
                    // Für Turbo: Bei AJAX-Anfragen einen JS-Reload zurückgeben, ansonsten umleiten
                    if ($isXhr) {
                        $template->ausgerollt = $session->get('deploy_log');
                        $template->success = true;
                        return $template->parse();
                    } else {
                        return new RedirectResponse($redirectUrl);
                    }
                } catch (ProcessFailedException $exception) {
                    $this->handleProcessException($session, $template, $exception, 'Deployment', $isXhr, $redirectUrl);
                    return $isXhr ? $template->parse() : new RedirectResponse($redirectUrl);
                } catch (\Exception $exception) {
                    $this->handleGenericException($session, $template, $exception, 'Deployment', $isXhr, $redirectUrl);
                    return $isXhr ? $template->parse() : new RedirectResponse($redirectUrl);
                }
            } elseif ($action === 'rollback') {
                try {
                    // Wenn ein spezifisches Backup ausgewählt wurde, kopiere es zuerst in den Staging-Pfad
                    if ($selectedBackup !== 'current') {
                        // Finde das ausgewählte Backup
                        $selectedBackupData = null;
                        foreach ($backups as $backup) {
                            if ($backup['id'] === $selectedBackup) {
                                $selectedBackupData = $backup;
                                break;
                            }
                        }
                        
                        if ($selectedBackupData) {
                            // Kopiere die ausgewählten Backup-Dateien zum Staging-Pfad
                            copy($selectedBackupData['path_files'], $stagingPath . '/x_live_files.tar.gz');
                            copy($selectedBackupData['path_db'], $stagingPath . '/x_live_db.sql');
                            
                            // Information über das verwendete Backup in einer temporären Datei speichern,
                            // die vom Rollback-Skript ausgelesen werden kann
                            file_put_contents($stagingPath . '/rollback_info.txt', 
                                "Verwendetes Backup: " . $selectedBackupData['name'] . "\n" .
                                "Dateien: " . $selectedBackupData['files'] . "\n" .
                                "Datenbank: " . $selectedBackupData['db'] . "\n"
                            );
                            
                            // Wenn das ausgewählte Backup eine Info hat, diese ebenfalls speichern
                            if (!empty($selectedBackupData['info'])) {
                                file_put_contents($stagingPath . '/backup_info.txt', $selectedBackupData['info']);
                            }
                        }
                    } else {
                        // Aktuelles Backup (das bereits im Staging-Pfad liegt) wird verwendet
                        file_put_contents($stagingPath . '/rollback_info.txt', 
                            "Verwendetes Backup: Aktuelles Backup\n" .
                            "Dateien: x_live_files.tar.gz\n" .
                            "Datenbank: x_live_db.sql\n"
                        );
                        
                        // Wenn eine Backup-Info eingegeben wurde, diese speichern
                        if (!empty($backupInfo)) {
                            file_put_contents($stagingPath . '/backup_info.txt', $backupInfo);
                        }
                    }
                
                    // Rollback-Script ausführen - im Projekt-Root-Verzeichnis
                    $process = new Process(['bash', $projectDir . '/xrollbacklive.sh']);
                    $process->setWorkingDirectory($projectDir);
                    $process->setTimeout(600); // Erhöhe Timeout auf 10 Minuten
                    $process->mustRun();
                    
                    // Log-Datei auslesen
                    $logFile = $stagingPath . '/rollback_log.txt';
                    if (!file_exists($logFile)) {
                        $logFile = $projectDir . '/rollback_log.txt';
                    }
                    
                    if (file_exists($logFile)) {
                        $logContent = file_get_contents($logFile);
                        $filteredLog = $this->filterLogContent($logContent);
                        
                        $session->set('deploy_log', $filteredLog);
                        $session->set('deploy_success', true);
                        
                        Message::addConfirmation('Rollback erfolgreich durchgeführt.');
                    } else {
                        $session->set('deploy_log', "Rollback erfolgreich, aber keine Log-Datei gefunden.");
                        $session->set('deploy_success', true);
                        
                        Message::addConfirmation('Rollback erfolgreich durchgeführt.');
                    }
                    
                    if ($isXhr) {
                        $template->ausgerollt = $session->get('deploy_log');
                        $template->success = true;
                        return $template->parse();
                    } else {
                        return new RedirectResponse($redirectUrl);
                    }
                } catch (ProcessFailedException $exception) {
                    $this->handleProcessException($session, $template, $exception, 'Rollback', $isXhr, $redirectUrl);
                    return $isXhr ? $template->parse() : new RedirectResponse($redirectUrl);
                } catch (\Exception $exception) {
                    $this->handleGenericException($session, $template, $exception, 'Rollback', $isXhr, $redirectUrl);
                    return $isXhr ? $template->parse() : new RedirectResponse($redirectUrl);
                }
            } elseif ($action === 'cleanup') {
                try {
                    // Backup-Bereinigung ausführen
                    $process = new Process(['bash', $projectDir . '/xcleanupbackups.sh']);
                    $process->setWorkingDirectory($projectDir);
                    $process->setTimeout(300); // 5 Minuten Timeout
                    $process->mustRun();
                    
                    // Log-Datei auslesen
                    $logFile = $stagingPath . '/cleanup_log.txt';
                    if (!file_exists($logFile)) {
                        $logFile = $projectDir . '/cleanup_log.txt';
                    }
                    
                    if (file_exists($logFile)) {
                        $logContent = file_get_contents($logFile);
                        $filteredLog = $this->filterLogContent($logContent);
                        
                        $session->set('deploy_log', $filteredLog);
                        $session->set('deploy_success', true);
                        
                        Message::addConfirmation('Backup-Bereinigung erfolgreich durchgeführt.');
                    } else {
                        $session->set('deploy_log', "Backup-Bereinigung erfolgreich, aber keine Log-Datei gefunden.");
                        $session->set('deploy_success', true);
                        
                        Message::addConfirmation('Backup-Bereinigung erfolgreich durchgeführt.');
                    }
                    
                    if ($isXhr) {
                        $template->ausgerollt = $session->get('deploy_log');
                        $template->success = true;
                        return $template->parse();
                    } else {
                        return new RedirectResponse($redirectUrl);
                    }
                } catch (ProcessFailedException $exception) {
                    $this->handleProcessException($session, $template, $exception, 'Backup-Bereinigung', $isXhr, $redirectUrl);
                    return $isXhr ? $template->parse() : new RedirectResponse($redirectUrl);
                } catch (\Exception $exception) {
                    $this->handleGenericException($session, $template, $exception, 'Backup-Bereinigung', $isXhr, $redirectUrl);
                    return $isXhr ? $template->parse() : new RedirectResponse($redirectUrl);
                }
            }
        }
        
        // Wenn aus Session-Daten verfügbar sind, diese ins Template übernehmen
        if ($session->has('deploy_log')) {
            $template->ausgerollt = $session->get('deploy_log');
            $template->success = $session->get('deploy_success', false);
            $template->isError = !$template->success;
            
            // Nach dem Anzeigen aus der Session löschen
            $session->remove('deploy_log');
            $session->remove('deploy_success');
        }

        return $template->parse();
    }
    
    /**
     * Liest einen Konfigurationswert aus der .env.local Datei
     * 
     * @param string $key
     * @param string $default
     * @return string
     */
    protected function getEnvConfig(string $key, string $default = ''): string
    {
        $projectDir = System::getContainer()->getParameter('kernel.project_dir');
        
        if (file_exists($projectDir . '/.env.local')) {
            $envContent = file_get_contents($projectDir . '/.env.local');
            if (preg_match('/' . $key . '\s*=\s*([^\r\n]+)/', $envContent, $matches)) {
                return trim($matches[1]);
            }
            
            // Alternativ versuchen, parse_ini_file zu nutzen
            $env = @parse_ini_file($projectDir . '/.env.local');
            if ($env !== false && isset($env[$key])) {
                return $env[$key];
            }
        }
        
        return $default;
    }
    
    /**
     * Behandelt einen ProcessFailedException
     */
    protected function handleProcessException($session, $template, ProcessFailedException $exception, string $operation, bool $isXhr, string $redirectUrl): void
    {
        $errorMsg = "Fehler bei $operation: " . $exception->getMessage();
        
        $session->set('deploy_log', $errorMsg);
        $session->set('deploy_success', false);
        
        $template->ausgerollt = $errorMsg;
        $template->isError = true;
        Message::addError("$operation fehlgeschlagen.");
    }
    
    /**
     * Behandelt eine allgemeine Exception
     */
    protected function handleGenericException($session, $template, \Exception $exception, string $operation, bool $isXhr, string $redirectUrl): void
    {
        $errorMsg = "Allgemeiner Fehler: " . $exception->getMessage();
        
        $session->set('deploy_log', $errorMsg);
        $session->set('deploy_success', false);
        
        $template->ausgerollt = $errorMsg;
        $template->isError = true;
        Message::addError("$operation fehlgeschlagen: " . $exception->getMessage());
    }
    
    /**
     * Filtert den Log-Inhalt, um nur wichtige Informationen anzuzeigen
     * 
     * @param string $logContent
     * @return string
     */
    protected function filterLogContent(string $logContent): string
    {
        $lines = explode("\n", $logContent);
        $filteredLines = [];
        
        foreach ($lines as $line) {
            // Leerzeichen am Anfang und Ende der Zeile entfernen
            $line = trim($line);
            
            // Leere Zeilen überspringen
            if ($line === '') {
                continue;
            }
            
            // Ignoriere unwichtige Zeilen
            if (
                strpos($line, 'sending incremental file list') !== false ||
                strpos($line, 'sent ') !== false ||
                strpos($line, 'total size is') !== false ||
                preg_match('/^\s*$/', $line) || // Leere Zeilen
                strpos($line, './') === 0 || // Dateipfade
                preg_match('/^\.\//', $line) || // Dateipfade mit ./
                preg_match('/^\//', $line) || // Absolute Pfade
                strpos($line, 'vendor/') !== false || // Vendor-Dateien
                strpos($line, '.php') !== false || // PHP-Dateien
                strpos($line, '.js') !== false || // JS-Dateien
                strpos($line, '.css') !== false // CSS-Dateien
            ) {
                continue;
            }
            
            // Nimm wichtige Informationszeilen
            if (
                strpos($line, '=====') !== false ||
                strpos($line, 'Sichere') !== false ||
                strpos($line, 'Erstelle') !== false ||
                strpos($line, 'Synchronisiere') !== false ||
                strpos($line, 'Übertrage') !== false ||
                strpos($line, 'Stelle') !== false ||
                strpos($line, 'Leere Cache') !== false ||
                strpos($line, 'abgeschlossen') !== false ||
                strpos($line, 'FEHLER') !== false ||
                strpos($line, 'WARNUNG') !== false ||
                strpos($line, 'Gesichert:') !== false ||
                strpos($line, 'Backup') !== false ||
                strpos($line, 'gelöscht') !== false ||
                strpos($line, 'Lösche') !== false ||
                strpos($line, 'Behalte') !== false ||
                strpos($line, 'Info:') !== false
            ) {
                $filteredLines[] = $line;
            }
        }
        
        return implode("\n", $filteredLines);
    }
    
    /**
     * Compile the current element
     */
    protected function compile(): void
    {
        // Diese Methode bleibt leer, da wir generate() verwenden
    }
} 