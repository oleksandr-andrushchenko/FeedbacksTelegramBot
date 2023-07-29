<?php

declare(strict_types=1);

namespace App\Enum\Telegram;

enum TelegramView: string
{
    case FEEDBACK = 'feedback';
    case SUBSCRIPTION = 'subscription';
    case PREMIUM = 'premium';
    case START = 'start';
    case CREATE = 'create';
    case SEARCH = 'search';
    case COUNTRY = 'country';
    case PURGE = 'purge';

    public function view(string $locale = null): string
    {
        if ($locale === null) {
            return 'tg.' . $this->value . '.html.twig';
        }

        return 'tg.' . $this->value . '.' . $locale . '.html.twig';
    }
}