<?php

declare(strict_types=1);

namespace App\Tests\Functional\Telegram;

use App\Entity\Telegram\TelegramBot;
use App\Service\Telegram\Channel\FeedbackTelegramChannel;

class StartTelegramCommandFunctionalTest extends TelegramCommandFunctionalTestCase
{
    public function testStartSuccess(): void
    {
        $this->refreshDatabase()->bootFixtures([
            TelegramBot::class,
        ]);

        $this
            ->type(FeedbackTelegramChannel::START)
            ->shouldSeeChooseAction()
        ;

        $this->assertCount(1, $this->getMessengerUserRepository()->findAll());

        $this->assertEquals(
            $this->telegram->getUpdate()->getMessage()->getFrom()->getId(),
            $this->getMessengerUserRepository()->findOneBy([])?->getIdentifier()
        );
    }

    public function testStartWithHintsSuccess(): void
    {
        $this->getUpdateMessengerUser()->setIsShowHints(true);

        $this->getEntityManager()->remove($this->getTelegramConversation());
        $this->getEntityManager()->flush();

        $this
            ->type(FeedbackTelegramChannel::START)
            ->shouldSeeReply(
                'describe.title',
                'describe.agreements'
            )
            ->shouldSeeChooseAction()
        ;
    }
}