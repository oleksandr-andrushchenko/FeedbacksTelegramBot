<?php

declare(strict_types=1);

namespace App\Service\Feedback\Notify;

use Symfony\Component\DependencyInjection\ServiceLocator;

class FeedbackSearchTermUsersNotifierRegistry
{
    public function __construct(
        private readonly ServiceLocator $serviceLocator,
    )
    {
    }

    public function getFeedbackSearchTermUserNotifier(string $id): FeedbackSearchTermUsersNotifierInterface
    {
        return $this->serviceLocator->get($id);
    }

    /**
     * @return FeedbackSearchTermUsersNotifierInterface[]
     */
    public function getFeedbackSearchTermUserNotifiers(): iterable
    {
        foreach ($this->serviceLocator->getProvidedServices() as $name => $service) {
            yield $name => $this->getFeedbackSearchTermUserNotifier($name);
        }
    }
}
