<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer\Telegram;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Search\UkrMissed\DisappearedPersonsUkrMissedRecord;
use App\Entity\Search\UkrMissed\UkrMissedPerson;
use App\Entity\Search\UkrMissed\WantedPersonsUkrMissedRecord;
use App\Service\Search\Viewer\SearchViewer;
use App\Service\Search\Viewer\SearchViewerHelper;
use App\Service\Search\Viewer\SearchViewerInterface;

class UkrMissedTelegramSearchViewer extends SearchViewer implements SearchViewerInterface
{
    public function __construct(SearchViewerHelper $searchViewerHelper)
    {
        parent::__construct($searchViewerHelper->withTransDomain('ukr_missed'));
    }

    public function getResultRecord($record, FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        if (is_string($record)) {
            return $record;
        }

        $full = $context['full'] ?? false;

        return match (get_class($record)) {
            DisappearedPersonsUkrMissedRecord::class => $this->getDisappearedPersonsResultRecord($record, $full),
            WantedPersonsUkrMissedRecord::class => $this->getWantedPersonsResultRecord($record, $full),
        };
    }

    private function getDisappearedPersonsResultRecord(DisappearedPersonsUkrMissedRecord $record, bool $full): string
    {
        $message = '😐 ';
        $message .= $this->searchViewerHelper->wrapResultRecord(
            $this->searchViewerHelper->trans('disappeared_persons_title'),
            $record->getItems(),
            $this->getWrapResultRecordCallback($full),
            $full
        );

        return $message;
    }

    private function getWantedPersonsResultRecord(WantedPersonsUkrMissedRecord $record, bool $full): string
    {
        $message = '🚨 ';
        $message .= $this->searchViewerHelper->wrapResultRecord(
            $this->searchViewerHelper->trans('wanted_persons_title'),
            $record->getItems(),
            $this->getWrapResultRecordCallback($full),
            $full
        );

        return $message;
    }

    public function getWrapResultRecordCallback(bool $full): callable
    {
        $h = $this->searchViewerHelper;

        return static fn (UkrMissedPerson $item): array => [
            $h->modifier()
                ->add($h->appendModifier($item->getName()))
                ->add($h->appendModifier($item->getMiddleName()))
                ->add($h->slashesModifier())
                ->add($h->boldModifier())
                ->apply($item->getSurname()),
            $item->getSex(),
            $h->modifier()
                ->add($h->datetimeModifier('d.m.Y'))
                ->add($full ? $h->nullModifier() : $h->secretsModifier())
                ->add($h->transBracketsModifier('born_at'))
                ->apply($item->getBirthday()),
            $h->modifier()
                ->add($h->slashesModifier())
                ->apply($item->getPrecaution()),
            $h->modifier()
                ->add($h->redWhiteModifier())
                ->add(
                    $h->appendModifier(
                        $h->modifier()
                            ->add($h->slashesModifier())
                            ->apply($item->getCategory())
                    )
                )
                ->add(
                    $h->appendModifier(
                        $h->modifier()
                            ->add($h->implodeModifier('; '))
                            ->add($h->slashesModifier())
                            ->apply($item->getArticles())
                    )
                )
                ->apply($item->getDisappeared() === false),
            $h->modifier()
                ->add($h->slashesModifier())
                ->apply($item->getOrgan()),
            $h->modifier()
                ->add($h->datetimeModifier('d.m.Y'))
                ->add($h->transBracketsModifier('absent_at'))
                ->apply($item->getDate()),
        ];
    }
}
