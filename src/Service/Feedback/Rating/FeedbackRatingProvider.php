<?php

declare(strict_types=1);

namespace App\Service\Feedback\Rating;

use App\Enum\Feedback\Rating;
use Symfony\Contracts\Translation\TranslatorInterface;

class FeedbackRatingProvider
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    )
    {
    }

    public function getRatingIcons(): iterable
    {
        static $icons = [
            Rating::extremely_unsatisfied->name => '👎👎👎',
            Rating::very_unsatisfied->name => '👎👎',
            Rating::unsatisfied->name => '👎',
            Rating::neutral->name => '🤔',
            Rating::satisfied->name => '❤️',
            Rating::very_satisfied->name => '❤️❤️',
            Rating::extremely_satisfied->name => '❤️❤️❤️',
        ];

        return $icons;
    }

    public function getRatingName(Rating $rating, string $localeCode = null): string
    {
        $name = $this->translator->trans($rating->name, domain: 'feedbacks.rating', locale: $localeCode);

        return ($rating->value > 0 ? '+' : '') . $rating->value . ' (' . $name . ')';
    }

    public function getRatingIcon(Rating $rating): ?string
    {
        return $this->getRatingIcons()[$rating->name] ?? null;
    }

    public function getRatingComposeName(Rating $rating, string $localeCode = null): string
    {
        $icon = $this->getRatingIcon($rating);
        $name = $this->getRatingName($rating, $localeCode);

        return $icon . ' ' . $name;
    }
}
