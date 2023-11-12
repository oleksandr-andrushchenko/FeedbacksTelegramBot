<?php

declare(strict_types=1);

namespace App\Entity\Lookup;

use App\Entity\Feedback\FeedbackSearch;
use App\Enum\Lookup\LookupProcessorName;
use Closure;

class LookupByFeedbackSearchProcessor
{
    public function __construct(
        private readonly LookupProcessorName $name,
        private readonly Closure $supportsClosure,
        private readonly Closure $onSearchTitleClosure,
        private readonly string $title,
        private array $records = [],
        private ?string $tip = null
    )
    {
    }

    public function getName(): LookupProcessorName
    {
        return $this->name;
    }

    public function supports(FeedbackSearch $feedbackSearch): bool
    {
        return call_user_func($this->supportsClosure, $feedbackSearch);
    }

    public function getOnSearchTitle():string {
        return call_user_func($this->)
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getRecords(): array
    {
        return $this->records;
    }

    public function addRecord(string $record): self
    {
        $this->records[] = $record;

        return $this;
    }

    public function getTip(): ?string
    {
        return $this->tip;
    }
}
