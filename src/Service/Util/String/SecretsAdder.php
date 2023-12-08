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

    public function addWordSecrets(string $text, string|array $excepts = null, string $char = '*'): string
    {
        $excepts = $excepts === null ? [] : (is_string($excepts) ? [$excepts] : $excepts);

        $keep = [];

        foreach ($excepts as $except) {
            $keep = array_merge($keep, array_filter(array_map('trim', explode(' ', $except))));
        }

        $all = array_filter(array_map('trim', explode(' ', $text)));
        $search = array_diff($all, $keep);

        $replace = array_map(static fn (string $occurrence): string => str_repeat($char, mb_strlen($occurrence)), $search);


        return str_ireplace($search, $replace, $text);
    }
}