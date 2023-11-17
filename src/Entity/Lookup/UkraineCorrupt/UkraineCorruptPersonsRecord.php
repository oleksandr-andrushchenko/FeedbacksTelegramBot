<?php

declare(strict_types=1);

namespace App\Entity\Lookup\UkraineCorrupt;

class UkraineCorruptPersonsRecord
{
    public function __construct(
        private array $persons = [],
    )
    {
    }

    /**
     * @return UkraineCorruptPerson[]
     */
    public function getPersons(): array
    {
        return $this->persons;
    }

    public function addPerson(UkraineCorruptPerson $person): self
    {
        $this->persons[] = $person;

        return $this;
    }
}