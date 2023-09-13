<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\View;

use App\Entity\Feedback\Feedback;
use App\Service\Feedback\Rating\FeedbackRatingProvider;
use App\Service\Feedback\SearchTerm\SearchTermByFeedbackProvider;
use App\Service\Intl\CountryProvider;
use App\Service\Intl\TimeProvider;
use App\Service\Telegram\Telegram;
use Symfony\Contracts\Translation\TranslatorInterface;

class FeedbackTelegramViewProvider
{
    public function __construct(
        private readonly SearchTermByFeedbackProvider $searchTermProvider,
        private readonly SearchTermTelegramViewProvider $searchTermViewProvider,
        private readonly CountryProvider $countryProvider,
        private readonly TimeProvider $timeProvider,
        private readonly FeedbackRatingProvider $ratingProvider,
        private readonly TranslatorInterface $translator,
        private readonly FeedbackTelegramReplySignViewProvider $signViewProvider,
    )
    {
    }

    public function getFeedbackTelegramView(
        Telegram $telegram,
        Feedback $feedback,
        int $number = null,
        string $localeCode = null,
        bool $showSign = true,
        bool $showTime = true
    ): string
    {
        $searchTerm = $this->searchTermProvider->getSearchTermByFeedback($feedback);

        $country = null;

        if ($feedback->getCountryCode() !== null) {
            $country = $this->countryProvider->getCountry($feedback->getCountryCode());
        }

        $user = $feedback->getMessengerUser()?->getUser();
        $localeCode = $localeCode ?? $user->getLocaleCode();

        $message = '';

        if ($number !== null) {
            $message .= $this->translator->trans('icon.number', domain: 'feedbacks.tg', locale: $localeCode);
            $message .= $number;
            $message .= "\n";
        }

        if ($showTime) {
            $createdAt = $this->timeProvider->getDate(
                $feedback->getCreatedAt(),
                timezone: $user->getTimezone(),
                localeCode: $localeCode
            );
            $message .= $createdAt;
            $message .= ', ';
        }

        $message .= $this->translator->trans('somebody_from', domain: 'feedbacks.tg.feedback', locale: $localeCode);
        $message .= ' ';
        $country = $this->countryProvider->getCountryComposeName($country, localeCode: $localeCode);
//        $message .= sprintf('<u>%s</u>', $country);
        $message .= $country;
        $message .= ' ';
        $message .= $this->translator->trans('wrote_about', domain: 'feedbacks.tg.feedback', locale: $localeCode);
        $message .= ' ';
        $searchTerm = $this->searchTermViewProvider->getSearchTermTelegramView($searchTerm, localeCode: $localeCode);
//        $message .= sprintf('<u>%s</u>', $searchTerm);
        $message .= $searchTerm;
        $message .= ':';

        $message .= "\n\n";

        if ($feedback->getDescription() !== null) {
            $message .= '<tg-spoiler>';
            $message .= $feedback->getDescription();
            $message .= '</tg-spoiler>';
        }

        $message .= ' ';
        $message .= '<b>';
        $rating = $this->ratingProvider->getRatingComposeName($feedback->getRating(), localeCode: $localeCode);
        $message .= $rating;
        $message .= '</b>';

        if ($showSign) {
            $message .= "\n\n";

            $message .= $this->signViewProvider->getFeedbackTelegramReplySignView($telegram);
        }

        return $message;
    }
}