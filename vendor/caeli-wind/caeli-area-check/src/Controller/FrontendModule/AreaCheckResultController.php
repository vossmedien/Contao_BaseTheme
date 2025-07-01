<?php

declare(strict_types=1);

namespace CaeliWind\CaeliAreaCheckBundle\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Input;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\Template;
use Contao\System;
use Contao\Database;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;

#[AsFrontendModule(category: 'caeli_wind', template: 'mod_area_check_result', name: 'area_check_result')]
class AreaCheckResultController extends AbstractFrontendModuleController
{
    public const TYPE = 'area_check_result';
    
    private string $api_url;
    private string $api_user;
    private string $api_pass;

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly LoggerInterface $logger
    ) {
        $this->api_url = getenv('CAELI_INFRA_API_URL') ?: "";
        $this->api_user = getenv('CAELI_INFRA_API_USERNAME') ?: "";
        $this->api_pass = getenv('CAELI_INFRA_API_PASSWORD') ?: "";
    }

    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        // Contao Sprachdatei laden - SOFORT am Anfang
        System::loadLanguageFile('default');
        
        // Übersetzungen aus Contao-Sprachdateien verwenden
        $translations = $GLOBALS['TL_LANG']['caeli_area_check'] ?? [];
        $template->translations = $translations;

        // Die Check-ID aus dem URL-Parameter abrufen (kann parkid oder DB-ID sein)
        $framework = $this->framework;
        $framework->initialize();
        $checkid = $framework->getAdapter(Input::class)->get('checkid') ?: $framework->getAdapter(Input::class)->get('parkid'); // Fallback für alte URLs
        
        $template->checkid = $checkid;
        $template->mapPage = $model->jumpTo ? $framework->getAdapter(PageModel::class)->findById($model->jumpTo) : null;
        
        if (!$checkid || $checkid === '0') {
            $template->error = 'Keine Check-ID gefunden. Bitte führen Sie zunächst eine Flächenprüfung durch.';
            return $template->getResponse();
        }
        
        try {
            
            // Zuerst in der Datenbank suchen - entweder über park_id, UUID oder ID (Fallback)
            $dbResult = null;
            
            // 1. Versuch: park_id (für erfolgreiche Parks) - ZUERST prüfen!
            if (!is_numeric($checkid)) {
                $dbResult = Database::getInstance()
                    ->prepare("SELECT * FROM tl_flaechencheck WHERE park_id = ? ORDER BY tstamp DESC LIMIT 1")
                    ->execute($checkid);
                
                // Wenn nicht als park_id gefunden und es UUID-Format hat, dann in uuid Spalte suchen
                if ((!$dbResult || $dbResult->numRows === 0) && 
                    (preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $checkid) || 
                     preg_match('/^fc-\d+-[a-f0-9]{16}$/', $checkid))) {
                    $dbResult = Database::getInstance()
                        ->prepare("SELECT * FROM tl_flaechencheck WHERE uuid = ?")
                        ->execute($checkid);
                }
            }
            // 2. Fallback: DB-ID (für alte fehlgeschlagene Parks)
            else {
                $dbResult = Database::getInstance()
                    ->prepare("SELECT * FROM tl_flaechencheck WHERE id = ?")
                    ->execute($checkid);
            }
            
            if (!$dbResult || $dbResult->numRows === 0) {
                // WARNING statt ERROR - oft sind das Bots/Tests mit ungültigen URLs
                $this->logger->warning('[AreaCheckResultController] Ungültige checkid aufgerufen: ' . $checkid . ' (möglicherweise Bot/Test/alter Link)');
                
                // Nur bei DEBUG-Level zusätzliche Infos loggen
                if ($this->logger instanceof \Psr\Log\LoggerInterface) {
                    $this->logger->debug('[AreaCheckResultController] Gefunden ' . ($dbResult ? $dbResult->numRows : 'NULL') . ' Zeilen für checkid: ' . $checkid);
                }
                
                $template->error = 'Diese Flächencheck-ID ist ungültig oder abgelaufen. Bitte führen Sie einen neuen Flächencheck durch.';
                return $template->getResponse();
            }
            
            $checkData = $dbResult->fetchAssoc();
            $isSuccess = $checkData['status'] === 'success';
            
            // Template-Variablen setzen
            $template->checkData = $checkData;
            $template->isSuccess = $isSuccess;
            $template->success = $isSuccess; // Für Rückwärtskompatibilität
            $template->error = null;
            
            if (($isSuccess || $checkData['status'] === 'failed_with_rating') && !empty($checkData['park_rating'])) {
                // Park mit Rating-Daten (erfolgreich oder fehlgeschlagen aber mit Rating)
                $parkData = json_decode($checkData['park_rating'], true);
                if ($parkData) {
                    $template->rating = (object) $parkData;
                } else {
                    // Fallback auf API falls JSON decode fehlschlägt
                    if ($checkData['park_id']) {
                        $template->rating = $this->getPlotRating($checkData['park_id']);
                    }
                }
            } else {
                // Fehlgeschlagener Park ohne Rating-Daten
                $template->rating = null;
                $template->errorMessage = $checkData['error_message'] ?? 'Unbekannter Fehler';
            }
            
        } catch (\Throwable $e) {
            $this->logger->error('[AreaCheckResultController] Fehler beim Abrufen der Check-Daten: ' . $e->getMessage());
            $template->error = 'Fehler beim Abrufen der Bewertung: ' . $e->getMessage();
            $template->isSuccess = false;
            $template->rating = null;
        }
        
        return $template->getResponse();
    }

    private function getApiSessionId(): ?string
    {
        // Get project root directory für Cookie-Speicherung
        $rootDir = System::getContainer()->getParameter('kernel.project_dir');
        
        $fields = json_encode([
            "email" =>  $this->api_user,
            "password" => $this->api_pass,
        ]);

        $curl_session = curl_init();
        curl_setopt($curl_session, CURLOPT_URL, $this->api_url."auth/login");
        curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl_session, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl_session, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($curl_session, CURLOPT_COOKIEJAR, $rootDir."/system/tmp/".session_id().'.txt');
        curl_setopt($curl_session, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        $result = curl_exec($curl_session);
        curl_close($curl_session);
        $json = json_decode($result);
        return $json->tokens->csrf_session_id ?? null;
    }

    private function getPlotRating($id)
    {
        // Get project root directory für Cookie-Speicherung
        $rootDir = System::getContainer()->getParameter('kernel.project_dir');
        
        $api_session_id = $this->getApiSessionId();
        if (!$api_session_id) {
            throw new \RuntimeException('API-Session konnte nicht erstellt werden.');
        }
        
        $curl_session = curl_init();
        curl_setopt($curl_session, CURLOPT_URL, $this->api_url."wind/caeli/rating?".http_build_query([
            'area_id'=>$id
        ]));
        curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl_session, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($curl_session, CURLOPT_COOKIEJAR, $rootDir."/system/tmp/".session_id().'.txt');
        curl_setopt($curl_session, CURLOPT_COOKIEFILE, $rootDir."/system/tmp/".session_id().'.txt');
        curl_setopt($curl_session, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl_session, CURLOPT_HTTPHEADER, [
            'X-CSRF-TOKEN: '.$api_session_id,
            'Content-Type: application/json'
        ]);
        $result = curl_exec($curl_session);
        curl_close($curl_session);
        return json_decode($result);
    }

    private function getPlotRatingFromParkData(array $parkData, string $parkid): ?object
    {
        // Wenn bereits Rating-Daten vorhanden sind, diese verwenden
        if (isset($parkData['rating_cutdensity'])) {
            $this->logger->debug('[AreaCheckResultController] Rating aus Park-Daten verwendet für parkid: ' . $parkid);
            return (object) $parkData;
        }
        
        // Fallback: Rating über API abrufen mit der Park-ID
        $this->logger->debug('[AreaCheckResultController] Rating per API aus Park-Daten für parkid: ' . $parkid);
        return $this->getPlotRating($parkid);
    }
} 