<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer\Telegram;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Search\Blackbox\BlackboxFeedback;
use App\Entity\Search\Blackbox\BlackboxFeedbacks;
use App\Enum\Feedback\SearchTermType;
use App\Service\Intl\TimeProvider;
use App\Service\Search\Viewer\SearchViewer;
use App\Service\Search\Viewer\SearchViewerCompose;
use App\Service\Search\Viewer\SearchViewerInterface;
use App\Service\Modifier;

class BlackboxTelegramSearchViewer extends SearchViewer implements SearchViewerInterface
{
    public function __construct(SearchViewerCompose $searchViewerCompose, Modifier $modifier)
    {
        parent::__construct($searchViewerCompose->withTransDomain('blackbox'), $modifier);
    }

    public function getResultMessage($record, FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        if (is_string($record)) {
            return $record;
        }

        $full = $context['full'] ?? false;

        $m = $this->modifier;

        $term = $searchTerm->getNormalizedText();
        $personSearch = $searchTerm->getType() === SearchTermType::person_name;
        $phoneSearch = $searchTerm->getType() === SearchTermType::phone_number;

        $message = '‼️ ';
        $message .= $this->implodeResult(
            $this->trans('feedbacks_title'),
            $record instanceof BlackboxFeedbacks ? $record->getItems() : [$record],
            fn (BlackboxFeedback $item): array => [
                $m->create()
                    ->add($full ? $m->nullModifier() : $m->wordSecretsModifier(excepts: $personSearch ? $term : null))
                    ->add($m->slashesModifier())
                    ->add($full ? $m->linkModifier($item->getHref()) : $m->nullModifier())
                    ->add($m->boldModifier())
                    ->add($m->bracketsModifier($this->trans('name')))
                    ->apply($item->getName()),
                $m->create()
                    ->add($m->emptyNullModifier())
                    ->add($full ? $m->nullModifier() : $m->wordSecretsModifier(excepts: $phoneSearch ? substr($term, 2) : null))
                    ->add($m->slashesModifier())
                    ->add($m->prependModifier(' '))
                    ->add($m->prependModifier($m->redModifier()(true)))
                    ->add($m->appendModifier(' '))
                    ->add($m->bracketsModifier($this->trans('phone')))
                    ->apply($item->getPhone()),
                $m->create()
                    ->add($m->emptyNullModifier())
                    ->add($m->slashesModifier())
                    ->add($m->spoilerModifier())
                    ->add($m->appendModifier(' '))
                    ->add($m->bracketsModifier($this->trans('comment')))
                    ->apply($item->getComment()),
                $m->create()
                    ->add($m->filterModifier())
                    ->add($m->implodeModifier(', '))
                    ->add($m->emptyNullModifier())
                    ->add($m->bracketsModifier($item->getType()))
                    ->add($m->slashesModifier())
                    ->apply([$item->getCity(), $item->getWarehouse()]),
                $m->create()
                    ->add($m->slashesModifier())
                    ->add($m->countryModifier())
                    ->add($m->bracketsModifier($this->trans('country')))
                    ->apply('ua'),
                $m->create()
                    ->add($m->datetimeModifier(TimeProvider::DATE))
                    ->add($m->bracketsModifier($this->trans('date')))
                    ->apply($item->getDate()),
            ],
            true
        );

        return $message;
    }
}
