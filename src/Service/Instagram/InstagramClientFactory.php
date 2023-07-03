<?php

declare(strict_types=1);

namespace App\Service\Instagram;

use App\Entity\Instagram\InstagramOptions;
use Instagram\SDK\Instagram as InstagramClient;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;

class InstagramClientFactory
{
    public function __construct(
        private readonly CacheInterface $cache,
    )
    {
    }

    /**
     * @see https://github.com/NicklasWallgren/instagram-api
     * @see https://github.com/NicklasWallgren/instagram-api/blob/master/examples/authenticate_using_session.php
     * @param InstagramOptions $instagramOptions
     * @return InstagramClient
     * @throws InvalidArgumentException
     */
    public function createInstagramClient(InstagramOptions $instagramOptions): InstagramClient
    {
        $clientBuilder = InstagramClient::builder();

        $sessionCacheKey = sprintf('instagram_session.%s', $instagramOptions->getUsername());

        $client = null;

        $session = $this->cache->get($sessionCacheKey, function () use ($clientBuilder, $instagramOptions, &$client) {
            $client = $clientBuilder->build();
            $response = $client->login($instagramOptions->getUsername(), $instagramOptions->getPassword());

            return $response->getSession();
        });

        if ($client === null) {
            $client = $clientBuilder->setSession($session)->build();
        }

        return $client;
    }
}