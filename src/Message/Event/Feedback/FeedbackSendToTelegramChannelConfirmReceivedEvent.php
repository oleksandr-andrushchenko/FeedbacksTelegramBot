<?php

declare(strict_types=1);

namespace App\Message\Event\Feedback;

use App\Entity\Feedback\Feedback;
use App\Entity\Telegram\TelegramBot;

readonly class FeedbackSendToTelegramChannelConfirmReceivedEvent extends FeedbackEvent
{
    public function __construct(
        ?string $feedbackId = null,
        ?Feedback $feedback = null,
        private ?TelegramBot $telegramBot = null,
    )
    {
        parent::__construct($feedbackId, $feedback);
    }

    public function getTelegramBot(): ?TelegramBot
    {
        return $this->telegramBot;
    }
}
