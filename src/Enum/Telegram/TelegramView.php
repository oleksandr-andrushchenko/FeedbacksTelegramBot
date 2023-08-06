<?php

declare(strict_types=1);

namespace App\Enum\Telegram;

enum TelegramView: string
{
    case COMMAND = 'command';
    case FEEDBACK = 'feedback';
    case SUBSCRIPTION = 'subscription';
    case PREMIUM = 'premium';
    case START = 'start';
    case CREATE = 'create';
    case SEARCH = 'search';
    case COUNTRY = 'country';
    case LOCALE = 'locale';
    case PURGE = 'purge';
    case MESSAGE = 'message';
    case RESTART = 'restart';

    public function view(string $locale = null): string
    {
        if ($locale === null) {
            return 'tg.' . $this->value . '.html.twig';
        }

        return 'tg.' . $this->value . '.' . $locale . '.html.twig';
    }
}