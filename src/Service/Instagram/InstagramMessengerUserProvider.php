<?php

declare(strict_types=1);

namespace App\Service\Instagram;

use App\Object\Messenger\MessengerUserTransfer;

class InstagramMessengerUserProvider implements InstagramMessengerUserProviderInterface
{
    public function __construct(
        private readonly TelegramMessengerUserFinderFactory $finderFactory,
    )
    {
    }

    public function getInstagramMessengerUser(string $username, $_ = null): ?MessengerUserTransfer
    {
        foreach ($this->finderFactory->createInstagramMessengerUserFinders() as $finder) {
            $user = $finder->findInstagramMessengerUser($username, $_);

            if ($user === null) {
                continue;
            }

            return $user;
        }

        return null;
    }
}
