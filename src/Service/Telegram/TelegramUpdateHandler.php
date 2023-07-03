<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Exception\Telegram\InvalidTelegramUpdateException;
use Longman\TelegramBot\TelegramLog;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class TelegramUpdateHandler
{
    public function __construct(
        private readonly TelegramUpdateFactory $updateFactory,
        private readonly TelegramExistingUpdateChecker $existingUpdateChecker,
        private readonly TelegramNonAdminUpdateChecker $nonAdminUpdateChecker,
        private readonly TelegramConversationManager $conversationManager,
        private readonly TelegramMessengerUserUpserter $messengerUserUpserter,
        private readonly TelegramChannelRegistry $channelRegistry,
        private readonly TelegramCommandFinder $commandFinder,
        private readonly LoggerInterface $logger,
    )
    {
    }

    /**
     * @param Telegram $telegram
     * @param Request $request
     * @return void
     * @throws InvalidTelegramUpdateException
     */
    public function handleTelegramUpdate(Telegram $telegram, Request $request): void
    {
        $update = $this->updateFactory->createUpdate($telegram, $request);
        $telegram->setUpdate($update);

        if ($telegram->getUpdate()?->getMessage() === null) {
            return;
        }

        // todo: remove on production
        if ($this->nonAdminUpdateChecker->checkNonAdminUpdate($telegram)) {
            return;
        }

        if ($this->existingUpdateChecker->checExistingUpdate($telegram)) {
            return;
        }

        $messengerUser = $this->messengerUserUpserter->upsertTelegramMessengerUser($telegram);
        $telegram->setMessengerUser($messengerUser);

        TelegramLog::initialize($this->logger, $this->logger);

        $channel = $this->channelRegistry->getTelegramChannel($telegram->getName());

        $text = $telegram->getUpdate()?->getMessage()?->getText();
        $commands = iterator_to_array($channel->getTelegramCommands($telegram));

        if ($beforeConversationCommand = $this->commandFinder->findBeforeConversationCommand($text, $commands)) {
            call_user_func($beforeConversationCommand->getCallback(), $telegram);
        } elseif ($conversation = $this->conversationManager->getLastTelegramConversation($telegram)) {
            $this->conversationManager->continueTelegramConversation($telegram, $conversation);
        } elseif ($command = $this->commandFinder->findCommand($text, $commands)) {
            call_user_func($command->getCallback(), $telegram);
        } elseif ($fallbackCommand = $this->commandFinder->findFallbackCommand($commands)) {
            call_user_func($fallbackCommand->getCallback(), $telegram);
        }
    }
}