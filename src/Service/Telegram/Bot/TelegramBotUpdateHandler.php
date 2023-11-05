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
        private readonly TelegramBotUpdateFactory $telegramBotUpdateFactory,
        private readonly TelegramBotUpdateChecker $telegramBotUpdateChecker,
        private readonly TelegramBotNonAdminUpdateChecker $telegramBotNonAdminUpdateChecker,
        private readonly TelegramBotConversationManager $telegramBotConversationManager,
        private readonly TelegramBotMessengerUserUpserter $telegramBotMessengerUserUpserter,
        private readonly TelegramBotGroupRegistry $telegramBotGroupRegistry,
        private readonly TelegramBotCommandFinder $telegramBotCommandFinder,
        private readonly TelegramBotPaymentManager $telegramBotPaymentManager,
        private readonly TelegramBotLocaleSwitcher $telegramBotLocaleSwitcher,
        private readonly TelegramBotInputProvider $telegramBotInputProvider,
        private readonly TelegramBotRegistry $telegramBotRegistry,
        private readonly TelegramBotRepository $telegramBotRepository,
        private readonly TelegramBotAwareHelper $telegramBotAwareHelper,
        private readonly TelegramBotLinkViewProvider $telegramBotLinkViewProvider,
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
        $bot = $this->telegramBotRegistry->getTelegramBot($botEntity);
        $update = $this->telegramBotUpdateFactory->createUpdate($bot, $request);
        $bot->setUpdate($update);

        // todo: remove on production
        if ($this->telegramBotNonAdminUpdateChecker->checkNonAdminUpdate($bot)) {
            return;
        }

        if ($this->telegramBotUpdateChecker->checkTelegramUpdate($bot)) {
            return;
        }

        $messengerUser = $this->telegramBotMessengerUserUpserter->upsertTelegramMessengerUser($bot);

        $bot->setMessengerUser($messengerUser);
        $this->telegramBotLocaleSwitcher->syncLocale($bot, $request);

        TelegramLog::initialize($this->logger, $this->logger);

        try {
            $group = $this->telegramBotGroupRegistry->getTelegramGroup($bot->getEntity()->getGroup());

            if ($update->getPreCheckoutQuery() !== null) {
                if (!$bot->deleted() && $bot->getEntity()->acceptPayments()) {
                    $this->telegramBotPaymentManager->acceptPreCheckoutQuery($bot, $update->getPreCheckoutQuery());
                }
                return;
            } elseif ($update->getMessage()?->getSuccessfulPayment() !== null) {
                if (!$bot->deleted() && $bot->getEntity()->acceptPayments()) {
                    $payment = $this->telegramBotPaymentManager->acceptSuccessfulPayment($bot, $update->getMessage()->getSuccessfulPayment());
                    $group->acceptTelegramPayment($bot, $payment);
                }
                return;
            }

            if (!$group->supportsTelegramUpdate($bot)) {
                return;
            }

            if ($bot->deleted() || !$bot->primary()) {
                $newBot = $this->telegramBotRepository->findOnePrimaryByBot($bot->getEntity());

                if ($newBot === null) {
                    $this->logger->warning('Primary bot has not been found to replace deleted/non-primary one', [
                        'bot_id' => $bot->getEntity()->getId(),
                    ]);
                } else {
                    $tg = $this->telegramBotAwareHelper->withTelegramBot($bot);
                    $message = $tg->trans('reply.use_primary');
                    $message .= ":\n\n";
                    $message .= $this->telegramBotLinkViewProvider->getTelegramBotLinkView($newBot);
                    $message = $tg->attentionText($message);
                    $tg->reply($message);
                }

                return;
            }

            $text = $this->telegramBotInputProvider->getTelegramInputByUpdate($bot->getUpdate());
            $commands = $group->getTelegramCommands($bot);

            if ($beforeConversationCommand = $this->telegramBotCommandFinder->findBeforeConversationCommand($text, $commands)) {
                call_user_func($beforeConversationCommand->getCallback());
            } elseif ($conversation = $this->telegramBotConversationManager->getCurrentTelegramConversation($bot)) {
                $this->telegramBotConversationManager->continueTelegramConversation($bot, $conversation);
            } elseif ($command = $this->telegramBotCommandFinder->findCommand($text, $commands)) {
                call_user_func($command->getCallback());
            } elseif ($fallbackCommand = $this->telegramBotCommandFinder->findFallbackCommand($commands)) {
                call_user_func($fallbackCommand->getCallback());
            }
        } catch (Throwable $exception) {
            if ($this->environment === 'test') {
                throw $exception;
            }
            $this->logger->error($exception);

            if ($errorCommand = $this->telegramBotCommandFinder->findErrorCommand($commands)) {
                call_user_func($errorCommand->getCallback(), $exception);
            }
        }
    }
}