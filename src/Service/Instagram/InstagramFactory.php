<?php

declare(strict_types=1);

namespace App\Service\Instagram;

use App\Entity\Instagram\InstagramOptions;
use App\Enum\Instagram\InstagramName;
use App\Exception\Instagram\InstagramException;
use Psr\Log\LoggerInterface;

class InstagramFactory
{
    public function __construct(
        private readonly array $options,
        private readonly InstagramClientRegistry $instagramClientRegistry,
        private readonly LoggerInterface $logger,
    )
    {
    }

    /**
     * @param InstagramName $instagramName
     * @return Instagram
     * @throws InstagramException
     */
    public function createInstagram(InstagramName $instagramName): Instagram
    {
        if (!isset($this->options[$instagramName->name])) {
            throw new InstagramException('Invalid instagram name provided');
        }

        $options = $this->options[$instagramName->name];

        return new Instagram(
            $instagramName,
            new InstagramOptions(
                $options['username'],
                $options['password'],
            ),
            $this->instagramClientRegistry,
            $this->logger,
        );
    }
}