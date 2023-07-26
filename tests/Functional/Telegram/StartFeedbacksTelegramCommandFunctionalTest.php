<?php

declare(strict_types=1);

namespace App\Tests\Functional\Telegram;

use App\Service\Telegram\Channel\FeedbackTelegramChannel;
use App\Service\Telegram\Conversation\ChooseFeedbackActionTelegramConversation;
use App\Tests\Traits\Telegram\TelegramCommandFunctionalTrait;

class StartFeedbacksTelegramCommandFunctionalTest extends TelegramCommandFunctionalTestCase
{
    use TelegramCommandFunctionalTrait;

    public function testStartFeedbacksSuccess(): void
    {
        $this->type(FeedbackTelegramChannel::START)
            ->shouldSeeReply(
                ChooseFeedbackActionTelegramConversation::getActionAsk($this->tg)
            )
            ->shouldSeeKeyboard(
                ChooseFeedbackActionTelegramConversation::getCreateButton($this->tg),
                ChooseFeedbackActionTelegramConversation::getSearchButton($this->tg)
            )
        ;
    }
}