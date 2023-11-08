<?php

declare(strict_types=1);

namespace App\Message\Event\Feedback;

use App\Entity\Feedback\FeedbackSearchSearchTermTelegramNotification;
use LogicException;

readonly class FeedbackSearchSearchTermTelegramNotificationCreatedEvent
{
    private ?string $notificationId;

    public function __construct(
        ?string $notificationId = null,
        private ?FeedbackSearchSearchTermTelegramNotification $notification = null,
    )
    {
        if ($notificationId === null) {
            if ($this->notification === null) {
                throw new LogicException('Either notification id or notification should be passed`');
            }

            $this->notificationId = $this->notification->getId();
        } else {
            $this->notificationId = $notificationId;
        }
    }

    public function getNotification(): ?FeedbackSearchSearchTermTelegramNotification
    {
        return $this->notification;
    }

    public function getNotificationId(): ?string
    {
        return $this->notificationId;
    }

    public function __sleep(): array
    {
        return [
            'notificationId',
        ];
    }
}