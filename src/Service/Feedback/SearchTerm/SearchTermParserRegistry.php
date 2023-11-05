<?php

declare(strict_types=1);

namespace App\Service\Feedback\SearchTerm;

use Symfony\Component\DependencyInjection\ServiceLocator;

class SearchTermParserRegistry
{
    public function __construct(
        private readonly ServiceLocator $serviceLocator,
    )
    {
    }

    public function getSearchTermParser(string $id): SearchTermParserInterface
    {
        return $this->serviceLocator->get($id);
    }

    /**
     * @return SearchTermParserInterface[]
     */
    public function getSearchTermParsers(): iterable
    {
        foreach ($this->serviceLocator->getProvidedServices() as $name => $service) {
            yield $name => $this->getSearchTermParser($name);
        }
    }
}
