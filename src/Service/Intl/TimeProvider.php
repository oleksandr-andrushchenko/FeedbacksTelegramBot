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
        $parameters = [
            'day' => $dateTime->format('d'),
            'month' => $this->getMonth($dateTime->format('m')),
            'year' => $dateTime->format('Y'),
        ];

        return $this->trans('date', $parameters, $localeCode);
    }

    public function getDatetime(DateTimeInterface $dateTime, string $timezone = null, string $localeCode = null): string
    {
        $dateTime = $this->applyTimezone($dateTime, $timezone);
        $localeCode = $localeCode ?? $this->translator->getLocale();
        $parameters = [
            'date' => $this->getDate($dateTime, localeCode: $localeCode),
            'hour' => $dateTime->format('H'),
            'minute' => $dateTime->format('i'),
        ];

        return $this->trans('datetime', $parameters, $localeCode);
    }

    public function getShortDate(DateTimeInterface $dateTime, string $timezone = null, string $localeCode = null): string
    {
        $dateTime = $this->applyTimezone($dateTime, $timezone);
        $localeCode = $localeCode ?? $this->translator->getLocale();
        $parameters = [
            'day' => $dateTime->format('d'),
            'short_month' => $this->getShortMonth($dateTime->format('m')),
            'year' => $dateTime->format('Y'),
        ];

        return $this->trans('short_date', $parameters, $localeCode);
    }

    public function getShortDateInterval(
        DateTimeInterface $dateTime1,
        DateTimeInterface $dateTime2,
        string $timezone = null,
        string $localeCode = null
    ): string
    {
        $year1 = $dateTime1->format('Y');
        $month1 = $dateTime1->format('m');
        $day1 = $dateTime1->format('d');
        $year2 = $dateTime2->format('Y');
        $month2 = $dateTime2->format('m');
        $day2 = $dateTime2->format('d');

        if ($year1 === $year2 && $month1 === $month2 && $day1 === $day2) {
            return $this->getShortDate($dateTime1, $timezone, $localeCode);
        }

        if ($year1 === $year2 && $month1 === $month2) {
            $parameters = [
                'day' => $day1 . ' - ' . $day2,
                'short_month' => $this->getShortMonth($month1),
                'year' => $year1,
            ];

            return $this->trans('short_date', $parameters, $localeCode);
        }

        if ($year1 === $year2) {
            $parameters = [
                'day1' => $day1,
                'short_month1' => $this->getShortMonth($month1),
                'day2' => $day2,
                'short_month2' => $this->getShortMonth($month2),
                'year' => $year1,
            ];

            return $this->trans('short_date_interval_same_year', $parameters, $localeCode);
        }

        $date1 = $this->getShortDate($dateTime1, $timezone, $localeCode);
        $date2 = $this->getShortDate($dateTime2, $timezone, $localeCode);

        return $date1 . ' - ' . $date2;
    }

    public function getShortDatetime(DateTimeInterface $dateTime, string $timezone = null, string $localeCode = null): string
    {
        $dateTime = $this->applyTimezone($dateTime, $timezone);
        $localeCode = $localeCode ?? $this->translator->getLocale();
        $parameters = [
            'short_date' => $this->getShortDate($dateTime, localeCode: $localeCode),
            'hour' => $dateTime->format('H'),
            'minute' => $dateTime->format('i'),
        ];

        return $this->trans('short_datetime', $parameters, $localeCode);
    }

    private function getMonth(int|string $num): string
    {
        return $this->trans(sprintf('month.%d', $num));
    }

    private function getShortMonth(int|string $num): string
    {
        return $this->trans(sprintf('short_month.%d', $num));
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