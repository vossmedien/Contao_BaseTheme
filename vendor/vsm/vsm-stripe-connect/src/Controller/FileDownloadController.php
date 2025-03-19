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

namespace Vsm\VsmStripeConnect\Controller;

use Contao\CoreBundle\Framework\ContaoFramework;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Vsm\VsmStripeConnect\Service\FileDownloadService;

#[Route('/download')]
class FileDownloadController extends AbstractController implements ServiceSubscriberInterface
{
    private ContaoFramework $framework;
    private LoggerInterface $logger;
    private FileDownloadService $fileDownloadService;

    public function __construct(
        ContaoFramework $framework,
        LoggerInterface $logger,
        FileDownloadService $fileDownloadService
    ) {
        $this->framework = $framework;
        $this->logger = $logger;
        $this->fileDownloadService = $fileDownloadService;
    }

    #[Route('/{token}', name: 'file_download', methods: ['GET'])]
    public function downloadFile(string $token, Request $request): Response
    {
        $this->framework->initialize();

        // Token validieren
        $downloadInfo = $this->fileDownloadService->validateDownloadToken($token);

        if (!$downloadInfo['valid']) {
            throw new AccessDeniedHttpException($downloadInfo['error']);
        }

        $filePath = $downloadInfo['absolute_path'];
        if (!file_exists($filePath) || !is_file($filePath)) {
            throw new NotFoundHttpException('Datei nicht gefunden');
        }

        // Dateiname zum Download vorbereiten
        $fileName = $downloadInfo['file_name'];
        
        // Download-Counter erhöhen
        $this->fileDownloadService->incrementDownloadCount($downloadInfo['download_id']);
        
        // Log-Eintrag
        $this->logger->info('Datei wird heruntergeladen', [
            'file' => $fileName,
            'email' => $downloadInfo['email'],
            'token' => $token,
            'ip' => $request->getClientIp()
        ]);

        // Datei zum Download anbieten
        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $fileName);
        
        return $response;
    }
} 