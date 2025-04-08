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
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Exception\TransportExceptionInterface;

class GrokApiService
{
    private HttpClientInterface $httpClient;

    public function __construct(
        ?HttpClientInterface $httpClient = null,
        private readonly LoggerInterface $logger,
        private readonly int $apiTimeout,
        private readonly int $apiMaxTokens = 8000
    ) {
        $this->httpClient = $httpClient ?? HttpClient::create();
    }
 
    /**
     * Ruft die Grok API auf und gibt die Antwort zurück
     * Use configured maxTokens if not overridden in call
     */
    public function callApi(string $apiKey, string $apiEndpoint, string $prompt, float $temperature = 0.7, ?int $maxTokens = null, float $topP = 0.95): string
    {
        $fullEndpoint = rtrim($apiEndpoint, '/') . '/chat/completions';
        $this->logger->info('Calling Grok API', ['endpoint' => $fullEndpoint]);

        $requestMaxTokens = $maxTokens ?? $this->apiMaxTokens;
        $this->logger->debug('Using max_tokens for API call', ['max_tokens' => $requestMaxTokens]);

        try {
            $response = $this->httpClient->request('POST', $fullEndpoint, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Accept' => 'application/json',
                    'User-Agent' => 'Caeli-ContentCreator/0.2'
                ],
                'json' => [
                    'model' => 'grok-2-latest',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Du bist ein hilfreiches AI-Assistent. Erzeuge JSON-Antworten gemäß den Anweisungen im User-Prompt.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'temperature' => $temperature,
                    'max_tokens' => $requestMaxTokens,
                    'top_p' => $topP,
                ],
                'timeout' => $this->apiTimeout,
                'verify_peer' => true,
                'verify_host' => true
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                $this->logger->error('Grok API request failed with status code', [
                    'status_code' => $statusCode,
                    'response_headers' => $response->getHeaders(false),
                    'response_body_snippet' => substr($response->getContent(false), 0, 500)
                ]);
                throw new \RuntimeException("API-Anfrage fehlgeschlagen mit Status-Code: {$statusCode}");
            }

            $content = $response->getContent();
            $this->logger->debug('Grok API response received', ['response_snippet' => substr($content, 0, 200) . '...']);

            $responseData = json_decode($content, true);

            // **Workaround: Directly extract text content, assuming no JSON structure is needed from step 1**
            if (isset($responseData['choices'][0]['message']['content'])) {
                $messageContent = $responseData['choices'][0]['message']['content'];
                $this->logger->debug('Extracted raw text content from API response.', ['content_snippet' => substr($messageContent, 0, 200) . '...']);
                // Return the raw text directly
                return $messageContent;
            } else {
                 $this->logger->error('Could not find expected message content structure in API response.', [
                    'response_structure_keys' => is_array($responseData) ? array_keys($responseData) : null,
                    'response_content_snippet' => substr($content, 0, 500)
                 ]);
                 throw new \RuntimeException('Die API-Antwort enthielt nicht die erwartete Struktur (choices[0].message.content).');
            }

            /* // Remove all JSON parsing logic
            $trimmedContent = trim($messageContent);
            // ... (rest of the JSON parsing logic removed) ...
            throw new \RuntimeException('Konnte keinen validen JSON-Inhalt aus der API-Antwort extrahieren.');
            */

        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Grok API request transport error', ['exception' => $e]);
            throw new \RuntimeException('Fehler bei der API-Anfrage (Transport): ' . $e->getMessage(), $e->getCode(), $e);
        } catch (\Exception $e) {
            $this->logger->error('Grok API general error', ['exception' => $e]);
            throw new \RuntimeException('Allgemeiner Fehler bei der API-Verarbeitung: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }
}
