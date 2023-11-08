<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Bot\View;

use App\Entity\Feedback\FeedbackSearch;
use App\Entity\Telegram\TelegramChannel;
use App\Service\Feedback\SearchTerm\SearchTermProvider;
use App\Service\Feedback\Telegram\View\SearchTermTelegramViewProvider;
use App\Service\Intl\CountryProvider;
use App\Service\Intl\TimeProvider;
use App\Entity\Telegram\TelegramBot;
use App\Service\Util\String\MbUcFirster;
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
        private readonly MbUcFirster $mbUcFirster,
    )
    {
    }

    public function getFeedbackSearchTelegramView(
        TelegramBot $bot,
        FeedbackSearch $feedbackSearch,
        int $numberToAdd = null,
        bool $addSecrets = false,
        bool $addSign = false,
        bool $addTime = false,
        bool $addCountry = false,
        bool $addQuotes = false,
        string $localeCode = null,
        TelegramChannel $channel = null,
    ): string
    {
        $message = '';

        if ($addQuotes) {
            $message .= '<i>';
        }

        $message2 = '';

        if ($numberToAdd !== null) {
            $message2 .= $this->translator->trans('icon.number', domain: 'feedbacks.tg', locale: $localeCode);
            $message2 .= $numberToAdd;
            $message2 .= "\n";
        }

        if ($addTime) {
            $message2 .= $this->timeProvider->getDate($feedbackSearch->getCreatedAt(), timezone: $feedbackSearch->getUser()->getTimezone(), localeCode: $localeCode);
            $message2 .= ', ';
        }

        $message2 .= $this->translator->trans('somebody', domain: 'feedbacks.tg.feedback_search', locale: $localeCode);
        $message2 .= ' ';

        $message .= $this->mbUcFirster->mbUcFirst($message2);

        if ($addCountry) {
            $message .= $this->translator->trans('from', domain: 'feedbacks.tg.feedback_search', locale: $localeCode);
            $message .= ' ';
            $message .= $this->countryProvider->getCountryComposeName($feedbackSearch->getCountryCode(), localeCode: $localeCode);
            $message .= ' ';
        }

        $message .= $this->translator->trans('searched_for', domain: 'feedbacks.tg.feedback_search', locale: $localeCode);
        $message .= ' ';
        $message .= $this->searchTermTelegramViewProvider->getSearchTermTelegramView(
            $this->searchTermProvider->getFeedbackSearchTermTransfer($feedbackSearch->getSearchTerm()),
            addSecrets: $addSecrets,
            localeCode: $localeCode
        );

        if ($addQuotes) {
            $message .= '</i>';
        }

        if ($addSign) {
            $message .= "\n\n";

            $message .= $this->feedbackTelegramReplySignViewProvider->getFeedbackTelegramReplySignView($bot, channel: $channel, localeCode: $localeCode);
        }

        return $message;
    }
}