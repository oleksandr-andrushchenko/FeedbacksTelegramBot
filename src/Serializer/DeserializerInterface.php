<?php

declare(strict_types=1);

namespace App\Serializer;

interface DeserializerInterface
{
    public function deserialize(mixed $data, string $type = null, string $format = null, array $context = []): mixed;
}