<?php

declare(strict_types=1);

namespace App\Entity\Telegram;

class TelegramConversationState
{
    public function __construct(
        protected ?int $step = null,
        protected ?string $type = null,
        protected ?string $text = null,
        protected ?int $messageId = null,
    )
    {
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type):static
    {
        $this->type = $type;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text):static
    {
        $this->text = $text;

        return $this;
    }

    public function getStep(): ?int
    {
        return $this->step;
    }

    public function setStep(?int $step):static
    {
        $this->step = $step;

        return $this;
    }

    public function getMessageId(): ?int
    {
        return $this->messageId;
    }

    public function setMessageId(?int $messageId):static
    {
        $this->messageId = $messageId;

        return $this;
    }
}
