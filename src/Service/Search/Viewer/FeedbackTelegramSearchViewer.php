<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer;

use App\Entity\Feedback\Feedback;
use App\Entity\Feedback\FeedbackSearchTerm;
use App\Service\Feedback\Telegram\Bot\View\FeedbackTelegramViewProvider;
use App\Service\Util\String\SecretsAdder;
use Symfony\Contracts\Translation\TranslatorInterface;

class FeedbackTelegramSearchViewer extends SearchViewer implements SearchViewerInterface
{
    public function __construct(
        TranslatorInterface $translator,
        SecretsAdder $secretsAdder,
        private readonly FeedbackTelegramViewProvider $feedbackTelegramViewProvider,
    )
    {
        parent::__construct($translator, $secretsAdder, 'feedback');
    }

    public function getOnSearchTitle(FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        return $this->trans('on_search');
    }

    public function getEmptyResultTitle(FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        return $this->trans('empty_result');
    }

    public function getResultRecord($record, FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        $full = $context['full'] ?? false;

        $message = '💫 ';
        $message .= $this->wrapResultRecord(
            $this->trans('feedbacks_title', ['count' => count($record)]),
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
