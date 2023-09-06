<?php

declare(strict_types=1);

namespace App\Service\Command;

use App\Entity\CommandLimit;
use App\Entity\CommandOptions;

class CommandOptionsFactory
{
    public function __invoke(array $options): CommandOptions
    {
        $limits = [];
        foreach ($options['limits'] as $period => $count) {
            $limits[] = new CommandLimit($period, $count);
        }

        return new CommandOptions(
            $limits,
            $options['log_activities'],
        );
    }
}