<?php

declare(strict_types=1);

namespace App\Tests\Functional\Telegram;

use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Entity\Telegram\TelegramConversationState;
use App\Entity\User\User;
use App\Service\Feedback\Telegram\Conversation\RestartConversationTelegramConversation;
use App\Service\Feedback\Telegram\FeedbackTelegramChannel;
use Generator;

class RestartTelegramCommandFunctionalTest extends TelegramCommandFunctionalTestCase
{
    /**
     * @param string $command
     * @return void
     * @dataProvider startSuccessDataProvider
     */
    public function testStartSuccess(string $command): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
        ]);

        $this
            ->type($command)
            ->shouldSeeActiveConversation(
                RestartConversationTelegramConversation::class,
                (new TelegramConversationState())
                    ->setStep(RestartConversationTelegramConversation::STEP_CONFIRM_QUERIED)
            )
            ->shouldSeeReply(
                'query.confirm',
            )
            ->shouldSeeButtons(
                $this->yesButton(),
                $this->noButton(),
            )
        ;
    }

    public function startSuccessDataProvider(): Generator
    {
        yield 'button' => [
            'command' => $this->command('restart'),
        ];

        yield 'command' => [
            'command' => FeedbackTelegramChannel::RESTART,
        ];
    }

    public function testGotConfirmSuccess(): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
        ]);

        $this->createConversation(
            RestartConversationTelegramConversation::class,
            (new TelegramConversationState())
                ->setStep(RestartConversationTelegramConversation::STEP_CONFIRM_QUERIED)
        );

        $this
            ->type($this->yesButton())
            ->shouldNotSeeActiveConversation()
            ->shouldSeeReply(
                'reply.ok',
            )
            ->shouldSeeChooseAction()
        ;
    }
}