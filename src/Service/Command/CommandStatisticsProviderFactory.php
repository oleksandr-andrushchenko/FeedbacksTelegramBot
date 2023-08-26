<?php

declare(strict_types=1);

namespace App\Service\Command;

use Symfony\Component\DependencyInjection\ServiceLocator;

class CommandStatisticsProviderFactory
{
    public function __construct(
        private readonly ServiceLocator $serviceLocator,
    )
    {
    }

    public function createCommandStatisticsProvider(string $class): CommandStatisticsProviderInterface
    {
        return $this->serviceLocator->get($class);
    }
}