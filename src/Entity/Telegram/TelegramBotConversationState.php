<?php

declare(strict_types=1);

namespace App\Entity\Telegram;

class TelegramBotConversationState
{
    public function __construct(
        protected ?int $step = null,
        private ?array $skipHelpButtons = null,
    )
    {
    }

    public function getStep(): ?int
    {
        return $this->step;
    }

    public function setStep(?int $step): static
    {
        $this->step = $step;

        return $this;
    }

    public function getSkipHelpButtons(): ?array
    {
        return $this->skipHelpButtons;
    }

    public function setSkipHelpButtons(?array $skipHelpButtons): static
    {
        $this->skipHelpButtons = $skipHelpButtons;

        return $this;
    }

    public function addSkipHelpButton(string $query): void
    {
        $this->setSkipHelpButtons(array_merge($this->getSkipHelpButtons() ?? [], [$query]));
    }

    public function hasNotSkipHelpButton(string $query): bool
    {
        if ($this->getSkipHelpButtons() === null) {
            return true;
        }

        return !in_array($query, $this->getSkipHelpButtons(), true);
    }
}
