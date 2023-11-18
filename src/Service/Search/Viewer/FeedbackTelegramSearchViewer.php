<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer;

use App\Entity\Feedback\Feedback;
use App\Entity\Feedback\FeedbackSearchTerm;
use App\Service\Feedback\Telegram\Bot\View\FeedbackTelegramViewProvider;

class FeedbackTelegramSearchViewer extends SearchViewer implements SearchViewerInterface
{
    public function __construct(
        SearchViewerHelper $searchViewerHelper,
        private readonly FeedbackTelegramViewProvider $feedbackTelegramViewProvider,
    )
    {
        parent::__construct($searchViewerHelper->withTransDomain('feedback'));
    }

    public function getOnSearchTitle(FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        return $this->searchViewerHelper->trans('on_search');
    }

    public function getResultRecord($record, FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        $full = $context['full'] ?? false;

        $h = $this->searchViewerHelper;
        $message = '💫 ';
        $message .= $h->wrapResultRecord(
            $h->trans('feedbacks_title', ['count' => count($record)]),
            $record,
            fn (Feedback $feedback): array => [
                $this->feedbackTelegramViewProvider->getFeedbackTelegramView(
                    $context['bot'] ?? $feedback->getTelegramBot(),
                    $feedback,
                    addSecrets: true,
                    addTime: true,
                    addCountry: true,
                ),
            ],
            $full
        );

        return $message;
    }
}
