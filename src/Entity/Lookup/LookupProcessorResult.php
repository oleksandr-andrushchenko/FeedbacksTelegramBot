<?php

declare(strict_types=1);

namespace App\Entity\Lookup;

use App\Enum\Lookup\LookupProcessorName;

class LookupProcessorResult
{
    public function __construct(
        private readonly LookupProcessorName $name,
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
