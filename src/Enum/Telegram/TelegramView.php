<?php

declare(strict_types=1);

namespace App\Enum\Telegram;

enum TelegramView: string
{
    case DESCRIPTION = 'description';
    case COMMAND = 'command';
    case FEEDBACK = 'feedback';
    case SUBSCRIPTION = 'subscription';
    case DESCRIBE_SUBSCRIBE = 'describe_subscribe';
    case DESCRIBE_START = 'describe_start';
    case DESCRIBE_CREATE = 'describe_create';
    case DESCRIBE_SEARCH = 'describe_search';
    case DESCRIBE_COUNTRY = 'describe_country';
    case DESCRIBE_LOCALE = 'describe_locale';
    case DESCRIBE_PURGE = 'describe_purge';
    case DESCRIBE_CONTACT = 'describe_contact';
    case QUERY_CONTACT = 'query_contact';
    case DESCRIBE_RESTART = 'describe_restart';
}