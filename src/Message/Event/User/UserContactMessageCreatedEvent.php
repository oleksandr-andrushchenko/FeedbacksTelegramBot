<?php

declare(strict_types=1);

namespace App\Message\Event\User;

use App\Entity\User\UserContactMessage;
use LogicException;

readonly class UserContactMessageCreatedEvent
{
    private ?string $messageId;

    public function __construct(
        private ?UserContactMessage $message = null,
        ?string $messageId = null,
    )
    {
        if ($messageId === null) {
            if ($this->message === null) {
                throw new LogicException('Either message id or message should be passed`');
            }

            $this->messageId = $this->message->getId();
        } else {
            $this->messageId = $messageId;
        }
    }

    public function getMessage(): ?UserContactMessage
    {
        return $this->message;
    }

    public function getMessageId(): ?string
    {
        return $this->messageId;
    }

    public function __sleep(): array
    {
        return [
            'messageId',
        ];
    }
}
