<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Search\UkrMissed\DisappearedPersonsUkrMissedRecord;
use App\Entity\Search\UkrMissed\UkrMissedPerson;
use App\Entity\Search\UkrMissed\WantedPersonsUkrMissedRecord;

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
        $h = $this->searchViewerHelper;
        $message = '😐 ';
        $message .= $h->wrapResultRecord(
            $h->trans('disappeared_persons_title', ['count' => count($record->getItems())]),
            $record->getItems(),
            $this->getWrapResultRecord($full, $h),
            $full
        );

        return $message;
    }

    private function getWantedPersonsResultRecord(WantedPersonsUkrMissedRecord $record, bool $full): string
    {
        $h = $this->searchViewerHelper;
        $message = '🚨 ';
        $message .= $h->wrapResultRecord(
            $h->trans('wanted_persons_title', ['count' => count($record->getItems())]),
            $record->getItems(),
            $this->getWrapResultRecord($full, $h),
            $full
        );

        return $message;
    }


    public function getWrapResultRecord(bool $full, SearchViewerHelper $h): callable
    {
        return static fn (UkrMissedPerson $person): array => match (true) {
            $full => [
                $h->modifier()
                    ->add($h->appendModifier($person->getName()))
                    ->add($h->appendModifier($person->getMiddleName()))
                    ->add($h->slashesModifier())
                    ->add($h->boldModifier())
                    ->apply($person->getSurname()),
                $person->getSex(),
                $h->modifier()
                    ->add($h->datetimeModifier('d.m.Y'))
                    ->add($h->transBracketsModifier('born_at'))
                    ->apply($person->getBirthday()),
                $person->getPrecaution(),
                $h->modifier()
                    ->add($h->redWhiteModifier())
                    ->add($h->appendModifier($person->getCategory()))
                    ->add(
                        $h->appendModifier(
                            $h->modifier()
                                ->add($h->implodeModifier('; '))
                                ->apply($person->getArticles())
                        )
                    )
                    ->apply($person->getDisappeared() === false),
                $person->getOrgan(),
                $h->modifier()
                    ->add($h->datetimeModifier('d.m.Y'))
                    ->add($h->transBracketsModifier('absent_at'))
                    ->apply($person->getDate()),
            ],
            default => [
                $h->modifier()
                    ->add($h->appendModifier($person->getName()))
                    ->add($h->appendModifier($person->getMiddleName()))
                    ->add($h->slashesModifier())
                    ->add($h->boldModifier())
                    ->apply($person->getSurname()),
                $person->getSex(),
                $h->modifier()
                    ->add($h->datetimeModifier('d.m.Y'))
                    ->add($h->secretsModifier())
                    ->add($h->transBracketsModifier('born_at'))
                    ->apply($person->getBirthday()),
                $person->getPrecaution(),
                $h->modifier()
                    ->add($h->redWhiteModifier())
                    ->add($h->appendModifier($person->getCategory()))
                    ->add(
                        $h->appendModifier(
                            $h->modifier()
                                ->add($h->implodeModifier('; '))
                                ->apply($person->getArticles())
                        )
                    )
                    ->apply($person->getDisappeared() === false),
                $person->getOrgan(),
                $h->modifier()
                    ->add($h->datetimeModifier('d.m.Y'))
                    ->add($h->transBracketsModifier('absent_at'))
                    ->apply($person->getDate()),
            ],
        };
    }
}
