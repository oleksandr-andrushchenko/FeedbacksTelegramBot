<?php

declare(strict_types=1);

namespace App\Service;

use GuzzleHttp\Client;

class HttpClientFactory
{
    public function createHttpClient(array $config = []): Client
    {
        return new Client($config);
    }
}