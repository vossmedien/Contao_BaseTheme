<?php

namespace CaeliWind\CaeliAreaCheckBundle\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AreaCheckController extends AbstractController
{
    private Connection $connection;
    private string $api_url;
    private string $api_user;
    private string $api_pass;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->api_url = getenv('CAELI_INFRA_API_URL') ?: "https://infra.caeli-wind.de/api/";
        $this->api_user = getenv('CAELI_INFRA_API_USERNAME') ?: "website-2025@caeli-wind.de";
        $this->api_pass = getenv('CAELI_INFRA_API_PASSWORD') ?: "0wuvgvh>LrpB(ef-";
        
        // Debug: Temporär die geladenen Werte loggen
        error_log("API URL: " . $this->api_url);
        error_log("API User: " . $this->api_user);
        error_log("API Pass: " . substr($this->api_pass, 0, 5) . "...");
    }

    #[Route('/flaechencheck/result', name: 'caeli_area_check_result', methods: ['GET', 'POST'])]
    public function result(Request $request): Response
    {
        $success = false;
        $error = null;
        $rating = null;
        $parkid = null;
        $searchedAddress = null;
        
        if ($request->isMethod('POST')) {
            try {
                $parkid = $this->createPark($request->request->all());
                $rating = $this->getPlotRating($parkid);
                $searchedAddress = $request->request->get('searched_address', '');

                $data = [
                    'tstamp'            => time(),
                    'name'              => $request->request->get('name', 'Testkunde'),
                    'vorname'           => $request->request->get('vorname', 'Muster'),
                    'phone'             => $request->request->get('phone', '1234567890'),
                    'email'             => $request->request->get('email', 'test@test.de'),
                    'searched_address'  => $searchedAddress,
                    'geometry'          => $request->request->get('geometry', ''),
                    'park_id'           => $parkid,
                    'park_rating'       => json_encode($rating),
                    'status'            => 'success',
                ];

                $this->connection->insert('tl_flaechencheck', $data);
                $success = true;
            } catch (\Throwable $e) {
                $error = $e->getMessage();
                
                // Auch bei Fehlern versuchen wir ein Rating zu bekommen
                // ähnlich wie im alten Modul
                try {
                    $geometry = $request->request->get('geometry', '');
                    if ($geometry) {
                        $geometryData = json_decode($geometry, true);
                        if (isset($geometryData['geometry']['coordinates'][0][0])) {
                            // Erste Koordinate aus dem Polygon extrahieren
                            $coords = $geometryData['geometry']['coordinates'][0][0];
                            $longitude = $coords[0];
                            $latitude = $coords[1];
                            $size_ha = $request->request->get('size_ha', 3);
                            
                            $rating = $this->getRatingArea($longitude, $latitude, $size_ha);
                            $searchedAddress = $request->request->get('searched_address', '');
                            
                            // Auch bei ungültigen Flächen die Daten speichern
                            $data = [
                                'tstamp'            => time(),
                                'name'              => $request->request->get('name', 'Testkunde'),
                                'vorname'           => $request->request->get('vorname', 'Muster'),
                                'phone'             => $request->request->get('phone', '1234567890'),
                                'email'             => $request->request->get('email', 'test@test.de'),
                                'searched_address'  => $searchedAddress,
                                'geometry'          => $geometry,
                                'park_id'           => null,
                                'park_rating'       => json_encode($rating),
                                'status'            => 'failed_with_rating',
                                'error_message'     => $error,
                            ];
                            
                            $this->connection->insert('tl_flaechencheck', $data);
                            $success = true; // Setzen auf true, da wir Rating haben
                        }
                    }
                } catch (\Throwable $ratingException) {
                    // Falls auch das Rating fehlschlägt, ursprünglichen Fehler beibehalten
                    error_log('Rating-Fehler: ' . $ratingException->getMessage());
                }
            }
        }

        return $this->render('@CaeliAreaCheck/result.html.twig', [
            'success' => $success,
            'error' => $error,
            'rating' => $rating,
            'parkid' => $parkid,
            'searchedAddress' => $searchedAddress,
        ]);
    }

    private function getApiSessionId(): ?string
    {
        $fields = json_encode([
            "email" =>  $this->api_user,
            "password" => $this->api_pass,
        ]);

        $curl_session = curl_init();
        curl_setopt($curl_session ,CURLOPT_URL, $this->api_url."auth/login");
        curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl_session, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl_session, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($curl_session, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        $result = curl_exec($curl_session);
        curl_close($curl_session);
        $json = json_decode($result);
        return $json->tokens->csrf_session_id ?? null;
    }

    private function createPark(array $data)
    {
        $api_session_id = $this->getApiSessionId();
        if (!$api_session_id) {
            throw new \RuntimeException('API-Session konnte nicht erstellt werden.');
        }
        $data['geometry'] = $data['geometry'] ?? '{"geometry":{"coordinates":[[[8.147900888731186,52.232090618982824],[8.149789166524073,52.22536137556068],[8.17387184960154,52.226623186819864],[8.182197157783861,52.229199273847854],[8.181534955077865,52.236724393655976],[8.179732510797493,52.242164146228674],[8.147900888731186,52.232090618982824]]],"type":"Polygon"}}';
        $postData = [
            'geometry' => json_decode($data['geometry']),
        ];

        $curl_session = curl_init();
        curl_setopt($curl_session ,CURLOPT_URL, $this->api_url."wind/caeli/park");
        curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl_session, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl_session, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($curl_session, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl_session, CURLOPT_HTTPHEADER, [
            'X-CSRF-TOKEN: '.$api_session_id,
            'Content-Type: application/json'
        ]);
        $result = curl_exec($curl_session);
        curl_close($curl_session);
        $json = json_decode($result);
        if(isset($json->status) && $json->status == 'success') {
            return str_replace(["[", "]", "'"], ["", "", ""], $json->parks->id);
        } else {
            throw new \RuntimeException('Park konnte nicht erstellt werden: '.($json->message ?? 'Unbekannter Fehler'));
        }
    }

    private function getPlotRating($id)
    {
        $api_session_id = $this->getApiSessionId();
        if (!$api_session_id) {
            throw new \RuntimeException('API-Session konnte nicht erstellt werden.');
        }
        $curl_session = curl_init();
        curl_setopt($curl_session ,CURLOPT_URL, $this->api_url."wind/caeli/rating?".http_build_query([
            'area_id'=>$id
        ]));
        curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl_session, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($curl_session, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl_session, CURLOPT_HTTPHEADER, [
            'X-CSRF-TOKEN: '.$api_session_id,
            'Content-Type: application/json'
        ]);
        $result = curl_exec($curl_session);
        curl_close($curl_session);
        return json_decode($result);
    }

    private function getRatingArea($longitude, $latitude, $size_ha = 3)
    {
        $api_session_id = $this->getApiSessionId();
        if (!$api_session_id) {
            throw new \RuntimeException('API-Session konnte nicht erstellt werden.');
        }

        // Geometrie aus Koordinaten erstellen (ähnlich wie im alten Modul)
        $coordinates = $this->getCoordinatesForMarker($longitude, $latitude, $size_ha);
        
        $postData = [
            'geometry' => $coordinates,
        ];

        $curl_session = curl_init();
        curl_setopt($curl_session, CURLOPT_URL, $this->api_url."wind/caeli/ratingArea");
        curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl_session, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl_session, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($curl_session, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl_session, CURLOPT_HTTPHEADER, [
            'X-CSRF-TOKEN: '.$api_session_id,
            'Content-Type: application/json'
        ]);
        $result = curl_exec($curl_session);
        curl_close($curl_session);

        return json_decode($result);
    }

    private function getCoordinatesForMarker($longitude, $latitude, $size_ha = 3)
    {
        // Einfache Polygon-Erzeugung um den Mittelpunkt
        // Basierend auf der size_ha einen groben Radius berechnen
        $radius = sqrt($size_ha * 10000) / 111320; // Grobe Umrechnung Hektar zu Grad
        
        return [
            "type" => "Polygon",
            "coordinates" => [[
                [$longitude - $radius, $latitude - $radius],
                [$longitude + $radius, $latitude - $radius], 
                [$longitude + $radius, $latitude + $radius],
                [$longitude - $radius, $latitude + $radius],
                [$longitude - $radius, $latitude - $radius]
            ]]
        ];
    }
} 