<?php

declare(strict_types=1);

namespace App;

use App\Service\Telegram\TelegramTranslator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TwigExtension extends AbstractExtension
{
    public function __construct(
        private readonly TelegramTranslator $telegramTranslator,
    )
    {
    }

    public function getFilters()
    {
        return [
            new TwigFilter('transTelegram', [$this, 'transTelegram']),
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('transTelegram', [$this, 'transTelegram']),
        ];
    }

    public function transTelegram(?string $id, string $locale, array $arguments = []): string
    {
        if (empty($id)) {
            return '';
        }

        return $this->telegramTranslator->transTelegram($locale, $id, $arguments);
    }
}