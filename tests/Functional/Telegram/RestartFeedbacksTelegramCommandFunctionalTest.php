<?php

declare(strict_types=1);

namespace App\Tests\Functional\Telegram;

use App\Service\Telegram\Channel\FeedbackTelegramChannel;
use App\Service\Telegram\Conversation\RestartConversationTelegramConversation;

class RestartFeedbacksTelegramCommandFunctionalTest extends TelegramCommandFunctionalTestCase
{
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