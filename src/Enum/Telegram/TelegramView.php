<?php

declare(strict_types=1);

namespace App\Enum\Telegram;

enum TelegramView: string
{
    case FEEDBACK = 'feedback';
    case FEEDBACK_USER_SUBSCRIPTION = 'feedback_user_subscription';
    case DESCRIBE_PREMIUM = 'describe_premium';
    case DESCRIBE_COUNTRY = 'describe_country';
}