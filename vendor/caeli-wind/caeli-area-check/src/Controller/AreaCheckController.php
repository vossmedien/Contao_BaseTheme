<?php

namespace CaeliWind\CaeliAreaCheckBundle\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Legacy Controller - NUR für GET-Requests der Ergebnisseite
 * 
 * ⚠️ WICHTIG: POST-Requests (Flächencheck-Submissions) werden NICHT mehr hier verarbeitet!
 * 
 * Die synchrone Verarbeitung wurde entfernt weil:
 * - Sie blockierte alle anderen User für bis zu 90 Sekunden  
 * - Nur ~20-50 parallele User möglich waren
 * - Schlechte User Experience (keine Progress-Anzeige)
 * 
 * Stattdessen werden alle Submissions jetzt asynchron verarbeitet:
 * - AreaCheckMapController: /flaechencheck/ajax/start + /ajax/status
 * - Unlimitierte parallele User
 * - Sofortiges Feedback + Progress-Anzeige
 * 
 * Diese Klasse bleibt nur für Rückwärtskompatibilität bestehen.
 */
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

    /**
     * Legacy Route - NUR für GET-Requests (Ergebnisseite)
     * POST-Requests werden zur asynchronen Verarbeitung umgeleitet
     */
    #[Route('/flaechencheck/result', name: 'caeli_area_check_result', methods: ['GET', 'POST'])]
    public function result(Request $request): Response
    {
        // POST-Requests sind nicht mehr erlaubt - Weiterleitung zur asynchronen Verarbeitung
        if ($request->isMethod('POST')) {
            error_log('[FLAECHENCHECK] POST-Request auf Legacy-Route - leite zu async weiter');
            
            return $this->render('@CaeliAreaCheck/result.html.twig', [
                'success' => false,
                'error' => 'Diese Route wird nicht mehr für Submissions verwendet. Bitte nutzen Sie die neue Karte.',
                'rating' => null,
                'parkid' => null,
                'searchedAddress' => null,
            ]);
        }

        // GET-Requests für Rückwärtskompatibilität
        error_log('[FLAECHENCHECK] GET-Request auf Legacy-Route - nur für Anzeige');

        return $this->render('@CaeliAreaCheck/result.html.twig', [
            'success' => false,
            'error' => null,
            'rating' => null,
            'parkid' => null,
            'searchedAddress' => null,
        ]);
    }

    // Alle API-Methoden entfernt - werden nur noch in AreaCheckMapController (async) verwendet
    // - getApiSessionId()
    // - createPark() 
    // - getPlotRating()
    // - getRatingArea()
    // - getCoordinatesForMarker()
} 