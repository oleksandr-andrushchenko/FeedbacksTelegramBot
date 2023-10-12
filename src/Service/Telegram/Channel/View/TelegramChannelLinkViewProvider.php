<?php

declare(strict_types=1);

namespace App\Service\Telegram\Channel\View;

use App\Entity\Telegram\TelegramChannel;
use App\Service\Telegram\TelegramLinkProvider;

class TelegramChannelLinkViewProvider
{
    public function __construct(
        private readonly TelegramLinkProvider $linkProvider,
    )
    {
    }

    public function getTelegramChannelLinkView(TelegramChannel $channel): string
    {
        $link = $this->linkProvider->getTelegramLink($channel->getUsername());

        if (str_starts_with($channel->getUsername(), '+')) {
            $anchor = $channel->getName();
        } else {
            $anchor = '@' . $channel->getUsername();
        }

        return sprintf('<u><b><a href="%s">%s</a></b>></u>', $link, $anchor);
    }
}