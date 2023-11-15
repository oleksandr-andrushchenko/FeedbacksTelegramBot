<?php

declare(strict_types=1);

namespace App\Service\Lookup\Viewer;

use App\Entity\Feedback\Feedback;
use App\Entity\Feedback\FeedbackSearchTerm;
use App\Service\Feedback\Telegram\Bot\View\FeedbackTelegramViewProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class FeedbackTelegramLookupViewer extends LookupViewer implements LookupViewerInterface
{
    public function __construct(
        TranslatorInterface $translator,
        private readonly FeedbackTelegramViewProvider $feedbackTelegramViewProvider,
    )
    {
        parent::__construct($translator, 'feedback');
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
            [$record],
            fn (Feedback $record): array => [
                $this->feedbackTelegramViewProvider->getFeedbackTelegramView(
                    $context['bot'] ?? $record->getTelegramBot(),
                    $record,
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
