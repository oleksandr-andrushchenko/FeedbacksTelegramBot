<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\View;

use App\Enum\Feedback\SearchTermType;
use App\Service\Util\String\MbLcFirster;
use App\Transfer\Feedback\SearchTermTransfer;
use Symfony\Contracts\Translation\TranslatorInterface;

class MultipleSearchTermTelegramViewProvider
{
    public function __construct(
        private readonly SearchTermTelegramViewProvider $searchTermTelegramViewProvider,
        private readonly TranslatorInterface $translator,
        private readonly MbLcFirster $mbLcFirster,
    )
    {
    }

    public function getSearchTermTelegramMainView(SearchTermTransfer $searchTerm): string
    {
        return $this->searchTermTelegramViewProvider->getSearchTermTelegramMainView($searchTerm);
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

        $searchTerm = array_shift($sortedSearchTerms);

        $message = $this->searchTermTelegramViewProvider->getSearchTermTelegramView(
            $searchTerm,
            addSecrets: $addSecrets,
        );

        if ($count > 1) {
            $message .= ', ';
            $message .= $this->mbLcFirster->mbLcFirst(
                $this->translator->trans('query.additionally', domain: 'feedbacks.tg', locale: $localeCode)
            );
            $message .= ': ';
        }

        $message .= '';

        $message .= implode(
            ', ',
            array_filter(
                array_map(
                    fn (SearchTermTransfer $searchTerm): string => $this->searchTermTelegramViewProvider->getSearchTermTelegramView(
                        $searchTerm,
                        addSecrets: $addSecrets,
                        forceType: false
                    ),
                    $sortedSearchTerms
                )
            )
        );

        return $message;
    }

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