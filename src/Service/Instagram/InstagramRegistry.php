<?php

declare(strict_types=1);

namespace App\Service\Instagram;

use App\Enum\Instagram\InstagramName;
use App\Exception\Instagram\InstagramException;
use WeakMap;

class InstagramRegistry
{
    public function __construct(
        private readonly InstagramFactory $instagramFactory,
        private ?WeakMap $cache = null,
    )
    {
        $this->cache = $this->cache ?? new WeakMap();
    }

    /**
     * @param string|InstagramName $instagramName
     * @return Instagram
     * @throws InstagramException
     */
    public function getInstagram(string|InstagramName $instagramName): Instagram
    {
        $instagramName = is_string($instagramName) ? InstagramName::from($instagramName) : $instagramName;
        
        if (isset($this->cache[$instagramName])) {
            return $this->cache[$instagramName];
        }

        return $this->cache[$instagramName] = $this->instagramFactory->createInstagram($instagramName);
    }
}