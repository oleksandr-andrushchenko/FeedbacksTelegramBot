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