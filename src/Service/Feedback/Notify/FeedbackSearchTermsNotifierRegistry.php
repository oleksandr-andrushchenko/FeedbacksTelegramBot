<?php

declare(strict_types=1);

namespace App\Service\Feedback\Notify;

use Symfony\Component\DependencyInjection\ServiceLocator;

class FeedbackSearchTermsNotifierRegistry
{
    public function __construct(
        private readonly ServiceLocator $serviceLocator,
    )
    {
    }

    public function getFeedbackSearchTermUserNotifier(string $id): FeedbackSearchTermsNotifierInterface
    {
        return $this->serviceLocator->get($id);
    }

    /**
     * @return FeedbackSearchTermsNotifierInterface[]
     */
    public function getFeedbackSearchTermUserNotifiers(): iterable
    {
        foreach ($this->serviceLocator->getProvidedServices() as $name => $service) {
            yield $name => $this->getFeedbackSearchTermUserNotifier($name);
        }
    }
}
