<?php

declare(strict_types=1);

namespace App\Service\Util\String;

class SecretsAdder
{
    function addSecrets(string $input, int $position = 1, string $char = '*', int $count = 3): string
    {
        return mb_substr($input, 0, $position) . str_repeat($char, $count) . mb_substr($input, $position + $count);
    }
}