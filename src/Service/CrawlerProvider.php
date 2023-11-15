<?php

declare(strict_types=1);

namespace App\Service;

use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CrawlerProvider
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
    )
    {
    }

    public function getCrawler(string $uri, string $baseUri = null): Crawler
    {
        static $crawlers = [];

        $url = $baseUri ?? '';
        $url .= $uri;

        if (!isset($crawlers[$url])) {
            $response = $this->httpClient->request('GET', $url);

            $status = $response->getStatusCode();

            if ($status !== 200) {
                throw new RuntimeException(sprintf('Non 200 status code received for "%s" url', $url));
            }

            $content = $response->getContent();

            $crawlers[$url] = new Crawler($content, baseHref: $baseUri);
        }

        return $crawlers[$url];
    }
}