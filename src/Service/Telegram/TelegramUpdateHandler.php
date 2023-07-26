<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Exception\Telegram\InvalidTelegramUpdateException;
use App\Exception\Telegram\PaymentNotFoundException;
use App\Exception\Telegram\UnknownPaymentException;
use App\Service\Telegram\Payment\TelegramPaymentManager;
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
        private readonly TelegramPaymentManager $paymentManager,
        private readonly LoggerInterface $logger,
    )
    {
    }

    /**
     * @param Telegram $telegram
     * @param Request $request
     * @return void
     * @throws InvalidTelegramUpdateException
     * @throws PaymentNotFoundException
     * @throws UnknownPaymentException
     */
    public function handleTelegramUpdate(Telegram $telegram, Request $request): void
    {
        $update = $this->updateFactory->createUpdate($telegram, $request);
        $telegram->setUpdate($update);

//        if ($telegram->getUpdate()?->getMessage() === null) {
//            return;
//        }

        // todo: remove on production
        if ($this->nonAdminUpdateChecker->checkNonAdminUpdate($telegram)) {
            return;
        }

        if ($this->existingUpdateChecker->checkExistingUpdate($telegram)) {
            return;
        }

        $messengerUser = $this->messengerUserUpserter->upsertTelegramMessengerUser($telegram);
        $telegram->setMessengerUser($messengerUser);

        TelegramLog::initialize($this->logger, $this->logger);

        $channel = $this->channelRegistry->getTelegramChannel($telegram->getName());

        $text = $telegram->getUpdate()?->getMessage()?->getText();
        $commands = iterator_to_array($channel->getTelegramCommands($telegram));

        if ($update->getPreCheckoutQuery() !== null) {
            if ($telegram->getOptions()->acceptPayments()) {
                $this->paymentManager->acceptPreCheckoutQuery($telegram, $update->getPreCheckoutQuery());
            }
        } elseif ($update->getMessage()?->getSuccessfulPayment() !== null) {
            if ($telegram->getOptions()->acceptPayments()) {
                $payment = $this->paymentManager->acceptSuccessfulPayment($telegram, $update->getMessage()->getSuccessfulPayment());
                $channel->acceptTelegramPayment($telegram, $payment);
            }
        } elseif ($beforeConversationCommand = $this->commandFinder->findBeforeConversationCommand($text, $commands)) {
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