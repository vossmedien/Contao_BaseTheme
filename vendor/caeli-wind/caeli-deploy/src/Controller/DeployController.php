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
    
    protected const ENV_PREFIX_PATTERN = '/^DEPLOY_(LIVE|STAGING|BACKUP)_PATH_([A-Z0-9_]+)$/';
    protected const DEFAULT_ENVIRONMENT_KEY = 'default'; // Key für die Standardumgebung

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
        
        // Verfügbare Umgebungen ermitteln
        $environments = $this->getAvailableEnvironments();
        $template->environments = $environments;

        // Ausgewählte Umgebung ermitteln (aus POST oder Standard)
        $selectedEnvironment = Input::post('environment') ?: self::DEFAULT_ENVIRONMENT_KEY;
        if (!isset($environments[$selectedEnvironment])) {
            // Fallback auf Default, wenn ungültige Umgebung übergeben wurde
            $selectedEnvironment = self::DEFAULT_ENVIRONMENT_KEY;
        }
        $template->selectedEnvironment = $selectedEnvironment;

        $projectDir = System::getContainer()->getParameter('kernel.project_dir');
        
        // Lade Pfade aus der Umgebungskonfiguration basierend auf der Auswahl
        $envSuffix = $selectedEnvironment === self::DEFAULT_ENVIRONMENT_KEY ? '' : '_' . $selectedEnvironment;
        $backupPath = $this->getEnvConfig('DEPLOY_BACKUP_PATH' . $envSuffix, '/usr/home/caelif/public_html/relaunch_live_autodeploy_versions');
        $stagingPath = $this->getEnvConfig('DEPLOY_STAGING_PATH' . $envSuffix, '/usr/home/caelif/public_html/staging.caeli');
        $livePath = $this->getEnvConfig('DEPLOY_LIVE_PATH' . $envSuffix, '/usr/home/caelif/public_html/relaunch.caeli');
        
        $backups = [];
        
        // Lade zentrale Backup-Info-Datei
        $backupInfos = $this->loadBackupInfos($backupPath);
        
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
                    
                    // Info aus der zentralen Datei holen
                    $backupInfo = isset($backupInfos[$basename]) ? $backupInfos[$basename] : '';
                    
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
                    // Deployment-Script ausführen - im Projekt-Root-Verzeichnis
                    $process = new Process([
                        'bash', 
                        $projectDir . '/xdeploystagingtolive.sh'
                    ],
                    null, // working directory (wird unten gesetzt)
                    [
                        // Umgebungsvariablen explizit für den Prozess setzen
                        'DEPLOY_LIVE_PATH' => $livePath,
                        'DEPLOY_STAGING_PATH' => $stagingPath,
                        'DEPLOY_BACKUP_PATH' => $backupPath,
                        // Bestehende Umgebungsvariablen erben, damit andere Variablen (z.B. PATH) verfügbar sind
                        // Wichtig: $_ENV und getenv() zusammenführen, um sicherzustellen, dass alle Variablen übergeben werden
                    ] + $_ENV + $_SERVER // Fügt $_ENV und $_SERVER hinzu, getenv() ist normalerweise darin enthalten
                    );
                    
                    $process->setWorkingDirectory($projectDir);
                    $process->setTimeout(600); // Erhöhe Timeout auf 10 Minuten
                    $process->mustRun();
                    
                    // Wenn Backup-Info vorhanden, in zentrale Datei speichern
                    if (!empty($backupInfo)) {
                        file_put_contents($stagingPath . '/backup_info.txt', $backupInfo);
                        
                        // Timestamp für das neue Backup ermitteln
                        $timestamp = date('Y-m-d_H-i-s');
                        $backupFilename = $timestamp . '_live_files.tar.gz';
                        
                        // In zentrale Info-Datei speichern
                        $this->saveBackupInfo($backupPath, $backupFilename, $backupInfo);
                    }
                    
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
                    // Rollback-Script ausführen - mit ausgewähltem Backup und Pfaden
                    $selectedBackupData = null;
                    foreach ($backups as $backup) {
                        if ($backup['id'] === $selectedBackup) {
                            $selectedBackupData = $backup;
                            break;
                        }
                    }

                    if (!$selectedBackupData) {
                         throw new \Exception("Ausgewähltes Backup nicht gefunden.");
                    }

                    $process = new Process([
                        'bash',
                        $projectDir . '/rollbacktolastversion.sh',
                        $selectedBackupData['path_files'] ?? '',
                        $selectedBackupData['path_db'] ?? '',
                        $livePath // Live-Pfad für die Zielumgebung
                    ]);

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
                    // Cleanup-Script ausführen
                    $process = new Process([
                        'bash',
                        $projectDir . '/backupcleanup.sh',
                        $backupPath // Pfad zum Backup-Verzeichnis der Umgebung
                    ]);
                    $process->setWorkingDirectory($projectDir);
                    $process->setTimeout(300); // Timeout 5 Minuten
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
                    $this->handleProcessException($session, $template, $exception, 'Cleanup', $isXhr, $redirectUrl);
                    return $isXhr ? $template->parse() : new RedirectResponse($redirectUrl);
                } catch (\Exception $exception) {
                    $this->handleGenericException($session, $template, $exception, 'Cleanup', $isXhr, $redirectUrl);
                    return $isXhr ? $template->parse() : new RedirectResponse($redirectUrl);
                }
            } else {
                Message::addError('Unbekannte Aktion: ' . $action);
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
     * Liest einen Konfigurationswert aus den Umgebungsvariablen.
     *
     * @param string $key     Der Schlüssel der Umgebungsvariable.
     * @param string $default Der Standardwert, falls die Variable nicht gesetzt ist.
     *
     * @return string Der Wert der Umgebungsvariable oder der Standardwert.
     */
    protected function getEnvConfig(string $key, string $default = ''): string
    {
        // Versuche, den Wert aus $_ENV zu lesen (bevorzugt, da von Dotenv geladen)
        if (isset($_ENV[$key])) {
            return (string) $_ENV[$key];
        }
    
        // Fallback auf getenv(), falls nicht in $_ENV
        $value = getenv($key);
    
        return $value !== false ? (string) $value : $default;
    }
    
    /**
     * Ermittelt die verfügbaren Deployment-Umgebungen aus den Umgebungsvariablen.
     * Sucht nach Variablen wie DEPLOY_LIVE_PATH_XXX, DEPLOY_STAGING_PATH_XXX etc.
     *
     * @return array Ein assoziatives Array der Umgebungen [key => name]. 'default' repräsentiert die Standardumgebung.
     */
    protected function getAvailableEnvironments(): array
    {
        $environments = [self::DEFAULT_ENVIRONMENT_KEY => 'Standard']; // Standardumgebung immer hinzufügen
        $envVars = $_ENV + getenv(); // Kombiniere $_ENV und getenv()

        foreach ($envVars as $key => $value) {
            if (preg_match(self::ENV_PREFIX_PATTERN, $key, $matches)) {
                $envName = $matches[2]; // Der Teil nach dem letzten Unterstrich
                if (!isset($environments[$envName])) {
                     // Verwende den Suffix als Namen (könnte man später anpassbar machen)
                    $environments[$envName] = $envName;
                }
            }
        }
        
        // Prüfen, ob die Standardumgebung tatsächlich konfiguriert ist
        $hasDefaultLive = !empty($this->getEnvConfig('DEPLOY_LIVE_PATH'));
        $hasDefaultStaging = !empty($this->getEnvConfig('DEPLOY_STAGING_PATH'));
        // Wenn keine Standardpfade definiert sind, aber andere Umgebungen existieren,
        // entfernen wir die Standardoption, es sei denn, sie ist die einzige.
        if (!$hasDefaultLive && !$hasDefaultStaging && count($environments) > 1) {
             unset($environments[self::DEFAULT_ENVIRONMENT_KEY]);
        } elseif (count($environments) === 1 && !$hasDefaultLive && !$hasDefaultStaging) {
            // Wenn nur Default da ist, aber nicht konfiguriert, leere Liste zurückgeben
            // oder eine Fehlermeldung anzeigen? Vorerst leere Liste.
             return [];
        }

        return $environments;
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
    
    /**
     * Lädt die zentrale Backup-Info-Datei
     *
     * @param string $backupPath
     * @return array
     */
    protected function loadBackupInfos(string $backupPath): array
    {
        $infoFile = $backupPath . '/backup_infos.txt';
        $infos = [];
        
        if (file_exists($infoFile)) {
            $lines = file($infoFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false) {
                    list($filename, $info) = explode('=', $line, 2);
                    $infos[trim($filename)] = trim($info);
                }
            }
        }
        
        return $infos;
    }
    
    /**
     * Speichert eine Info in die zentrale Backup-Info-Datei
     *
     * @param string $backupPath
     * @param string $filename
     * @param string $info
     */
    protected function saveBackupInfo(string $backupPath, string $filename, string $info): void
    {
        if (empty($info)) {
            return; // Keine Info zu speichern
        }
        
        $infoFile = $backupPath . '/backup_infos.txt';
        $infos = $this->loadBackupInfos($backupPath);
        
        // Info aktualisieren oder hinzufügen
        $infos[$filename] = $info;
        
        // Zurück in die Datei schreiben
        $content = '';
        foreach ($infos as $key => $value) {
            $content .= $key . '=' . $value . PHP_EOL;
        }
        
        file_put_contents($infoFile, $content);
    }
} 