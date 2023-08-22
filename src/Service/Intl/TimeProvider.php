<?php

declare(strict_types=1);

namespace App\Service\Intl;

use DateTimeInterface;
use DateTimeZone;
use Symfony\Contracts\Translation\TranslatorInterface;

class TimeProvider
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    )
    {
    }

    public function getDate(DateTimeInterface $dateTime, string $timezone = null, string $localeCode = null): string
    {
        $dateTime = $this->applyTimezone($dateTime, $timezone);
        $localeCode = $localeCode ?? $this->translator->getLocale();

        return $this->trans(
            'date',
            [
                'day' => $dateTime->format('d'),
                'month' => $this->trans(sprintf('month.%d', $dateTime->format('m'))),
                'year' => $dateTime->format('Y'),
            ],
            $localeCode
        );
    }

    public function getDatetime(DateTimeInterface $dateTime, string $timezone = null, string $localeCode = null): string
    {
        $dateTime = $this->applyTimezone($dateTime, $timezone);
        $localeCode = $localeCode ?? $this->translator->getLocale();

        return $this->trans(
            'datetime',
            [
                'date' => $this->getDate($dateTime, localeCode: $localeCode),
                'hour' => $dateTime->format('H'),
                'minute' => $dateTime->format('i'),
            ],
            $localeCode
        );
    }

    public function getShortDate(DateTimeInterface $dateTime, string $timezone = null, string $localeCode = null): string
    {
        $dateTime = $this->applyTimezone($dateTime, $timezone);
        $localeCode = $localeCode ?? $this->translator->getLocale();

        return $this->trans(
            'short_date',
            [
                'day' => $dateTime->format('d'),
                'short_month' => $this->trans(sprintf('short_month.%d', $dateTime->format('m'))),
                'year' => $dateTime->format('Y'),
            ],
            $localeCode
        );
    }

    public function getShortDatetime(DateTimeInterface $dateTime, string $timezone = null, string $localeCode = null): string
    {
        $dateTime = $this->applyTimezone($dateTime, $timezone);
        $localeCode = $localeCode ?? $this->translator->getLocale();

        return $this->trans(
            'short_datetime',
            [
                'short_date' => $this->getShortDate($dateTime, localeCode: $localeCode),
                'hour' => $dateTime->format('H'),
                'minute' => $dateTime->format('i'),
            ],
            $localeCode
        );
    }

    private function trans(string $id, array $parameters = [], string $localeCode = null): string
    {
        return $this->translator->trans($id, $parameters, 'time', $localeCode);
    }

    private function applyTimezone(DateTimeInterface $dateTime, string $timezone = null): DateTimeInterface
    {
        if ($timezone === null) {
            return $dateTime;
        }

        return $dateTime->setTimezone(new DateTimeZone($timezone));
    }
}