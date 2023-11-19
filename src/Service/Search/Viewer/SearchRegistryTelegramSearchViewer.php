<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer;

use App\Entity\Feedback\FeedbackSearch;
use App\Entity\Feedback\FeedbackSearchTerm;
use App\Service\Feedback\Telegram\Bot\View\FeedbackSearchTelegramViewProvider;

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

        $h = $this->searchViewerHelper;
        $message = '💫 ';
        $message .= $h->wrapResultRecord(
            $h->trans('searches_title', ['count' => count($record)]),
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
