<?php

declare(strict_types=1);

namespace App\Tests\Functional\Telegram;

use App\Service\Telegram\Channel\FeedbackTelegramChannel;
use App\Service\Telegram\Chat\ChooseActionTelegramChatSender;

class StartFeedbacksTelegramCommandFunctionalTest extends TelegramCommandFunctionalTestCase
{
    public function testStartFeedbacksSuccess(): void
    {
        $this->getEntityManager()->remove($this->getTelegramConversation());
        $this->getEntityManager()->flush();

        $this->type(FeedbackTelegramChannel::START)
            ->shouldSeeReply(
                ChooseActionTelegramChatSender::getActionQuery($this->tg)
            )
            ->shouldSeeKeyboard(
                ChooseActionTelegramChatSender::getCreateButton($this->tg),
                ChooseActionTelegramChatSender::getSearchButton($this->tg)
            )
        ;
    }
}