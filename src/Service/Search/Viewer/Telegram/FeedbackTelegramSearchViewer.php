<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer\Telegram;

use App\Entity\Feedback\Feedback;
use App\Entity\Feedback\FeedbackSearchTerm;
use App\Service\Feedback\Telegram\Bot\View\FeedbackTelegramViewProvider;
use App\Service\Intl\TimeProvider;
use App\Service\Search\Viewer\SearchViewer;
use App\Service\Search\Viewer\SearchViewerCompose;
use App\Service\Search\Viewer\SearchViewerInterface;
use App\Service\Modifier;

class FeedbackTelegramSearchViewer extends SearchViewer implements SearchViewerInterface
{
    public function __construct(
        SearchViewerCompose $searchViewerCompose,
        Modifier $modifier,
        private readonly FeedbackTelegramViewProvider $feedbackTelegramViewProvider,
    )
    {
        parent::__construct($searchViewerCompose->withTransDomain('feedback'), $modifier);
    }

    public function getResultMessage($record, FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        $message = '💫 ';

        $full = $context['full'] ?? false;
        $locale = $context['locale'] ?? null;

        $m = $this->modifier;

        $message .= $this->implodeResult(
            $this->trans('feedbacks_title'),
            $record,
            fn (Feedback $item): array => [
                $m->create()
//                    ->add($m->slashesModifier())
//                    ->add($m->boldModifier())
                    ->apply(
                        $this->feedbackTelegramViewProvider->getFeedbackSearchTermsTelegramView(
                            $item->getSearchTerms()->toArray(),
                            addSecrets: !$full,
                            localeCode: $locale
                        )
                    ),
                $m->create()
                    ->add($m->markModifier())
                    ->add($m->appendModifier($this->trans('mark_' . ($item->getRating()->value > 0 ? '+1' : $item->getRating()->value))))
                    ->add($m->bracketsModifier($this->trans('mark')))
                    ->apply($item->getRating()->value),
                $m->create()
                    ->add($m->slashesModifier())
                    ->add($m->spoilerModifier())
                    ->add($m->bracketsModifier($this->trans('description')))
                    ->apply($item->getDescription()),
                $m->create()
                    ->add($m->slashesModifier())
                    ->add($m->countryModifier(locale: $locale))
                    ->add($m->bracketsModifier($this->trans('country')))
                    ->apply($item->getCountryCode()),
                $m->create()
                    ->add($m->datetimeModifier(TimeProvider::DATE, timezone: $item->getUser()->getTimezone(), locale: $locale))
                    ->add($m->bracketsModifier($this->trans('created_at')))
                    ->apply($item->getCreatedAt()),
            ],
            $full
        );

        return $message;
    }
}
