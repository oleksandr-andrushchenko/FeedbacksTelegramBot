<?php

declare(strict_types=1);

namespace App\Tests\Functional\Telegram;

use App\Service\Telegram\Channel\FeedbackTelegramChannel;
use App\Service\Telegram\Conversation\ChooseFeedbackActionTelegramConversation;
use App\Tests\Traits\Telegram\TelegramCommandFunctionalTrait;

class RestartFeedbacksTelegramCommandFunctionalTest extends TelegramCommandFunctionalTestCase
{
    use TelegramCommandFunctionalTrait;

    public function testRestartFeedbacksSuccess(): void
    {
        $this->type(FeedbackTelegramChannel::RESTART)
            ->shouldSeeReply(
                $this->trans('reply.icon.ok') . ' ' . $this->trans('reply.restart.ok'),
                ChooseFeedbackActionTelegramConversation::getActionAsk($this->tg),
            )
            ->shouldSeeKeyboard(
                ChooseFeedbackActionTelegramConversation::getCreateButton($this->tg),
                ChooseFeedbackActionTelegramConversation::getSearchButton($this->tg),
            )
        ;
    }
}