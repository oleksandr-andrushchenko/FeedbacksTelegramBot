<?php

declare(strict_types=1);

namespace App\Tests\Functional\Telegram;

use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Entity\Telegram\TelegramConversationState;
use App\Entity\User\User;
use App\Service\Telegram\Channel\FeedbackTelegramChannel;
use App\Service\Telegram\Conversation\RestartConversationTelegramConversation;
use Generator;

class RestartTelegramCommandFunctionalTest extends TelegramCommandFunctionalTestCase
{
    /**
     * @param string $command
     * @param bool $showHints
     * @return void
     * @dataProvider startSuccessDataProvider
     */
    public function testStartSuccess(string $command, bool $showHints): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
        ]);
        $this->getUpdateMessengerUser()->setIsShowHints($showHints);

        if ($showHints) {
            $shouldReply = [
                'describe.title',
                'toggle_hints',
            ];
        } else {
            $shouldReply = [];
        }

        $this
            ->type($command)
            ->shouldSeeActiveConversation(
                RestartConversationTelegramConversation::class,
                (new TelegramConversationState())
                    ->setStep(RestartConversationTelegramConversation::STEP_CONFIRM_QUERIED)
            )
            ->shouldSeeReply(
                ...$shouldReply,
                ...['query.confirm'],
            )
            ->shouldSeeButtons(
                'keyboard.yes',
                'keyboard.no',
            )
        ;
    }

    public function startSuccessDataProvider(): Generator
    {
        yield 'button & no hints' => [
            'command' => 'icon.restart command.restart',
            'showHints' => false,
        ];

        yield 'button & hints' => [
            'command' => 'icon.restart command.restart',
            'showHints' => true,
        ];

        yield 'command & no hints' => [
            'command' => FeedbackTelegramChannel::RESTART,
            'showHints' => false,
        ];

        yield 'command & hints' => [
            'command' => FeedbackTelegramChannel::RESTART,
            'showHints' => true,
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
            ->type('keyboard.yes')
            ->shouldNotSeeActiveConversation()
            ->shouldSeeReply(
                'reply.ok',
            )
            ->shouldSeeChooseAction()
        ;
    }
}