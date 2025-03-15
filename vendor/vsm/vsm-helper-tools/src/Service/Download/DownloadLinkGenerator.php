<?php

namespace Vsm\VsmHelperTools\Service\Download;

use Psr\Log\LoggerInterface;
use Contao\FilesModel;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\System;

class DownloadLinkGenerator
{
    private LoggerInterface $logger;
    private string $projectDir;
    private ?Connection $db;
    private ?ContaoFramework $framework;
    
    public function __construct(
        LoggerInterface $logger, 
        string $projectDir, 
        ?Connection $db = null,
        ?ContaoFramework $framework = null
    ) {
        $this->logger = $logger;
        $this->projectDir = $projectDir;
        $this->db = $db;
        $this->framework = $framework;
    }
    
    /**
     * Generiert einen sicheren Download-Link für eine Datei
     */
    public function generateDownloadLink(string $filePath, int $expiryDays = 7, int $downloadLimit = 3): array
    {
        try {
            if ($this->framework) {
                $this->framework->initialize();
            }
            
            if (empty($filePath)) {
                $this->logger->error('Leerer Dateipfad für Download-Link-Generierung');
                return [
                    'url' => '',
                    'token' => '',
                    'expires' => 0,
                    'limit' => 0,
                    'file_path' => '',
                    'error' => 'Leerer Dateipfad'
                ];
            }
            
            // Detaillierte Logging für Debugging
            $this->logger->debug('Generiere Download-Link', [
                'file_path' => $filePath,
                'expiry_days' => $expiryDays,
                'download_limit' => $downloadLimit
            ]);
            
            // Prüfen, ob es sich um eine UUID handelt
            $file = null;
            $originalPath = $filePath;
            
            // Normalisierung der UUID (entfernt Bindestriche falls vorhanden)
            $uuid = str_replace('-', '', $filePath);
            
            // Prüfen, ob es sich um eine hexadezimale UUID handelt
            if (preg_match('/^[a-f0-9]{8}\-[a-f0-9]{4}\-[a-f0-9]{4}\-[a-f0-9]{4}\-[a-f0-9]{12}$/i', $filePath) || 
                preg_match('/^[a-f0-9]{32}$/i', $uuid)) {
                
                $this->logger->debug('UUID erkannt, suche Datei', ['uuid' => $filePath]);
                
                if ($this->framework) {
                    $this->framework->initialize();
                    
                    // Versuche sowohl mit als auch ohne Bindestriche
                    $file = FilesModel::findByUuid($filePath);
                    
                    if (!$file && $filePath !== $uuid) {
                        $file = FilesModel::findByUuid($uuid);
                        $this->logger->debug('Versuche UUID ohne Bindestriche', ['uuid' => $uuid]);
                    }
                    
                    if ($file) {
                        $this->logger->info('Datei über UUID gefunden, konvertiere zu Pfad', [
                            'uuid' => $filePath,
                            'path' => $file->path
                        ]);
                        $filePath = $file->path;
                    } else {
                        $this->logger->warning('Datei über UUID nicht gefunden', [
                            'uuid' => $filePath
                        ]);
                    }
                }
            } else {
                $this->logger->debug('Kein UUID-Format erkannt, versuche direkten Pfad', ['path' => $filePath]);
                
                if ($this->framework) {
                    $this->framework->initialize();
                    $file = FilesModel::findByPath($filePath);
                    
                    if ($file) {
                        $this->logger->debug('Datei über Pfad gefunden', ['path' => $filePath, 'uuid' => $file->uuid]);
                    }
                }
            }
            
            // Wenn keine Datei gefunden wurde, versuche alle möglichen Varianten
            if ($file === null && $this->framework) {
                $this->logger->warning('Versuche alternative Methoden zur Dateisuche', ['original_path' => $originalPath]);
                
                // Versuche mit Binary-UUID Format (falls hexadezimale UUID übergeben wurde)
                if (strlen($uuid) === 32) {
                    $binaryUuid = hex2bin($uuid);
                    if ($binaryUuid !== false) {
                        $this->logger->debug('Versuche Binary-UUID Format', ['binary_uuid' => bin2hex($binaryUuid)]);
                        $file = FilesModel::findByUuid($binaryUuid);
                        
                        if ($file) {
                            $this->logger->info('Datei über Binary-UUID gefunden', [
                                'uuid' => $uuid,
                                'path' => $file->path
                            ]);
                            $filePath = $file->path;
                        }
                    }
                }
                
                // Fallback: Suche in der Datenbank
                if (!$file && $this->db) {
                    $this->logger->debug('Versuche direkte DB-Abfrage für UUID');
                    
                    // Versuche verschiedene Abfragen
                    $queries = [
                        "SELECT path FROM tl_files WHERE uuid = :uuid",
                        "SELECT path FROM tl_files WHERE LOWER(HEX(uuid)) = LOWER(:hex_uuid)"
                    ];
                    
                    foreach ($queries as $query) {
                        try {
                            if (strpos($query, 'HEX') !== false) {
                                $result = $this->db->executeQuery($query, ['hex_uuid' => $uuid]);
                            } else {
                                $result = $this->db->executeQuery($query, ['uuid' => $filePath]);
                            }
                            
                            $row = $result->fetchAssociative();
                            if ($row && !empty($row['path'])) {
                                $filePath = $row['path'];
                                $this->logger->info('Datei über direkte DB-Abfrage gefunden', [
                                    'query' => $query,
                                    'path' => $filePath
                                ]);
                                
                                // Hole jetzt das FilesModel
                                $file = FilesModel::findByPath($filePath);
                                break;
                            }
                        } catch (\Exception $e) {
                            $this->logger->warning('Fehler bei DB-Abfrage: ' . $e->getMessage());
                        }
                    }
                }
            }
            
            if ($file === null) {
                $this->logger->error('Download-Datei nicht gefunden: ' . $originalPath);
                return [
                    'url' => '',
                    'token' => '',
                    'expires' => 0,
                    'limit' => 0,
                    'file_path' => $originalPath,
                    'error' => 'Datei nicht gefunden'
                ];
            }
            
            // Prüfen, ob der Pfad existiert und eine Datei ist
            $absolutePath = $this->projectDir . '/' . $file->path;
            if (!file_exists($absolutePath)) {
                $this->logger->error('Download-Datei existiert nicht auf dem Dateisystem', [
                    'path' => $file->path,
                    'absolute_path' => $absolutePath
                ]);
                return [
                    'url' => '',
                    'token' => '',
                    'expires' => 0,
                    'limit' => 0, 
                    'file_path' => $file->path,
                    'error' => 'Datei existiert nicht auf dem Dateisystem'
                ];
            }
            
            if (is_dir($absolutePath)) {
                $this->logger->warning('Gefundener Pfad ist ein Verzeichnis, nicht eine Datei', [
                    'path' => $file->path
                ]);
                
                // Versuche, die erste Datei im Verzeichnis zu verwenden
                $files = scandir($absolutePath);
                $foundFile = null;
                
                foreach ($files as $f) {
                    if ($f !== '.' && $f !== '..' && is_file($absolutePath . '/' . $f)) {
                        $foundFile = $f;
                        break;
                    }
                }
                
                if ($foundFile) {
                    $file->path = $file->path . '/' . $foundFile;
                    $this->logger->info('Verwende erste Datei im Verzeichnis', [
                        'directory' => $absolutePath,
                        'file' => $foundFile,
                        'new_path' => $file->path
                    ]);
                } else {
                    return [
                        'url' => '',
                        'token' => '',
                        'expires' => 0,
                        'limit' => 0,
                        'file_path' => $file->path,
                        'error' => 'Pfad ist ein Verzeichnis und enthält keine Dateien'
                    ];
                }
            }
            
            // Einschränkungen berechnen
            $expiresAt = time() + ($expiryDays * 86400); // 86400 Sekunden = 1 Tag
            
            // Eindeutigen Token generieren
            $token = $this->generateSecureToken();
            
            // Base-URL ermitteln
            $baseUrl = '';
            if ($this->framework) {
                try {
                    $container = System::getContainer();
                    if ($container->hasParameter('contao.base_url')) {
                        $baseUrl = $container->getParameter('contao.base_url') ?: '';
                    }
                } catch (\Exception $e) {
                    $this->logger->warning('Container-Parameter contao.base_url nicht verfügbar: ' . $e->getMessage());
                }
            }
            
            if (empty($baseUrl)) {
                // Fallback: URL aus den Server-Variablen ableiten
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'contao5.vossmedien.de';
                $baseUrl = $protocol . '://' . $host;
                
                $this->logger->info('Fallback-URL verwendet: ' . $baseUrl);
            }
            
            $downloadUrl = rtrim($baseUrl, '/') . '/stripe/download/' . $token;
            
            // Download-Token in der Datenbank speichern, wenn verfügbar
            if ($this->db) {
                try {
                    $this->createDownloadToken($token, $file->uuid, $expiresAt, $downloadLimit);
                } catch (\Exception $e) {
                    // Fehlerprotokollierung, aber kein Abbrechen des gesamten Vorgangs
                    $this->logger->error('Fehler beim Erstellen des Download-Tokens: ' . $e->getMessage(), [
                        'token' => $token,
                        'exception' => get_class($e),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    // Fahre trotzdem fort, da der Download über die Session-Daten immer noch funktionieren wird
                    $this->logger->info('Download-Link wird trotz Tokenfehler generiert (Fallback über Session)');
                }
            }
            
            $this->logger->info('Download-Link generiert', [
                'token' => $token,
                'url' => $downloadUrl,
                'expires' => date('Y-m-d H:i:s', $expiresAt),
                'limit' => $downloadLimit
            ]);
            
            return [
                'url' => $downloadUrl,
                'token' => $token,
                'expires' => $expiresAt,
                'limit' => $downloadLimit,
                'file_path' => $file->path,
            ];
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Generieren des Download-Links: ' . $e->getMessage());
            return [
                'url' => '',
                'token' => '',
                'expires' => 0,
                'limit' => 0,
                'file_path' => '',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Überladene Methode zur einfacheren Verwendung aus dem WebhookController
     * 
     * @param string $filePath Pfad zur Datei oder UUID
     * @param int $expiryDays Anzahl der Tage, für die der Link gültig ist
     * @param int $downloadLimit Maximale Anzahl von Downloads
     * @return array Link-Informationen
     */
    public function generateDownloadLinkSimple(string $filePath, int $expiryDays = 7, int $downloadLimit = 3): array
    {
        try {
            if ($this->framework) {
                $this->framework->initialize();
            }
            
            // Prüfen, ob es sich um eine UUID handelt
            $file = null;
            if (preg_match('/^[a-f0-9]{8}\-[a-f0-9]{4}\-[a-f0-9]{4}\-[a-f0-9]{4}\-[a-f0-9]{12}$/i', $filePath)) {
                $file = FilesModel::findByUuid($filePath);
            } else {
                $file = FilesModel::findByPath($filePath);
            }
            
            if ($file === null) {
                throw new \Exception('Download-Datei nicht gefunden: ' . $filePath);
            }
            
            // Einschränkungen berechnen
            $expiresAt = time() + ($expiryDays * 86400); // 86400 Sekunden = 1 Tag
            
            // Eindeutigen Token generieren
            $token = $this->generateSecureToken();
            
            // Base-URL ermitteln
            $baseUrl = '';
            if ($this->framework) {
                try {
                    $container = System::getContainer();
                    if ($container->hasParameter('contao.base_url')) {
                        $baseUrl = $container->getParameter('contao.base_url') ?: '';
                    }
                } catch (\Exception $e) {
                    $this->logger->warning('Container-Parameter contao.base_url nicht verfügbar: ' . $e->getMessage());
                }
            }
            
            if (empty($baseUrl)) {
                // Fallback: URL aus den Server-Variablen ableiten
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'contao5.vossmedien.de';
                $baseUrl = $protocol . '://' . $host;
                
                $this->logger->info('Fallback-URL verwendet: ' . $baseUrl);
            }
            
            $downloadUrl = rtrim($baseUrl, '/') . '/stripe/download/' . $token;
            
            // Download-Token in der Datenbank speichern, wenn verfügbar
            if ($this->db) {
                try {
                    $this->createDownloadToken($token, $file->uuid, $expiresAt, $downloadLimit);
                } catch (\Exception $e) {
                    // Fehlerprotokollierung, aber kein Abbrechen des gesamten Vorgangs
                    $this->logger->error('Fehler beim Erstellen des Download-Tokens in generateDownloadLinkSimple: ' . $e->getMessage(), [
                        'token' => $token,
                        'exception' => get_class($e)
                    ]);
                    
                    // Fahre trotzdem fort, da der Download über die Session-Daten immer noch funktionieren wird
                    $this->logger->info('Download-Link wird trotz Tokenfehler generiert (Fallback über Session)');
                }
            }
            
            return [
                'url' => $downloadUrl,
                'token' => $token,
                'expires' => $expiresAt,
                'limit' => $downloadLimit,
                'file_path' => $file->path,
            ];
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Generieren des Download-Links: ' . $e->getMessage());
            return [
                'url' => '',
                'token' => '',
                'expires' => 0,
                'limit' => 0,
                'file_path' => '',
            ];
        }
    }
    
    /**
     * Erstellt die Download-Token-Tabelle, falls sie nicht existiert
     */
    private function ensureTokenTableExists(): bool
    {
        if (!$this->db) {
            return false;
        }
        
        try {
            // Prüfen, ob die Tabelle existiert
            $tableExists = false;
            try {
                $this->db->executeQuery('SHOW TABLES LIKE "tl_download_tokens"');
                $tableExists = true;
            } catch (\Exception $e) {
                $this->logger->warning('Tabelle tl_download_tokens existiert nicht: ' . $e->getMessage());
            }
            
            // Wenn nicht, dann erstellen wir sie
            if (!$tableExists) {
                $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `tl_download_tokens` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `tstamp` int(10) unsigned NOT NULL default '0',
  `token` varchar(255) NOT NULL default '',
  `file_id` binary(16) NULL,
  `file_uuid` varchar(255) NOT NULL default '',
  `file_path` varchar(255) NOT NULL default '',
  `expires` int(10) unsigned NOT NULL default '0',
  `expires_at` int(10) unsigned NOT NULL default '0',
  `download_limit` int(10) unsigned NOT NULL default '3',
  `download_count` int(10) unsigned NOT NULL default '0',
  `order_id` varchar(255) NOT NULL default '',
  `customer_email` varchar(255) NOT NULL default '',
  `created_at` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
                $this->db->executeStatement($sql);
                $this->logger->info('Tabelle tl_download_tokens wurde erstellt');
                return true;
            }
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Erstellen der Tabelle: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Speichert einen Download-Token in der Datenbank
     */
    private function createDownloadToken(string $token, string $fileUuid, int $expiresAt, int $downloadLimit): bool
    {
        if (!$this->db) {
            return false;
        }
        
        try {
            // Sicherstellen, dass die Token-Tabelle existiert
            $this->ensureTokenTableExists();
            
            // Token in der Datenbank speichern
            $filePath = $this->getFilePathFromUuid($fileUuid);
            
            // Stellen Sie sicher, dass fileUuid ein formatierter String ist, kein Binärwert
            $normalizedUuid = $fileUuid;
            
            // Überprüfen, ob die UUID binäre Zeichen enthält, die ein Einfügen in die DB verhindern würden
            if (preg_match('/[\x00-\x1F\x7F-\xFF]/', $fileUuid)) {
                $this->logger->warning('UUID enthält binäre Zeichen, versuche zu normalisieren', [
                    'original_uuid' => $fileUuid
                ]);
                
                // Versuche die UUID zu formatieren, falls sie aus dem FilesModel kommt
                try {
                    if ($this->framework) {
                        $this->framework->initialize();
                        $file = \Contao\FilesModel::findByUuid($fileUuid);
                        if ($file) {
                            // Verwende die UUID als String aus dem FilesModel
                            $normalizedUuid = $file->uuid;
                            $this->logger->info('UUID aus FilesModel extrahiert', [
                                'normalized_uuid' => $normalizedUuid
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    $this->logger->warning('Fehler beim Normalisieren der UUID: ' . $e->getMessage());
                }
                
                // Falls immer noch binäre Zeichen enthalten sind, erzeuge einen String-Platzhalter
                if (preg_match('/[\x00-\x1F\x7F-\xFF]/', $normalizedUuid)) {
                    $normalizedUuid = 'uuid_' . bin2hex(random_bytes(8));
                    $this->logger->warning('UUID konnte nicht normalisiert werden, verwende Platzhalter', [
                        'placeholder_uuid' => $normalizedUuid
                    ]);
                }
            }
            
            $data = [
                'tstamp' => time(),
                'token' => $token,
                'file_uuid' => $normalizedUuid,
                'file_path' => $filePath,
                'expires' => $expiresAt,
                'expires_at' => $expiresAt,
                'download_limit' => $downloadLimit,
                'download_count' => 0,
                'created_at' => time(),
                'download_file' => $filePath // Füge auch das Feld download_file hinzu
            ];
            
            // Versuche file_id zu setzen, falls möglich
            try {
                // Prüfen, ob die UUID ein gültiges Format hat
                if (preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $normalizedUuid)) {
                    // Entferne Bindestriche für die bin-Konvertierung
                    $hexUuid = str_replace('-', '', $normalizedUuid);
                    
                    // Sicherstellen, dass es nur hexadezimale Zeichen enthält
                    if (ctype_xdigit($hexUuid)) {
                        $binUuid = hex2bin($hexUuid);
                        if ($binUuid) {
                            $data['file_id'] = $binUuid;
                            $this->logger->info('UUID erfolgreich in Binärformat konvertiert');
                        }
                    } else {
                        $this->logger->warning('Normalisierte UUID enthält ungültige Zeichen: ' . $normalizedUuid);
                    }
                } else {
                    $this->logger->warning('Normalisierte UUID hat kein gültiges Format: ' . $normalizedUuid);
                }
            } catch (\Exception $e) {
                $this->logger->warning('Konnte UUID nicht in Binärformat konvertieren: ' . $e->getMessage());
            }
            
            // Entferne die file_id, wenn die Konvertierung fehlgeschlagen ist
            if (!isset($data['file_id']) || empty($data['file_id'])) {
                $this->logger->info('file_id wird nicht in die Datenbank eingefügt, da die Konvertierung fehlgeschlagen ist');
                unset($data['file_id']);
            }
            
            $this->db->insert('tl_download_tokens', $data);
            
            $this->logger->info('Download-Token erstellt mit UUID und Pfad', [
                'token' => $token,
                'uuid' => $normalizedUuid,
                'path' => $filePath
            ]);
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Erstellen des Download-Tokens: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Konvertiert eine UUID in einen Dateipfad
     */
    private function getFilePathFromUuid(string $uuid): string
    {
        if (empty($uuid)) {
            $this->logger->warning('Leere UUID übergeben');
            return '';
        }
        
        $this->logger->debug('Versuche Dateipfad aus UUID zu ermitteln', ['uuid' => $uuid]);
        
        try {
            if ($this->framework) {
                $this->framework->initialize();
                
                // 1. Versuche direkt mit der übergebenen UUID
                $file = \Contao\FilesModel::findByUuid($uuid);
                if ($file) {
                    $this->logger->info('Datei direkt über UUID gefunden', [
                        'uuid' => $uuid,
                        'path' => $file->path
                    ]);
                    return $file->path;
                }
                
                // 2. Versuche mit formatierter UUID (mit Bindestrichen)
                if (!preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $uuid)) {
                    // Versuche, die UUID zu formatieren, falls sie nur aus Hexziffern besteht
                    $cleanUuid = str_replace('-', '', $uuid);
                    if (preg_match('/^[a-f0-9]{32}$/i', $cleanUuid)) {
                        $formattedUuid = sprintf(
                            '%s-%s-%s-%s-%s',
                            substr($cleanUuid, 0, 8),
                            substr($cleanUuid, 8, 4),
                            substr($cleanUuid, 12, 4),
                            substr($cleanUuid, 16, 4),
                            substr($cleanUuid, 20, 12)
                        );
                        
                        $file = \Contao\FilesModel::findByUuid($formattedUuid);
                        if ($file) {
                            $this->logger->info('Datei über formatierte UUID gefunden', [
                                'original' => $uuid,
                                'formatted' => $formattedUuid,
                                'path' => $file->path
                            ]);
                            return $file->path;
                        }
                    }
                }
                
                // 3. Versuche ohne Bindestriche
                $cleanUuid = str_replace('-', '', $uuid);
                if ($cleanUuid !== $uuid) {
                    $file = \Contao\FilesModel::findByUuid($cleanUuid);
                    if ($file) {
                        $this->logger->info('Datei über UUID ohne Bindestriche gefunden', [
                            'original' => $uuid,
                            'clean' => $cleanUuid,
                            'path' => $file->path
                        ]);
                        return $file->path;
                    }
                }
                
                // 4. Fallback: Versuche direkt als Pfad zu verwenden
                if (strpos($uuid, 'files/') === 0) {
                    $this->logger->info('UUID sieht wie ein Dateipfad aus, verwende direkt', [
                        'path' => $uuid
                    ]);
                    return $uuid;
                }
                
                $this->logger->warning('Keine Datei für UUID gefunden', ['uuid' => $uuid]);
            } else {
                $this->logger->warning('Contao Framework nicht verfügbar für UUID-Auflösung');
            }
        } catch (\Exception $e) {
            $this->logger->error('Fehler bei der UUID-Konvertierung: ' . $e->getMessage(), [
                'uuid' => $uuid
            ]);
        }
        
        return '';
    }
    
    /**
     * Validiert einen Download-Token und gibt den Dateipfad zurück
     */
    public function validateDownloadToken(string $token, array $sessionData): ?string
    {
        try {
            // Prüfen, ob der Token mit dem in der Session übereinstimmt
            if (empty($sessionData['download_token']) || $sessionData['download_token'] !== $token) {
                $this->logger->error('Ungültiger Download-Token: ' . $token);
                return null;
            }
            
            // Prüfen, ob der Download noch gültig ist
            if (empty($sessionData['download_expires']) || $sessionData['download_expires'] < time()) {
                $this->logger->error('Download abgelaufen für Token: ' . $token);
                return null;
            }
            
            // Prüfen, ob das Download-Limit noch nicht erreicht ist
            if (isset($sessionData['download_count'], $sessionData['download_limit']) && 
                $sessionData['download_count'] >= $sessionData['download_limit']) {
                $this->logger->error('Download-Limit erreicht für Token: ' . $token);
                return null;
            }
            
            // Dateipfad zurückgeben
            $filePath = $sessionData['download_file'] ?? '';
            
            if (empty($filePath)) {
                $this->logger->error('Keine Datei mit dem Token verknüpft: ' . $token);
                return null;
            }
            
            // Vollständigen Pfad zur Datei im Projektverzeichnis erstellen
            $fullFilePath = $this->projectDir . '/' . $filePath;
            
            if (!file_exists($fullFilePath)) {
                $this->logger->error('Download-Datei nicht gefunden: ' . $fullFilePath);
                return null;
            }
            
            return $fullFilePath;
        } catch (\Exception $e) {
            $this->logger->error('Fehler bei der Validierung des Download-Tokens: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Validiert und überprüft einen Download-Token aus der Datenbank
     */
    public function validateTokenFromDb(string $token): ?array
    {
        if (!$this->db) {
            $this->logger->error('Keine Datenbankverbindung verfügbar');
            return null;
        }
        
        try {
            // Token aus der Datenbank abrufen
            $result = $this->db->fetchAssociative(
                'SELECT * FROM tl_download_tokens WHERE token = :token',
                ['token' => $token]
            );
            
            if (!$result) {
                $this->logger->error('Token nicht gefunden: ' . $token);
                return null;
            }
            
            // Ablaufdatum prüfen
            if ($result['expires'] < time()) {
                $this->logger->error('Token abgelaufen: ' . $token);
                return null;
            }
            
            // Download-Limit prüfen
            if ($result['download_count'] >= $result['download_limit']) {
                $this->logger->error('Download-Limit erreicht für Token: ' . $token);
                return null;
            }
            
            // Datei ermitteln - verschiedene Methoden versuchen
            $file = null;
            $filePath = null;
            
            if ($this->framework) {
                $this->framework->initialize();
                
                // 1. Versuch: Direkt über den Dateipfad
                if (!empty($result['file_path'])) {
                    $filePath = $this->projectDir . '/' . $result['file_path'];
                    if (file_exists($filePath)) {
                        $this->logger->info('Datei über direkten Pfad gefunden', [
                            'path' => $result['file_path']
                        ]);
                        
                        // Dateinamen extrahieren
                        $fileName = basename($filePath);
                    } else {
                        $this->logger->warning('Dateipfad existiert nicht', [
                            'path' => $filePath
                        ]);
                        $filePath = null; // Zurücksetzen für nächsten Versuch
                    }
                }
                
                // 2. Versuch: Über download_file
                if (!$filePath && !empty($result['download_file'])) {
                    $filePath = $this->projectDir . '/' . $result['download_file'];
                    if (file_exists($filePath)) {
                        $this->logger->info('Datei über download_file gefunden', [
                            'path' => $result['download_file']
                        ]);
                        
                        // Dateinamen extrahieren
                        $fileName = basename($filePath);
                    } else {
                        $this->logger->warning('download_file-Pfad existiert nicht', [
                            'path' => $filePath
                        ]);
                        $filePath = null; // Zurücksetzen für nächsten Versuch
                    }
                }
                
                // 3. Versuch: Über file_id falls vorhanden
                if (!$filePath && !empty($result['file_id'])) {
                    try {
                        // Vorsicht bei der UUID-Konvertierung
                        $file = FilesModel::findByUuid(StringUtil::binToUuid($result['file_id']));
                        
                        if ($file) {
                            $filePath = $this->projectDir . '/' . $file->path;
                            if (file_exists($filePath)) {
                                $this->logger->info('Datei über file_id gefunden', [
                                    'path' => $file->path
                                ]);
                                $fileName = $file->name;
                            } else {
                                $this->logger->warning('Dateipfad aus file_id existiert nicht', [
                                    'path' => $filePath
                                ]);
                                $filePath = null;
                            }
                        }
                    } catch (\Exception $e) {
                        $this->logger->warning('Konnte file_id nicht konvertieren: ' . $e->getMessage());
                    }
                }
                
                // 4. Versuch: Über file_uuid
                if (!$filePath && !empty($result['file_uuid'])) {
                    $file = FilesModel::findByUuid($result['file_uuid']);
                    
                    if ($file) {
                        $filePath = $this->projectDir . '/' . $file->path;
                        if (file_exists($filePath)) {
                            $this->logger->info('Datei über file_uuid gefunden', [
                                'uuid' => $result['file_uuid'],
                                'path' => $file->path
                            ]);
                            $fileName = $file->name;
                        } else {
                            $this->logger->warning('Dateipfad aus file_uuid existiert nicht', [
                                'path' => $filePath
                            ]);
                            $filePath = null;
                        }
                    }
                }
                
                if (!$filePath) {
                    $this->logger->error('Keine Datei für Token gefunden', [
                        'token' => $token,
                        'has_file_path' => !empty($result['file_path']),
                        'has_download_file' => !empty($result['download_file']),
                        'has_file_id' => !empty($result['file_id']),
                        'has_file_uuid' => !empty($result['file_uuid'])
                    ]);
                    return null;
                }
                
                return [
                    'token' => $token,
                    'file_path' => $filePath,
                    'file_name' => $fileName ?? basename($filePath),
                    'download_count' => $result['download_count'],
                    'download_limit' => $result['download_limit'],
                    'expires' => $result['expires'],
                    'order_id' => $result['order_id'] ?? '',
                    'customer_email' => $result['customer_email'] ?? ''
                ];
            }
            
            return null;
        } catch (\Exception $e) {
            $this->logger->error('Fehler bei der Validierung des Tokens: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Generiert einen sicheren Token für Download-Links
     */
    private function generateSecureToken(): string
    {
        return bin2hex(random_bytes(32));
    }
} 