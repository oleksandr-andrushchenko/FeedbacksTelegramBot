<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Exception\Telegram\InvalidTelegramUpdateException;
use App\Exception\Telegram\PaymentNotFoundException;
use App\Exception\Telegram\TelegramException;
use App\Exception\Telegram\UnknownPaymentException;
use App\Service\Telegram\Payment\TelegramPaymentManager;
use Longman\TelegramBot\TelegramLog;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Twig\Error\Error as TwigError;

class TelegramUpdateHandler
{
    public function __construct(
        private readonly TelegramUpdateFactory $updateFactory,
        private readonly TelegramUpdateChecker $updateChecker,
        private readonly TelegramNonAdminUpdateChecker $nonAdminUpdateChecker,
        private readonly TelegramConversationManager $conversationManager,
        private readonly TelegramMessengerUserUpserter $messengerUserUpserter,
        private readonly TelegramChannelRegistry $channelRegistry,
        private readonly TelegramCommandFinder $commandFinder,
        private readonly TelegramPaymentManager $paymentManager,
        private readonly TelegramLocaleSwitcher $localeSwitcher,
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

        // todo: remove on production
        if ($this->nonAdminUpdateChecker->checkNonAdminUpdate($telegram)) {
            return;
        }

        if ($this->updateChecker->checkTelegramUpdate($telegram)) {
            return;
        }

        $messengerUser = $this->messengerUserUpserter->upsertTelegramMessengerUser($telegram);
        $telegram->setMessengerUser($messengerUser);
        $this->localeSwitcher->syncLocale($telegram, $request);

        TelegramLog::initialize($this->logger, $this->logger);

        $channel = $this->channelRegistry->getTelegramChannel($telegram->getBot()->getGroup());

        if ($update->getPreCheckoutQuery() !== null) {
            if ($telegram->getBot()->acceptPayments()) {
                $this->paymentManager->acceptPreCheckoutQuery($telegram, $update->getPreCheckoutQuery());
            }
            return;
        } elseif ($update->getMessage()?->getSuccessfulPayment() !== null) {
            if ($telegram->getBot()->acceptPayments()) {
                $payment = $this->paymentManager->acceptSuccessfulPayment($telegram, $update->getMessage()->getSuccessfulPayment());
                $channel->acceptTelegramPayment($telegram, $payment);
            }
            return;
        }

        $text = $telegram->getUpdate()?->getMessage()?->getText();
        $commands = $channel->getTelegramCommands($telegram);

        try {
            if ($beforeConversationCommand = $this->commandFinder->findBeforeConversationCommand($text, $commands)) {
                call_user_func($beforeConversationCommand->getCallback());
            } elseif ($conversation = $this->conversationManager->getLastTelegramConversation($telegram)) {
                $this->conversationManager->continueTelegramConversation($telegram, $conversation);
            } elseif ($command = $this->commandFinder->findCommand($text, $commands)) {
                call_user_func($command->getCallback());
            } elseif ($fallbackCommand = $this->commandFinder->findFallbackCommand($commands)) {
                call_user_func($fallbackCommand->getCallback());
            }
        } catch (TelegramException|TwigError $exception) {
            $this->logger->error($exception);

            if ($errorCommand = $this->commandFinder->findErrorCommand($commands)) {
                call_user_func($errorCommand->getCallback(), $exception);
            }
        }
    }
}