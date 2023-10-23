<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use App\Entity\Telegram\TelegramBot;
use App\Exception\Telegram\Bot\Payment\TelegramBotPaymentNotFoundException;
use App\Exception\Telegram\Bot\Payment\TelegramBotUnknownPaymentException;
use App\Exception\Telegram\Bot\TelegramBotInvalidUpdateException;
use App\Repository\Telegram\Bot\TelegramBotRepository;
use App\Service\Telegram\Bot\Conversation\TelegramBotConversationManager;
use App\Service\Telegram\Bot\Group\TelegramBotGroupRegistry;
use App\Service\Telegram\Bot\Payment\TelegramBotPaymentManager;
use App\Service\Telegram\Bot\View\TelegramBotLinkViewProvider;
use Longman\TelegramBot\TelegramLog;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

class TelegramBotUpdateHandler
{
    public function __construct(
        private readonly string $environment,
        private readonly TelegramBotUpdateFactory $updateFactory,
        private readonly TelegramBotUpdateChecker $updateChecker,
        private readonly TelegramBotNonAdminUpdateChecker $nonAdminUpdateChecker,
        private readonly TelegramBotConversationManager $conversationManager,
        private readonly TelegramBotMessengerUserUpserter $messengerUserUpserter,
        private readonly TelegramBotGroupRegistry $groupRegistry,
        private readonly TelegramBotCommandFinder $commandFinder,
        private readonly TelegramBotPaymentManager $paymentManager,
        private readonly TelegramBotLocaleSwitcher $localeSwitcher,
        private readonly TelegramBotInputProvider $inputProvider,
        private readonly TelegramBotRegistry $registry,
        private readonly TelegramBotRepository $botRepository,
        private readonly TelegramBotAwareHelper $awareHelper,
        private readonly TelegramBotLinkViewProvider $botLinkViewProvider,
        private readonly LoggerInterface $logger,
    )
    {
    }

    /**
     * @param TelegramBot $botEntity
     * @param Request $request
     * @return void
     * @throws TelegramBotInvalidUpdateException
     * @throws TelegramBotPaymentNotFoundException
     * @throws Throwable
     * @throws TelegramBotUnknownPaymentException
     */
    public function handleTelegramBotUpdate(TelegramBot $botEntity, Request $request): void
    {
        $bot = $this->registry->getTelegramBot($botEntity);
        $update = $this->updateFactory->createUpdate($bot, $request);
        $bot->setUpdate($update);

        // todo: remove on production
        if ($this->nonAdminUpdateChecker->checkNonAdminUpdate($bot)) {
            return;
        }

        if ($this->updateChecker->checkTelegramUpdate($bot)) {
            return;
        }

        $messengerUser = $this->messengerUserUpserter->upsertTelegramMessengerUser($bot);

        $bot->setMessengerUser($messengerUser);
        $this->localeSwitcher->syncLocale($bot, $request);

        TelegramLog::initialize($this->logger, $this->logger);

        try {
            $group = $this->groupRegistry->getTelegramGroup($bot->getEntity()->getGroup());

            if ($update->getPreCheckoutQuery() !== null) {
                if (!$bot->deleted() && $bot->getEntity()->acceptPayments()) {
                    $this->paymentManager->acceptPreCheckoutQuery($bot, $update->getPreCheckoutQuery());
                }
                return;
            } elseif ($update->getMessage()?->getSuccessfulPayment() !== null) {
                if (!$bot->deleted() && $bot->getEntity()->acceptPayments()) {
                    $payment = $this->paymentManager->acceptSuccessfulPayment($bot, $update->getMessage()->getSuccessfulPayment());
                    $group->acceptTelegramPayment($bot, $payment);
                }
                return;
            }

            if (!$group->supportsTelegramUpdate($bot)) {
                return;
            }

            if ($bot->deleted() || !$bot->primary()) {
                $newBot = $this->botRepository->findOnePrimaryByBot($bot->getEntity());

                if ($newBot === null) {
                    $this->logger->warning('Primary bot has not been found to replace deleted/non-primary one', [
                        'bot_id' => $bot->getEntity()->getId(),
                    ]);
                } else {
                    $tg = $this->awareHelper->withTelegramBot($bot);
                    $message = $tg->trans('reply.use_primary');
                    $message .= ":\n\n";
                    $message .= $this->botLinkViewProvider->getTelegramBotLinkView($newBot);
                    $message = $tg->attentionText($message);
                    $tg->reply($message);
                }

                return;
            }

            $text = $this->inputProvider->getTelegramInputByUpdate($bot->getUpdate());
            $commands = $group->getTelegramCommands($bot);

            if ($beforeConversationCommand = $this->commandFinder->findBeforeConversationCommand($text, $commands)) {
                call_user_func($beforeConversationCommand->getCallback());
            } elseif ($conversation = $this->conversationManager->getCurrentTelegramConversation($bot)) {
                $this->conversationManager->continueTelegramConversation($bot, $conversation);
            } elseif ($command = $this->commandFinder->findCommand($text, $commands)) {
                call_user_func($command->getCallback());
            } elseif ($fallbackCommand = $this->commandFinder->findFallbackCommand($commands)) {
                call_user_func($fallbackCommand->getCallback());
            }
        } catch (Throwable $exception) {
            if ($this->environment === 'test') {
                throw $exception;
            }
            $this->logger->error($exception);

            if ($errorCommand = $this->commandFinder->findErrorCommand($commands)) {
                call_user_func($errorCommand->getCallback(), $exception);
            }
        }
    }
}