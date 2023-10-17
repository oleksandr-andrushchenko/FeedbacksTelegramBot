<?php

declare(strict_types=1);

namespace App\Tests\Functional\Feedback\Telegram\Bot;

use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Entity\Telegram\TelegramBotConversationState;
use App\Entity\User\User;
use App\Service\Feedback\Telegram\Bot\Conversation\RestartConversationTelegramBotConversation;
use App\Service\Feedback\Telegram\Bot\FeedbackTelegramBotGroup;
use App\Tests\Functional\Telegram\Bot\TelegramBotCommandFunctionalTestCase;
use Generator;

class RestartTelegramBotCommandFunctionalTest extends TelegramBotCommandFunctionalTestCase
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
                RestartConversationTelegramBotConversation::class,
                (new TelegramBotConversationState())
                    ->setStep(RestartConversationTelegramBotConversation::STEP_CONFIRM_QUERIED)
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
            'command' => FeedbackTelegramBotGroup::RESTART,
        ];
    }

    public function testConfirmStepSuccess(): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
        ]);

        $this->createConversation(
            RestartConversationTelegramBotConversation::class,
            (new TelegramBotConversationState())
                ->setStep(RestartConversationTelegramBotConversation::STEP_CONFIRM_QUERIED)
        );

        $this
            ->type($this->yesButton())
            ->shouldSeeReply(
                'reply.ok',
            )
            ->shouldSeeChooseAction()
            ->shouldNotSeeActiveConversation()
        ;
    }
}