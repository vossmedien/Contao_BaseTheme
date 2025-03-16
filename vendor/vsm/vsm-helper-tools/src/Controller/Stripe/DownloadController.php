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
namespace Vsm\VsmHelperTools\Controller\Stripe;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller für die Download-Funktionalität nach Stripe-Checkout
 */
class DownloadController extends BaseStripeController
{
    use UtilityTrait;
    
    /**
     * Download-Handler für Dateien
     */
    #[Route('/download/{token}', name: 'stripe_download_file', methods: ['GET'])]
    public function downloadFile(string $token, Request $request): Response
    {
        try {
            // Stripe-Client initialisieren
            $this->initStripeClient();
            
            $this->logger->info('Download-Anfrage erhalten', [
                'token' => $token,
                'ip' => $request->getClientIp()
            ]);
            
            // Session-Daten anhand des Tokens abrufen
            $queryBuilder = $this->db->createQueryBuilder();
            $result = $queryBuilder
                ->select('*')
                ->from('tl_stripe_payment_sessions')
                ->where('download_token = :token')
                ->setParameter('token', $token)
                ->execute()
                ->fetchAssociative();
            
            if (!$result) {
                $this->logger->error('Download nicht gefunden oder nicht autorisiert', [
                    'token' => $token,
                    'ip' => $request->getClientIp()
                ]);
                
                // Versuche, den Token in der alten Tabelle zu finden
                try {
                    $tokenResult = $this->db->createQueryBuilder()
                        ->select('*')
                        ->from('tl_download_tokens')
                        ->where('token = :token')
                        ->andWhere('expires > :now')
                        ->andWhere('download_count < download_limit')
                        ->setParameter('token', $token)
                        ->setParameter('now', time())
                        ->execute()
                        ->fetchAssociative();
                    
                    if ($tokenResult) {
                        $this->logger->info('Token in der Download-Tokens-Tabelle gefunden', [
                            'token' => $token,
                            'file_path' => $tokenResult['file_path']
                        ]);
                        
                        // Prüfen, ob der Download noch gültig ist
                        $now = time();
                        if ($tokenResult['expires'] < $now) {
                            $this->logger->error('Der Download ist abgelaufen', [
                                'token' => $token, 
                                'expires' => $tokenResult['expires'], 
                                'now' => $now
                            ]);
                            return $this->json(['error' => 'Der Download ist abgelaufen'], 403);
                        }
                        
                        // Prüfen, ob das Download-Limit erreicht ist
                        if ($tokenResult['download_count'] >= $tokenResult['download_limit']) {
                            $this->logger->error('Das Download-Limit wurde erreicht', [
                                'token' => $token, 
                                'count' => $tokenResult['download_count'], 
                                'limit' => $tokenResult['download_limit']
                            ]);
                            return $this->json(['error' => 'Das Download-Limit wurde erreicht'], 403);
                        }
                        
                        // Download-Zähler erhöhen
                        $this->db->update(
                            'tl_download_tokens',
                            [
                                'download_count' => $tokenResult['download_count'] + 1,
                                'last_download' => time()
                            ],
                            ['token' => $token]
                        );
                        
                        $this->logger->info('Download-Zähler erhöht', [
                            'token' => $token,
                            'count' => $tokenResult['download_count'] + 1,
                            'limit' => $tokenResult['download_limit'],
                            'last_download' => date('Y-m-d H:i:s', time())
                        ]);
                        
                        // Dateipfad ermitteln
                        $filePath = $this->projectDir . '/' . $tokenResult['file_path'];
                        
                        // Prüfen, ob die Datei existiert
                        if (!file_exists($filePath)) {
                            $this->logger->error('Download-Datei existiert nicht', ['path' => $filePath]);
                            return $this->json(['error' => 'Die angeforderte Datei wurde nicht gefunden'], 404);
                        }
                        
                        // Datei zum Download anbieten
                        $response = new BinaryFileResponse($filePath);
                        $response->setContentDisposition(
                            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                            basename($filePath)
                        );
                        
                        $this->logger->info('Download wird gestartet (aus Token-Tabelle)', [
                            'file' => basename($filePath), 
                            'token' => $token
                        ]);
                        return $response;
                    }
                } catch (\Exception $e) {
                    $this->logger->warning('Fehler beim Suchen in der Token-Tabelle: ' . $e->getMessage());
                }
                
                return $this->json(['error' => 'Download nicht gefunden oder nicht autorisiert'], 404);
            }
            
            // Prüfen, ob der Download noch gültig ist
            $now = time();
            if (isset($result['download_expires']) && $result['download_expires'] < $now) {
                $this->logger->error('Der Download ist abgelaufen', ['token' => $token, 'expires' => $result['download_expires'], 'now' => $now]);
                return $this->json(['error' => 'Der Download ist abgelaufen'], 403);
            }
            
            // Prüfen, ob das Download-Limit erreicht ist
            if (isset($result['download_limit']) && isset($result['download_count']) && 
                (int)$result['download_count'] >= (int)$result['download_limit']) {
                $this->logger->error('Das Download-Limit wurde erreicht', ['token' => $token, 'count' => $result['download_count'], 'limit' => $result['download_limit']]);
                return $this->json(['error' => 'Das Download-Limit wurde erreicht'], 403);
            }
            
            // Download-Zähler erhöhen
            $this->sessionManager->incrementDownloadCount($result['session_id']);
            
            $this->logger->info('Download-Zähler erhöht', [
                'token' => $token,
                'session_id' => $result['session_id'],
                'previous_count' => $result['download_count'],
                'new_count' => $result['download_count'] + 1,
                'limit' => $result['download_limit'] ?? 'unbegrenzt'
            ]);
            
            // Dateipfad ermitteln - mehrere Quellen prüfen
            $filePath = null;
            
            // Prüfen, ob die download_file Spalte existiert
            $hasDownloadFileColumn = true;
            try {
                $this->db->executeQuery('SELECT download_file FROM tl_stripe_payment_sessions LIMIT 1');
            } catch (\Exception $e) {
                $hasDownloadFileColumn = false;
                $this->logger->warning('Spalte download_file nicht abrufbar: ' . $e->getMessage());
            }
            
            // 1. Direkt aus dem download_file-Feld (falls Spalte existiert)
            if ($hasDownloadFileColumn && isset($result['download_file']) && !empty($result['download_file'])) {
                $filePath = $this->projectDir . '/' . $result['download_file'];
                $this->logger->info('Dateipfad direkt aus download_file-Feld verwendet', ['path' => $result['download_file']]);
            } 
            // 2. Aus den Produkt-Daten
            else {
                $productData = json_decode($result['product_data'], true);
                
                if (isset($productData['download_file']) && !empty($productData['download_file'])) {
                    $filePath = $this->projectDir . '/' . $productData['download_file'];
                    $this->logger->info('Dateipfad aus product_data.download_file verwendet', ['path' => $productData['download_file']]);
                }
                // 3. Aus einer File-UUID in den Produktdaten
                elseif (isset($productData['file_uuid']) && !empty($productData['file_uuid'])) {
                    $filePathFromUuid = $this->getFilePathFromUuid($productData['file_uuid']);
                    if (!empty($filePathFromUuid)) {
                        $filePath = $this->projectDir . '/' . $filePathFromUuid;
                        $this->logger->info('Dateipfad aus UUID generiert', [
                            'uuid' => $productData['file_uuid'],
                            'path' => $filePathFromUuid
                        ]);
                    }
                }
                // 4. Alternative UUID-Schlüssel prüfen
                else {
                    $uuidKeys = ['data-file-uuid', 'download_uuid', 'uuid'];
                    foreach ($uuidKeys as $key) {
                        if (isset($productData[$key]) && !empty($productData[$key])) {
                            $filePathFromUuid = $this->getFilePathFromUuid($productData[$key]);
                            if (!empty($filePathFromUuid)) {
                                $filePath = $this->projectDir . '/' . $filePathFromUuid;
                                $this->logger->info('Dateipfad aus alternativer UUID generiert', [
                                    'key' => $key,
                                    'uuid' => $productData[$key],
                                    'path' => $filePathFromUuid
                                ]);
                                break;
                            }
                        }
                    }
                }
            }
            
            // Wenn immer noch kein Dateipfad gefunden wurde
            if (!$filePath) {
                $this->logger->error('Kein Dateipfad für den Download gefunden', [
                    'token' => $token, 
                    'session_id' => $result['session_id'],
                    'product_data_keys' => $productData ? array_keys($productData) : []
                ]);
                return $this->json(['error' => 'Die angeforderte Datei wurde nicht gefunden'], 404);
            }
            
            // Prüfen, ob die Datei existiert
            if (!file_exists($filePath)) {
                $this->logger->error('Download-Datei existiert nicht', ['path' => $filePath]);
                return $this->json(['error' => 'Die angeforderte Datei wurde nicht gefunden'], 404);
            }
            
            // Wenn es ein Verzeichnis ist, versuche die erste Datei darin zu verwenden
            if (is_dir($filePath)) {
                $this->logger->warning('Download-Pfad ist ein Verzeichnis, suche nach Datei darin', ['directory' => $filePath]);
                $files = scandir($filePath);
                $foundFile = null;
                
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..' && is_file($filePath . '/' . $file)) {
                        $foundFile = $file;
                        break;
                    }
                }
                
                if ($foundFile) {
                    $filePath = $filePath . '/' . $foundFile;
                    $this->logger->info('Verwende erste Datei im Verzeichnis', ['file' => $foundFile, 'path' => $filePath]);
                } else {
                    $this->logger->error('Keine Dateien im Verzeichnis gefunden', ['directory' => $filePath]);
                    return $this->json(['error' => 'Keine Dateien im Verzeichnis gefunden'], 404);
                }
            }
            
            // Datei zum Download anbieten
            $response = new BinaryFileResponse($filePath);
            $response->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                basename($filePath)
            );
            
            $this->logger->info('Download wird gestartet', ['file' => basename($filePath), 'token' => $token]);
            return $response;
            
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Bereitstellen des Downloads: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->json(['error' => 'Fehler beim Bereitstellen des Downloads: ' . $e->getMessage()], 500);
        }
    }
} 