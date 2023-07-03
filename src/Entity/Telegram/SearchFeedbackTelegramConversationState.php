<?php

declare(strict_types=1);

namespace App\Entity\Telegram;

use App\Object\Feedback\SearchTermTransfer;

class SearchFeedbackTelegramConversationState extends TelegramConversationState
{
    public function __construct(
        ?int $step = null,
        private ?SearchTermTransfer $searchTerm = null,
        private ?bool $change = null,
    )
    {
        parent::__construct($step);
    }

    public function getSearchTerm(): ?SearchTermTransfer
    {
        return $this->searchTerm;
    }

    public function setSearchTerm(?SearchTermTransfer $searchTerm): self
    {
        $this->searchTerm = $searchTerm;

        return $this;
    }

    public function isChange(): ?bool
    {
        return $this->change;
    }

    public function setChange(?bool $change): self
    {
        $this->change = $change;

        return $this;
    }
}
