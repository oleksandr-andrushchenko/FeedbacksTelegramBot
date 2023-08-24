<?php

declare(strict_types=1);

namespace App\Tests\Functional\Telegram;

use App\Entity\Telegram\TelegramBot;
use App\Service\Telegram\Channel\FeedbackTelegramChannel;
use Generator;

class StartTelegramCommandFunctionalTest extends TelegramCommandFunctionalTestCase
{
    /**
     * @param bool $showHints
     * @return void
     * @dataProvider startSuccessDataProvider
     */
    public function testStartSuccess(bool $showHints): void
    {
        $this->bootFixtures([
            TelegramBot::class,
        ]);

        if ($showHints) {
            $shouldReply = [
                'describe.title',
                'describe.agreements',
            ];
        } else {
            $shouldReply = [];
        }

        $this
            ->type(FeedbackTelegramChannel::START)
        ;

        $this->assertNotNull($this->getUpdateMessengerUser());
        $this->assertNotNull($this->getUpdateMessengerUser()->getUser());

        $this->getUpdateMessengerUser()->setIsShowHints($showHints);
        $this
            ->shouldSeeNotActiveConversation()
            ->shouldSeeReply(...$shouldReply)
            ->shouldSeeChooseAction()
        ;
    }

    public function startSuccessDataProvider(): Generator
    {
        yield 'no hints' => [
            'showHints' => false,
        ];

        yield 'hints' => [
            'showHints' => true,
        ];
    }
}