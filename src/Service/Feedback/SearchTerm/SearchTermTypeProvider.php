<?php

declare(strict_types=1);

namespace App\Service\Feedback\SearchTerm;

use App\Enum\Feedback\SearchTermType;
use Symfony\Contracts\Translation\TranslatorInterface;

class SearchTermTypeProvider
{
    public function __construct(
        private readonly TranslatorInterface $translator,
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

    public function getSearchTermTypes(): array
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
}
