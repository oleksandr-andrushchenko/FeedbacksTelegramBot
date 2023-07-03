<?php

declare(strict_types=1);

namespace App\Service\Instagram\UserFinder;

use App\Object\Messenger\MessengerUserTransfer;

interface InstagramMessengerUserFinderInterface
{
    public function findInstagramMessengerUser(string $username, $_): ?MessengerUserTransfer;
}
