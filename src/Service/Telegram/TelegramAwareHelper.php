<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Telegram\TelegramConversation;
use App\Entity\Telegram\TelegramConversationState;
use App\Service\Telegram\Api\TelegramChatActionSenderInterface;
use App\Service\Telegram\Api\TelegramMessageSenderInterface;
use Longman\TelegramBot\ChatAction;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class TelegramAwareHelper
{
    private Telegram $telegram;

    public function __construct(
        private readonly TelegramKeyboardFactory $keyboardFactory,
        private readonly TelegramMessageSenderInterface $messageSender,
        private readonly TranslatorInterface $translator,
        private readonly TelegramConversationManager $conversationManager,
        private readonly TelegramChatActionSenderInterface $chatActionSender,
        private readonly TelegramChatProvider $chatProvider,
        private readonly TelegramInputProvider $inputProvider,
        private readonly Environment $twig,
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
        return $this->inputProvider->getTelegramInputByUpdate($this->getTelegram()->getUpdate());
    }

    public function matchText(?string $text): bool
    {
        return $this->getText() === $text;
    }

    public function getChatId(): ?int
    {
        return $this->chatProvider->getTelegramChatByUpdate($this->getTelegram()->getUpdate())?->getId();
    }

    public function getLocaleCode(): ?string
    {
        return $this->getTelegram()->getMessengerUser()?->getUser()->getLocaleCode();
    }

    public function getCountryCode(): ?string
    {
        return $this->getTelegram()->getMessengerUser()?->getUser()->getCountryCode();
    }

    public function getCurrencyCode(): ?string
    {
        return $this->getTelegram()->getMessengerUser()?->getUser()->getCurrencyCode();
    }

    public function getTimezone(): ?string
    {
        return $this->getTelegram()->getMessengerUser()?->getUser()->getTimezone();
    }

    public function startConversation(string $class, TelegramConversationState $state = null): static
    {
        $this->conversationManager->startTelegramConversation($this->getTelegram(), $class, $state);

        return $this;
    }

    public function executeConversation(string $class, TelegramConversationState $state, string $method): static
    {
        $this->conversationManager->executeTelegramConversation($this->getTelegram(), $class, $state, $method);

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

    public function view(string $template, array $context = []): string
    {
        $group = $this->getTelegram()->getBot()->getGroup()->name;

        return $this->twig->render(sprintf('%s.tg.%s.html.twig', $group, $template), $context);
    }

    public function reply(
        string $text,
        Keyboard $keyboard = null,
        string $parseMode = 'HTML',
        bool $protectContent = null,
        bool $disableWebPagePreview = true,
        bool $keepKeyboard = false
    ): static
    {
        $this->chatActionSender->sendChatAction(
            $this->getTelegram(),
            $this->getChatId(),
            ChatAction::TYPING
        );
        $this->messageSender->sendTelegramMessage(
            $this->getTelegram(),
            $this->getChatId(),
            $text,
            keyboard: $keyboard,
            parseMode: $parseMode,
            protectContent: $protectContent,
            disableWebPagePreview: $disableWebPagePreview,
            keepKeyboard: $keepKeyboard
        );

        return $this;
    }

    public function trans(string $id, array $parameters = [], ?string $domain = null, string $locale = null): string
    {
        $group = $this->getTelegram()->getBot()->getGroup()->name;
        $prefix = $group . '.tg';
        $domain = $domain === null ? $prefix : ($prefix . '.' . $domain);

        return $this->translator->trans($id, $parameters, $domain, $locale ?? $this->getLocaleCode());
    }

    public function okText(string $text): string
    {
        return '🫡 ' . $text;
    }

    public function failText(string $text): string
    {
        return '🤕 ' . $text;
    }

    public function attentionText(string $text): string
    {
        return '‼️ ' . $text;
    }

    public function wrongText(string $text): string
    {
        return '🤔 ' . $text;
    }

    public function upsetText(string $text): string
    {
        return '😐 ' . $text;
    }

    public function infoText(string $text): string
    {
        return 'ℹ️ ' . $text;
    }

    public function keyboard(...$buttons): Keyboard
    {
        return $this->keyboardFactory->createTelegramKeyboard(...$buttons);
    }

    public function button(string $text): KeyboardButton
    {
        return $this->keyboardFactory->createTelegramButton($text);
    }

    public function inlineKeyboard(...$buttons): InlineKeyboard
    {
        return $this->keyboardFactory->createTelegramInlineKeyboard(...$buttons);
    }

    public function inlineButton(string $text): InlineKeyboardButton
    {
        return $this->keyboardFactory->createTelegramInlineButton($text);
    }

    public function yesButton(): KeyboardButton
    {
        return $this->button($this->trans('keyboard.yes'));
    }

    public function noButton(): KeyboardButton
    {
        return $this->button($this->trans('keyboard.no'));
    }

    public function confirmButton(): KeyboardButton
    {
        return $this->button('👌 ' . $this->trans('keyboard.confirm'));
    }

    public function backButton(): KeyboardButton
    {
        return $this->button('⬅️ ' . $this->trans('keyboard.back'));
    }

    public function helpButton(): KeyboardButton
    {
        return $this->button('🚨 ' . $this->trans('keyboard.help'));
    }

    public function leaveAsButton(string $text): KeyboardButton
    {
        return $this->button($this->trans('keyboard.leave_as', ['text' => $text]));
    }

    public function cancelButton(): KeyboardButton
    {
        return $this->button('❌ ' . $this->trans('keyboard.cancel'));
    }

    public function command(string $name, bool $locked = false, bool $html = false): string
    {
        if ($html) {
            return $this->view('command', [
                'name' => $name,
            ]);
        }

        return join(' ', [
            $locked ? '🔒' : $this->trans($name, domain: 'command_icon'),
            $this->trans($name, domain: 'command'),
        ]);
    }

    public function null(): null
    {
        return null;
    }
}