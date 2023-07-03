<?php

declare(strict_types=1);

namespace App\Service\Instagram;

use App\Object\Messenger\MessengerUserTransfer;

interface InstagramMessengerUserProviderInterface
{
    public function getInstagramMessengerUser(string $username, $_): ?MessengerUserTransfer;
}
