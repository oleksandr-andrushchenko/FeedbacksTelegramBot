<?php

declare(strict_types=1);

namespace App\Service\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Throwable;

class DryRunner
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }

    public function dryRun(callable $func): mixed
    {
        $result = null;
        $exceptionFunc = function () use ($func, &$result) {
            $result = $func();
            throw new RuntimeException('Dry run');
        };

        try {
            $this->entityManager->wrapInTransaction($exceptionFunc);
        } catch (Throwable $exception) {
            if ($exception->getMessage() !== 'Dry run') {
                throw $exception;
            }
        }

        return $result;
    }
}