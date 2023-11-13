<?php

declare(strict_types=1);

namespace App\Service\Lookup\Viewer;

use App\Entity\Feedback\Feedback;
use App\Entity\Feedback\FeedbackSearchTerm;
use App\Service\Feedback\Telegram\Bot\View\FeedbackTelegramViewProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class FeedbackTelegramLookupViewer implements LookupViewerInterface
{
    public function __construct(
        private readonly FeedbackTelegramViewProvider $feedbackTelegramViewProvider,
        private readonly TranslatorInterface $translator,
    )
    {
    }

    public function getOnSearchTitle(FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        return $this->trans('on_search');
    }

    public function getEmptyResultTitle(FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        return $this->trans('empty_result');
    }

    public function getResultTitle(FeedbackSearchTerm $searchTerm, int $count, array $context = []): string
    {
        return $this->trans('result');
    }

    /**
     * @param Feedback $record
     * @param array $context
     * @return string
     */
    public function getResultRecord($record, array $context = []): string
    {
        return $this->feedbackTelegramViewProvider->getFeedbackTelegramView(
            $context['bot'] ?? $record->getTelegramBot(),
            $record,
            numberToAdd: ($context['index'] ?? 0) + 1,
            addSecrets: $context['addSecrets'] ?? false,
            addSign: $context['addSign'] ?? false,
            addTime: $context['addTime'] ?? false,
            addCountry: $context['addCountry'] ?? false,
        );
    }

    private function trans($id, array $parameters = []): string
    {
        return $this->translator->trans($id, $parameters, 'lookups.tg.feedback');
    }
}
