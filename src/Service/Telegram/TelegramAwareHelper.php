<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Telegram\TelegramConversation;
use App\Enum\Telegram\TelegramView;
use App\Service\Telegram\Api\TelegramChatActionSenderInterface;
use App\Service\Telegram\Api\TelegramMessageSenderInterface;
use Longman\TelegramBot\ChatAction;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;

class TelegramAwareHelper
{
    private Telegram $telegram;

    public function __construct(
        private readonly TelegramKeyboardFactory $keyboardFactory,
        private readonly TelegramMessageSenderInterface $messageSender,
        private readonly TelegramTranslator $translator,
        private readonly TelegramConversationManager $conversationManager,
        private readonly TelegramTemplateRenderer $templateRenderer,
        private readonly TelegramChatActionSenderInterface $chatActionSender,
        private readonly TelegramChatProvider $chatProvider,
    )
    {
    }

    public function withTelegram(Telegram $telegram): static
    {
        $new = clone $this;
        $new->telegram = $telegram;

        return $new;
    }

    public function getTelegram(): Telegram
    {
        return $this->telegram;
    }

    public function getText(): ?string
    {
        return $this->getTelegram()?->getUpdate()?->getMessage()->getText();
    }

    public function matchText(?string $text): bool
    {
        return $this->getText() === $text;
    }

    public function getChatId(): ?int
    {
        return $this->chatProvider->getTelegramChatByUpdate($this->getTelegram()->getUpdate())?->getId();
    }

    public function getLanguageCode(): ?string
    {
        return $this->getTelegram()->getMessengerUser()?->getLanguageCode();
    }

    public function getCountryCode(): ?string
    {
        return $this->getTelegram()->getMessengerUser()?->getUser()->getCountryCode();
    }

    public function startConversation(string $conversationClass): static
    {
        $this->conversationManager->startTelegramConversation($this->getTelegram(), $conversationClass);

        return $this;
    }

    public function stopConversations(): static
    {
        $this->conversationManager->stopTelegramConversations($this->getTelegram());

        return $this;
    }

    public function stopConversation(TelegramConversation $conversation): static
    {
        $this->conversationManager->stopTelegramConversation($conversation);

        return $this;
    }

    public function replyView(
        string|TelegramView $template,
        array $context = [],
        Keyboard $keyboard = null,
        string $parseMode = 'HTML',
        bool $protectContent = null,
        bool $disableWebPagePreview = null
    ): static
    {
        $this->chatActionSender->sendChatAction($this->getTelegram(), $this->getChatId(), ChatAction::TYPING);
        $content = $this->templateRenderer->renderTelegramTemplate($template, $context, $this->getLanguageCode());

        $this->messageSender->sendTelegramMessage(
            $this->getTelegram(),
            $this->getChatId(),
            $content,
            keyboard: $keyboard,
            parseMode: $parseMode,
            protectContent: $protectContent,
            disableWebPagePreview: $disableWebPagePreview
        );

        return $this;
    }

    public function reply(
        string $text,
        Keyboard $keyboard = null,
        bool $protectContent = null,
        bool $disableWebPagePreview = null
    ): static
    {
        $this->chatActionSender->sendChatAction($this->getTelegram(), $this->getChatId(), ChatAction::TYPING);
        $this->messageSender->sendTelegramMessage(
            $this->getTelegram(),
            $this->getChatId(),
            $text,
            keyboard: $keyboard,
            protectContent: $protectContent,
            disableWebPagePreview: $disableWebPagePreview
        );

        return $this;
    }

    public function trans(string $id, array $parameters = [], ?string $domain = 'telegram'): string
    {
        return $this->translator->transTelegram($this->getLanguageCode(), $id, $parameters, $domain);
    }

    public function replyOk(string $transId = 'reply.ok', array $transParameters = [], ?string $domain = 'telegram'): static
    {
//        $this->reply($this->trans('reply.icon.ok') . ' ' . $this->trans($transId, $transParameters, $domain));
        $this->reply($this->trans($transId, $transParameters, $domain));
        $this->reply($this->trans('reply.icon.ok'));

        return $this;
    }

    public function replyFail(string $transId = 'reply.fail', array $transParameters = [], ?string $domain = 'telegram'): static
    {
        // todo: find command by key
//        $this->reply($this->trans('reply.icon.fail') . ' ' . $this->trans($transId, array_merge(['restart_command' => '/restart'], $transParameters), $domain));
        $this->reply($this->trans($transId, $transParameters, $domain));
        $this->reply($this->trans('reply.icon.fail'));

        return $this;
    }

    public function replyWrong(string $transId = 'reply.wrong', array $transParameters = [], ?string $domain = 'telegram'): static
    {
//        $this->reply($this->trans('reply.icon.wrong') . ' ' . $this->trans($transId, $transParameters, $domain));
        $this->reply($this->trans($transId, $transParameters, $domain));
        $this->reply($this->trans('reply.icon.wrong'));

        return $this;
    }

    public function replyUpset(string $transId = 'reply.upset', array $transParameters = [], ?string $domain = 'telegram'): static
    {
//        $this->reply($this->trans('reply.icon.upset') . ' ' . $this->trans($transId, $transParameters, $domain));
        $this->reply($this->trans($transId, $transParameters, $domain));
        $this->reply($this->trans('reply.icon.upset'));

        return $this;
    }

    public function keyboard(...$buttons): Keyboard
    {
        return $this->keyboardFactory->createTelegramKeyboard(...$buttons);
    }

    public function button(string $transId, array $transParameters = []): KeyboardButton
    {
        return $this->keyboardFactory->createTelegramButton($this->getLanguageCode(), $transId, $transParameters);
    }

    public function null(): null
    {
        return null;
    }
}