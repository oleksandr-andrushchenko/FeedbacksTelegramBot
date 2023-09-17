<?php

declare(strict_types=1);

namespace App\Service\Feedback\SearchTerm;

use App\Enum\Feedback\SearchTermType;
use App\Service\Util\Array\ArrayValueEraser;
use Symfony\Contracts\Translation\TranslatorInterface;

class FeedbackSearchTermTypeProvider
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly ArrayValueEraser $arrayValueEraser,
    )
    {
    }

    public function getSearchTermTypeName(SearchTermType $type, string $localeCode = null): string
    {
        return $this->translator->trans($type->name, domain: 'feedbacks.search_term_type', locale: $localeCode);
    }

    public function getSearchTermTypeIcon(SearchTermType $type): string
    {
        return $this->translator->trans($type->name, domain: 'feedbacks.search_term_type_icon');
    }

    public function getSearchTermTypeComposeName(SearchTermType $type, string $localeCode = null): string
    {
        $icon = $this->getSearchTermTypeIcon($type);
        $name = $this->getSearchTermTypeName($type, $localeCode);

        return $icon . ' ' . $name;
    }

    public function getSearchTermTypes(string $countryCode = null): array
    {
        return SearchTermType::cases();
    }

    public function sortSearchTermTypes(array $types): array
    {
        $sortedAll = SearchTermType::cases();

        $sorted = [];

        foreach ($sortedAll as $type) {
            if (in_array($type, $types, true)) {
                $sorted[] = $type;
            }
        }

        return $sorted;
    }

    public function moveUnknownToEnd(array $types): array
    {
        if (in_array(SearchTermType::unknown, $types, true)) {
            $types = $this->arrayValueEraser->eraseValue($types, SearchTermType::unknown);
            $types[] = SearchTermType::unknown;
        }

        return $types;
    }
}
