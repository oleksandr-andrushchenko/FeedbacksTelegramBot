<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class  Command extends BaseCommand
{
    protected function validateRequiredOptions(InputInterface|Input $input, InputDefinition $definition): void
    {
        $missingOptions = array_filter(
            array_keys($definition->getOptions()),
            fn ($option) => $definition->getOption($option)->isValueRequired() && $input->getOption($option) === null
        );

        if (count($missingOptions) > 0) {
            throw new RuntimeException(sprintf('Not enough options (missing: "%s").', implode(', ', $missingOptions)));
        }
    }

    abstract protected function invoke(InputInterface $input, OutputInterface $output): int;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->validateRequiredOptions($input, $this->getDefinition());

        return $this->invoke($input, $output);
    }
}