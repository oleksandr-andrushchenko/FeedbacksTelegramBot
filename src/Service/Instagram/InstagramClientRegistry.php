<?php

declare(strict_types=1);

namespace App\Service\Instagram;

use App\Entity\Instagram\InstagramOptions;
use App\Enum\Instagram\InstagramName;
use Instagram\SDK\Instagram as InstagramClient;
use WeakMap;

class InstagramClientRegistry
{
    public function __construct(
        private readonly InstagramClientFactory $instagramClientFactory,
        private ?WeakMap $cache = null,
    )
    {
        $this->cache = $this->cache ?? new WeakMap();
    }

    public function getInstagramClient(InstagramName $instagramName, InstagramOptions $instagramOptions): InstagramClient
    {
        if (isset($this->cache[$instagramName])) {
            return $this->cache[$instagramName];
        }

        return $this->cache[$instagramName] = $this->instagramClientFactory->createInstagramClient($instagramOptions);
    }
}