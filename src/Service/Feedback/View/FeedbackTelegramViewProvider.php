<?php

declare(strict_types=1);

namespace App\Service\Feedback\View;

use App\Entity\Feedback\Feedback;
use App\Service\Feedback\SearchTerm\SearchTermByFeedbackProvider;
use App\Service\Intl\CountryProvider;
use App\Service\Intl\TimeProvider;
use App\Service\Telegram\TelegramAwareHelper;
use Twig\Environment;

class FeedbackTelegramViewProvider
{
    public function __construct(
        private readonly Environment $twig,
        private readonly SearchTermByFeedbackProvider $searchTermProvider,
        private readonly SearchTermTelegramViewProvider $searchTermViewProvider,
        private readonly CountryProvider $countryProvider,
        private readonly TimeProvider $timeProvider,
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

        return $this->twig->render('tg.feedback.html.twig', [
            'number' => $number,
            'search_term' => $this->searchTermViewProvider->getSearchTermTelegramView($searchTerm),
            'rating' => $feedback->getRating(),
            'description' => $feedback->getDescription(),
            'country' => $this->countryProvider->getComposeCountryName($country),
            'created_at' => $this->timeProvider->getShortDate(
                $feedback->getCreatedAt(),
                timezone: $tg->getTimezone(),
                localeCode: $tg->getLocaleCode()
            ),
        ]);
    }
}