<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\View;

use App\Entity\Telegram\TelegramChannel;
use App\Enum\Messenger\Messenger;
use App\Repository\Telegram\TelegramChannelRepository;
use App\Service\Messenger\MessengerUserProfileUrlProvider;
use App\Service\Telegram\Telegram;
use Symfony\Contracts\Translation\TranslatorInterface;

class FeedbackTelegramReplySignViewProvider
{
    public function __construct(
        private readonly TelegramChannelRepository $channelRepository,
        private readonly MessengerUserProfileUrlProvider $messengerUserProfileUrlProvider,
        private readonly TranslatorInterface $translator,
    )
    {
    }

    public function getFeedbackTelegramReplySignView(Telegram $telegram, TelegramChannel $channel = null): string
    {
        $bot = $telegram->getBot();
        $localeCode = $bot->getLocaleCode();
        $text = fn ($key) => $this->translator->trans('sign.' . $key, domain: 'feedbacks.tg', locale: $localeCode);

        $botLink = $this->messengerUserProfileUrlProvider->getMessengerUserProfileUrl(Messenger::telegram, $bot->getUsername());
        $message = in_array($localeCode, ['ru'], true) ? '' : '➡️ ';
        $message .= sprintf('<a href="%s">%s</a>', $botLink, $text('create'));
        $message .= ' • ';
        $message .= sprintf('<a href="%s">%s</a>', $botLink, $text('search'));

        if ($channel === null) {
            $channel = $this->channelRepository->findOnePrimaryByBot($bot);
        }

        if ($channel !== null) {
            $message .= ' • ';
            $channelLink = $this->messengerUserProfileUrlProvider->getMessengerUserProfileUrl(Messenger::telegram, $channel->getUsername());
            $message .= sprintf('<a href="%s">%s</a>', $channelLink, $text('channel'));
        }

        return $message;
    }
}