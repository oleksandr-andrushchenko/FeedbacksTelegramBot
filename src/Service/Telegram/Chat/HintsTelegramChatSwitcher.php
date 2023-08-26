<?php

declare(strict_types=1);

namespace App\Service\Telegram\Chat;

use App\Service\Telegram\TelegramAwareHelper;
use Longman\TelegramBot\Entities\Keyboard;

class HintsTelegramChatSwitcher
{
    public function toggleHints(TelegramAwareHelper $tg, Keyboard $keyboard = null): void
    {
        $messengerUser = $tg->getTelegram()->getMessengerUser();
        $messengerUser->setIsShowHints(!$messengerUser->showHints());
    }

    public function getReplyText(TelegramAwareHelper $tg): string
    {
        $messengerUser = $tg->getTelegram()->getMessengerUser();
        $transParameters = [
            'command' => $tg->command('hints', html: true),
        ];

        if ($messengerUser->showHints()) {
            $transParameters['now'] = sprintf('<u><b>%s</b></u>', $tg->trans('enabled'));
            $transParameters['will'] = $tg->trans('disable');
        } else {
            $transParameters['now'] = sprintf('<u><b>%s</b></u>', $tg->trans('disabled'));
            $transParameters['will'] = $tg->trans('enable');
        }

        return $tg->trans('reply.hints.ok', $transParameters);
    }
}
