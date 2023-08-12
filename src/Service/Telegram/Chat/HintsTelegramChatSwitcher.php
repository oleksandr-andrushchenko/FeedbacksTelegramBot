<?php

declare(strict_types=1);

namespace App\Service\Telegram\Chat;

use App\Enum\Telegram\TelegramView;
use App\Service\Telegram\TelegramAwareHelper;

class HintsTelegramChatSwitcher
{
    public function toggleHints(TelegramAwareHelper $tg): null
    {
        $messengerUser = $tg->getTelegram()->getMessengerUser();
        $messengerUser->setIsShowHints(!$messengerUser->showHints());

        $transParameters = [
            'command' => $tg->view(TelegramView::COMMAND, [
                'name' => 'hints',
            ]),
        ];

        if ($messengerUser->showHints()) {
            $transParameters['now'] = sprintf('<u><b>%s</b></u>', $tg->trans('enabled'));
            $transParameters['will'] = $tg->trans('disable');
        } else {
            $transParameters['now'] = sprintf('<u><b>%s</b></u>', $tg->trans('disabled'));
            $transParameters['will'] = $tg->trans('enable');
        }

        return $tg->replyOk($tg->trans('reply.hints.ok', $transParameters), parseMode: 'HTML')->null();
    }
}
