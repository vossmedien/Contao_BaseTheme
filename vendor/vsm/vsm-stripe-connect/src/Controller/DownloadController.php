<?php

namespace Vsm\VsmStripeConnect\Controller;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FilesModel;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\Routing\Attribute\Route;

class DownloadController extends AbstractController
{
    private $framework;
    private $projectDir;
    private $db;

    public function __construct(ContaoFramework $framework, string $projectDir, Connection $db)
    {
        $this->framework = $framework;
        $this->projectDir = $projectDir;
        $this->db = $db;
    }

    /**
     * @Route("/download/{token}/{fileId}", name="download_file")
     */
    public function download(string $token, string $fileId): Response
    {
        $this->framework->initialize();

        // Token validieren
        $downloadToken = $this->validateDownloadToken($token);
        if (!$downloadToken) {
            throw $this->createNotFoundException('Download nicht verfügbar oder abgelaufen.');
        }

        // Datei finden
        $file = FilesModel::findByUuid(StringUtil::uuidToBin($fileId));
        if (!$file) {
            throw $this->createNotFoundException('Datei nicht gefunden.');
        }

        $filePath = $this->projectDir . '/' . $file->path;
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Datei nicht gefunden.');
        }

        // Download-Counter aktualisieren
        $this->updateDownloadStats($downloadToken['id']);

        // Datei zum Download senden
        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $file->name
        );

        return $response;
    }

    private function validateDownloadToken(string $token): ?array
    {
        $now = time();
        
        $qb = $this->db->createQueryBuilder();
        $result = $qb->select('*')
            ->from('tl_download_tokens')
            ->where('token = :token')
            ->andWhere('expires > :now')
            ->andWhere('download_count < download_limit')
            ->setParameter('token', $token)
            ->setParameter('now', $now)
            ->execute()
            ->fetchAssociative();

        return $result ?: null;
    }

    private function updateDownloadStats(int $tokenId): void
    {
        // Debug-Ausgabe oder Logging
        error_log('Aktualisiere Download-Statistik: ID=' . $tokenId);
        
        // Aktualisiere den Download-Counter nur einmal mit der ID
        $this->db->executeStatement(
            'UPDATE tl_download_tokens SET download_count = download_count + 1, last_download = ? WHERE id = ?',
            [time(), $tokenId]
        );
    }
} 