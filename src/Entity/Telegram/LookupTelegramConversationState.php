<?php

declare(strict_types=1);

namespace App\Entity\Telegram;

use App\Object\Feedback\SearchTermTransfer;

class LookupTelegramConversationState extends TelegramConversationState
{
    public function __construct(
        ?int $step = null,
        private ?SearchTermTransfer $searchTerm = null,
    )
    {
        parent::__construct($step);
    }

    public function getSearchTerm(): ?SearchTermTransfer
    {
        return $this->searchTerm;
    }

    public function setSearchTerm(?SearchTermTransfer $searchTerm): static
    {
        $this->searchTerm = $searchTerm;

        return $this;
    }
}
