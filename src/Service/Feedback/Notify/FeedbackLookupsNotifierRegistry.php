<?php

declare(strict_types=1);

namespace App\Service\Feedback\Notify;

use Symfony\Component\DependencyInjection\ServiceLocator;

class FeedbackLookupsNotifierRegistry
{
    public function __construct(
        private readonly ServiceLocator $serviceLocator,
    )
    {
    }

    public function getFeedbackLookupUserNotifier(string $id): FeedbackLookupsNotifierInterface
    {
        return $this->serviceLocator->get($id);
    }

    /**
     * @return FeedbackLookupsNotifierInterface[]
     */
    public function getFeedbackLookupUserNotifiers(): iterable
    {
        foreach ($this->serviceLocator->getProvidedServices() as $name => $service) {
            yield $name => $this->getFeedbackLookupUserNotifier($name);
        }
    }
}
