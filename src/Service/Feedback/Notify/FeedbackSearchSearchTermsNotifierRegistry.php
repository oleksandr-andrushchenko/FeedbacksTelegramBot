<?php

declare(strict_types=1);

namespace App\Service\Feedback\Notify;

use Symfony\Component\DependencyInjection\ServiceLocator;

class FeedbackSearchSearchTermsNotifierRegistry
{
    public function __construct(
        private readonly ServiceLocator $serviceLocator,
    )
    {
    }

    public function getFeedbackSearchSearchTermUserNotifier(string $id): FeedbackSearchSearchTermsNotifierInterface
    {
        return $this->serviceLocator->get($id);
    }

    /**
     * @return FeedbackSearchSearchTermsNotifierInterface[]
     */
    public function getFeedbackSearchSearchTermUserNotifiers(): iterable
    {
        foreach ($this->serviceLocator->getProvidedServices() as $name => $service) {
            yield $name => $this->getFeedbackSearchSearchTermUserNotifier($name);
        }
    }
}
