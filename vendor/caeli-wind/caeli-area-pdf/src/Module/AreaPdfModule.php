<?php

namespace CaeliWind\CaeliAreaPdfBundle\Module;

use Contao\Controller;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\Module;
use Contao\System;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Symfony\Component\Security\Csrf\CsrfToken;
use Doctrine\DBAL\Connection;
use Contao\Database;
use Contao\Environment;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Log\LoggerInterface;

class AreaPdfModule extends Module
{
    protected $strTemplate = 'mod_caeli_area_pdf';

    protected $api_url;
    protected $api_user;
    protected $api_pass;

    public function __construct($module, $column = 'main')
    {
        parent::__construct($module, $column);
        
        // Environment-Variablen laden
        $this->api_url = $_ENV['CAELI_INFRA_API_URL'] ?? '';
        $this->api_user = $_ENV['CAELI_INFRA_API_USERNAME'] ?? '';
        $this->api_pass = $_ENV['CAELI_INFRA_API_PASSWORD'] ?? '';
    }

    /**
     * Sichere Umgebungsvariablen-Behandlung
     */
    private function getEnvironmentVariable(string $key, string $default = ''): string
    {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }

    /**
     * UUID Validierung
     */
    private function isValidUuid(string $uuid): bool
    {
        return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid);
    }

    private function getApiSessionId() {

      $rootDir = System::getContainer()->getParameter('kernel.project_dir');

      $fields = json_encode(array(
        "email" =>  $this->api_user,
        "password" => $this->api_pass,
      ));

      $curl_session = curl_init();
      curl_setopt($curl_session ,CURLOPT_URL, $this->api_url."auth/login");
      curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($curl_session, CURLOPT_CUSTOMREQUEST, "POST");
      curl_setopt($curl_session, CURLOPT_POSTFIELDS, $fields);
      curl_setopt($curl_session, CURLOPT_COOKIEJAR, $rootDir."/system/tmp/".session_id().'.txt');
      curl_setopt($curl_session, CURLOPT_HTTPHEADER, array(
          'Content-Type: application/json')
      );
      $result = curl_exec($curl_session );

      /*
      if(curl_error($curl_session)) {
        dump(curl_error($curl_session));
      }
      */
      curl_close($curl_session);

      //$_SESSION['new_plot']['api_session'] = json_decode($result)->tokens->csrf_session_id;
      return json_decode($result)->tokens->csrf_session_id;
    }

    /**
     * Park erstellen mit verbessertem Error Handling
     */
    private function createPark(array $data): ?string
    {
        $rootDir = System::getContainer()->getParameter('kernel.project_dir');
        $api_session_id = $this->getApiSessionId();
        
        if (!$api_session_id) {
            return null;
        }

        $cookieFile = $rootDir . '/var/tmp/api_session_' . session_id() . '.txt';
        
        $postData = [
            'geometry' => json_decode($data['geometry']),
        ];

        $curl_session = curl_init();
        curl_setopt_array($curl_session, [
            CURLOPT_URL => $this->api_url . "wind/caeli/park",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_COOKIEJAR => $cookieFile,
            CURLOPT_COOKIEFILE => $cookieFile,
            CURLOPT_POSTFIELDS => json_encode($postData),
            CURLOPT_HTTPHEADER => [
                'X-CSRF-TOKEN: ' . $api_session_id,
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        
        $result = curl_exec($curl_session);
        $httpCode = curl_getinfo($curl_session, CURLINFO_HTTP_CODE);
        
        if (curl_error($curl_session)) {
            // Park creation cURL Error
            curl_close($curl_session);
            return null;
        }
        
        curl_close($curl_session);
        
        if ($httpCode !== 200) {
            // Park creation failed
            return null;
        }
        
        $response = json_decode($result);
        if ($response && $response->status === 'success' && isset($response->parks->id)) {
            return str_replace(["[", "]", "'"], ["", "", ""], $response->parks->id);
        }
        
        // Park creation failed
        return null;
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

    private function getPlotInfo($id)
    {
        $rootDir = System::getContainer()->getParameter('kernel.project_dir');
        $api_session_id = $this->getApiSessionId();

        $curl_session = curl_init();
        curl_setopt($curl_session, CURLOPT_URL, $this->api_url . "wind/caeli/park?" . http_build_query(['area_id' => $id]));
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
        if (json_decode($result)->status != 'fail') {
            return json_decode($result);
        }
    }

    /**
     * Google Static Map URL erstellen mit Error Handling
     */
    private function buildStaticMapUrl($geometry)
    {
        $apiKey = $_ENV['GOOGLE_MAPS_API_KEY']; // oder aus Config
        
        if (empty($apiKey)) {
            // Google Maps API Key not configured
            return null;
        }
        
        if (!isset($geometry->coordinates[0][0])) {
            // Invalid geometry structure for map generation
            return null;   
        }

        $polygon = $geometry->coordinates[0][0]; // [ [lng,lat], ... ]
        
        // Koordinaten in "lat,lng" umwandeln
        $path = [];
        $lats = [];
        $lngs = [];
        
        foreach ($polygon as $point) {
            if (!is_array($point) || count($point) < 2) {
                continue;
            }
            
            $lng = (float) $point[0];
            $lat = (float) $point[1];
            
            // Koordinaten-Validierung
            if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
                continue;
            }
            
            $path[] = $lat . ',' . $lng;
            $lats[] = $lat;
            $lngs[] = $lng;
        }
        
        if (empty($path)) {
            // No valid coordinates found for map generation
            return null;
        }
        
        // Mittelpunkt berechnen
        $centerLat = (min($lats) + max($lats)) / 2;
        $centerLng = (min($lngs) + max($lngs)) / 2;
        $pathStr = implode('|', $path);
        
        // URL sicher zusammenbauen
        $params = [
            'center' => "{$centerLat},{$centerLng}",
            'zoom' => '15',
            'size' => '600x400',
            'maptype' => 'satellite',
            'path' => "fillcolor:0x11363433|color:0xffff00ff|weight:3|{$pathStr}",
            'key' => $apiKey
        ];
        
        return "https://maps.googleapis.com/maps/api/staticmap?" . http_build_query($params);
    }

    protected function compile(): void
    {
        $parkid = Input::get('parkid');

        // Ohne parkid nichts machen (für Backend oder Frontend ohne Parameter)
        if (empty($parkid)) {
            return;
        }

        $rating = $this->getPlotRating($parkid);
        $plot_info = $this->getPlotInfo($parkid);
        $plot_from_db = Database::getInstance()->prepare("SELECT * FROM tl_flaechencheck WHERE park_id='" . $parkid . "'")->execute();

        $pdf = new \CaeliWind\CaeliAreaPdfBundle\Module\WindenergiePDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
        $pdf->SetCreator('CaeliWind');
        $pdf->SetAuthor('CaeliWind');
        $pdf->SetTitle($GLOBALS['TL_LANG']['caeli_area_pdf']['pdf']['document_title']);
        $pdf->SetSubject($GLOBALS['TL_LANG']['caeli_area_pdf']['pdf']['document_subject']);

// Set default font to Figtree
        $pdf->SetFont($pdf->fontRegular, '', 12);

// Remove default header/footer
        $pdf->setPrintHeader(true);
        $pdf->setPrintFooter(true);

// Set margins
        $pdf->SetMargins(20, 80, 20);
        $pdf->SetAutoPageBreak(TRUE, 0);

// Add a page
        $pdf->AddPage();
        $pdf->SetFillColor(17, 53, 52); // Dark green color
        $pdf->Rect(0, 0, 210, 297, 'F');
        // Logo aus dem Resources/public Verzeichnis einbinden
        $logoPath = __DIR__ . '/../Resources/public/caeliwind_pdf_logo.png';
        $logoWidth = 60; // oder deine Wunschbreite
        $logoX = 210 - $logoWidth - 10; // 10mm Abstand vom rechten Rand
        $pdf->Image($logoPath, $logoX, 15, $logoWidth, 0, 'PNG', '', '', true, 300, '', false, false, 0, false, false, false);

// Set font for main title
        $pdf->SetFont($pdf->fontRegular, '', 40);
        $pdf->SetTextColor(222, 238, 198); // #deeec6

// Main title
        $pdf->SetY(40);
        // Zeilenabstand reduzieren
        $pdf->setCellHeightRatio(1.25); // Standard ist 1.25

// Dann die MultiCell
        $pdf->MultiCell(0, 0, $GLOBALS['TL_LANG']['caeli_area_pdf']['pdf']['main_title_1'], 0, 'L', 0, 1, '', '', true);
        $pdf->MultiCell(0, 0, $GLOBALS['TL_LANG']['caeli_area_pdf']['pdf']['main_title_2'], 0, 'L', 0, 1, '', '', true);

// Subtitle
        $pdf->SetFont($pdf->fontRegular, '', 20);
        $pdf->Ln(10);
        $pdf->MultiCell(0, 10, $GLOBALS['TL_LANG']['caeli_area_pdf']['pdf']['subtitle'], 0, 'L', 0, 1, '', '', true);

// Map placeholder with white background
        $pdf->Ln(10);
        $mapX = 20;
        $mapY = $pdf->GetY();
        $mapWidth = 170;
        $mapHeight = 100;

// White rectangle for map
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect($mapX, $mapY, $mapWidth, $mapHeight, 'F');

        if (!empty($plot_info->property->geometry)) {
            $geometry = $plot_info->property->geometry;
            $staticMapUrl = $this->buildStaticMapUrl($geometry);

            $imageData = file_get_contents($staticMapUrl);
            if ($imageData === false) {
                throw new \Exception($GLOBALS['TL_LANG']['caeli_area_pdf']['api']['map_load_error']);
            }

            $tmpFile = tempnam(sys_get_temp_dir(), 'staticmap_') . '.png';
            file_put_contents($tmpFile, $imageData);

            // Im PDF platzieren (z.B. auf Seite 1)
            $pdf->Image($tmpFile, $mapX, $mapY, $mapWidth, $mapHeight, 'PNG');
            unlink($tmpFile);
        } else {
// Draw X lines for map placeholder
            $pdf->SetDrawColor(200, 200, 200);
            $pdf->Line($mapX, $mapY, $mapX + $mapWidth, $mapY + $mapHeight);
            $pdf->Line($mapX + $mapWidth, $mapY, $mapX, $mapY + $mapHeight);

            // Map label
            $pdf->SetTextColor(100, 100, 100);
            $pdf->SetFont($pdf->fontRegular, '', 14);
            $pdf->SetXY($mapX + ($mapWidth / 2) - 30, $mapY + ($mapHeight / 2) - 5);
            $pdf->Cell(60, 10, $GLOBALS['TL_LANG']['caeli_area_pdf']['pdf']['map_placeholder'], 0, 0, 'C');
        }

// Property data section
        $pdf->SetY($mapY + $mapHeight + 5);
        $dataBoxX = 20;
        $dataBoxY = $pdf->GetY() - 5;
        $dataBoxWidth = 140;
        $dataBoxHeight = 40;

// Light green background for data box
        $pdf->SetFillColor(222, 238, 198);
        $pdf->Rect($dataBoxX, $dataBoxY, $dataBoxWidth, $dataBoxHeight, 'F');

// Property details - mit MultiCell für bessere Kontrolle
        $pdf->SetTextColor(17, 53, 52);
        $pdf->SetFont($pdf->fontRegular, 'B', 12);
        $pdf->setCellHeightRatio(1.25);

// Position innerhalb der Box setzen
        $pdf->SetXY($dataBoxX + 10, $dataBoxY + 8); // 10mm Einrückung von links
        $pdf->MultiCell($dataBoxWidth - 20, 0, $GLOBALS['TL_LANG']['caeli_area_pdf']['pdf']['property_data_title'], 0, 'L', 0, 1, '', '', true);
        $pdf->SetFont($pdf->fontRegular, '', 12);
        $pdf->Ln(1);
        $pdf->SetX($dataBoxX + 10); // X-Position für jede Zeile setzen
        $pdf->MultiCell(0, 0, $GLOBALS['TL_LANG']['caeli_area_pdf']['pdf']['municipality'] . ' ' . $plot_info->gemeinde . ' ' . $GLOBALS['TL_LANG']['caeli_area_pdf']['pdf']['district'] . ' ' . $plot_info->landkreis . '', 0, 'L', 0, 1, '', '', true);
        $pdf->Ln(1);
        $pdf->SetX($dataBoxX + 10);
        $pdf->MultiCell($dataBoxWidth - 20, 0, $GLOBALS['TL_LANG']['caeli_area_pdf']['pdf']['area_size'] . ' ' . round($plot_info->property->properties->size_ha, 0) . ' ' . $GLOBALS['TL_LANG']['caeli_area_pdf']['pdf']['hectares'], 0, 'L', 0, 1, '', '', true);
        $pdf->Ln(1);
        $pdf->SetX($dataBoxX + 10);
        $pdf->MultiCell(0, 0, $GLOBALS['TL_LANG']['caeli_area_pdf']['pdf']['geo_id'] . ' ' . $parkid, 0, 'L', 0, 1, '', '', true);

        // Footer text
        $pdf->Ln(15);
        $pdf->SetFont($pdf->fontRegular, '', 10);
        $pdf->SetTextColor(222, 238, 198); // #deeec6
        if (!empty($plot_from_db->vorname)) {
            $pdf->Cell(0, 5, $GLOBALS['TL_LANG']['caeli_area_pdf']['pdf']['created_for'] . ' ' . $plot_from_db->vorname . ' ' . $plot_from_db->name . '', 0, 0, 'L');
        }
        $pdf->Ln(5); // 5mm Abstand statt Standard
        $pdf->Cell(0, 5, date($GLOBALS['TL_LANG']['caeli_area_pdf']['pdf']['date_format']), 0, 0, 'L');


//Sec Page
//Sec Page
//Sec Page

// Set margins
        $pdf->SetMargins(20, 50, 28);
        $pdf->SetAutoPageBreak(TRUE, 0);

// Add a page
        $pdf->AddPage();
        $startX = 20;
// Main title
        $pdf->SetX($startX);
        $pdf->SetFont($pdf->fontRegular, '', 32);
        $pdf->SetTextColor(17, 53, 52);
        $pdf->MultiCell(0, 0, $GLOBALS['TL_LANG']['caeli_area_pdf']['results']['result_title'] . "\n" . $GLOBALS['TL_LANG']['caeli_area_pdf']['results']['result_subtitle'], 0, 'L', 0, 1, '', '', true);

        $pdf->Ln(10);

// Subtitle
        $pdf->SetX($startX);
        $pdf->SetFont($pdf->fontBold, 'B', 16);
        $pdf->Cell(0, 10, $GLOBALS['TL_LANG']['caeli_area_pdf']['results']['congratulations'], 0, 1, 'L');

        $pdf->SetX($startX);
        $pdf->SetFont($pdf->fontRegular, '', 14);
        $pdf->MultiCell(0, 8, $GLOBALS['TL_LANG']['caeli_area_pdf']['results']['recommendation'], 0, 'L');

        $pdf->Ln(10);

// Windgegebenheiten section
// Checkmark als PNG-Bild
        $CheckmarkPath = __DIR__ . '/../Resources/public/check.png';
        $pdf->Image($CheckmarkPath, 33, $pdf->GetY(), 10, 10);

// Text
        $pdf->SetX(45);
        $pdf->SetFont($pdf->fontRegular, '', 16);
        $pdf->SetTextColor(17, 53, 52);
        $pdf->Cell(0, 10, $GLOBALS['TL_LANG']['caeli_area_pdf']['results']['wind_conditions_title'], 0, 1);

        $pdf->SetX(45);
        $pdf->SetFont($pdf->fontRegular, '', 12);
        $pdf->SetTextColor(17, 53, 52);
        $pdf->MultiCell(140, 6, sprintf($GLOBALS['TL_LANG']['caeli_area_pdf']['results']['wind_conditions_text'], $rating->range_cutdensity[0], $rating->range_cutdensity[1]), 0, 'L');

        $pdf->Ln(10);

// Restriktionen section
// Checkmark als PNG-Bild
        $CheckmarkPath = __DIR__ . '/../Resources/public/check.png';
        $pdf->Image($CheckmarkPath, 33, $pdf->GetY(), 10, 10);

        $pdf->SetX(45);
        $pdf->SetFont($pdf->fontRegular, '', 16);
        $pdf->SetTextColor(17, 53, 52);
        $pdf->Cell(0, 10, $GLOBALS['TL_LANG']['caeli_area_pdf']['results']['restrictions_title'], 0, 1);

        $pdf->SetX(45);
        $pdf->SetFont($pdf->fontRegular, '', 12);
        $pdf->SetTextColor(17, 53, 52);
        $pdf->MultiCell(140, 6, sprintf($GLOBALS['TL_LANG']['caeli_area_pdf']['results']['restrictions_text'], number_format($rating->range_restrictions[1], 0, ",", ".")), 0, 'L');

        $pdf->Ln(10);

// Netzanschluss section
// Checkmark als PNG-Bild
        $CheckmarkPath = __DIR__ . '/../Resources/public/check.png';
        $pdf->Image($CheckmarkPath, 33, $pdf->GetY(), 10, 10);

        $pdf->SetX(45);
        $pdf->SetFont($pdf->fontRegular, '', 16);
        $pdf->SetTextColor(17, 53, 52);
        $pdf->Cell(0, 10, $GLOBALS['TL_LANG']['caeli_area_pdf']['results']['grid_connection_title'], 0, 1);

        $pdf->SetX(45);
        $pdf->SetFont($pdf->fontRegular, '', 12);
        $pdf->SetTextColor(17, 53, 52);
        $pdf->MultiCell(140, 6, sprintf($GLOBALS['TL_LANG']['caeli_area_pdf']['results']['grid_connection_text'], number_format($rating->range_grid_connection[0], 0, ",", "."), number_format($rating->range_grid_connection[1], 0, ",", ".")), 0, 'L');

        $pdf->Ln(15);

// Hinweis Box
        $pdf->SetFillColor(17, 53, 52); // Dark green background
        $pdf->SetTextColor(222, 238, 198); // #deeec6
        $pdf->Rect(20, $pdf->GetY() - 5, 170, 25, 'F');


        $pdf->SetY($pdf->GetY());
        $pdf->SetX(28);
        $pdf->SetFont($pdf->fontRegular, '', 10);
        $pdf->MultiCell(0, 6, $GLOBALS['TL_LANG']['caeli_area_pdf']['results']['disclaimer'], 0, 'L', false);

        $pdf->Ln(30);

        $pdf->SetX(20);
        $pdf->SetFont($pdf->fontRegular, '', 11);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(50, 5, $GLOBALS['TL_LANG']['caeli_area_pdf']['results']['copyright'], 0, 0, 'L');
        $pdf->SetFont($pdf->fontRegular, '', 11);
        
        // Dynamischer Impressums-Link basierend auf der Sprache
        $imprintUrl = ($GLOBALS['TL_LANGUAGE'] === 'en') ? 'https://www.caeli-wind.de/en/imprint' : 'https://www.caeli-wind.de/impressum';
        $pdf->Cell(50, 5, $GLOBALS['TL_LANG']['caeli_area_pdf']['results']['imprint'], 0, 0, 'L', false, $imprintUrl);


// Page three
// Page three
// Page three
// Add new page
        $pdf->AddPage();

// Main title
        $pdf->SetFont($pdf->fontRegular, '', 36);
        $pdf->SetTextColor(17, 53, 52);
        $pdf->Cell(0, 20, $GLOBALS['TL_LANG']['caeli_area_pdf']['steps']['title'], 0, 1, 'L');

        $pdf->Ln(10);

// Subtitle
        $pdf->SetFont($pdf->fontRegular, 'B', 18);
        $pdf->MultiCell(0, 10, $GLOBALS['TL_LANG']['caeli_area_pdf']['steps']['subtitle_1'] . $GLOBALS['TL_LANG']['caeli_area_pdf']['steps']['subtitle_2'], 0, 'L');

        $pdf->Ln(10);

// Schritt 1
        $pdf->SetFont($pdf->fontRegular, '', 18);
        $pdf->SetTextColor(139, 167, 255); // Blue color
        $pdf->Cell(0, 7, $GLOBALS['TL_LANG']['caeli_area_pdf']['steps']['step_1_title'], 0, 1, 'L');

        $pdf->SetFont($pdf->fontRegular, '', 12);
        $pdf->SetTextColor(17, 53, 52);
        $pdf->MultiCell(0, 7, $GLOBALS['TL_LANG']['caeli_area_pdf']['steps']['step_1_text'], 0, 'L');

        $pdf->Ln(8);

// Schritt 2
        $pdf->SetFont($pdf->fontRegular, '', 18);
        $pdf->SetTextColor(139, 167, 255);
        $pdf->Cell(0, 10, $GLOBALS['TL_LANG']['caeli_area_pdf']['steps']['step_2_title'], 0, 1, 'L');

        $pdf->SetFont($pdf->fontRegular, '', 12);
        $pdf->SetTextColor(17, 53, 52);
        $pdf->MultiCell(0, 7, $GLOBALS['TL_LANG']['caeli_area_pdf']['steps']['step_2_text'], 0, 'L');

        $pdf->Ln(8);

// Schritt 3
        $pdf->SetFont($pdf->fontRegular, '', 18);
        $pdf->SetTextColor(139, 167, 255);
        $pdf->Cell(0, 10, $GLOBALS['TL_LANG']['caeli_area_pdf']['steps']['step_3_title'], 0, 1, 'L');

        $pdf->SetFont($pdf->fontRegular, '', 12);
        $pdf->SetTextColor(17, 53, 52);
        $pdf->MultiCell(0, 0, $GLOBALS['TL_LANG']['caeli_area_pdf']['steps']['step_3_text'], 0, 'L');

        $pdf->Ln(15);

// Dark box with property data
        $pdf->SetFillColor(17, 53, 52); // Dark green background
        $pdf->SetTextColor(222, 238, 198); // #deeec6
        $pdf->Rect(20, $pdf->GetY() - 5, 170, 43, 'F');

        $pdf->SetY($pdf->GetY() + 3);
        $pdf->SetX(30);
        $pdf->SetFont($pdf->fontBold, 'B', 14);
        $pdf->SetTextColor(222, 238, 198); // Light green
        $pdf->Cell(0, 0, $GLOBALS['TL_LANG']['caeli_area_pdf']['steps']['property_data_title'], 0, 1, 'L');

        $pdf->SetX(30);
        $pdf->SetFont($pdf->fontRegular, '', 14);
        $pdf->SetTextColor(222, 238, 198); // Light green
        $pdf->Cell(0, 7, $GLOBALS['TL_LANG']['caeli_area_pdf']['pdf']['municipality'] . ' ' . $plot_info->gemeinde . ' ' . $GLOBALS['TL_LANG']['caeli_area_pdf']['pdf']['district'] . ' ' . $plot_info->landkreis . '', 0, 1, 'L');

        $pdf->SetX(30);
        $pdf->Cell(0, 7, $GLOBALS['TL_LANG']['caeli_area_pdf']['pdf']['area_size'] . ' ' . round($plot_info->property->properties->size_ha, 0) . ' ' . $GLOBALS['TL_LANG']['caeli_area_pdf']['pdf']['hectares'], 0, 1, 'L');

        $pdf->SetX(30);
        $pdf->Cell(0, 7, $GLOBALS['TL_LANG']['caeli_area_pdf']['pdf']['geo_id'] . ' ' . $parkid, 0, 1, 'L');

        $pdf->Ln(22);

        $pdf->SetX(20);
        $pdf->SetFont($pdf->fontRegular, '', 11);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(50, 5, $GLOBALS['TL_LANG']['caeli_area_pdf']['results']['copyright'], 0, 0, 'L');
        $pdf->SetFont($pdf->fontRegular, '', 11);
        
        // Dynamischer Impressums-Link basierend auf der Sprache
        $imprintUrl = ($GLOBALS['TL_LANGUAGE'] === 'en') ? 'https://www.caeli-wind.de/en/imprint' : 'https://www.caeli-wind.de/impressum';
        $pdf->Cell(50, 5, $GLOBALS['TL_LANG']['caeli_area_pdf']['results']['imprint'], 0, 0, 'L', false, $imprintUrl);

        // Dynamischer Dateiname basierend auf der Sprache
        $filename = ($GLOBALS['TL_LANGUAGE'] === 'en') ? 'caeli-wind-site-check.pdf' : 'caeli-wind-flaechencheck.pdf';

// Output the PDF
        $pdf->Output($filename, 'I');


        //$pdf->genPDF($parkid,"","","","");
        exit;

    }
}

use TCPDF;
use TCPDF_FONTS;

class WindenergiePDF extends TCPDF
{
    public $fontRegular = 'helvetica';
    public $fontBold = 'helvetica';

    public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4', $unicode = true, $encoding = 'UTF-8', $diskcache = false)
    {
        parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache);

        // Try to add Figtree fonts
        $this->addFigtreeFonts();
    }

    private function addFigtreeFonts()
    {
        // Check if font files exist and add them
        $fontPath = __DIR__ . '/../Resources/public/fonts/';

        if (file_exists($fontPath . 'Figtree-Regular.ttf')) {
            try {
                $this->fontRegular = TCPDF_FONTS::addTTFfont($fontPath . 'Figtree-Regular.ttf', 'TrueTypeUnicode', '', 96);
            } catch (Exception $e) {
                // Fallback to helvetica if font addition fails
                $this->fontRegular = 'helvetica';
            }
        }

        if (file_exists($fontPath . 'Figtree-Bold.ttf')) {
            try {
                $this->fontBold = TCPDF_FONTS::addTTFfont($fontPath . 'Figtree-Bold.ttf', 'TrueTypeUnicode', '', 96);
            } catch (Exception $e) {
                // Fallback to helvetica bold if font addition fails
                $this->fontBold = 'helvetica';
            }
        }
    }

    // Page header
    public function Header()
    {
        // Set background color for header
        $this->SetFillColor(255, 255, 255);
        $this->Rect(0, 0, 210, 297, 'F');

        // Logo aus dem Resources/public Verzeichnis einbinden
        $logoPath = __DIR__ . '/../Resources/public/caeliwind_pdf_logo_w.png';
        $logoWidth = 60; // oder deine Wunschbreite
        $logoX = 210 - $logoWidth - 10; // 10mm Abstand vom rechten Rand
        $this->Image($logoPath, $logoX, 15, $logoWidth, 0, 'PNG', '', '', true, 300, '', false, false, 0, false, false, false);

    }

    // Page footer
    public function Footer()
    {
        // Position at 15 mm from bottom
        //$this->SetY(-20);
    }

}