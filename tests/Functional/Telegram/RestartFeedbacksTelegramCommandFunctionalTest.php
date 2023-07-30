<?php

declare(strict_types=1);

namespace App\Tests\Functional\Telegram;

use App\Service\Telegram\Channel\FeedbackTelegramChannel;
use App\Service\Telegram\Chat\ChooseActionTelegramChatSender;
use App\Tests\Traits\Telegram\TelegramCommandFunctionalTrait;

class RestartFeedbacksTelegramCommandFunctionalTest extends TelegramCommandFunctionalTestCase
{
    use TelegramCommandFunctionalTrait;

    public function testRestartFeedbacksSuccess(): void
    {
        $this->type(FeedbackTelegramChannel::RESTART)
            ->shouldSeeReply(
                $this->trans('reply.restart.ok'),
                ChooseActionTelegramChatSender::getActionAsk($this->tg),
            )
            ->shouldSeeKeyboard(
                ChooseActionTelegramChatSender::getCreateButton($this->tg),
                ChooseActionTelegramChatSender::getSearchButton($this->tg),
            )
        ;
    }
}