<?php

declare(strict_types=1);

namespace App\Tests\Fake\Service\Instagram;

use App\Object\Messenger\MessengerUserTransfer;
use App\Service\Instagram\InstagramMessengerUserProviderInterface;

class FakeInstagramMessengerUserProvider implements InstagramMessengerUserProviderInterface
{
    public function __construct(
        private array $returns = []
    )
    {
    }

    public function clearReturn(): self
    {
        $this->returns = [];

        return $this;
    }

    public function addReturn(string $username, ?MessengerUserTransfer $return): self
    {
        $this->returns[$username] = $return;

        return $this;
    }

    public function getInstagramMessengerUser(string $username, $_): ?MessengerUserTransfer
    {
        return $this->returns[$username] ?? null;
    }
}

