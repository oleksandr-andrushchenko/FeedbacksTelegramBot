<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer\Telegram;

use App\Entity\Feedback\FeedbackSearch;
use App\Entity\Feedback\FeedbackSearchTerm;
use App\Service\Feedback\Telegram\Bot\View\FeedbackSearchTelegramViewProvider;
use App\Service\Search\Viewer\SearchViewer;
use App\Service\Search\Viewer\SearchViewerCompose;
use App\Service\Search\Viewer\SearchViewerInterface;
use App\Service\Modifier;

class SearchRegistryTelegramSearchViewer extends SearchViewer implements SearchViewerInterface
{
    public function __construct(
        SearchViewerCompose $searchViewerCompose,
        Modifier $modifier,
        private readonly FeedbackSearchTelegramViewProvider $feedbackSearchTelegramViewProvider,
    )
    {
        parent::__construct($searchViewerCompose->withTransDomain('search'), $modifier);
    }

    public function getResultMessage($record, FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        $full = $context['full'] ?? false;

        $message = '💫 ';
        $message .= $this->implodeResult(
            $this->trans('searches_title'),
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
