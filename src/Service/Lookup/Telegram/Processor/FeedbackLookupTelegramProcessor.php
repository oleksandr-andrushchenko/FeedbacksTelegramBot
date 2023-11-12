<?php

declare(strict_types=1);

namespace App\Service\Lookup\Telegram\Processor;

use App\Entity\Feedback\Feedback;
use App\Entity\Feedback\FeedbackSearch;
use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Lookup\LookupProcessorResult;
use App\Enum\Lookup\LookupProcessorName;
use App\Service\Feedback\FeedbackSearcher;
use App\Service\Feedback\SearchTerm\SearchTermProvider;
use App\Service\Feedback\Telegram\Bot\View\FeedbackTelegramViewProvider;
use App\Service\Feedback\Telegram\View\SearchTermTelegramViewProvider;
use App\Service\Telegram\Bot\TelegramBotAwareHelper;

class FeedbackLookupTelegramProcessor implements LookupTelegramProcessorInterface
{
    public function __construct(
        private readonly FeedbackSearcher $feedbackSearcher,
        private readonly SearchTermTelegramViewProvider $searchTermTelegramViewProvider,
        private readonly FeedbackTelegramViewProvider $feedbackTelegramViewProvider,
        private readonly SearchTermProvider $searchTermProvider,
    )
    {
    }

    public function lookupByFeedbackSearch(FeedbackSearch $feedbackSearch, TelegramBotAwareHelper $tg): ?LookupProcessorResult
    {
        // timezone lives in user
        $addTime = true;
        $feedbacks = $this->feedbackSearcher->searchFeedbacks($feedbackSearch->getSearchTerm(), withUsers: $addTime);
        $count = count($feedbacks);

        if ($count === 0) {
            return new LookupProcessorResult(
                LookupProcessorName::feedbacks_registry,
                $this->getEmptyResultTitle($feedbackSearch->getSearchTerm(), $tg)
            );
        }

        $result = new LookupProcessorResult(
            LookupProcessorName::feedbacks_registry,
            $this->getResultTitle($feedbackSearch->getSearchTerm(), $count, $tg)
        );

        foreach ($feedbacks as $index => $feedback) {
            $result->addRecord($this->getResultRecord($feedback, $index, $addTime, $tg));
        }

        return $result;
    }

    private function getResultTitle(FeedbackSearchTerm $feedbackSearchTerm, int $count, TelegramBotAwareHelper $tg): string
    {
        $parameters = [
            'search_term' => $this->searchTermTelegramViewProvider->getSearchTermTelegramView(
                $this->searchTermProvider->getFeedbackSearchTermTransfer($feedbackSearchTerm)
            ),
            'count' => $count,
        ];

        return $tg->trans('reply.title', $parameters, domain: 'search');
    }

    public function getEmptyResultTitle(FeedbackSearchTerm $feedbackSearchTerm, TelegramBotAwareHelper $tg): string
    {
        $parameters = [
            'search_term' => $this->searchTermTelegramViewProvider->getSearchTermTelegramView(
                $this->searchTermProvider->getFeedbackSearchTermTransfer($feedbackSearchTerm)
            ),
        ];
        $message = $tg->trans('reply.empty_list', $parameters, domain: 'search');

        return $tg->upsetText($message);
    }

    private function getResultRecord(Feedback $feedback, int $index, bool $addTime, TelegramBotAwareHelper $tg): string
    {
        return $this->feedbackTelegramViewProvider->getFeedbackTelegramView(
            $tg->getBot()->getEntity(),
            $feedback,
            numberToAdd: $index + 1,
            addSecrets: true,
            addSign: true,
            addTime: $addTime,
            addCountry: true
        );
    }
}
