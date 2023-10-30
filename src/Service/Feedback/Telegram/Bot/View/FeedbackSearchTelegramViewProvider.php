<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Bot\View;

use App\Entity\Feedback\FeedbackSearch;
use App\Entity\Telegram\TelegramChannel;
use App\Service\Feedback\SearchTerm\SearchTermProvider;
use App\Service\Feedback\Telegram\View\SearchTermTelegramViewProvider;
use App\Service\Intl\CountryProvider;
use App\Service\Intl\TimeProvider;
use App\Service\Telegram\Bot\TelegramBot;
use Symfony\Contracts\Translation\TranslatorInterface;

class FeedbackSearchTelegramViewProvider
{
    public function __construct(
        private readonly SearchTermProvider $searchTermProvider,
        private readonly SearchTermTelegramViewProvider $searchTermTelegramViewProvider,
        private readonly CountryProvider $countryProvider,
        private readonly TimeProvider $timeProvider,
        private readonly TranslatorInterface $translator,
        private readonly FeedbackTelegramReplySignViewProvider $feedbackTelegramReplySignViewProvider,
    )
    {
    }

    public function getFeedbackSearchTelegramView(
        TelegramBot $bot,
        FeedbackSearch $feedbackSearch,
        int $number = null,
        bool $addSecrets = false,
        string $localeCode = null,
        TelegramChannel $channel = null,
    ): string
    {
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
        $message .= sprintf('<u>%s</u>', $country);
        $message .= ' ';
        $message .= $this->translator->trans('searched_for', domain: 'feedbacks.tg.feedback_search', locale: $localeCode);
        $message .= ' ';
        $searchTerm = $this->searchTermProvider->getSearchTermByFeedbackSearchTerm($feedbackSearch->getSearchTerm());
        $message .= $this->searchTermTelegramViewProvider->getSearchTermTelegramView(
            $searchTerm,
            addSecrets: $addSecrets,
            localeCode: $localeCode
        );

        $message .= "\n\n";

        $message .= $this->feedbackTelegramReplySignViewProvider->getFeedbackTelegramReplySignView(
            $bot,
            channel: $channel
        );

        return $message;
    }
}