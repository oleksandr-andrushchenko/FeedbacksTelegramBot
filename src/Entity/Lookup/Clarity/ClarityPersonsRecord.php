<?php

declare(strict_types=1);

namespace App\Entity\Lookup\Clarity;

class ClarityPersonsRecord
{
    public function __construct(
        private array $persons = []
    )
    {
    }

    /**
     * @return ClarityPerson[]
     */
    public function getPersons(): array
    {
        return $this->persons;
    }

    public function addPerson(ClarityPerson $person): self
    {
        $this->persons[] = $person;

        return $this;
    }
}