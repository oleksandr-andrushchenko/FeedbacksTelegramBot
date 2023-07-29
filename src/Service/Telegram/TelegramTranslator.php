<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Service\Util\Array\ArrayKeyQuoter;
use Symfony\Contracts\Translation\TranslatorInterface;

class TelegramTranslator implements TranslatorInterface
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    )
    {
    }

    public function trans(string $id, array $parameters = [], string $domain = null, string $locale = null): string
    {
        return $this->translator->trans($id, $parameters, $domain === null ? 'translator' : $domain, $locale);
    }

    public function getLocale(): string
    {
        return $this->translator->getLocale();
    }
}