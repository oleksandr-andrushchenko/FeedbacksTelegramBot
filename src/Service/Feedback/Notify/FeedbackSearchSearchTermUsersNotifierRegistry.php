<?php

declare(strict_types=1);

namespace App\Service\Feedback\Notify;

use Symfony\Component\DependencyInjection\ServiceLocator;

class FeedbackSearchSearchTermUsersNotifierRegistry
{
    public function __construct(
        private readonly ServiceLocator $serviceLocator,
    )
    {
    }

    public function getFeedbackSearchSearchTermUserNotifier(string $id): FeedbackSearchSearchTermUsersNotifierInterface
    {
        return $this->serviceLocator->get($id);
    }

    /**
     * @return FeedbackSearchSearchTermUsersNotifierInterface[]
     */
    public function getFeedbackSearchSearchTermUserNotifiers(): iterable
    {
        foreach ($this->serviceLocator->getProvidedServices() as $name => $service) {
            yield $name => $this->getFeedbackSearchSearchTermUserNotifier($name);
        }
    }
}
