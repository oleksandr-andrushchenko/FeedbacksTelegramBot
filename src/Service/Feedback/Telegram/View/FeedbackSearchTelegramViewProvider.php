<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\View;

use App\Entity\Feedback\FeedbackSearch;
use App\Service\Feedback\SearchTerm\SearchTermByFeedbackSearchProvider;
use App\Service\Intl\CountryProvider;
use App\Service\Intl\TimeProvider;
use App\Service\Telegram\TelegramAwareHelper;

class FeedbackSearchTelegramViewProvider
{
    public function __construct(
        private readonly SearchTermByFeedbackSearchProvider $searchTermProvider,
        private readonly SearchTermTelegramViewProvider $searchTermViewProvider,
        private readonly CountryProvider $countryProvider,
        private readonly TimeProvider $timeProvider,
    )
    {
    }

    public function getFeedbackSearchTelegramView(TelegramAwareHelper $tg, FeedbackSearch $feedbackSearch, int $number = null): string
    {
        $searchTerm = $this->searchTermProvider->getSearchTermByFeedbackSearch($feedbackSearch);

        $country = null;
        if ($feedbackSearch->getCountryCode() !== null) {
            $country = $this->countryProvider->getCountry($feedbackSearch->getCountryCode());
        }

        return $tg->view('feedback_search', [
            'number' => $number,
            'search_term' => $this->searchTermViewProvider->getSearchTermTelegramView($searchTerm),
            'country' => $this->countryProvider->getCountryComposeName($country),
            'created_at' => $this->timeProvider->getShortDate(
                $feedbackSearch->getCreatedAt(),
                timezone: $tg->getTimezone(),
                localeCode: $tg->getLocaleCode()
            ),
        ]);
    }
}