<?php

declare(strict_types=1);

namespace App\Service\Instagram;

use App\Enum\Instagram\InstagramMessengerUserFinderType;
use App\Service\Instagram\UserFinder\InstagramMessengerUserFinderInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;

class InstagramMessengerUserFinderFactory
{
    public function __construct(
        private readonly ServiceLocator $finderLocator
    )
    {
    }

    public function createInstagramMessengerUserFinder(InstagramMessengerUserFinderType $type): InstagramMessengerUserFinderInterface
    {
        return $this->finderLocator->get($type->value);
    }

    /**
     * @return InstagramMessengerUserFinderInterface[]
     */
    public function createInstagramMessengerUserFinders(): iterable
    {
        foreach ($this->finderLocator->getProvidedServices() as $name => $service) {
            yield $name => $this->createInstagramMessengerUserFinder(InstagramMessengerUserFinderType::from($name));
        }
    }
}
