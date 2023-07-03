<?php

declare(strict_types=1);

namespace App\Exception;

use Generator;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Throwable;

class ValidatorException extends Exception
{
    public function __construct(
        private readonly ConstraintViolationListInterface $violations,
        int $code = 0,
        ?Throwable $previous = null
    )
    {
        parent::__construct(implode("\r\n", iterator_to_array($this->getMessages())), $code, $previous);
    }

    public function getViolations(): ConstraintViolationListInterface
    {
        return $this->violations;
    }

    public function getMessages(): Generator
    {
        foreach ($this->violations as $violation) {
            yield $violation->getMessage();
        }
    }

    public function getFirstMessage(): string
    {
        foreach ($this->violations as $violation) {
            return $violation->getMessage();
        }
    }

    public function getFirstProperty(): string
    {
        foreach ($this->violations as $violation) {
            return $violation->getPropertyPath();
        }
    }

    public function isFirstProperty(string $propertyToCheck): bool
    {
        $propertiesToCheck = func_get_args();

        if (count($propertiesToCheck) > 1) {
            foreach ($propertiesToCheck as $propertyToCheck) {
                if (!$this->isFirstProperty($propertyToCheck)) {
                    return false;
                }
            }

            return true;
        }
        return $this->getFirstProperty() === $propertyToCheck;
    }
}
