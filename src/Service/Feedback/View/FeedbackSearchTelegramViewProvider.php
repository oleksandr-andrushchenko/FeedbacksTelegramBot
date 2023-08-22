<?php

declare(strict_types=1);

namespace App\Service\Feedback\View;

use App\Entity\Feedback\FeedbackSearch;
use App\Service\Feedback\SearchTerm\SearchTermByFeedbackSearchProvider;
use App\Service\Intl\CountryProvider;
use App\Service\Intl\TimeProvider;
use App\Service\Telegram\TelegramAwareHelper;
use Twig\Environment;

class FeedbackSearchTelegramViewProvider
{
    public function __construct(
        private readonly Environment $twig,
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

        return $this->twig->render('tg.feedback_search.html.twig', [
            'number' => $number,
            'search_term' => $this->searchTermViewProvider->getSearchTermTelegramView($searchTerm),
            'country' => $this->countryProvider->getComposeCountryName($country),
            'created_at' => $this->timeProvider->getShortDate(
                $feedbackSearch->getCreatedAt(),
                timezone: $tg->getTimezone(),
                localeCode: $tg->getLocaleCode()
            ),
        ]);
    }
}