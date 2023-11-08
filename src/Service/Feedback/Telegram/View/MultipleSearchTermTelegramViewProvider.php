<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\View;

use App\Enum\Feedback\SearchTermType;
use App\Service\Feedback\SearchTerm\SearchTermTypeProvider;
use App\Transfer\Feedback\SearchTermTransfer;

class MultipleSearchTermTelegramViewProvider
{
    public function __construct(
        private readonly SearchTermTelegramViewProvider $searchTermTelegramViewProvider,
        private readonly SearchTermTypeProvider $searchTermTypeProvider,
    )
    {
    }

    public function getSearchTermTelegramReverseView(SearchTermTransfer $searchTerm): string
    {
        return $this->searchTermTelegramViewProvider->getSearchTermTelegramReverseView($searchTerm);
    }

    /**
     * @param SearchTermTransfer[] $searchTerms
     * @param bool $addSecrets
     * @param string|null $localeCode
     * @return string
     */
    public function getMultipleSearchTermTelegramView(
        array $searchTerms,
        bool $addSecrets = false,
        string $localeCode = null
    ): string
    {
        $count = count($searchTerms);

        if ($count === 0) {
            return '';
        }

        if ($count === 1) {
            return $this->searchTermTelegramViewProvider->getSearchTermTelegramView(
                $searchTerms[0],
                addSecrets: $addSecrets,
                localeCode: $localeCode
            );
        }

        $sortedSearchTerms = $this->getSortedSearchTerms($searchTerms);

        /** @var SearchTermTransfer $searchTerm */
        $searchTerm = array_shift($sortedSearchTerms);

        $message = $this->searchTermTelegramViewProvider->getSearchTermTelegramMainView($searchTerm, addSecrets: $addSecrets);
        $message .= ' [ ';

        $message .= $this->searchTermTypeProvider->getSearchTermTypeName($searchTerm->getType(), localeCode: $localeCode);

        foreach ($sortedSearchTerms as $searchTerm) {
            $message .= ', ';
            $message .= $this->searchTermTypeProvider->getSearchTermTypeName($searchTerm->getType(), localeCode: $localeCode);
            $message .= ': ';
            $message .= $this->searchTermTelegramViewProvider->getSearchTermTelegramMainView($searchTerm, addSecrets: $addSecrets);
        }

        $message .= ' ] ';

        return $message;
    }

    /**
     * @param SearchTermTransfer[] $searchTerms
     * @param bool $addSecrets
     * @param bool $forceType
     * @param string|null $localeCode
     * @return string
     */
    public function getPrimarySearchTermTelegramView(
        array $searchTerms,
        bool $addSecrets = false,
        bool $forceType = true,
        string $localeCode = null
    ): string
    {
        $count = count($searchTerms);

        if ($count === 0) {
            return '';
        }

        if ($count === 1) {
            $searchTerm = $searchTerms[0];
        } else {
            $searchTerm = $this->getSortedSearchTerms($searchTerms)[0];
        }

        return $this->searchTermTelegramViewProvider->getSearchTermTelegramView(
            $searchTerm,
            addSecrets: $addSecrets,
            forceType: $forceType,
            localeCode: $localeCode
        );
    }

    /**
     * @param SearchTermTransfer[] $searchTerms
     * @return SearchTermTransfer[]
     */
    private function getSortedSearchTerms(array $searchTerms): array
    {
        $sortSearchTerms = [];

        $sortTypes = [
            SearchTermType::person_name,
            SearchTermType::organization_name,
            SearchTermType::place_name,
            ...SearchTermType::messengers,
        ];

        foreach ($sortTypes as $type) {
            foreach ($searchTerms as $searchTerm) {
                if ($searchTerm->getType() === $type) {
                    $sortSearchTerms[] = $searchTerm;
                }
            }
        }

        foreach ($searchTerms as $searchTerm) {
            if (!in_array($searchTerm, $sortSearchTerms, true)) {
                $sortSearchTerms[] = $searchTerm;
            }
        }

        return $sortSearchTerms;
    }
}