<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DomCrawler\Crawler;

class CrawlerProvider
{
    public function __construct(
        private readonly HttpRequester $httpRequester,
    )
    {
    }

    public function getCrawler(
        string $method,
        string $url,
        string $base = null,
        array $headers = null,
        array $query = null,
        array $body = null,
        array $json = null,
        float $timeout = 3.0,
        bool $user = false,
        bool $array = false
    ): Crawler
    {
        static $crawlers = [];

        if ($base !== null) {
            $url = $base . $url;
        }

        if (!isset($crawlers[$url])) {
            $content = $this->httpRequester->requestHttp(
                $method,
                $url,
                headers: $headers,
                query: $query,
                body: $body,
                json: $json,
                timeout: $timeout,
                user: $user,
                array: $array
            );

            $crawlers[$url] = new Crawler($content, baseHref: $base);
        }

        return $crawlers[$url];
    }
}