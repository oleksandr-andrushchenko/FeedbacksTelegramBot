<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\View;

use App\Entity\Feedback\FeedbackSearch;
use App\Service\Feedback\SearchTerm\SearchTermByFeedbackSearchProvider;
use App\Service\Intl\CountryProvider;
use App\Service\Intl\TimeProvider;
use App\Service\Telegram\Telegram;
use Symfony\Contracts\Translation\TranslatorInterface;

class FeedbackSearchTelegramViewProvider
{
    public function __construct(
        private readonly SearchTermByFeedbackSearchProvider $searchTermProvider,
        private readonly SearchTermTelegramViewProvider $searchTermViewProvider,
        private readonly CountryProvider $countryProvider,
        private readonly TimeProvider $timeProvider,
        private readonly TranslatorInterface $translator,
        private readonly FeedbackTelegramReplySignViewProvider $signViewProvider,
    )
    {
    }

    public function getFeedbackSearchTelegramView(
        Telegram $telegram,
        FeedbackSearch $feedbackSearch,
        int $number = null,
        string $localeCode = null
    ): string
    {
        $searchTerm = $this->searchTermProvider->getSearchTermByFeedbackSearch($feedbackSearch);

        $country = null;

        if ($feedbackSearch->getCountryCode() !== null) {
            $country = $this->countryProvider->getCountry($feedbackSearch->getCountryCode());
        }

        $user = $feedbackSearch->getMessengerUser()?->getUser();
        $localeCode = $localeCode ?? $user->getLocaleCode();

        $message = '';

        if ($number !== null) {
            $message .= $this->translator->trans('icon.number', domain: 'feedbacks.tg', locale: $localeCode);
            $message .= $number;
            $message .= "\n";
        }

        $createdAt = $this->timeProvider->getDate(
            $feedbackSearch->getCreatedAt(),
            timezone: $user->getTimezone(),
            localeCode: $localeCode
        );
        $message .= $createdAt;
        $message .= ', ';
        $message .= $this->translator->trans('somebody_from', domain: 'feedbacks.tg.feedback_search', locale: $localeCode);
        $message .= ' ';
        $country = $this->countryProvider->getCountryComposeName($country, localeCode: $localeCode);
//        $message .= sprintf('<u>%s</u>', $country);
        $message .= $country;
        $message .= ' ';
        $message .= $this->translator->trans('searched_for', domain: 'feedbacks.tg.feedback_search', locale: $localeCode);
        $message .= ' ';
        $searchTerm = $this->searchTermViewProvider->getSearchTermTelegramView($searchTerm, localeCode: $localeCode);
//        $message .= sprintf('<u>%s</u>', $searchTerm);
        $message .= $searchTerm;

        $message .= "\n\n";

        $message .= $this->signViewProvider->getFeedbackTelegramReplySignView($telegram);

        return $message;
    }
}