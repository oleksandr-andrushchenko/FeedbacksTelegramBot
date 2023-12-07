<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\Intl\CountryProvider;
use App\Service\Intl\TimeProvider;
use App\Service\Util\String\SecretsAdder;
use DateTimeInterface;
use App\Entity\Search\Viewer\Modifiers;

class Modifier
{
    public function __construct(
        private readonly SecretsAdder $secretsAdder,
        private readonly TimeProvider $timeProvider,
        private readonly CountryProvider $countryProvider,
    )
    {
    }

    public function create(): Modifiers
    {
        return new Modifiers();
    }

    public function boldModifier(): callable
    {
        return static fn ($any): ?string => empty($any) ? null : ('<b>' . $any . '</b>');
    }

    public function italicModifier(): callable
    {
        return static fn ($any): ?string => empty($any) ? null : ('<i>' . $any . '</i>');
    }

    public function underlineModifier(): callable
    {
        return static fn ($any): ?string => empty($any) ? null : ('<u>' . $any . '</u>');
    }

    public function linkModifier(?string $href): callable
    {
        return static fn ($any): ?string => empty($any) ? null : (empty($href) ? $any : ('<a href="' . $href . '">' . $any . '</a>'));
    }

    public function secretsModifier(): callable
    {
        return fn ($any): ?string => empty($any) ? null : $this->secretsAdder->addSecrets($any);
    }

    public function greenWhiteModifier(string $active = null, string $inactive = null): callable
    {
        return fn ($any): ?string => $any === null ? null : rtrim($any ? ('🟢 ' . $active) : ('⚪️ ' . $inactive));
    }

    public function redModifier(): callable
    {
        return static fn ($any): ?string => $any === null ? null : '🔴';
    }

    public function redWhiteModifier(string $active = null, string $inactive = null): callable
    {
        return fn ($any): ?string => $any === null ? null : rtrim($any ? ('🔴 ' . $active) : ('⚪️ ' . $inactive));
    }

    public function redGreenModifier(string $red = null, string $green = null): callable
    {
        return fn ($any): ?string => $any === null ? null : rtrim($any ? ('🔴 ' . $red) : ('🟢 ' . $green));
    }

    public function slashesModifier(): callable
    {
        return static fn ($any): ?string => empty($any) ? null : addslashes($any);
    }

    public function conditionalModifier($condition): callable
    {
        return static fn ($any): mixed => $condition ? $any : null;
    }

    public function bracketsModifier(?string $add): callable
    {
        return fn ($any): ?string => empty($any) ? null : (empty($add) ? $any : ($any . ' [ ' . $add . ' ]'));
    }

    public function filterModifier(): callable
    {
        return static fn (?array $array): ?array => empty($array) ? null : array_filter($array);
    }

    public function implodeModifier(string $separator): callable
    {
        return static fn (?array $array): ?string => empty($array) ? null : implode($separator, $array);
    }

    public function appendModifier(?string $append): callable
    {
        return static fn ($any): ?string => empty($any) ? null : (empty($append) ? $any : ($any . ' ' . $append));
    }

    public function datetimeModifier(string|int $format, string $timezone = null, string $locale = null): callable
    {
        if (is_string($format)) {
            return static fn (?DateTimeInterface $any): ?string => empty($any) ? null : $any->format($format);
        }

        return fn (?DateTimeInterface $any): ?string => empty($any) ? null : $this->timeProvider->format($format, $any, $timezone, $locale);
    }

    public function trimModifier(): callable
    {
        return static fn ($any): ?string => empty($any) ? null : trim($any);
    }

    public function numberFormatModifier(int $decimals = 0, ?string $decimalSeparator = '.', ?string $thousandsSeparator = ','): callable
    {
        return static fn ($any): ?string => empty($any) ? null : number_format((float) $any, $decimals, $decimalSeparator, $thousandsSeparator);
    }

    public function markModifier(): callable
    {
        return static fn ($any): ?string => match (true) {
            $any < 0 => '🔴',
            $any === 0 => '⚪️',
            $any > 0 => '🟢',
            default => null,
        };
    }

    public function ratingModifier(): callable
    {
        return static fn ($any): ?string => empty($any) ? null : str_repeat('⭐️', (int) round((float) $any));
    }

    public function spoilerModifier(): callable
    {
        return static fn ($any): string => empty($any) ? null : ('<tg-spoiler>' . $any . '</tg-spoiler>');
    }

    public function countryModifier(string $locale = null): callable
    {
        return fn ($any): ?string => empty($any) ? null : $this->countryProvider->getCountryComposeName($any, localeCode: $locale);
    }

    public function nullModifier(): callable
    {
        return static fn ($any): mixed => $any;
    }
}
