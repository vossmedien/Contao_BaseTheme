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
namespace Vsm\VsmHelperTools\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class PlunkService
{
    private string $publicKey;
    private string $secretKey;
    private HttpClientInterface $client;
    private LoggerInterface $logger;

    public function __construct(
        string              $publicKey,
        string              $secretKey,
        HttpClientInterface $client,
        ?LoggerInterface    $logger = null
    )
    {
        $this->publicKey = $publicKey;
        $this->secretKey = $secretKey;
        $this->client = $client;
        $this->logger = $logger ?? new NullLogger();
    }

 public function trackEvent(string $eventName, array $data): bool
{
    try {
        // Wenn die E-Mail in der obersten Ebene ist
        if (isset($data['email']) && !isset($data['data'])) {
            $payload = [
                'event' => $eventName,
                'email' => $data['email'],
                'data' => array_map('strval', array_filter($data, function ($key) {
                    return $key !== 'email';
                }, ARRAY_FILTER_USE_KEY))
            ];
        } // Wenn die Daten im 'data' Unterobjekt sind
        else if (isset($data['email']) && isset($data['data'])) {
            $payload = [
                'event' => $eventName,
                'email' => $data['email'],
                'data' => array_map('strval', $data['data'])
            ];
        } else {
            $this->logger->error('Email is required for Plunk event', [
                'eventName' => $eventName,
                'data' => $data
            ]);
            throw new \Exception('Email is required');
        }

        $this->logger->debug('Sending Plunk request', [
            'payload' => $payload,
            'url' => 'https://api.useplunk.com/v1/track',
            'event' => $eventName
        ]);

        $response = $this->client->request('POST', 'https://api.useplunk.com/v1/track', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ],
            'json' => $payload
        ]);

        $statusCode = $response->getStatusCode();
        $content = $response->getContent(false);

        $this->logger->debug('Plunk API response', [
            'statusCode' => $statusCode,
            'content' => $content,
            'event' => $eventName,
            'email' => $data['email']
        ]);

        if ($statusCode !== 200) {
            throw new \Exception('Plunk API returned status ' . $statusCode . ': ' . $content);
        }

        return true;
    } catch (\Exception $e) {
        $this->logger->error('Plunk API Error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'event' => $eventName,
            'email' => $data['email'] ?? 'unknown'
        ]);
        return false;
    }
}
}