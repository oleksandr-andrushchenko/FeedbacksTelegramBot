<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer\Telegram;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Search\Blackbox\BlackboxFeedback;
use App\Entity\Search\Blackbox\BlackboxFeedbacks;
use App\Enum\Feedback\SearchTermType;
use App\Service\Search\Viewer\SearchViewer;
use App\Service\Search\Viewer\SearchViewerHelper;
use App\Service\Search\Viewer\SearchViewerInterface;

class BlackboxTelegramSearchViewer extends SearchViewer implements SearchViewerInterface
{
    public function __construct(SearchViewerHelper $searchViewerHelper)
    {
        parent::__construct($searchViewerHelper->withTransDomain('blackbox'));
    }

    public function getResultRecord($record, FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        if (is_string($record)) {
            return $record;
        }

        $full = $context['full'] ?? false;

        if ($searchTerm->getType() === SearchTermType::person_name) {
            $surname = explode(' ', $searchTerm->getNormalizedText())[0];
        } else {
            $surname = null;
        }

        $h = $this->searchViewerHelper;

        $message = '‼️ ';
        $message .= $h->wrapResultRecord(
            $surname === null
                ? $h->trans('feedbacks_title')
                : $h->trans('feedbacks_title_by_surname', ['surname' => $surname]),
            $record instanceof BlackboxFeedbacks ? $record->getItems() : [$record],
            static fn (BlackboxFeedback $item): array => [
                $h->modifier()
                    ->add($h->slashesModifier())
                    ->add($full ? $h->nullModifier() : $h->secretsModifier())
                    ->add($full ? $h->linkModifier($item->getHref()) : $h->nullModifier())
                    ->add($h->boldModifier())
                    ->apply($item->getName()),
                $h->modifier()
                    ->add($h->redModifier())
                    ->add($h->appendModifier($item->getPhoneFormatted()))
                    ->add($h->slashesModifier())
                    ->add($full ? $h->nullModifier() : $h->secretsModifier())
                    ->add($h->transBracketsModifier('phone'))
                    ->apply(true),
                $h->modifier()
                    ->add($h->slashesModifier())
                    ->add($h->italicModifier())
                    ->apply($item->getComment()),
                $h->modifier()
                    ->add($h->filterModifier())
                    ->add($h->implodeModifier(', '))
                    ->add($h->bracketsModifier($item->getType()))
                    ->add($h->slashesModifier())
                    ->apply([$item->getCity(), $item->getWarehouse()]),
                $h->modifier()
                    ->add($h->datetimeModifier('d.m.Y'))
                    ->add($h->transBracketsModifier('date'))
                    ->apply($item->getDate()),
            ],
            $full
        );

        return $message;
    }
}
