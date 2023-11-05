<?php

declare(strict_types=1);

namespace App\Message\Command;

use LogicException;

readonly class LogActivityCommand
{
    private ?string $entityClass;
    private ?string $entityId;

    public function __construct(
        ?string $entityClass = null,
        ?string $entityId = null,
        private ?object $entity = null,
    )
    {
        if ($entityClass === null && $entityId === null) {
            if ($this->entity === null) {
                throw new LogicException('Either entity class & id or entity should be passed`');
            }

            $this->entityClass = get_class($this->entity);
            $this->entityId = $this->entity->getId();
        } else {
            $this->entityId = $entityId;
            $this->entityClass = $entityClass;
        }
    }

    public function getEntityClass(): ?string
    {
        return $this->entityClass;
    }

    public function getEntityId(): ?string
    {
        return $this->entityId;
    }

    public function getEntity(): ?object
    {
        return $this->entity;
    }

    public function __sleep(): array
    {
        return [
            'entityClass',
            'entityId',
        ];
    }
}