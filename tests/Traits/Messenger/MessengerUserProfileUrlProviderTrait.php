<?php

declare(strict_types=1);

namespace App\Tests\Traits\Messenger;

use App\Enum\Messenger\Messenger;
use App\Object\Messenger\MessengerUserTransfer;

trait MessengerUserProfileUrlProviderTrait
{
    public function getMessengerUserProfileUrl(MessengerUserTransfer $messengerUser): ?string
    {
        return match ($messengerUser->getMessenger()) {
            Messenger::instagram => sprintf('https://instagram.com/%s', $messengerUser->getUsername()),
            Messenger::facebook => sprintf('https://facebook.com/%s', $messengerUser->getUsername()),
            Messenger::reddit => sprintf('https://www.reddit.com/user/%s/', $messengerUser->getUsername()),
            Messenger::onlyfans => sprintf('https://onlyfans.com/%s', $messengerUser->getUsername()),
            Messenger::telegram => sprintf('https://t.me/%s', $messengerUser->getUsername()),
            Messenger::tiktok => sprintf('https://tiktok.com/@%s', $messengerUser->getUsername()),
            Messenger::twitter => sprintf('https://x.com/%s', $messengerUser->getUsername()),
            Messenger::youtube => sprintf('https://www.youtube.com/@%s', $messengerUser->getUsername()),
            Messenger::unknown => sprintf('https://unknown.com/@%s', $messengerUser->getUsername()),
            default => null,
        };
    }
}