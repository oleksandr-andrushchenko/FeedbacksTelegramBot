<?php

declare(strict_types=1);

namespace App\Message\Event\Feedback;

use App\Entity\Feedback\Feedback;

readonly class FeedbackSendToTelegramChannelConfirmReceivedEvent extends FeedbackEvent
{
    public function __construct(
        ?string $feedbackId = null,
        ?Feedback $feedback = null,
        private bool $showTime = false,
    )
    {
        parent::__construct($feedbackId, $feedback);
    }

    public function showTime(): bool
    {
        return $this->showTime;
    }
}
