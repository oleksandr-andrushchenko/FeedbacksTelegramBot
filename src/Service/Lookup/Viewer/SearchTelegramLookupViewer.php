<?php

declare(strict_types=1);

namespace App\Service\Lookup\Viewer;

use App\Entity\Feedback\FeedbackSearch;
use App\Entity\Feedback\FeedbackSearchTerm;
use App\Service\Feedback\Telegram\Bot\View\FeedbackSearchTelegramViewProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class SearchTelegramLookupViewer extends LookupViewer implements LookupViewerInterface
{
    public function __construct(
        TranslatorInterface $translator,
        private readonly FeedbackSearchTelegramViewProvider $feedbackSearchTelegramViewProvider,
    )
    {
        parent::__construct($translator, 'search');
    }

    public function getOnSearchTitle(FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        return $this->trans('on_search');
    }

    public function getEmptyResultTitle(FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        return $this->trans('empty_result');
    }

    public function getResultRecord($record, array $context = []): string
    {
        $full = $context['full'] ?? false;

        return $this->wrapResultRecord(
            null,
            $record,
            fn (FeedbackSearch $search): array => [
                $this->feedbackSearchTelegramViewProvider->getFeedbackSearchTelegramView(
                    $context['bot'] ?? $search->getTelegramBot(),
                    $search,
                    numberToAdd: ($context['index'] ?? 0) + 1,
                    addSecrets: true,
                    addTime: true,
                    addCountry: true,
                ),
            ],
            $full
        );
    }
}
