<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Money;
use App\Service\Intl\CurrencyProvider;

class MoneyFormatter
{
    public function __construct(
        private readonly CurrencyProvider $currencyProvider,
    )
    {
    }

    public function formatMoney(Money $money, bool $native = false): string
    {
        $currency = $this->currencyProvider->getCurrency($money->getCurrency());

        $amount = number_format($money->getAmount(), 2, ',', '');
        $space = $currency->isSpaceBetween() ? ' ' : '';
        $symbol = $native ? $currency->getNative() : $currency->getSymbol();

        if ($currency->isSymbolLeft()) {
            return sprintf('%s%s%s', $symbol, $space, $amount);
        }

        return sprintf('%s%s%s', $amount, $space, $symbol);
    }
}