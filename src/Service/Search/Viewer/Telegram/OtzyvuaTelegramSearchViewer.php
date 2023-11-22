<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer\Telegram;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Search\Otzyvua\OtzyvuaFeedback;
use App\Entity\Search\Otzyvua\OtzyvuaFeedbackSearchTerm;
use App\Entity\Search\Otzyvua\OtzyvuaFeedbackSearchTermsRecord;
use App\Entity\Search\Otzyvua\OtzyvuaFeedbacksRecord;
use App\Service\Search\Viewer\SearchViewer;
use App\Service\Search\Viewer\SearchViewerHelper;
use App\Service\Search\Viewer\SearchViewerInterface;

class OtzyvuaTelegramSearchViewer extends SearchViewer implements SearchViewerInterface
{
    public function __construct(SearchViewerHelper $searchViewerHelper)
    {
        parent::__construct($searchViewerHelper->withTransDomain('otzyvua'));
    }

    public function getResultRecord($record, FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        if (is_string($record)) {
            return $record;
        }

        $full = $context['full'] ?? false;

        return match (get_class($record)) {
            OtzyvuaFeedbackSearchTermsRecord::class => $this->getFeedbackSearchTermsResultRecord($record, $full),
            OtzyvuaFeedbacksRecord::class => $this->getFeedbackResultRecord($record, $full),
        };
    }

    private function getFeedbackSearchTermsResultRecord(OtzyvuaFeedbackSearchTermsRecord $record, bool $full): string
    {
        $h = $this->searchViewerHelper;
        $message = '🤔 ';
        $message .= $h->wrapResultRecord(
            $h->trans('feedback_search_terms_title', ['count' => count($record->getItems())]),
            $record->getItems(),
            static fn (OtzyvuaFeedbackSearchTerm $item): array => [
                $h->modifier()
                    ->add($h->slashesModifier())
                    ->add($full ? $h->linkModifier($item->getHref()) : $h->nullModifier())
                    ->add($h->boldModifier())
                    ->apply($item->getName()),
                $h->modifier()
                    ->add($h->transBracketsModifier('category'))
                    ->add($h->underlineModifier())
                    ->apply($item->getCategory()),
                $h->modifier()
                    ->add($h->ratingModifier())
                    ->add($h->transBracketsModifier('rating', ['value' => $item->getRating(), 'total' => 5]))
                    ->apply((string) $item->getRating()),
                $h->modifier()
                    ->add($h->numberFormatModifier(thousandsSeparator: ' '))
                    ->add($h->transBracketsModifier('feedback_count'))
                    ->apply((string) $item->getCount()),
            ],
            $full
        );

        return $message;
    }

    private function getFeedbackResultRecord(OtzyvuaFeedbacksRecord $record, bool $full): string
    {
        $h = $this->searchViewerHelper;
        $message = '💫 ';
        $message .= $h->wrapResultRecord(
            $h->trans('feedbacks_title', ['count' => count($record->getItems())]),
            $record->getItems(),
            static fn (OtzyvuaFeedback $item): array => [
                $h->modifier()
                    ->add($h->slashesModifier())
                    ->add($full ? $h->linkModifier($item->getHref()) : $h->nullModifier())
                    ->add($h->boldModifier())
                    ->apply($item->getTitle()),
                $h->modifier()
                    ->add($h->ratingModifier())
                    ->add($h->transBracketsModifier('rating', ['value' => $item->getRating(), 'total' => 5]))
                    ->apply((string) $item->getRating()),
                $h->modifier()
                    ->add($h->italicModifier())
                    ->apply($item->getDescription()),
                $h->modifier()
                    ->add($h->conditionalModifier($full))
                    ->add($h->slashesModifier())
                    ->add($full ? $h->linkModifier($item->getAuthorHref()) : $h->nullModifier())
                    ->add($h->transBracketsModifier('author'))
                    ->apply($item->getAuthorName()),
                $h->modifier()
                    ->add($h->datetimeModifier('d.m.Y'))
                    ->add($h->transBracketsModifier('created_at'))
                    ->apply($item->getCreatedAt()),
            ],
            $full
        );

        return $message;
    }
}
