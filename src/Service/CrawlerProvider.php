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

    public function getCrawler(string $uri, string $baseUri = null): Crawler
    {
        static $crawlers = [];

        $url = $baseUri ?? '';
        $url .= $uri;

        if (!isset($crawlers[$url])) {
            $content = $this->httpRequester->requestHttp('GET', $url);

            $crawlers[$url] = new Crawler($content, baseHref: $baseUri);
        }

        return $crawlers[$url];
    }
}