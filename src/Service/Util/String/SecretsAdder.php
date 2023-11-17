<?php

declare(strict_types=1);

namespace App\Service\Util\String;

class SecretsAdder
{
    public function addSecrets(string $input, int $position = 2, string $char = '*', int $count = 3): string
    {
        $length = mb_strlen($input);

        if ($length <= $count) {
            return str_repeat($char, $length);
        }

        if ($length - $position < $count) {
            $position = $length - $count;
        }

        return mb_substr($input, 0, $position) . str_repeat($char, $count) . mb_substr($input, $position + $count);
    }
}