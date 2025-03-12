<?php

declare(strict_types=1);

/*
 * This file is part of Caeli KI Content-Creator.
 *
 * (c) Caeli Wind - Christian Voss 2025 <christian.voss@caeli-wind.de>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/caeli-wind/contao-caeli-content-creator
 */

namespace CaeliWind\ContaoCaeliContentCreator\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GrokApiService
{
    private HttpClientInterface $httpClient;

    public function __construct(?HttpClientInterface $httpClient = null)
    {
        $this->httpClient = $httpClient ?? HttpClient::create();
    }

    /**
     * Ruft die Grok API auf und gibt die Antwort zurück
     */
    public function callApi(string $apiKey, string $apiEndpoint, string $prompt, float $temperature = 0.7, int $maxTokens = 8000, float $topP = 0.95): string
    {
        try {
            // Stellen Sie sicher, dass der Endpunkt richtig formatiert ist
            $fullEndpoint = rtrim($apiEndpoint, '/') . '/chat/completions';
            
            $response = $this->httpClient->request('POST', $fullEndpoint, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Accept' => 'application/json',
                    'User-Agent' => 'Caeli-ContentCreator/0.1'
                ],
                'json' => [
                    'model' => 'grok-2-latest', // Aktualisiert auf das aktuelle Modell
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Du bist ein professioneller Content-Writer für Blogs im Bereich Windenergie und erneuerbare Energien. Erzeuge stets ein valides JSON mit den Feldern title, teaser, content und tags. Folge exakt den Formatierungsanweisungen im Benutzer-Prompt.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'temperature' => $temperature,
                    'max_tokens' => $maxTokens,
                    'top_p' => $topP,
                ],
                'timeout' => 60,
                // TLS/SSL-Optionen
                'verify_peer' => true,
                'verify_host' => true
            ]);

            $content = $response->getContent();
            
            // Debug-Information
            $logFile = sys_get_temp_dir() . '/grok-api-debug.log';
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - API-Anfrage an: " . $fullEndpoint . "\n", FILE_APPEND);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - API-Antwort: " . substr($content, 0, 1000) . "...\n", FILE_APPEND);
            
            // Antwort parsen
            $responseData = json_decode($content, true);
            if (isset($responseData['choices'][0]['message']['content'])) {
                $messageContent = $responseData['choices'][0]['message']['content'];
                
                // Log für Debug-Zwecke
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Message Content: " . substr($messageContent, 0, 500) . "...\n", FILE_APPEND);
                
                // Überprüfen, ob die Antwort bereits gültiges JSON ist oder nur Rohtext
                if (preg_match('/^\s*\{.*\}\s*$/s', $messageContent)) {
                    return $messageContent; // Antwort ist bereits ein JSON-Objekt
                }
                
                // Versuchen, JSON aus einer Code-Markierung zu extrahieren
                if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/s', $messageContent, $matches)) {
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - JSON aus Code-Block extrahiert\n", FILE_APPEND);
                    return $matches[1];
                }
                
                // Fallback: Versuchen, ein JSON-Objekt irgendwo im Text zu finden
                if (preg_match('/\{[\s\S]*?"title"[\s\S]*?\}/', $messageContent, $matches)) {
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - JSON aus Rohtext extrahiert\n", FILE_APPEND);
                    return $matches[0];
                }
                
                return $messageContent;
            }
            
            return $content;
        } catch (\Exception $e) {
            $logFile = sys_get_temp_dir() . '/grok-api-error.log';
            file_put_contents(
                $logFile, 
                date('Y-m-d H:i:s') . " - Fehler bei API-Anfrage: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n\n", 
                FILE_APPEND
            );
            throw new \RuntimeException('Fehler bei der API-Anfrage: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }
} 