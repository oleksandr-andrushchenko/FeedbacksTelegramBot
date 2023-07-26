<?php

declare(strict_types=1);

namespace App\Service\Intl;

use App\Entity\Intl\Currency;

class TelegramBotCurrenciesFetcher implements CurrenciesFetcherInterface
{
    public function fetchCurrencies(): ?array
    {
        $response = file_get_contents('https://core.telegram.org/bots/payments/currencies.json');
        $data = json_decode($response, true);

        unset($response);

        if (!is_array($data)) {
            return null;
        }

        $currencies = [];

        foreach ($data as $currency) {
            if (!isset($currency['code'], $currency['exp'], $currency['min_amount'])) {
                continue;
            }

            $code = $currency['code'];
            $symbol = $currency['symbol'] ?? null;
            $native = $currency['native'] ?? null;
            $symbolLeft = $currency['symbol_left'] ?? null;
            $spaceBetween = $currency['space_between'] ?? null;
            $exp = $currency['exp'];
            $minAmount = $currency['min_amount'];

            $rate = pow(10, $exp) / $minAmount;

            $currencies[$code] = new Currency($code, $rate, $exp, $symbol, $native, $symbolLeft, $spaceBetween);
        }

        return count($currencies) === 0 ? null : array_values($currencies);
    }
}