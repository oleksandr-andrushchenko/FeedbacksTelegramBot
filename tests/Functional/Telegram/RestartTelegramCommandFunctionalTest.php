<?php

declare(strict_types=1);

namespace App\Tests\Functional\Telegram;

use App\Entity\Telegram\TelegramConversationState;
use App\Service\Telegram\Channel\FeedbackTelegramChannel;
use App\Service\Telegram\Conversation\RestartConversationTelegramConversation;

class RestartTelegramCommandFunctionalTest extends TelegramCommandFunctionalTestCase
{
    public function testStartSuccess(): void
    {
        $this
            ->type(
                FeedbackTelegramChannel::RESTART
            )
            ->shouldSeeReply(
                'query.confirm',
            )
            ->shouldSeeKeyboard(
                RestartConversationTelegramConversation::getConfirmButton($this->tg),
                RestartConversationTelegramConversation::getCancelButton($this->tg),
            )
        ;
    }

    public function testStartWithHintsSuccess(): void
    {
        $this->getUpdateMessengerUser()->setIsShowHints(true);
        $this->getEntityManager()->flush();

        $this
            ->type(
                FeedbackTelegramChannel::RESTART
            )
            ->shouldSeeReply(
                'describe.title',
                'toggle_hints',
                'query.confirm',
            )
            ->shouldSeeKeyboard(
                RestartConversationTelegramConversation::getConfirmButton($this->tg),
                RestartConversationTelegramConversation::getCancelButton($this->tg),
            )
        ;
    }

    public function testGotConfirmSuccess(): void
    {
        $this
            ->command(
                RestartConversationTelegramConversation::getConfirmButton($this->tg)->getText()
            )
            ->conversation(
                RestartConversationTelegramConversation::class,
                new TelegramConversationState(RestartConversationTelegramConversation::STEP_CONFIRM_QUERIED)
            )
            ->invoke()
            ->shouldSeeReply(
                'reply.ok',
            )
            ->shouldSeeChooseAction()
        ;
    }
}