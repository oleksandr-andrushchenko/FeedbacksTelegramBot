<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\View;

use App\Transfer\Feedback\SearchTermTransfer;

class MultipleSearchTermTelegramViewProvider
{
    public function __construct(
        private readonly SearchTermTelegramViewProvider $singleViewProvider,
    )
    {
    }

    public function getSearchTermTelegramView(SearchTermTransfer $searchTerm, string $localeCode = null): string
    {
        return $this->singleViewProvider->getSearchTermTelegramView($searchTerm, $localeCode);
    }

    public function getSearchTermTelegramMainView(SearchTermTransfer $searchTerm): string
    {
        return $this->singleViewProvider->getSearchTermTelegramMainView($searchTerm);
    }

    /**
     * @param SearchTermTransfer[] $searchTerms
     * @param string|null $localeCode
     * @return string
     */
    public function getMultipleSearchTermTelegramView(array $searchTerms, string $localeCode = null): string
    {
        array_map(static fn ($searchTerm): bool => assert($searchTerm instanceof SearchTermTransfer), $searchTerms);

        return implode(
            ', ',
            array_map(
                fn (SearchTermTransfer $searchTerm): string => $this->singleViewProvider->getSearchTermTelegramView($searchTerm, $localeCode),
                $searchTerms
            )
        );
    }
}