<?php

declare(strict_types=1);

namespace App\Tests\Functional\Telegram;

use App\Service\Telegram\Channel\FeedbackTelegramChannel;
use App\Service\Telegram\Conversation\RestartConversationTelegramConversation;
use App\Tests\Traits\Telegram\TelegramCommandFunctionalTrait;

class RestartFeedbacksTelegramCommandFunctionalTest extends TelegramCommandFunctionalTestCase
{
    use TelegramCommandFunctionalTrait;

    public function testRestartFeedbacksSuccess(): void
    {
        $this->type(FeedbackTelegramChannel::RESTART)
            ->shouldSeeReply(
                'title',
                'ask.restart.confirm',
            )
            ->shouldSeeKeyboard(
                RestartConversationTelegramConversation::getConfirmButton($this->tg),
                RestartConversationTelegramConversation::getCancelButton($this->tg),
            )
        ;
    }
}