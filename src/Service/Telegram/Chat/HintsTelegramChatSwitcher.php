<?php

declare(strict_types=1);

namespace App\Service\Telegram\Chat;

use App\Service\Telegram\Channel\FeedbackTelegramChannel;
use App\Service\Telegram\TelegramAwareHelper;

class HintsTelegramChatSwitcher
{
    public function toggleHints(TelegramAwareHelper $tg): null
    {
        $messengerUser = $tg->getTelegram()->getMessengerUser();
        $messengerUser->setIsShowHints(!$messengerUser->isShowHints());

        $transParameters = [
            'command' => FeedbackTelegramChannel::HINTS,
        ];

        if ($messengerUser->isShowHints()) {
            $transParameters['now'] = sprintf('<u><b>%s</b></u>', $tg->trans('enabled'));
            $transParameters['will'] = $tg->trans('disable');
        } else {
            $transParameters['now'] = sprintf('<u><b>%s</b></u>', $tg->trans('disabled'));
            $transParameters['will'] = $tg->trans('enable');
        }

        return $tg->replyOk('reply.hints.ok', $transParameters, parseMode: 'HTML')->null();
    }
}
