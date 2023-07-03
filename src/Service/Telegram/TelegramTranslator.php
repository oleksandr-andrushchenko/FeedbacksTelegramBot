<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Service\Util\Array\ArrayKeyQuoter;
use Symfony\Contracts\Translation\TranslatorInterface;

class TelegramTranslator
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly ArrayKeyQuoter $arrayKeyQuoter,
    )
    {
    }

    public function transTelegram(?string $languageCode, string $id, array $parameters = []): string
    {
        return $this->translator->trans($id, $this->arrayKeyQuoter->quoteKeys($parameters), 'telegram', $languageCode);
    }
}