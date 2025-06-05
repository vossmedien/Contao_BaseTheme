<?php

namespace CaeliWind\CaeliAreaCheckBundle\Module;

use Contao\Input;
use Contao\Module;
use Contao\System;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Symfony\Component\Security\Csrf\CsrfToken;
use Doctrine\DBAL\Connection;
use Contao\Database;
use Contao\Environment;

class AreaCheckModule extends Module
{
    protected $strTemplate = 'mod_caeli_area_check';

    private string $api_url;
    private string $api_user;
    private string $api_pass;

    public function __construct($module, $column = 'main')
    {
        parent::__construct($module, $column);
        
        // Versuche Environment-Variablen zu laden
        $this->api_url = getenv('CAELI_INFRA_API_URL') ?: 
                        (System::getContainer()->hasParameter('env(CAELI_INFRA_API_URL)') ? 
                         System::getContainer()->getParameter('env(CAELI_INFRA_API_URL)') : 
                         "https://infra.caeli-wind.de/api/");
        
        $this->api_user = getenv('CAELI_INFRA_API_USERNAME') ?: 
                         (System::getContainer()->hasParameter('env(CAELI_INFRA_API_USERNAME)') ? 
                          System::getContainer()->getParameter('env(CAELI_INFRA_API_USERNAME)') : 
                          "website-2025@caeli-wind.de");
        
        $this->api_pass = getenv('CAELI_INFRA_API_PASSWORD') ?: 
                         (System::getContainer()->hasParameter('env(CAELI_INFRA_API_PASSWORD)') ? 
                          System::getContainer()->getParameter('env(CAELI_INFRA_API_PASSWORD)') : 
                          "0wuvgvh>LrpB(ef-");
    }

    private function getApiSessionId()
    {

        $rootDir = System::getContainer()->getParameter('kernel.project_dir');

        $fields = json_encode(array(
            "email" => $this->api_user,
            "password" => $this->api_pass,
        ));

        $curl_session = curl_init();
        curl_setopt($curl_session, CURLOPT_URL, $this->api_url . "auth/login");
        curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl_session, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl_session, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($curl_session, CURLOPT_COOKIEJAR, $rootDir . "/system/tmp/" . session_id() . '.txt');
        curl_setopt($curl_session, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json')
        );
        $result = curl_exec($curl_session);

        /*
        if(curl_error($curl_session)) {
          dump(curl_error($curl_session));
        }
        */
        curl_close($curl_session);

        //$_SESSION['new_plot']['api_session'] = json_decode($result)->tokens->csrf_session_id;
        return json_decode($result)->tokens->csrf_session_id;
    }

    private function createPark($data)
    {
        //return 'bcaf9ff4-65c1-47f2-9139-82430d3710c3';
        $rootDir = System::getContainer()->getParameter('kernel.project_dir');
        $api_session_id = $this->getApiSessionId();

        //$data['geometry'] = '{"geometry":{"coordinates":[[[8.147900888731186,52.232090618982824],[8.149789166524073,52.22536137556068],[8.17387184960154,52.226623186819864],[8.182197157783861,52.229199273847854],[8.181534955077865,52.236724393655976],[8.179732510797493,52.242164146228674],[8.147900888731186,52.232090618982824]]],"type":"Polygon"}}';
        //{"status": "success", "message": "Parkplanung erfolgreich durchgef\u00fchrt", "parks": {"status": "success", "id": "['bcaf9ff4-65c1-47f2-9139-82430d3710c3']"}}
        $postData = [
            'geometry' => json_decode($data['geometry']),
        ];

        $curl_session = curl_init();
        curl_setopt($curl_session, CURLOPT_URL, $this->api_url . "wind/caeli/park");
        curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl_session, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl_session, CURLOPT_COOKIEJAR, $rootDir . "/system/tmp/" . session_id() . '.txt');
        curl_setopt($curl_session, CURLOPT_COOKIEFILE, $rootDir . "/system/tmp/" . session_id() . '.txt');
        curl_setopt($curl_session, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($curl_session, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl_session, CURLOPT_HTTPHEADER, [
            'X-CSRF-TOKEN: ' . $api_session_id,
            'Content-Type: application/json'
        ]);
        $result = curl_exec($curl_session);

        curl_close($curl_session);
        if (json_decode($result)->status == 'success') {

            return str_replace(["[", "]", "'"], ["", "", ""], json_decode($result)->parks->id);
        } else {
            return "-";//json_decode($result);
        }
    }

    private function getPlotRating($id)
    {
        $rootDir = System::getContainer()->getParameter('kernel.project_dir');
        $api_session_id = $this->getApiSessionId();

        $curl_session = curl_init();
        curl_setopt($curl_session, CURLOPT_URL, $this->api_url . "wind/caeli/rating?" . http_build_query([
                'area_id' => $id
            ]));
        curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl_session, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($curl_session, CURLOPT_COOKIEJAR, $rootDir . "/system/tmp/" . session_id() . '.txt');
        curl_setopt($curl_session, CURLOPT_COOKIEFILE, $rootDir . "/system/tmp/" . session_id() . '.txt');
        curl_setopt($curl_session, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl_session, CURLOPT_HTTPHEADER, [
            'X-CSRF-TOKEN: ' . $api_session_id,
            'Content-Type: application/json'
        ]);
        $result = curl_exec($curl_session);

        curl_close($curl_session);
        return json_decode($result);
    }

    protected function compile(): void
    {
        // Debug: Prüfen welche URL aufgerufen wird
        error_log("AreaCheckModule compile() aufgerufen - auto_item: " . Input::get('auto_item'));
        
        if (Input::get('auto_item') == 'result') {
            // Debug: Result-Bereich erreicht
            error_log("Result-Bereich erreicht");
            
            // Es gitb 3 Einstiege:
            // 1. Park wurde bereits angelegt und die Daten wurden gespeichert, aufruf der ergebnissseite mit plot id in url
            // 2. Park wurde noch nicht angelegt, erstellung des parks mit $_POSTund dann aufruf der ergebnissseite mit plot id in url
            // 3. Kein Suche Keine ID

            if (Input::get('parkid')) {
                // 1. Park wurde bereits angelegt und die Daten wurden gespeichert, aufruf der ergebnissseite mit plot id in url
                $parkid = Input::get('parkid');
                //Hier könnte ggf auch aus der Datenbank ausgelesen werden
                $rating = $this->getPlotRating($parkid);
                
                // Template-Variablen für Result setzen
                $this->Template->isResult = true;
                $this->Template->success = true;
                $this->Template->rating = $rating;
                $this->Template->parkid = $parkid;
                $this->Template->searchedAddress = '';
                $this->Template->error = null;

            } elseif (!empty($_POST)) {
                // 2. Park wurde noch nicht angelegt, erstellung des parks mit $_POSTund dann aufruf der ergebnissseite mit plot id in url

                $parkid = $this->createPark($_POST);
                $rating = $this->getPlotRating($parkid);

                // Daten für die Speicherung vorbereiten
                $set = [
                    'tstamp' => time(),
                    'name' => $_POST['name'] ?? 'Testkunde',
                    'vorname' => $_POST['vorname'] ?? 'Muster',
                    'phone' => $_POST['phone'] ?? '1234567890',
                    'email' => $_POST['email'] ?? 'test@test.de',
                    'searched_address' => $_POST['searched_address'] ?? 'Teststraße 1, 12345 Teststadt',
                    'geometry' => $_POST['geometry'] ?? '',
                    'park_id' => $parkid,
                    'park_rating' => json_encode($rating),
                ];

                // Insert ausführen
                $insertId = Database::getInstance()
                    ->prepare("INSERT INTO tl_flaechencheck %s")
                    ->set($set)
                    ->execute()
                    ->insertId;

                // Nach dem Insert: Wenn parkid vorhanden, an die URL anhängen und weiterleiten
                if ($parkid != "-") {
                    $url = Environment::get('request'); // aktuelle URL inkl. Query-String
                    $url = preg_replace('/([&?])parkid=[^&]*/', '', $url); // alten parkid-Parameter entfernen
                    $url = rtrim($url, '&?');
                    $url .= (strpos($url, '?') === false ? '?' : '&') . 'parkid=' . urlencode($parkid);
                    $this->redirect($url);
                } else {
                    // Template-Variablen für Fehler setzen
                    $this->Template->isResult = true;
                    $this->Template->success = false;
                    $this->Template->error = 'Die Fläche konnte nicht bewertet werden.';
                    $this->Template->rating = null;
                    $this->Template->parkid = null;
                    $this->Template->searchedAddress = $_POST['searched_address'] ?? '';
                }

            } else {
                // 3. Kein Suche Keine ID

                // Redirect zur Startseite oder Fehlermeldung
                $this->redirect('/flaechencheck');
            }

            // Bei result: KEIN weiterer Code ausführen
            return;
        }

        if (Input::get('auto_item') == 'ersteinschaetzung') {

        }

        // Nur bei Map: Google Maps Variablen und normale Template-Variablen setzen
        $this->Template->isResult = false;
        $googleMapsApiKey = getenv('GOOGLE_MAPS_API_KEY') ?: ($_ENV['GOOGLE_MAPS_API_KEY'] ?? '');
        $googleMapsMapId = getenv('GOOGLE_MAPS_MAP_ID') ?: ($_ENV['GOOGLE_MAPS_MAP_ID'] ?? '');
        
        // Debug: Temporär die geladenen Werte loggen
        error_log("Google Maps API Key: " . ($googleMapsApiKey ? substr($googleMapsApiKey, 0, 10) . "..." : "LEER"));
        error_log("Google Maps Map ID: " . ($googleMapsMapId ?: "LEER"));
        
        $this->Template->googleMapsApiKey = $googleMapsApiKey;
        $this->Template->googleMapsMapId = $googleMapsMapId;
        $tokenManager = System::getContainer()->get('contao.csrf.token_manager');
        $this->Template->request_token = $tokenManager->getDefaultTokenValue();
    }
}

use TCPDF;

class WindenergiePDF extends TCPDF
{

    // Header
    public function Header()
    {
        // Keine Standard-Header auf Seite 1
        if ($this->getPage() == 1) {
            return;
        }
    }

    // Footer
    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0, 10, '© Caeli Wind GmbH  Impressum', 0, false, 'C');
    }
}
