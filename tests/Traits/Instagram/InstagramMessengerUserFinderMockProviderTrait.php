<?php

declare(strict_types=1);

namespace App\Tests\Traits\Instagram;

use App\Service\Instagram\InstagramMessengerUserProvider;
use PHPUnit\Framework\MockObject\MockObject;

trait InstagramMessengerUserFinderMockProviderTrait
{
    public function getInstagramMessengerUserFinderMock(bool $replace = true): InstagramMessengerUserProvider|MockObject
    {
        $mock = $this->createMock(InstagramMessengerUserProvider::class);

        if ($replace) {
            static::getContainer()->set('app.instagram_messenger_user_provider', $mock);
        }

        return $mock;
    }
}
