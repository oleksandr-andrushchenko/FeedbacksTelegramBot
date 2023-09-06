<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\View;

use App\Entity\Feedback\Feedback;
use App\Service\Feedback\Rating\FeedbackRatingProvider;
use App\Service\Feedback\SearchTerm\SearchTermByFeedbackProvider;
use App\Service\Intl\CountryProvider;
use App\Service\Intl\TimeProvider;
use App\Service\Telegram\TelegramAwareHelper;

class FeedbackTelegramViewProvider
{
    public function __construct(
        private readonly SearchTermByFeedbackProvider $searchTermProvider,
        private readonly SearchTermTelegramViewProvider $searchTermViewProvider,
        private readonly CountryProvider $countryProvider,
        private readonly TimeProvider $timeProvider,
        private readonly FeedbackRatingProvider $ratingProvider,
    )
    {
    }

    public function getFeedbackTelegramView(TelegramAwareHelper $tg, Feedback $feedback, int $number = null): string
    {
        $searchTerm = $this->searchTermProvider->getSearchTermByFeedback($feedback);

        $country = null;
        if ($feedback->getCountryCode() !== null) {
            $country = $this->countryProvider->getCountry($feedback->getCountryCode());
        }

        return $tg->view('feedback', [
            'number' => $number,
            'search_term' => $this->searchTermViewProvider->getSearchTermTelegramView($searchTerm),
            'rating' => $this->ratingProvider->getRatingComposeName($feedback->getRating()),
            'description' => $feedback->getDescription(),
            'country' => $this->countryProvider->getCountryComposeName($country),
            'created_at' => $this->timeProvider->getShortDate(
                $feedback->getCreatedAt(),
                timezone: $tg->getTimezone(),
                localeCode: $tg->getLocaleCode()
            ),
        ]);
    }
}