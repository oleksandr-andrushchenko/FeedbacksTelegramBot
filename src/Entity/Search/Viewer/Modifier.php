<?php

declare(strict_types=1);

namespace App\Entity\Search\Viewer;

class Modifier
{
    public function __construct(
        private array $modifiers = [],
    )
    {
    }

    public function add(callable $modifier): self
    {
        $this->modifiers[] = $modifier;

        return $this;
    }

    public function apply($target): ?string
    {
        foreach ($this->modifiers as $modifier) {
            $target = $modifier($target);
        }

        return $target;
    }
}