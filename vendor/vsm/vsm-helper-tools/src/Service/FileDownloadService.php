<?php

declare(strict_types=1);

/*
 * This file is part of VSM Helper und Integrations.
 *
 * (c) Vossmedien - Christian Voss 2025 <christian@vossmedien.de>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/vsm/vsm-helper-tools
 */

namespace Vsm\VsmHelperTools\Service;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Database;
use Contao\Environment;
use Contao\FilesModel;
use Contao\StringUtil;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Doctrine\DBAL\Connection;

class FileDownloadService
{
    private ContaoFramework $framework;
    private LoggerInterface $logger;
    private string $projectDir;
    private Connection $db;

    public function __construct(
        ContaoFramework $framework,
        string $projectDir,
        LoggerInterface $logger,
        Connection $db
    ) {
        $this->framework = $framework;
        $this->projectDir = $projectDir;
        $this->logger = $logger;
        $this->db = $db;
    }

    /**
     * Erstellt einen Download-Eintrag und gibt den Download-Link zurück
     *
     * @param string $filePath Pfad zur Datei
     * @param string $token Eindeutiger Download-Token
     * @param string $userEmail E-Mail-Adresse des Benutzers
     * @param string $paymentId ID der Zahlung
     * @param int $expirationDays Anzahl der Tage, nach denen der Download abläuft (0 = nie)
     * @param int $downloadLimit Maximale Anzahl an Downloads (0 = unbegrenzt)
     * @return string|null URL zum Download der Datei oder null bei Fehler
     */
    public function createDownloadEntry(
        string $filePath,
        string $token,
        int $expirationDays = 7,
        int $downloadLimit = 3,
        string $orderId = '',
        string $customerEmail = ''
    ) {
        $this->framework->initialize();

        try {
            if (empty($filePath) || empty($token)) {
                throw new \Exception('Keine Datei oder kein Token angegeben');
            }

            // Datei überprüfen
            $file = FilesModel::findByPath($filePath);
            
            if ($file === null) {
                throw new \Exception('Datei nicht gefunden: ' . $filePath);
            }

            // Ablaufdatum berechnen
            $expiresTimestamp = $expirationDays > 0 ? time() + ($expirationDays * 24 * 60 * 60) : 0;

            // Download-Eintrag erstellen
            $this->db->insert('tl_download_tokens', [
                'tstamp' => time(),
                'token' => $token,
                'file_id' => $file->uuid,
                'expires' => $expiresTimestamp,
                'download_limit' => $downloadLimit,
                'download_count' => 0,
                'order_id' => $orderId,
                'customer_email' => $customerEmail
            ]);

            $this->logger->info('Download-Link erstellt', [
                'context' => ContaoContext::GENERAL,
                'file' => $filePath,
                'expires' => $expiresTimestamp ? date('Y-m-d H:i:s', $expiresTimestamp) : 'unbegrenzt',
                'token' => $token
            ]);

            return $token;

        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Erstellen des Download-Links', [
                'context' => ContaoContext::ERROR,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Überprüft, ob ein Download-Token gültig ist
     */
    public function validateDownloadToken(string $token): ?array
    {
        try {
            $now = time();
            
            $qb = $this->db->createQueryBuilder();
            $result = $qb->select('t.*, f.uuid, f.path')
                ->from('tl_download_tokens', 't')
                ->leftJoin('t', 'tl_files', 'f', 't.file_id = f.uuid')
                ->where('t.token = :token')
                ->andWhere('t.expires > :now')
                ->andWhere('t.download_count < t.download_limit')
                ->setParameter('token', $token)
                ->setParameter('now', $now)
                ->execute()
                ->fetchAssociative();

            if (!$result) {
                return null;
            }

            // Prüfen ob die Datei existiert
            $filePath = $this->projectDir . '/' . $result['path'];
            if (!file_exists($filePath)) {
                $this->logger->error('Datei nicht gefunden', [
                    'path' => $filePath,
                    'token' => $token
                ]);
                return null;
            }

            return $result;

        } catch (\Exception $e) {
            $this->logger->error('Fehler bei der Token-Validierung: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Inkrementiert den Download-Zähler
     */
    public function incrementDownloadCount(int $downloadId): void
    {
        $this->framework->initialize();
        
        try {
            $this->db->executeStatement(
                'UPDATE tl_download_tokens 
                SET download_count = download_count + 1, last_download = ?
                WHERE id = ?',
                [time(), $downloadId]
            );
            
            $this->logger->info('Download-Zähler erhöht', [
                'context' => ContaoContext::GENERAL,
                'download_id' => $downloadId
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Erhöhen des Download-Zählers', [
                'context' => ContaoContext::ERROR,
                'error' => $e->getMessage(),
                'download_id' => $downloadId
            ]);
        }
    }

    /**
     * Generiert einen Download-Link für einen Token
     */
    public function generateDownloadLink(string $token): string
    {
        $baseUrl = \Contao\System::getContainer()->getParameter('contao.base_url');
        return sprintf(
            '%s/download/%s',
            rtrim($baseUrl, '/'),
            $token
        );
    }

    /**
     * Aktualisiert die Download-Statistik für einen Token
     */
    public function updateDownloadStats(int $tokenId): void
    {
        try {
            $this->db->executeStatement(
                'UPDATE tl_download_tokens SET download_count = download_count + 1 WHERE id = ?',
                [$tokenId]
            );
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Aktualisieren der Download-Statistik: ' . $e->getMessage());
        }
    }
} 