<?php

declare(strict_types=1);

/*
 * This file is part of VSM Deploy.
 *
 * (c) VSM 2025
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 */

namespace VSM\VsmDeploy\Controller;

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
    protected $strTemplate = 'be_vsm_deploy';

    // Muster für Deployment-Umgebungsvariablen
    protected const ENV_PATH_PATTERN = '/^DEPLOY_([A-Z]+)_PATH$/';

    /**
     * Generate the module
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

        // Ausgewählte Umgebung ermitteln
        $selectedEnvironment = Input::post('environment');

        if ((empty($selectedEnvironment) || !isset($environments[$selectedEnvironment])) && !empty($environments)) {
            $selectedEnvironment = array_key_first($environments);
        } elseif (empty($environments)) {
            $selectedEnvironment = null;
        }

        $template->selectedEnvironment = $selectedEnvironment;

        $projectDir = System::getContainer()->getParameter('kernel.project_dir');

        // Konfiguration für Deployment-Skripte laden
        $envConfigForScript = [];
        $backupPath = '';

        // Globale Backup-Pfad laden
        $globalBackupPath = $this->getEnvConfig('DEPLOY_BACKUP_PATH', '');
        $envConfigForScript['TARGET_BACKUP_PATH'] = $globalBackupPath;
        $backupPath = $globalBackupPath;

        // Current-System-Variablen laden
        $envConfigForScript['SOURCE_PATH'] = $this->getEnvConfig('DEPLOY_CURRENT_PATH', '');
        
        // Datenbankverbindung aus DATABASE_URL parsen
        $databaseUrl = $this->getEnvConfig('DATABASE_URL', '');
        if (!empty($databaseUrl)) {
            $dbUrlParts = parse_url($databaseUrl);
            if ($dbUrlParts !== false) {
                $envConfigForScript['SOURCE_DB_USER'] = $dbUrlParts['user'] ?? '';
                $envConfigForScript['SOURCE_DB_PASSWORD'] = $dbUrlParts['pass'] ?? '';
                $envConfigForScript['SOURCE_DB_HOST'] = $dbUrlParts['host'] ?? '';
                $envConfigForScript['SOURCE_DB_NAME'] = isset($dbUrlParts['path']) ? ltrim($dbUrlParts['path'], '/') : '';
            } else {
                Message::addWarning('DATABASE_URL konnte nicht geparsed werden.');
                $envConfigForScript['SOURCE_DB_USER'] = '';
                $envConfigForScript['SOURCE_DB_PASSWORD'] = '';
                $envConfigForScript['SOURCE_DB_HOST'] = '';
                $envConfigForScript['SOURCE_DB_NAME'] = '';
            }
        } else {
            Message::addWarning('DATABASE_URL ist nicht in .env.local gesetzt.');
        }

        // Zielsystem-Variablen laden
        if ($selectedEnvironment) {
            $envConfigForScript['TARGET_PATH'] = $this->getEnvConfig('DEPLOY_' . $selectedEnvironment . '_PATH', '');
            
            // Spezifische Exceptions/Excludes laden
            $envConfigForScript['TARGET_EXCEPTIONS'] = $this->getEnvConfig('DEPLOY_' . $selectedEnvironment . '_EXCEPTIONS');
            if (empty($envConfigForScript['TARGET_EXCEPTIONS'])) {
                $envConfigForScript['TARGET_EXCEPTIONS'] = $this->getEnvConfig('DEPLOY_EXCEPTIONS', '');
            }
            
            $envConfigForScript['TARGET_EXCLUDES'] = $this->getEnvConfig('DEPLOY_' . $selectedEnvironment . '_EXCLUDES');
            if (empty($envConfigForScript['TARGET_EXCLUDES'])) {
                $envConfigForScript['TARGET_EXCLUDES'] = $this->getEnvConfig('DEPLOY_EXCLUDES', '');
            }
            
            $envConfigForScript['TARGET_IGNORE_TABLES'] = $this->getEnvConfig('DEPLOY_' . $selectedEnvironment . '_IGNORE_TABLES');
            if (empty($envConfigForScript['TARGET_IGNORE_TABLES'])) {
                $envConfigForScript['TARGET_IGNORE_TABLES'] = $this->getEnvConfig('DEPLOY_IGNORE_TABLES', '');
            }
        } else {
            if (empty($environments)) {
                Message::addError('Keine Ziel-Umgebungen konfiguriert (z.B. DEPLOY_LIVE_PATH).');
            } else {
                Message::addError('Keine Ziel-Umgebung ausgewählt.');
            }
        }

        // Prüfen, ob notwendige Pfade gesetzt sind
        $missingPaths = [];
        if (empty($envConfigForScript['SOURCE_PATH'])) $missingPaths[] = 'DEPLOY_CURRENT_PATH';
        if (empty($envConfigForScript['TARGET_BACKUP_PATH'])) $missingPaths[] = 'DEPLOY_BACKUP_PATH';
        if ($selectedEnvironment && empty($envConfigForScript['TARGET_PATH'])) $missingPaths[] = 'DEPLOY_' . $selectedEnvironment . '_PATH';

        if (!empty($missingPaths)) {
            Message::addError('Fehlende Pfad-Konfiguration in .env.local: ' . implode(', ', $missingPaths));
            $template->isError = true;
        }

        // Backups laden
        $backups = [];
        if (!empty($backupPath)) {
            $backupInfos = $this->loadBackupInfos($backupPath);
            $backups = $this->loadBackups($backupPath, $backupInfos);
        }

        $template->backups = $backups;
        $template->backupCount = count($backups);

        // Request-Stack für Session-Handling
        $requestStack = System::getContainer()->get('request_stack');
        $request = $requestStack->getCurrentRequest();
        $isXhr = $request && $request->isXmlHttpRequest();
        $session = $requestStack->getSession();

        // Formular-Handling
        if (Input::post('FORM_SUBMIT') === 'vsm_deploy_form' || Input::post('TL_SUBMIT') !== null) {
            $action = Input::post('ausrollen') ?: 'ausrollen';
            $selectedBackup = Input::post('selected_backup') ?: 'current';
            $backupInfo = Input::post('backup_info') ?: '';

            $redirectUrl = System::getContainer()->get('router')->generate('contao_backend', [
                'do' => 'vsm_deploy'
            ]);

            if ($action === 'ausrollen') {
                $this->handleDeploy($envConfigForScript, $selectedEnvironment, $backupInfo, $projectDir, $session, $template, $isXhr, $redirectUrl);
            } elseif ($action === 'rollback') {
                $this->handleRollback($envConfigForScript, $selectedEnvironment, $selectedBackup, $backups, $projectDir, $session, $template, $isXhr, $redirectUrl);
            } elseif ($action === 'cleanup') {
                $this->handleCleanup($envConfigForScript, $projectDir, $session, $template, $isXhr, $redirectUrl);
            } else {
                Message::addError('Unbekannte Aktion: ' . $action);
            }

            if ($isXhr) {
                return $template->parse();
            } else {
                return new RedirectResponse($redirectUrl);
            }
        }

        // Session-Daten ins Template übernehmen
        if ($session->has('deploy_log')) {
            $template->ausgerollt = $session->get('deploy_log');
            $template->success = $session->get('deploy_success', false);
            $template->isError = !$template->success;

            $session->remove('deploy_log');
            $session->remove('deploy_success');
        }

        return $template->parse();
    }

    /**
     * Behandelt Deployment-Anfragen
     */
    protected function handleDeploy(array $envConfigForScript, ?string $selectedEnvironment, string $backupInfo, string $projectDir, $session, $template, bool $isXhr, string $redirectUrl): void
    {
        if (!$selectedEnvironment || empty($envConfigForScript['TARGET_PATH'])) {
            Message::addError('Für Deployment muss eine gültige Ziel-Umgebung ausgewählt sein.');
            return;
        }

        try {
            $timestamp = date('Y-m-d_H-i-s');
            $envConfigForScript['DEPLOY_TIMESTAMP'] = $timestamp;
            $envConfigForScript['TARGET_ENV_NAME'] = $selectedEnvironment;

            if (empty($envConfigForScript['SOURCE_PATH']) || empty($envConfigForScript['TARGET_PATH']) || empty($envConfigForScript['TARGET_BACKUP_PATH']) || empty($envConfigForScript['SOURCE_DB_NAME'])) {
                throw new \Exception("Fehlende Konfiguration für Deployment. Prüfe: DEPLOY_CURRENT_PATH, DEPLOY_BACKUP_PATH, DEPLOY_" . $selectedEnvironment . "_PATH, DATABASE_URL.");
            }

            $process = new Process([
                'bash',
                $projectDir . '/xdeploystagingtolive.sh'
            ], null, $envConfigForScript);

            $process->setWorkingDirectory($projectDir);
            $process->setTimeout(600);
            $process->mustRun();

            // Backup-Info speichern
            if (!empty($backupInfo)) {
                $backupFilename = $timestamp . '_' . $selectedEnvironment . '_files.tar.gz';
                $this->saveBackupInfo($envConfigForScript['TARGET_BACKUP_PATH'], $backupFilename, $backupInfo);
            }

            // Log-Datei auslesen
            $logFile = $envConfigForScript['SOURCE_PATH'] . '/deploy_log.txt';
            if (!file_exists($logFile)) {
                $logFile = $projectDir . '/deploy_log.txt';
            }

            $logContent = file_exists($logFile) ? file_get_contents($logFile) : "Erfolgreich ausgerollt, aber keine Log-Datei gefunden.";
            $filteredLog = $this->filterLogContent($logContent);

            $session->set('deploy_log', $filteredLog);
            $session->set('deploy_success', true);
            Message::addConfirmation('Deployment erfolgreich durchgeführt.');

            if ($isXhr) {
                $template->ausgerollt = $filteredLog;
                $template->success = true;
            }

        } catch (ProcessFailedException $exception) {
            $this->handleProcessException($session, $template, $exception, 'Deployment', $isXhr, $redirectUrl);
        } catch (\Exception $exception) {
            $this->handleGenericException($session, $template, $exception, 'Deployment', $isXhr, $redirectUrl);
        }
    }

    /**
     * Behandelt Rollback-Anfragen
     */
    protected function handleRollback(array $envConfigForScript, ?string $selectedEnvironment, string $selectedBackup, array $backups, string $projectDir, $session, $template, bool $isXhr, string $redirectUrl): void
    {
        if (!$selectedEnvironment || empty($envConfigForScript['TARGET_PATH'])) {
            Message::addError('Für Rollback muss eine gültige Ziel-Umgebung ausgewählt sein.');
            return;
        }

        try {
            $selectedBackupData = null;
            foreach ($backups as $backup) {
                if ($backup['id'] === $selectedBackup) {
                    $selectedBackupData = $backup;
                    break;
                }
            }

            if (!$selectedBackupData || empty($selectedBackupData['path_files']) || empty($selectedBackupData['path_db'])) {
                throw new \Exception("Ausgewähltes Backup ungültig oder Pfade nicht gefunden.");
            }

            $rollbackEnvConfig = $envConfigForScript;
            $rollbackEnvConfig['ROLLBACK_FILE_SOURCE'] = $selectedBackupData['path_files'];
            $rollbackEnvConfig['ROLLBACK_DB_SOURCE'] = $selectedBackupData['path_db'];

            $process = new Process([
                'bash',
                $projectDir . '/xrollbacklive.sh'
            ], null, $rollbackEnvConfig);

            $process->setWorkingDirectory($projectDir);
            $process->setTimeout(600);
            $process->mustRun();

            $logFile = $envConfigForScript['SOURCE_PATH'] . '/rollback_log.txt';
            if (!file_exists($logFile)) {
                $logFile = $projectDir . '/rollback_log.txt';
            }

            $logContent = file_exists($logFile) ? file_get_contents($logFile) : "Rollback erfolgreich, aber keine Log-Datei gefunden.";
            $filteredLog = $this->filterLogContent($logContent);

            $session->set('deploy_log', $filteredLog);
            $session->set('deploy_success', true);
            Message::addConfirmation('Rollback erfolgreich durchgeführt.');

            if ($isXhr) {
                $template->ausgerollt = $filteredLog;
                $template->success = true;
            }

        } catch (ProcessFailedException $exception) {
            $this->handleProcessException($session, $template, $exception, 'Rollback', $isXhr, $redirectUrl);
        } catch (\Exception $exception) {
            $this->handleGenericException($session, $template, $exception, 'Rollback', $isXhr, $redirectUrl);
        }
    }

    /**
     * Behandelt Cleanup-Anfragen
     */
    protected function handleCleanup(array $envConfigForScript, string $projectDir, $session, $template, bool $isXhr, string $redirectUrl): void
    {
        try {
            if (empty($envConfigForScript['TARGET_BACKUP_PATH']) || empty($envConfigForScript['SOURCE_PATH'])) {
                throw new \Exception("Fehlende Pfad-Konfiguration für Cleanup (DEPLOY_BACKUP_PATH, DEPLOY_CURRENT_PATH).");
            }

            $process = new Process([
                'bash',
                $projectDir . '/xcleanupbackups.sh'
            ], null, $envConfigForScript);

            $process->setWorkingDirectory($projectDir);
            $process->setTimeout(300);
            $process->mustRun();

            $logFile = $envConfigForScript['SOURCE_PATH'] . '/cleanup_log.txt';
            if (!file_exists($logFile)) {
                $logFile = $projectDir . '/cleanup_log.txt';
            }

            $logContent = file_exists($logFile) ? file_get_contents($logFile) : "Cleanup erfolgreich, aber keine Log-Datei gefunden.";
            $filteredLog = $this->filterLogContent($logContent);

            $session->set('deploy_log', $filteredLog);
            $session->set('deploy_success', true);
            Message::addConfirmation('Backup-Bereinigung erfolgreich durchgeführt.');

            if ($isXhr) {
                $template->ausgerollt = $filteredLog;
                $template->success = true;
            }

        } catch (ProcessFailedException $exception) {
            $this->handleProcessException($session, $template, $exception, 'Cleanup', $isXhr, $redirectUrl);
        } catch (\Exception $exception) {
            $this->handleGenericException($session, $template, $exception, 'Cleanup', $isXhr, $redirectUrl);
        }
    }

    /**
     * Ermittelt verfügbare Umgebungen
     */
    protected function getAvailableEnvironments(): array
    {
        $environments = [];
        $envVars = $_ENV;

        foreach ($envVars as $key => $value) {
            if (!empty($value) && preg_match(self::ENV_PATH_PATTERN, $key, $matches)) {
                $envKey = strtoupper($matches[1]);
                if (!isset($environments[$envKey])) {
                    $environments[$envKey] = $envKey;
                }
            }
        }

        // Entferne BACKUP und CURRENT
        unset($environments['BACKUP'], $environments['CURRENT']);

        return $environments;
    }

    /**
     * Liest Umgebungsvariablen
     */
    protected function getEnvConfig(string $key, $default = ''): ?string
    {
        $envValue = $_ENV[$key] ?? getenv($key);
        return $envValue !== false && $envValue !== null ? (string)$envValue : $default;
    }

    /**
     * Lädt Backup-Informationen
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
     * Lädt verfügbare Backups
     */
    protected function loadBackups(string $backupPath, array $backupInfos): array
    {
        $backups = [];

        if (is_dir($backupPath)) {
            $fileBackups = glob($backupPath . '/*_*_files.tar.gz');

            if (!empty($fileBackups)) {
                foreach ($fileBackups as $file) {
                    $basename = basename($file);
                    $basePart = str_replace('_files.tar.gz', '', $basename);
                    $dbFile = $backupPath . '/' . $basePart . '_db.sql';

                    $backupInfo = $backupInfos[$basename] ?? '';

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

        // Sortiere nach Zeit absteigend
        if (!empty($backups)) {
            usort($backups, function($a, $b) {
                return $b['time'] - $a['time'];
            });
        }

        return $backups;
    }

    /**
     * Speichert Backup-Informationen
     */
    protected function saveBackupInfo(string $backupPath, string $filename, string $info): void
    {
        if (empty($info)) {
            return;
        }

        $infoFile = $backupPath . '/backup_infos.txt';
        $infos = $this->loadBackupInfos($backupPath);

        $infos[$filename] = $info;

        $content = '';
        foreach ($infos as $key => $value) {
            $content .= $key . '=' . $value . PHP_EOL;
        }

        file_put_contents($infoFile, $content);
    }

    /**
     * Filtert Log-Inhalt für bessere Darstellung
     */
    protected function filterLogContent(string $logContent): string
    {
        $lines = explode("\n", $logContent);
        $filteredLines = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            // Ignoriere unwichtige Zeilen
            if (
                strpos($line, 'sending incremental file list') !== false ||
                strpos($line, 'sent ') !== false ||
                strpos($line, 'total size is') !== false ||
                strpos($line, './') === 0 ||
                strpos($line, 'vendor/') !== false ||
                strpos($line, '.php') !== false ||
                strpos($line, '.js') !== false ||
                strpos($line, '.css') !== false
            ) {
                continue;
            }

            // Wichtige Informationen beibehalten
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
                strpos($line, 'Backup') !== false ||
                strpos($line, 'Info:') !== false
            ) {
                $filteredLines[] = $line;
            }
        }

        return implode("\n", $filteredLines);
    }

    /**
     * Behandelt ProcessFailedException
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
     * Behandelt allgemeine Exceptions
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
     * Compile the current element
     */
    protected function compile(): void
    {
        // Leer, da generate() verwendet wird
    }
}