<?php

declare(strict_types=1);

namespace App\Service\Messenger;

use App\Enum\Messenger\Messenger;
use App\Object\Messenger\MessengerUserTransfer;

class MessengerUserProfileUrlProvider
{
    public function getMessengerUserProfileUrl(Messenger $messenger, string $username): ?string
    {
        return match ($messenger) {
            Messenger::instagram => sprintf('https://instagram.com/%s', $username),
            Messenger::facebook => sprintf('https://facebook.com/%s', $username),
            Messenger::reddit => sprintf('https://www.reddit.com/user/%s/', $username),
            Messenger::onlyfans => sprintf('https://onlyfans.com/%s', $username),
            Messenger::telegram => sprintf('https://t.me/%s', $username),
            Messenger::tiktok => sprintf('https://tiktok.com/@%s', $username),
            Messenger::twitter => sprintf('https://x.com/%s', $username),
            Messenger::youtube => sprintf('https://www.youtube.com/@%s', $username),
            Messenger::vkontakte => sprintf('https://vk.com/%s', is_numeric($username) ? ('id' . $username) : $username),
            default => null,
        };
    }

    public function getMessengerUserProfileUrlByUser(MessengerUserTransfer $messengerUser): ?string
    {
        return $this->getMessengerUserProfileUrl(
            $messengerUser->getMessenger(),
            $messengerUser->getUsername() ?? $messengerUser->getId()
        );
    }
}