<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Intl\Locale;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\LocaleSwitcher;

class TelegramLocaleSwitcher
{
    public function __construct(
        private readonly LocaleSwitcher $localeSwitcher,
    )
    {
    }

    public function syncLocale(Telegram $telegram, Request $request): void
    {
        $localeCode = $telegram->getMessengerUser()?->getUser()?->getLocaleCode();

        if ($localeCode === null) {
            $localeCode = $telegram->getMessengerUser()?->getLocaleCode();
        }

        $localeCode ??= $this->localeSwitcher->getLocale();

        $this->localeSwitcher->setLocale($localeCode);
        $request->setLocale($this->localeSwitcher->getLocale());
    }

    public function switchLocale(TelegramAwareHelper $tg, null|string|Locale $locale): void
    {
        $localeCode = $locale === null ? null : (is_string($locale) ? $locale : $locale->getCode());

        $tg->getTelegram()->getMessengerUser()->getUser()->setLocaleCode($localeCode);

        if ($tg->getLocaleCode() === null) {
            $this->localeSwitcher->reset();;
        } else {
            $this->localeSwitcher->setLocale($tg->getLocaleCode());
        }
    }
}