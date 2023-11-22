<?php

declare(strict_types=1);

namespace App\Service;

use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HttpRequester
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
    )
    {
    }

    public function requestHttp(
        string $method,
        string $url,
        array $headers = null,
        array $query = null,
        array $body = null,
        array $json = null,
        float $timeout = 3.0,
        bool $user = false,
        bool $array = false
    ): string|array
    {
        $options = array_filter([
            'headers' => $headers,
            'query' => $query,
            'body' => $body,
            'json' => $json,
            'timeout' => $timeout,
        ]);

        if (!isset($options['headers'])) {
            $options['headers'] = [];
        }

        $options['headers']['User-Agent'] = $user
            ? 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_1_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Mobile/15E148 Safari/604.1'
            : 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.96 Mobile Safari/537.36 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';

        $response = $this->httpClient->request($method, $url, $options);

        $status = $response->getStatusCode();

        if ($status !== 200) {
            throw new RuntimeException(
                sprintf('%s status code received for "%s" url', $status, $url)
            );
        }

        return $array ? $response->toArray() : $response->getContent();
    }
}