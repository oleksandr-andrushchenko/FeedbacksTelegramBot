<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer\Telegram;

use App\Entity\Feedback\FeedbackSearch;
use App\Entity\Feedback\FeedbackSearchTerm;
use App\Service\Feedback\SearchTerm\SearchTermProvider;
use App\Service\Feedback\Telegram\View\SearchTermTelegramViewProvider;
use App\Service\Intl\TimeProvider;
use App\Service\Search\Viewer\SearchViewer;
use App\Service\Search\Viewer\SearchViewerCompose;
use App\Service\Search\Viewer\SearchViewerInterface;
use App\Service\Modifier;

class SearchRegistryTelegramSearchViewer extends SearchViewer implements SearchViewerInterface
{
    public function __construct(
        SearchViewerCompose $searchViewerCompose,
        Modifier $modifier,
        private readonly SearchTermProvider $searchTermProvider,
        private readonly SearchTermTelegramViewProvider $searchTermTelegramViewProvider,
    )
    {
        parent::__construct($searchViewerCompose->withTransDomain('search'), $modifier);
    }

    public function getResultMessage($record, FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        $message = '💫 ';

        $full = $context['full'] ?? false;
        $locale = $context['locale'] ?? null;

        $m = $this->modifier;

        $message .= $this->implodeResult(
            $this->trans('searches_title'),
            $record,
            fn (FeedbackSearch $item): array => [
                $m->create()
//                    ->add($m->slashesModifier())
//                    ->add($m->boldModifier())
                    ->apply(
                        $this->searchTermTelegramViewProvider->getSearchTermTelegramView(
                            $this->searchTermProvider->getFeedbackSearchTermTransfer($item->getSearchTerm()),
                            addSecrets: !$full,
                            localeCode: $locale
                        )
                    ),
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
