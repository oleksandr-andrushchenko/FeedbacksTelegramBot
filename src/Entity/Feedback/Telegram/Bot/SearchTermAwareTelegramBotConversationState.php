<?php

declare(strict_types=1);

namespace App\Entity\Feedback\Telegram\Bot;

use App\Entity\Telegram\TelegramBotConversationState;
use App\Transfer\Feedback\SearchTermTransfer;

abstract class SearchTermAwareTelegramBotConversationState extends TelegramBotConversationState
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
