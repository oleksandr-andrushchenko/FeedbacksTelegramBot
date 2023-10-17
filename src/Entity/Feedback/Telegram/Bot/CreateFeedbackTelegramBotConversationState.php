<?php

declare(strict_types=1);

namespace App\Entity\Feedback\Telegram\Bot;

use App\Entity\Telegram\TelegramBotConversationState;
use App\Enum\Feedback\Rating;
use App\Transfer\Feedback\SearchTermTransfer;
use LogicException;

class CreateFeedbackTelegramBotConversationState extends TelegramBotConversationState
{
    public function __construct(
        ?int $step = null,
        private ?array $searchTerms = null,
        private ?Rating $rating = null,
        private ?string $description = null,
        private ?int $createdId = null,
    )
    {
        parent::__construct($step);
    }

    public function getSearchTerms(): ?array
    {
        return $this->searchTerms;
    }

    public function addSearchTerm(SearchTermTransfer $searchTerm): self
    {
        if ($this->searchTerms === null) {
            $this->searchTerms = [];
        }

        $this->searchTerms[] = $searchTerm;

        return $this;
    }

    public function removeSearchTerm(SearchTermTransfer $termRemove): self
    {
        foreach ($this->searchTerms as $index => $searchTerm) {
            if ($searchTerm === $termRemove) {
                unset($this->searchTerms[$index]);
                break;
            }
        }

        $this->searchTerms = array_values(array_filter($this->searchTerms));

        if (count($this->searchTerms) === 0) {
            $this->searchTerms = null;
        }

        return $this;
    }

    public function getFirstSearchTerm(): SearchTermTransfer
    {
        if ($this->searchTerms === null || count($this->searchTerms) === 0) {
            throw new LogicException('No terms found');
        }

        return $this->searchTerms[0];
    }

    public function getLastSearchTerm(): SearchTermTransfer
    {
        if ($this->searchTerms === null || count($this->searchTerms) === 0) {
            throw new LogicException('No terms found');
        }

        return $this->searchTerms[count($this->searchTerms) - 1];
    }

    public function setSearchTerms(?array $searchTerms): self
    {
        $this->searchTerms = $searchTerms;

        return $this;
    }

    public function getRating(): ?Rating
    {
        return $this->rating;
    }

    public function setRating(?Rating $rating): self
    {
        $this->rating = $rating;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getCreatedId(): ?int
    {
        return $this->createdId;
    }

    public function setCreatedId(?int $createdId): self
    {
        $this->createdId = $createdId;

        return $this;
    }
}
