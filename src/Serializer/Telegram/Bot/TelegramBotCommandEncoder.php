<?php

declare(strict_types=1);

namespace App\Serializer\Telegram\Bot;

use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;

class TelegramBotCommandEncoder implements EncoderInterface, DecoderInterface
{
    public const FORMAT = 'telegram_cmd';
    public const TRIM_ALL = 'trim_all';
    public const COMMAND_WITH_TRAILING_SLASH = 'command_with_trailing_slash';
    public const SPLIT_COMMAND_AND_PARAMS = 'split_command_and_params';
    public const COMMAND_KEY = 'command_key';
    public const PARAMS_KEY = 'params_key';
    public const PARAM_NAMES = 'param_names';

    private $defaultContext = [
        self::TRIM_ALL => true,
        self::COMMAND_WITH_TRAILING_SLASH => true,
        self::SPLIT_COMMAND_AND_PARAMS => false,
        self::COMMAND_KEY => 'command',
        self::PARAMS_KEY => 'params',
        self::PARAM_NAMES => null,
    ];

    public function __construct(array $defaultContext = [])
    {
        $this->defaultContext = array_merge($this->defaultContext, $defaultContext);
    }

    public function encode(mixed $data, string $format, array $context = []): string
    {
        if ($this->getContextValue(self::SPLIT_COMMAND_AND_PARAMS, $context)) {
            $command = $data[$this->getContextValue(self::COMMAND_KEY, $context)] ?? '';
            $params = $data[$this->getContextValue(self::PARAMS_KEY, $context)] ?? [];
        } else {
            $command = array_shift($data);
            $params = $data;
        }

        $trimAll = $this->getContextValue(self::TRIM_ALL, $context);

        if ($trimAll) {
            $command = trim($command);
        }

        $command = ltrim($command, '/');

        if ($this->getContextValue(self::COMMAND_WITH_TRAILING_SLASH, $context)) {
            $command = '/' . $command;
        }

        if ($trimAll) {
            $params = array_map(fn ($param) => trim($param), $params);
        }

        return $command . ' ' . implode(' ', $params);
    }

    public function supportsEncoding(string $format): bool
    {
        return self::FORMAT === $format;
    }

    public function decode(string $data, string $format, array $context = []): array
    {
        $peaces = explode(' ', $data);

        $trimAll = $this->getContextValue(self::TRIM_ALL, $context);

        if ($trimAll) {
            $peaces = array_map(fn ($peace) => trim($peace), $peaces);
        }

        $command = array_shift($peaces) ?? '';
        $peaces = array_values($peaces);

        $command = ltrim($command, '/');

        if ($this->getContextValue(self::COMMAND_WITH_TRAILING_SLASH, $context)) {
            $command = '/' . $command;
        }

        $paramNames = $this->getContextValue(self::PARAM_NAMES, $context);

        if (is_array($paramNames)) {
            $params = [];

            foreach (array_values($paramNames) as $index => $paramName) {
                $params[$paramName] = $peaces[$index] ?? '';
            }
        } else {
            $params = $peaces;
        }

        if ($this->getContextValue(self::SPLIT_COMMAND_AND_PARAMS)) {
            return [
                self::COMMAND_KEY => $command,
                self::PARAMS_KEY => $params,
            ];
        }

        return array_merge(
            [
                self::COMMAND_KEY => $command,
            ],
            $params
        );
    }

    public function supportsDecoding(string $format): bool
    {
        return self::FORMAT === $format;
    }

    private function getContextValue(string $key, array $context = [])
    {
        return $context[$key] ?? $this->defaultContext[$key];
    }
}