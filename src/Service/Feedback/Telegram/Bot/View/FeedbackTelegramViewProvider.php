<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Bot\View;

use App\Entity\Feedback\Feedback;
use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Telegram\TelegramChannel;
use App\Service\Feedback\Rating\FeedbackRatingProvider;
use App\Service\Feedback\SearchTerm\SearchTermProvider;
use App\Service\Feedback\Telegram\View\MultipleSearchTermTelegramViewProvider;
use App\Service\Intl\CountryProvider;
use App\Service\Intl\TimeProvider;
use App\Service\Telegram\Bot\TelegramBot;
use App\Service\Util\String\MbLcFirster;
use App\Transfer\Feedback\SearchTermTransfer;
use Symfony\Contracts\Translation\TranslatorInterface;

class FeedbackTelegramViewProvider
{
    public function __construct(
        private readonly SearchTermProvider $searchTermProvider,
        private readonly MultipleSearchTermTelegramViewProvider $multipleSearchTermTelegramViewProvider,
        private readonly CountryProvider $countryProvider,
        private readonly TimeProvider $timeProvider,
        private readonly FeedbackRatingProvider $feedbackRatingProvider,
        private readonly TranslatorInterface $translator,
        private readonly FeedbackTelegramReplySignViewProvider $feedbackTelegramReplySignViewProvider,
        private readonly MbLcFirster $mbLcFirster,
    )
    {
    }

    public function getFeedbackTelegramView(
        TelegramBot $bot,
        Feedback $feedback,
        int $number = null,
        bool $addSecrets = false,
        bool $showSign = true,
        bool $showTime = true,
        TelegramChannel $channel = null,
        string $localeCode = null,
    ): string
    {
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

        $somebodyFrom = $this->translator->trans('somebody_from', domain: 'feedbacks.tg.feedback', locale: $localeCode);
        $message .= $showTime ? $this->mbLcFirster->mbLcFirst($somebodyFrom) : $somebodyFrom;
        $message .= ' ';
        $country = $this->countryProvider->getCountryComposeName($country, localeCode: $localeCode);
        $message .= sprintf('<u>%s</u>', $country);
        $message .= ' ';
        $message .= $this->translator->trans('wrote_about', domain: 'feedbacks.tg.feedback', locale: $localeCode);
        $message .= ' ';
        $message .= $this->multipleSearchTermTelegramViewProvider->getMultipleSearchTermTelegramView(
            array_map(
                fn (FeedbackSearchTerm $searchTerm): SearchTermTransfer => $this->searchTermProvider->getSearchTermByFeedbackSearchTerm($searchTerm),
                $feedback->getSearchTerms()->toArray()
            ),
            addSecrets: $addSecrets,
            localeCode: $localeCode
        );
        $message .= ':';

        $message .= "\n\n";

        if ($feedback->getDescription() !== null) {
            $message .= '<tg-spoiler>';
            $message .= $feedback->getDescription();
            $message .= '</tg-spoiler>';
            $message .= "\n";
        }

        $message .= '<b>';
        $rating = $this->feedbackRatingProvider->getRatingComposeName($feedback->getRating(), localeCode: $localeCode);
        $message .= $rating;
        $message .= '</b>';

        if ($showSign) {
            $message .= "\n\n";

            $message .= $this->feedbackTelegramReplySignViewProvider->getFeedbackTelegramReplySignView(
                $bot,
                channel: $channel,
                localeCode: $localeCode
            );
        }

        return $message;
    }
}