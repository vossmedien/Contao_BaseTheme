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
        private readonly LoggerInterface $logger,
        private readonly int $apiTimeout,
        ?HttpClientInterface $httpClient = null,
        private readonly int $apiMaxTokens = 8000
    ) {
        $this->httpClient = $httpClient ?? HttpClient::create();
    }

    /**
     * Ruft die Grok API auf und gibt die Antwort zurück
     */
    public function callApi(string $apiKey, string $apiEndpoint, string $prompt, float $temperature = 0.7, ?int $maxTokens = null, float $topP = 0.95): string
    {
        return $this->callApiWithRetry($apiKey, $apiEndpoint, $prompt, $temperature, $maxTokens, $topP, 3);
    }

    /**
     * API-Aufruf mit Retry-Pattern und exponential backoff
     */
    private function callApiWithRetry(string $apiKey, string $apiEndpoint, string $prompt, float $temperature, ?int $maxTokens, float $topP, int $maxRetries): string
    {
        $fullEndpoint = rtrim($apiEndpoint, '/') . '/chat/completions';
        $requestMaxTokens = $maxTokens ?? $this->apiMaxTokens;
        
        $this->logger->info('Calling Grok API with retry pattern', [
            'endpoint' => $fullEndpoint,
            'max_tokens' => $requestMaxTokens,
            'temperature' => $temperature,
            'max_retries' => $maxRetries,
            'timeout' => $this->apiTimeout,
            'reasoning_effort' => 'high'
        ]);

        $lastException = null;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $this->logger->debug('API call attempt', ['attempt' => $attempt]);

                $response = $this->httpClient->request('POST', $fullEndpoint, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Accept' => 'application/json',
                        'User-Agent' => 'Caeli-ContentCreator/0.3'
                    ],
                    'json' => [
                        'model' => 'grok-3-beta',
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => 'Du bist ein hilfreicher AI-Assistent, spezialisiert auf die Erstellung von Blogbeiträgen für Caeli Wind im Bereich Windenergie. Halte dich strikt an die Anweisungen im User-Prompt, insbesondere bezüglich Struktur, Tonalität, SEO-Keywords und Mindestlänge. Erzeuge qualitativ hochwertige, informative und ansprechende Inhalte. Wenn nach Quellen gefragt wird, gib diese bitte an.'
                            ],
                            [
                                'role' => 'user',
                                'content' => $prompt
                            ]
                        ],
                        'temperature' => $temperature,
                        'max_tokens' => $requestMaxTokens,
                        'top_p' => $topP,
                        'top_k' => 40,
                        'repetition_penalty' => 1.1,
                        'reasoning' => [
                            'effort' => 'medium',
                            'exclude' => false
                        ],
                        'stop' => null
                    ],
                    'timeout' => $this->apiTimeout,
                    'verify_peer' => true,
                    'verify_host' => true
                ]);

                $statusCode = $response->getStatusCode();
                if ($statusCode !== 200) {
                    throw new \RuntimeException("API-Anfrage fehlgeschlagen mit Status-Code: {$statusCode}");
                }

                $content = $response->getContent();
                $this->logger->debug('API call successful', [
                    'attempt' => $attempt,
                    'response_length' => strlen($content)
                ]);

                $responseData = json_decode($content, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \RuntimeException('Fehler beim Dekodieren der API-Antwort: ' . json_last_error_msg());
                }

                return $this->extractMessageContent($responseData);

            } catch (TransportExceptionInterface $e) {
                $lastException = $e;
                $this->logger->warning('API transport error on attempt', [
                    'attempt' => $attempt,
                    'max_retries' => $maxRetries,
                    'error' => $e->getMessage()
                ]);

                // Bei letztem Versuch: Exception werfen
                if ($attempt === $maxRetries) {
                    break;
                }

                // Exponential backoff: 1s, 2s, 4s...
                $backoffSeconds = min(pow(2, $attempt - 1), 10);
                $this->logger->info('Retrying API call after backoff', [
                    'backoff_seconds' => $backoffSeconds,
                    'next_attempt' => $attempt + 1
                ]);
                sleep($backoffSeconds);

            } catch (\Exception $e) {
                $lastException = $e;
                $this->logger->error('API call failed on attempt', [
                    'attempt' => $attempt,
                    'error' => $e->getMessage()
                ]);

                // Bei Server-Fehlern (5xx) retry, bei Client-Fehlern (4xx) nicht
                if ($this->shouldRetry($e, $attempt, $maxRetries)) {
                    $backoffSeconds = min(pow(2, $attempt - 1), 10);
                    sleep($backoffSeconds);
                    continue;
                }

                // Sofort abbrechen bei nicht-retryable Fehlern
                break;
            }
        }

        // Alle Versuche fehlgeschlagen
        $this->logger->error('All API retry attempts failed', [
            'max_retries' => $maxRetries,
            'last_error' => $lastException ? $lastException->getMessage() : 'Unknown error'
        ]);

        if ($lastException instanceof TransportExceptionInterface) {
            throw new \RuntimeException('Fehler bei der API-Anfrage (Transport): ' . $lastException->getMessage(), $lastException->getCode(), $lastException);
        }

        throw new \RuntimeException('API-Aufruf nach ' . $maxRetries . ' Versuchen fehlgeschlagen: ' . ($lastException ? $lastException->getMessage() : 'Unbekannter Fehler'));
    }

    /**
     * Prüft, ob ein Retry sinnvoll ist
     */
    private function shouldRetry(\Exception $e, int $attempt, int $maxRetries): bool
    {
        if ($attempt >= $maxRetries) {
            return false;
        }

        $message = $e->getMessage();
        
        // Retry bei Timeout/Netzwerkfehlern
        if (str_contains($message, 'timeout') || str_contains($message, 'network')) {
            return true;
        }

        // Retry bei 5xx Server-Fehlern
        if (preg_match('/Status-Code: 5\d\d/', $message)) {
            return true;
        }

        // Retry bei Rate Limiting
        if (str_contains($message, '429') || str_contains($message, 'rate limit')) {
            return true;
        }

        // Kein Retry bei 4xx Client-Fehlern (außer 429)
        return false;
    }

    /**
     * Extrahiert den Message-Content aus der API-Response
     */
    private function extractMessageContent(array $responseData): string
    {
        if (!isset($responseData['choices'][0]['message']['content'])) {
            $this->logger->error('Invalid API response structure', [
                'available_keys' => array_keys($responseData)
            ]);
            throw new \RuntimeException('Die API-Antwort enthielt nicht die erwartete Struktur (choices[0].message.content).');
        }

        $messageContent = $responseData['choices'][0]['message']['content'];
        
        $this->logger->debug('Message content extracted', [
            'content_length' => strlen($messageContent)
        ]);

        return $messageContent;
    }
}
