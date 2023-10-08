<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use App\Entity\Telegram\TelegramBotConversation;
use App\Entity\Telegram\TelegramBotConversationState;
use App\Service\Telegram\Bot\Api\TelegramBotChatActionSenderInterface;
use App\Service\Telegram\Bot\Api\TelegramBotMessageSenderInterface;
use App\Service\Telegram\Bot\Conversation\TelegramBotConversationManager;
use Longman\TelegramBot\ChatAction;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Entities\Location;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class TelegramBotAwareHelper
{
    private TelegramBot $bot;

    public function __construct(
        private readonly TelegramBotKeyboardFactory $keyboardFactory,
        private readonly TelegramBotMessageSenderInterface $messageSender,
        private readonly TranslatorInterface $translator,
        private readonly TelegramBotConversationManager $conversationManager,
        private readonly TelegramBotChatActionSenderInterface $chatActionSender,
        private readonly TelegramBotChatProvider $chatProvider,
        private readonly TelegramBotInputProvider $inputProvider,
        private readonly Environment $twig,
    )
    {
    }

    public function withTelegram(TelegramBot $bot): static
    {
        $new = clone $this;
        $new->bot = $bot;

        return $new;
    }

    public function getBot(): TelegramBot
    {
        return $this->bot;
    }

    public function getText(): ?string
    {
        return $this->inputProvider->getTelegramInputByUpdate($this->getBot()->getUpdate());
    }

    public function matchText(?string $text): bool
    {
        return $this->getText() === $text;
    }

    public function getChatId(): ?int
    {
        return $this->chatProvider->getTelegramChatByUpdate($this->getBot()->getUpdate())?->getId();
    }

    public function getLocation(): ?Location
    {
        return $this->getBot()->getUpdate()->getMessage()?->getLocation();
    }

    public function getLocaleCode(): ?string
    {
        return $this->getBot()->getMessengerUser()?->getUser()->getLocaleCode();
    }

    public function getCountryCode(): ?string
    {
        return $this->getBot()->getMessengerUser()?->getUser()->getCountryCode();
    }

    public function getCurrencyCode(): ?string
    {
        return $this->getBot()->getMessengerUser()?->getUser()->getCurrencyCode();
    }

    public function getTimezone(): ?string
    {
        return $this->getBot()->getMessengerUser()?->getUser()->getTimezone();
    }

    public function startConversation(string $class, TelegramBotConversationState $state = null): static
    {
        $this->conversationManager->startTelegramConversation($this->getBot(), $class, $state);

        return $this;
    }

    public function executeConversation(string $class, TelegramBotConversationState $state, string $method): static
    {
        $this->conversationManager->executeTelegramConversation($this->getBot(), $class, $state, $method);

        return $this;
    }

    public function stopCurrentConversation(): static
    {
        $this->conversationManager->stopCurrentTelegramConversation($this->getBot());

        return $this;
    }

    public function stopConversation(TelegramBotConversation $conversation): static
    {
        $this->conversationManager->stopTelegramConversation($conversation);

        return $this;
    }

    public function view(string $template, array $context = []): string
    {
        $group = $this->getBot()->getEntity()->getGroup()->name;

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
            $this->getBot(),
            $this->getChatId(),
            ChatAction::TYPING
        );
        $this->messageSender->sendTelegramMessage(
            $this->getBot()->getEntity(),
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
        $group = $this->getBot()->getEntity()->getGroup()->name;
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

    public function button(string $text, bool $requestLocation = false): KeyboardButton
    {
        return $this->keyboardFactory->createTelegramButton($text, $requestLocation);
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
        return $this->button('👌 ' . $this->trans('keyboard.yes'));
    }

    public function noButton(): KeyboardButton
    {
        return $this->button($this->trans('keyboard.no'));
    }

    public function confirmButton(): KeyboardButton
    {
        return $this->yesButton();
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