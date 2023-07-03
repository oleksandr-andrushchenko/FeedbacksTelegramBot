<?php

declare(strict_types=1);

namespace App\Tests\Traits\Telegram;

use App\Serializer\Telegram\CreateFeedbackTelegramConversationStateNormalizer;

trait CreateFeedbackTelegramConversationStateNormalizerProviderTrait
{
    public function getCreateFeedbackTelegramConversationStateNormalizer(): CreateFeedbackTelegramConversationStateNormalizer
    {
        return static::getContainer()->get('app.normalizer.telegram_conversation_state_create_feedback');
    }
}