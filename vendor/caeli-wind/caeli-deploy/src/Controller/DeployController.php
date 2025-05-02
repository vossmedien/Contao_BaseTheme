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

    // Angepasstes Muster, um DEPLOY_XXX_PATH zu erkennen
    protected const ENV_PATH_PATTERN = '/^DEPLOY_([A-Z]+)_PATH$/';
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

        // Ausgewählte Umgebung ermitteln (aus POST oder erste verfügbare)
        $selectedEnvironment = Input::post('environment'); // Erstmal nur POST prüfen

        // Wenn nichts gepostet wurde ODER die gepostete Auswahl ungültig ist
        // UND es überhaupt Umgebungen gibt, dann nimm die erste verfügbare als Standard.
        if ((empty($selectedEnvironment) || !isset($environments[$selectedEnvironment])) && !empty($environments)) {
            $selectedEnvironment = array_key_first($environments); // Nimm die erste Umgebung als Default
        } elseif (empty($environments)) {
             $selectedEnvironment = null; // Keine Umgebungen, keine Auswahl
        }
        // Wenn eine gültige Umgebung gepostet wurde, bleibt $selectedEnvironment so.

        $template->selectedEnvironment = $selectedEnvironment;

        $projectDir = System::getContainer()->getParameter('kernel.project_dir');

        // --- Lade Konfiguration für die ausgewählte Umgebung und Staging ---
        $envConfigForScript = [];
        $backupPath = ''; // Für die Backup-Anzeige im Template
        $currentPath = ''; // Neuer Name für den Quellpfad im Controller
        $targetPath = ''; // Neuer Name für den Zielpfad im Controller

        // Lade globalen Backup-Pfad IMMER
        $globalBackupPath = $this->getEnvConfig('DEPLOY_BACKUP_PATH', '');
        $envConfigForScript['TARGET_BACKUP_PATH'] = $globalBackupPath;
        $backupPath = $globalBackupPath; // Für Template/Controller-Logik

        // Lade Current-System-Variablen IMMER (als Quelle für Skripte)
        $envConfigForScript['SOURCE_PATH'] = $this->getEnvConfig('DEPLOY_CURRENT_PATH', '');
        // Lade DB-Details für Current System aus DATABASE_URL
        $databaseUrl = $this->getEnvConfig('DATABASE_URL', '');
        if (!empty($databaseUrl)) {
            $dbUrlParts = parse_url($databaseUrl);
            if ($dbUrlParts !== false) {
                $envConfigForScript['SOURCE_DB_USER'] = $dbUrlParts['user'] ?? '';
                $envConfigForScript['SOURCE_DB_PASSWORD'] = $dbUrlParts['pass'] ?? '';
                $sourceDbHost = $dbUrlParts['host'] ?? '';
                if (isset($dbUrlParts['port'])) {
                    // Füge Port zum Host hinzu, falls vorhanden und nicht Standard
                    // mysqldump/mysql verwenden oft host:port oder --host=... --port=...
                    // Sicherer ist es, den Host ohne Port zu übergeben und den Port separat, falls nötig
                    // Aktuell erwarten die Skripte nur einen Host-Namen.
                    // Wir übergeben nur den Host hier. Port wird ignoriert.
                     // $sourceDbHost .= ':' . $dbUrlParts['port'];
                }
                $envConfigForScript['SOURCE_DB_HOST'] = $sourceDbHost;
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
             $envConfigForScript['SOURCE_DB_USER'] = '';
             $envConfigForScript['SOURCE_DB_PASSWORD'] = '';
             $envConfigForScript['SOURCE_DB_HOST'] = '';
             $envConfigForScript['SOURCE_DB_NAME'] = '';
        }
        $currentPath = $envConfigForScript['SOURCE_PATH']; // Für Template/Controller-Logik

        if ($selectedEnvironment) {
            // Zielsystem-Variablen laden (z.B. DEPLOY_DEV_PATH)
            $envConfigForScript['TARGET_PATH'] = $this->getEnvConfig('DEPLOY_' . $selectedEnvironment . '_PATH', '');
            // Ziel-DB-Credentials werden nicht mehr benötigt, da sie aus der .env.local des Ziels gelesen werden
            // $envConfigForScript['TARGET_DB_USER'] = $this->getEnvConfig('DEPLOY_' . $selectedEnvironment . '_DB_USER', '');
            // $envConfigForScript['TARGET_DB_PASSWORD'] = $this->getEnvConfig('DEPLOY_' . $selectedEnvironment . '_DB_PASSWORD', '');
            // $envConfigForScript['TARGET_DB_HOST'] = $this->getEnvConfig('DEPLOY_' . $selectedEnvironment . '_DB_HOST', '');
            // $envConfigForScript['TARGET_DB_NAME'] = $this->getEnvConfig('DEPLOY_' . $selectedEnvironment . '_DB_NAME', '');

            // Lade spezifische Exceptions/Excludes, falle zurück auf globale
            $envConfigForScript['TARGET_EXCEPTIONS'] = $this->getEnvConfig('DEPLOY_' . $selectedEnvironment . '_EXCEPTIONS');
            if (empty($envConfigForScript['TARGET_EXCEPTIONS'])) {
                $envConfigForScript['TARGET_EXCEPTIONS'] = $this->getEnvConfig('DEPLOY_EXCEPTIONS', ''); // Fallback auf global
            }
            $envConfigForScript['TARGET_EXCLUDES'] = $this->getEnvConfig('DEPLOY_' . $selectedEnvironment . '_EXCLUDES');
            if (empty($envConfigForScript['TARGET_EXCLUDES'])) {
                $envConfigForScript['TARGET_EXCLUDES'] = $this->getEnvConfig('DEPLOY_EXCLUDES', ''); // Fallback auf global
            }

            // Pfade für Controller/Template-Logik holen
            $targetPath = $envConfigForScript['TARGET_PATH'];

        } else {
            // Fallback oder Fehlermeldung, falls keine Umgebung ausgewählt werden konnte
            // Wenn keine Umgebung gewählt ist, kann nicht deployed/rollbacked werden.
            // Cleanup könnte noch gehen, wenn BackupPath existiert.
            if (empty($environments)) {
                 Message::addError('Keine Ziel-Umgebungen konfiguriert (z.B. DEPLOY_LIVE_PATH).');
            } else {
                 Message::addError('Keine Ziel-Umgebung ausgewählt.');
            }
        }
        // --------------------------------------------------------

        // Prüfen, ob notwendige Pfade für Aktionen gesetzt sind
        $missingPaths = [];
        if (empty($envConfigForScript['SOURCE_PATH'])) $missingPaths[] = 'DEPLOY_CURRENT_PATH';
        if (empty($envConfigForScript['TARGET_BACKUP_PATH'])) $missingPaths[] = 'DEPLOY_BACKUP_PATH';
        // TARGET_PATH wird nur benötigt, wenn eine Umgebung ausgewählt ist (für Deploy/Rollback)
        if ($selectedEnvironment && empty($envConfigForScript['TARGET_PATH'])) $missingPaths[] = 'DEPLOY_' . $selectedEnvironment . '_PATH';

        if (!empty($missingPaths)) {
            Message::addError('Fehlende Pfad-Konfiguration in .env.local: ' . implode(', ', $missingPaths));
            $template->isError = true;
        }

        $backups = [];

        // Lade zentrale Backup-Info-Datei (aus dem globalen Backup Path)
        $backupInfos = [];
        if (!empty($backupPath)) {
            $backupInfos = $this->loadBackupInfos($backupPath);
        }

        // Im globalen Backup-Verzeichnis nach weiteren Backups suchen
        if (!empty($backupPath) && is_dir($backupPath)) {
            // Angepasstes glob-Muster, um alle Umgebungen zu finden
            $fileBackups = glob($backupPath . '/*_*_files.tar.gz');

            // Debug-Log hinzufügen (temporär, kann später entfernt werden)
            if (empty($fileBackups)) {
                 // Hier loggen, wenn glob nichts findet
                 // Dies erfordert evtl. das Einrichten eines Loggers,
                 // einfacher ist es, eine Warnung über Contao\Message auszugeben.
                 Message::addInfo('Debug: glob() fand keine Dateien mit Muster /*_*_files.tar.gz in ' . $backupPath);
            }
            // Ende Debug-Log

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

            if ($action === 'ausrollen' || $action === 'rollback') {
                // Deploy und Rollback brauchen eine ausgewählte Zielumgebung und deren Pfad
                if (!$selectedEnvironment || empty($envConfigForScript['TARGET_PATH'])) {
                     Message::addError('Für Deployment oder Rollback muss eine gültige Ziel-Umgebung ausgewählt sein.');
                     return $template->parse(); // Aktion abbrechen
                 }
            }

            if ($action === 'ausrollen') {
                try {
                    // -- Timestamp und Zielnamen für Skript und Info-Datei generieren --
                    $timestamp = date('Y-m-d_H-i-s');
                    $envConfigForScript['DEPLOY_TIMESTAMP'] = $timestamp; // Timestamp übergeben
                    $envConfigForScript['TARGET_ENV_NAME'] = $selectedEnvironment; // Zielname übergeben
                    // ------------------------------------------------------------------

                    // Prüfung auf SOURCE_PATH, TARGET_PATH, TARGET_BACKUP_PATH (und implizit SOURCE_DB_NAME)
                    if (empty($envConfigForScript['SOURCE_PATH']) || empty($envConfigForScript['TARGET_PATH']) || empty($envConfigForScript['TARGET_BACKUP_PATH']) || empty($envConfigForScript['SOURCE_DB_NAME'])) {
                        throw new \Exception("Fehlende Konfiguration für Deployment. Prüfe: DEPLOY_CURRENT_PATH, DEPLOY_BACKUP_PATH, DEPLOY_" . $selectedEnvironment . "_PATH, DATABASE_URL.");
                    }

                    // Deployment-Script ausführen (bekommt Timestamp und Zielnamen)
                    $process = new Process([
                        'bash',
                        $projectDir . '/xdeploystagingtolive.sh'
                    ],
                    null, // working directory
                    $envConfigForScript // Übergibt TARGET_*, SOURCE_*, DEPLOY_TIMESTAMP, TARGET_ENV_NAME
                    );

                    $process->setWorkingDirectory($projectDir);
                    $process->setTimeout(600);
                    $process->mustRun();

                    // Wenn Backup-Info vorhanden, in zentrale Datei speichern (im globalen TARGET_BACKUP_PATH)
                    if (!empty($backupInfo)) {
                        // Korrekten Dateinamen mit übergebenem Timestamp und Zielnamen bilden
                        $backupFilename = $timestamp . '_' . $selectedEnvironment . '_files.tar.gz';
                        $this->saveBackupInfo($envConfigForScript['TARGET_BACKUP_PATH'], $backupFilename, $backupInfo);
                    }

                    // Log-Datei auslesen (aus SOURCE_PATH)
                    $logFile = $envConfigForScript['SOURCE_PATH'] . '/deploy_log.txt';

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
                     // Finde die Pfade des ausgewählten Backups
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

                     // Füge die spezifischen Backup-Pfade zur Konfiguration hinzu
                     $rollbackEnvConfig = $envConfigForScript;
                     $rollbackEnvConfig['ROLLBACK_FILE_SOURCE'] = $selectedBackupData['path_files'];
                     $rollbackEnvConfig['ROLLBACK_DB_SOURCE'] = $selectedBackupData['path_db'];

                     // Prüfung: Sind alle nötigen Config-Werte vorhanden?
                     if (empty($rollbackEnvConfig['TARGET_PATH']) || empty($rollbackEnvConfig['SOURCE_PATH']) || empty($rollbackEnvConfig['TARGET_BACKUP_PATH']) || empty($rollbackEnvConfig['SOURCE_DB_NAME']) || empty($rollbackEnvConfig['TARGET_DB_NAME'])) {
                         throw new \Exception("Fehlende Konfiguration für Rollback. Prüfe: DEPLOY_CURRENT_PATH, DEPLOY_BACKUP_PATH, DEPLOY_" . $selectedEnvironment . "_PATH, DATABASE_URL, DEPLOY_" . $selectedEnvironment . "_DB_NAME.");
                     }

                     $process = new Process([
                         'bash',
                         $projectDir . '/xrollbacklive.sh'
                     ],
                     null,
                     $rollbackEnvConfig // Übergibt TARGET_*, SOURCE_*, ROLLBACK_*
                     );

                     $process->setWorkingDirectory($projectDir);
                     $process->setTimeout(600);
                     $process->mustRun();

                     // Log-Datei auslesen (aus SOURCE_PATH)
                     $logFile = $envConfigForScript['SOURCE_PATH'] . '/rollback_log.txt';
                    // ... (Rest der Erfolgsbehandlung bleibt gleich)

                 } catch (ProcessFailedException $exception) {
                    // ... (Fehlerbehandlung bleibt gleich)
                 } catch (\Exception $exception) {
                    // ... (Fehlerbehandlung bleibt gleich)
                 }
            } elseif ($action === 'cleanup') {
                 try {
                     // Prüfung: Sind alle nötigen Config-Werte vorhanden?
                     if (empty($envConfigForScript['TARGET_BACKUP_PATH']) || empty($envConfigForScript['SOURCE_PATH'])) {
                         throw new \Exception("Fehlende Pfad-Konfiguration für Cleanup (DEPLOY_BACKUP_PATH, DEPLOY_CURRENT_PATH)."); // DB nicht benötigt
                     }

                     // Cleanup-Script ausführen (bekommt globalen Backup Path als TARGET_BACKUP_PATH)
                     $process = new Process([
                         'bash',
                         $projectDir . '/xcleanupbackups.sh'
                     ],
                     null,
                     $envConfigForScript // Übergibt TARGET_*, SOURCE_*
                     );
                     $process->setWorkingDirectory($projectDir);
                     $process->setTimeout(300);
                     $process->mustRun();

                     // Log-Datei auslesen (aus SOURCE_PATH)
                     $logFile = $envConfigForScript['SOURCE_PATH'] . '/cleanup_log.txt';
                    // ... (Rest der Erfolgsbehandlung bleibt gleich)

                 } catch (ProcessFailedException $exception) {
                   // ... (Fehlerbehandlung bleibt gleich)
                 } catch (\Exception $exception) {
                    // ... (Fehlerbehandlung bleibt gleich)
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
     * Ermittelt die verfügbaren Umgebungen basierend auf Umgebungsvariablen.
     *
     * Sucht nach Variablen im Format DEPLOY_XXX_PATH (z.B. DEPLOY_LIVE_PATH, DEPLOY_DEV_PATH).
     *
     * @return array Ein assoziatives Array [Key => Name], z.B. ['LIVE' => 'LIVE', 'DEV' => 'DEV'].
     */
    protected function getAvailableEnvironments(): array
    {
        $environments = [];
        // $_ENV ist oft zuverlässiger für .env-Variablen in Symfony/Contao
        $envVars = $_ENV;

        foreach ($envVars as $key => $value) {
            // Prüfen, ob der Schlüssel dem neuen Muster entspricht UND ein Wert gesetzt ist
            if (!empty($value) && preg_match(self::ENV_PATH_PATTERN, $key, $matches)) {
                $envKey = strtoupper($matches[1]); // z.B. LIVE, DEV, STAGING
                // Füge die Umgebung hinzu, wenn sie noch nicht existiert
                if (!isset($environments[$envKey])) {
                    // Verwende den erkannten Schlüssel (z.B. LIVE) direkt als Anzeigenamen.
                    // Könnte später durch eine Konfiguration schöner gemacht werden (z.B. LIVE => "Live-System").
                    $environments[$envKey] = $envKey;
                }
            }
        }

        // Sortiere die Umgebungen alphabetisch nach dem Schlüssel für Konsistenz
        //ksort($environments);

        // Entferne explizit BACKUP und CURRENT, da sie keine Ziele für Deployment sind
        unset($environments['BACKUP']);
        unset($environments['CURRENT']); // STAGING durch CURRENT ersetzt

        return $environments;
    }

    /**
     * Liest einen Konfigurationswert aus den Umgebungsvariablen.
     *
     * @param string $key Der Schlüssel der Umgebungsvariable (z.B. 'DEPLOY_LIVE_PATH').
     * @param mixed|null $default Der Standardwert, falls die Variable nicht gesetzt ist.
     * @return string|null Der Wert der Umgebungsvariable oder der Standardwert.
     */
    protected function getEnvConfig(string $key, $default = ''): ?string
    {
        $envValue = $_ENV[$key] ?? getenv($key);
        return $envValue !== false && $envValue !== null ? (string)$envValue : $default;
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
