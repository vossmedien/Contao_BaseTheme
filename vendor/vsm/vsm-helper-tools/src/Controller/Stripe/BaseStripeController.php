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

use Contao\CoreBundle\Framework\ContaoFramework;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;
use Vsm\VsmHelperTools\Service\Download\DownloadLinkGenerator;
use Vsm\VsmHelperTools\Service\Email\EmailService;
use Vsm\VsmHelperTools\Service\Payment\PaymentSessionManager;
use Vsm\VsmHelperTools\Service\Stripe\StripePaymentService;
use Vsm\VsmHelperTools\Service\User\UserCreationService;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

#[Route('/stripe', defaults: ['_scope' => 'frontend'])]
#[AutoconfigureTag('controller.service_arguments')]
abstract class BaseStripeController extends AbstractController
{
    protected ContaoFramework $framework;
    protected LoggerInterface $logger;
    protected string $projectDir;
    protected Connection $db;
    protected string $stripeSecretKey;
    protected PaymentSessionManager $sessionManager;
    protected StripePaymentService $stripeService;
    protected EmailService $emailService;
    protected DownloadLinkGenerator $downloadService;
    protected UserCreationService $userService;
    protected Environment $twig;
    protected ParameterBagInterface $params;
    protected Connection $connection;
    protected bool $isDebug;
    
    public function __construct(
        ContaoFramework $framework,
        LoggerInterface $logger,
        string $projectDir,
        Connection $db,
        string $stripeSecretKey,
        PaymentSessionManager $sessionManager,
        StripePaymentService $stripeService,
        EmailService $emailService,
        DownloadLinkGenerator $downloadService,
        UserCreationService $userService,
        Environment $twig,
        ParameterBagInterface $params,
        Connection $connection
    ) {
        $this->framework = $framework;
        $this->logger = $logger;
        $this->projectDir = $projectDir;
        $this->db = $db;
        $this->stripeSecretKey = $stripeSecretKey;
        $this->sessionManager = $sessionManager;
        $this->stripeService = $stripeService;
        $this->emailService = $emailService;
        $this->downloadService = $downloadService;
        $this->userService = $userService;
        $this->twig = $twig;
        $this->params = $params;
        $this->connection = $connection;
        
        // Debug-Modus aus Parameter oder Umgebungsvariable
        $this->isDebug = $params->get('vsm_helper_tools.stripe.debug') ?? 
                          ($_ENV['APP_ENV'] === 'dev') ?? 
                          false;
    }
    
    /**
     * Initialisiert den Stripe-Client mit dem Secret Key
     */
    protected function initStripeClient(): void
    {
        try {
            \Stripe\Stripe::setApiKey($this->stripeSecretKey);
            
            $this->logger->info('Stripe-Client initialisiert');
        } catch (\Exception $e) {
            $this->logger->error('Fehler bei der Initialisierung des Stripe-Clients: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Debug-Log-Ausgabe, nur wenn Debug-Modus aktiv
     */
    protected function debugLog(string $message, array $context = []): void
    {
        if ($this->isDebug) {
            $this->logger->debug($message, $context);
        }
    }
    
    /**
     * Überprüft die Datenbankverbindung und gibt Diagnostikinfos zurück
     */
    protected function checkDatabaseConnection(): array
    {
        try {
            $info = [];
            
            // Prüfen, ob wir eine Verbindung herstellen können
            $testResult = $this->connection->executeQuery('SELECT 1')->fetchOne();
            $info['connection'] = $testResult == 1 ? 'ok' : 'error';
            
            // Vorhandene Tabellen prüfen
            $tables = $this->connection->executeQuery("SHOW TABLES")->fetchFirstColumn();
            $info['tables'] = $tables;
            $info['has_stripe_sessions'] = in_array('tl_stripe_payment_sessions', $tables);
            $info['has_stripe_locks'] = in_array('tl_stripe_locks', $tables);
            $info['has_download_tokens'] = in_array('tl_download_tokens', $tables);
            
            // Anzahl der Datensätze
            if ($info['has_stripe_sessions']) {
                $count = $this->connection->executeQuery("SELECT COUNT(*) FROM tl_stripe_payment_sessions")->fetchOne();
                $info['session_count'] = (int)$count;
            }
            
            $this->logger->info('Datenbankprüfung erfolgreich:', $info);
            return [
                'status' => 'ok',
                'info' => $info
            ];
        } catch (\Exception $e) {
            $this->logger->error('Fehler bei Datenbankprüfung: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
} 