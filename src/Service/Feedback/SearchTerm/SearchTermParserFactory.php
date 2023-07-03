<?php

declare(strict_types=1);

namespace App\Service\Feedback\SearchTerm;

use Symfony\Component\DependencyInjection\ServiceLocator;

class SearchTermParserFactory
{
    public function __construct(
        private readonly ServiceLocator $parserLocator
    )
    {
    }

    public function createSearchTermParser(string $id): SearchTermParserInterface
    {
        return $this->parserLocator->get($id);
    }

    /**
     * @return SearchTermParserInterface[]
     */
    public function createSearchTermParsers(): iterable
    {
        foreach ($this->parserLocator->getProvidedServices() as $name => $service) {
            yield $name => $this->createSearchTermParser($name);
        }
    }
}
