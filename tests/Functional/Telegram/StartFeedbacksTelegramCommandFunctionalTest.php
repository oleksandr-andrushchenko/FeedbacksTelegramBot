<?php

declare(strict_types=1);

namespace App\Tests\Functional\Telegram;

use App\Service\Telegram\Channel\FeedbackTelegramChannel;
use App\Service\Telegram\Chat\ChooseActionTelegramChatSender;
use App\Tests\Traits\Telegram\TelegramCommandFunctionalTrait;

class StartFeedbacksTelegramCommandFunctionalTest extends TelegramCommandFunctionalTestCase
{
    use TelegramCommandFunctionalTrait;

    public function testStartFeedbacksSuccess(): void
    {
        $this->type(FeedbackTelegramChannel::START)
            ->shouldSeeReply(
                'title',
                'agreements',
                ChooseActionTelegramChatSender::getActionAsk($this->tg)
            )
            ->shouldSeeKeyboard(
                ChooseActionTelegramChatSender::getCreateButton($this->tg),
                ChooseActionTelegramChatSender::getSearchButton($this->tg)
            )
        ;
    }
}