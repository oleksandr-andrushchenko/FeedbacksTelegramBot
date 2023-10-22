<?php

declare(strict_types=1);

namespace App\Service\Feedback\Command;

use Symfony\Component\DependencyInjection\ServiceLocator;

class CommandStatisticProviderFactory
{
    public function __construct(
        private readonly ServiceLocator $serviceLocator,
    )
    {
    }

    public function __invoke(string $class): CommandStatisticProviderInterface
    {
        return $this->serviceLocator->get($class);
    }
}