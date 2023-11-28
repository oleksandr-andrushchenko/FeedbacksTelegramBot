<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer\Telegram;

use App\Entity\Feedback\FeedbackSearch;
use App\Entity\Feedback\FeedbackSearchTerm;
use App\Service\Feedback\Telegram\Bot\View\FeedbackSearchTelegramViewProvider;
use App\Service\Search\Viewer\SearchViewer;
use App\Service\Search\Viewer\SearchViewerHelper;
use App\Service\Search\Viewer\SearchViewerInterface;

class SearchRegistryTelegramSearchViewer extends SearchViewer implements SearchViewerInterface
{
    public function __construct(
        SearchViewerHelper $searchViewerHelper,
        private readonly FeedbackSearchTelegramViewProvider $feedbackSearchTelegramViewProvider,
    )
    {
        parent::__construct($searchViewerHelper->withTransDomain('search'));
    }

    public function getResultRecord($record, FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        $full = $context['full'] ?? false;

        $message = '💫 ';
        $message .= $this->searchViewerHelper->wrapResultRecord(
            $this->searchViewerHelper->trans('searches_title'),
            $record,
            fn (FeedbackSearch $search): array => [
                $this->feedbackSearchTelegramViewProvider->getFeedbackSearchTelegramView(
                    $context['bot'] ?? $search->getTelegramBot(),
                    $search,
                    addSecrets: !$full,
                    addTime: true,
                    addCountry: true,
                ),
            ],
            $full
        );

        return $message;
    }
}
