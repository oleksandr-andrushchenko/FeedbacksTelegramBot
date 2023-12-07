<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer\Telegram;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Search\Otzyvua\OtzyvuaFeedback;
use App\Entity\Search\Otzyvua\OtzyvuaFeedbacks;
use App\Entity\Search\Otzyvua\OtzyvuaFeedbackSearchTerm;
use App\Entity\Search\Otzyvua\OtzyvuaFeedbackSearchTerms;
use App\Service\Intl\TimeProvider;
use App\Service\Search\Viewer\SearchViewer;
use App\Service\Search\Viewer\SearchViewerCompose;
use App\Service\Search\Viewer\SearchViewerInterface;
use App\Service\Modifier;

class OtzyvuaTelegramSearchViewer extends SearchViewer implements SearchViewerInterface
{
    public function __construct(SearchViewerCompose $searchViewerCompose, Modifier $modifier)
    {
        parent::__construct($searchViewerCompose->withTransDomain('otzyvua'), $modifier);
    }

    public function getResultMessage($record, FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        if (is_string($record)) {
            return $record;
        }

        $full = $context['full'] ?? false;

        return match (get_class($record)) {
            OtzyvuaFeedbackSearchTerms::class => $this->getFeedbackSearchTermsMessage($record, $full),
            OtzyvuaFeedbacks::class => $this->getFeedbacksMessage($record, $full),
        };
    }

    private function getFeedbackSearchTermsMessage(OtzyvuaFeedbackSearchTerms $record, bool $full): string
    {
        $m = $this->modifier;
        $message = '💫 ';
        $message .= $this->implodeResult(
            $this->trans('feedback_search_terms_title'),
            $record->getItems(),
            fn (OtzyvuaFeedbackSearchTerm $item): array => [
                $m->create()
                    ->add($m->slashesModifier())
                    ->add($full ? $m->linkModifier($item->getHref()) : $m->nullModifier())
                    ->add($m->boldModifier())
                    ->apply($item->getName()),
                $m->create()
                    ->add($m->slashesModifier())
                    ->add($m->bracketsModifier($this->trans('category')))
                    ->add($m->underlineModifier())
                    ->apply($item->getCategory()),
                $m->create()
                    ->add($m->ratingModifier())
                    ->add($m->bracketsModifier($this->trans('rating', ['value' => $item->getRating(), 'total' => 5])))
                    ->apply((string) $item->getRating()),
                $m->create()
                    ->add($m->numberFormatModifier(thousandsSeparator: ' '))
                    ->add($m->bracketsModifier($this->trans('feedback_count')))
                    ->apply((string) $item->getCount()),
            ],
            $full
        );

        return $message;
    }

    private function getFeedbacksMessage(OtzyvuaFeedbacks $record, bool $full): string
    {
        $m = $this->modifier;
        $message = '💫 ';
        $message .= $this->implodeResult(
            $this->trans('feedbacks_title'),
            $record->getItems(),
            fn (OtzyvuaFeedback $item): array => [
                $m->create()
                    ->add($m->slashesModifier())
                    ->add($full ? $m->linkModifier($item->getHref()) : $m->nullModifier())
                    ->add($m->boldModifier())
                    ->apply($item->getTitle()),
                $m->create()
                    ->add($m->ratingModifier())
                    ->add($m->bracketsModifier($this->trans('rating', ['value' => $item->getRating(), 'total' => 5])))
                    ->apply((string) $item->getRating()),
                $m->create()
                    ->add($m->slashesModifier())
                    ->add($m->spoilerModifier())
                    ->add($m->bracketsModifier($this->trans('description')))
                    ->apply($item->getDescription()),
                $m->create()
                    ->add($m->conditionalModifier($full))
                    ->add($m->slashesModifier())
                    ->add($full ? $m->linkModifier($item->getAuthorHref()) : $m->nullModifier())
                    ->add($m->bracketsModifier($this->trans('author')))
                    ->apply($item->getAuthorName()),
                $m->create()
                    ->add($m->slashesModifier())
                    ->add($m->countryModifier())
                    ->add($m->bracketsModifier($this->trans('country')))
                    ->apply('ua'),
                $m->create()
                    ->add($m->datetimeModifier(TimeProvider::DATE))
                    ->add($m->bracketsModifier($this->trans('created_at')))
                    ->apply($item->getCreatedAt()),
            ],
            $full
        );

        return $message;
    }
}
