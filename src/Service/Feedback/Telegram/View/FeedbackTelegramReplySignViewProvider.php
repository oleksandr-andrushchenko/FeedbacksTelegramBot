<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\View;

use App\Enum\Messenger\Messenger;
use App\Service\Messenger\MessengerUserProfileUrlProvider;
use App\Service\Telegram\Telegram;
use Symfony\Contracts\Translation\TranslatorInterface;

class FeedbackTelegramReplySignViewProvider
{
    public function __construct(
        private readonly MessengerUserProfileUrlProvider $messengerUserProfileUrlProvider,
        private readonly TranslatorInterface $translator,
    )
    {
    }

    public function getFeedbackTelegramReplySignView(Telegram $telegram): string
    {
        $bot = $telegram->getBot();
        $text = fn ($key) => $this->translator->trans('sign.' . $key, domain: 'feedbacks.tg', locale: $bot->getLocaleCode());

        $botLink = $this->messengerUserProfileUrlProvider->getMessengerUserProfileUrl(Messenger::telegram, $bot->getUsername());
        $message = $text('title');
        $message .= ":\n⇉ ";
        $message .= sprintf('<a href="%s">%s</a>', $botLink, $text('bot'));

        if ($bot->getChannelUsername() !== null) {
            $message .= ' • ';
            $channelLink = $this->messengerUserProfileUrlProvider->getMessengerUserProfileUrl(Messenger::telegram, $bot->getChannelUsername());
            $message .= sprintf('<a href="%s">%s</a>', $channelLink, $text('channel'));
        }

        if ($bot->getGroupUsername() !== null) {
            $message .= ' • ';
            $groupLink = $this->messengerUserProfileUrlProvider->getMessengerUserProfileUrl(Messenger::telegram, $bot->getGroupUsername());
            $message .= sprintf('<a href="%s">%s</a>', $groupLink, $text('group'));
        }

        $message .= ' ⇇';

        return $message;
    }
}