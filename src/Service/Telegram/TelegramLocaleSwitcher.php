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
        $messengerUser = $telegram->getMessengerUser();

        $localeCode = null;

        if ($messengerUser?->getId() === null) {
            $localeCode = $telegram->getBot()->getLocaleCode();
        }

        $localeCode ??= $messengerUser?->getUser()?->getLocaleCode();
        $localeCode ??= $this->localeSwitcher->getLocale();

        $messengerUser?->getUser()->setLocaleCode($localeCode);
        $this->setLocale($localeCode);
        $request->setLocale($this->localeSwitcher->getLocale());
    }

    public function switchLocale(null|string|Locale $locale): void
    {
        $localeCode = $locale === null ? null : (is_string($locale) ? $locale : $locale->getCode());

        if ($localeCode === null) {
            $this->localeSwitcher->reset();;
        } else {
            $this->setLocale($localeCode);
        }
    }

    public function setLocale(string|Locale $locale): void
    {
        $localeCode = is_string($locale) ? $locale : $locale->getCode();
//        setlocale(LC_TIME, $localeCode);
        $this->localeSwitcher->setLocale($localeCode);
    }
}