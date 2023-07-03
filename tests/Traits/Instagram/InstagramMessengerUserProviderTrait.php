<?php

declare(strict_types=1);

namespace App\Tests\Traits\Instagram;

use App\Service\Instagram\InstagramMessengerUserProviderInterface;
use App\Tests\Fake\Service\Instagram\FakeInstagramMessengerUserProvider;

trait InstagramMessengerUserProviderTrait
{
    public function getInstagramMessengerUserProvider(): InstagramMessengerUserProviderInterface
    {
        $finder = static::getContainer()->get('app.instagram_messenger_user_provider');

        if ($finder instanceof FakeInstagramMessengerUserProvider) {
            $finder->clearReturn();
        }

        return $finder;
    }
}
