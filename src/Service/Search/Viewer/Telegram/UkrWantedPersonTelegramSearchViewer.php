<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer\Telegram;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Search\UkrWantedPerson\UkrWantedPerson;
use App\Entity\Search\UkrWantedPerson\UkrWantedPersons;
use App\Service\Search\Viewer\SearchViewer;
use App\Service\Search\Viewer\SearchViewerHelper;
use App\Service\Search\Viewer\SearchViewerInterface;

class UkrWantedPersonTelegramSearchViewer extends SearchViewer implements SearchViewerInterface
{
    public function __construct(SearchViewerHelper $searchViewerHelper)
    {
        parent::__construct($searchViewerHelper->withTransDomain('ukr_wanted_persons'));
    }

    public function getResultRecord($record, FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        if (is_string($record)) {
            return $record;
        }

        $full = $context['full'] ?? false;

        return match (get_class($record)) {
            UkrWantedPersons::class => $this->getPersonsResultRecord($record, $full),
            UkrWantedPerson::class => $this->getPersonResultRecord($record, $full),
        };
    }

    public function getPersonWrapResultRecordCallback(bool $full): callable
    {
        $h = $this->searchViewerHelper;

        return static fn (UkrWantedPerson $item): array => [
            $h->modifier()
                ->add($h->appendModifier($item->getUkrName()))
                ->add($h->appendModifier($item->getUkrPatronymic()))
                ->add(
                    $h->bracketsModifier(
                        $h->modifier()
                            ->add($h->appendModifier($item->getRusSurname()))
                            ->add($h->appendModifier($item->getRusName()))
                            ->add($h->appendModifier($item->getRusPatronymic()))
                            ->apply($h->trans('rus_name') . ':')
                    )
                )
                ->add($h->slashesModifier())
                ->add($full ? $h->nullModifier() : $h->secretsModifier())
                ->add($full ? $h->linkModifier($item->getHref()) : $h->nullModifier())
                ->add($h->boldModifier())
                ->apply($item->getUkrSurname()),
            $h->modifier()
                ->add($h->slashesModifier())
                ->add($h->transBracketsModifier('gender'))
                ->apply($item->getGender()),
            $h->modifier()
                ->add($h->datetimeModifier('d.m.Y'))
                ->add($h->transBracketsModifier('born_at'))
                ->apply($item->getBornAt()),
            $h->modifier()
                ->add($h->redModifier())
                ->add(
                    $h->appendModifier(
                        $h->modifier()
                            ->add($h->slashesModifier())
                            ->apply($item->getCategory())
                    )
                )
                ->add($h->bracketsModifier($item->getCodexArticle()))
                ->apply(true),
            $h->modifier()
                ->add($h->datetimeModifier('d.m.Y'))
                ->add($h->transBracketsModifier('absent_at'))
                ->apply($item->getAbsentAt()),
            $h->modifier()
                ->add($h->slashesModifier())
                ->add($h->transBracketsModifier('absent_place'))
                ->apply($item->getAbsentPlace()),
            $h->modifier()
                ->add($h->slashesModifier())
                ->add($h->transBracketsModifier('precaution'))
                ->apply($item->getPrecaution()),
            $h->modifier()
                ->add($h->slashesModifier())
                ->add($h->transBracketsModifier('region'))
                ->apply($item->getRegion()),
            $h->modifier()
                ->add($h->slashesModifier())
                ->add($h->transBracketsModifier('call_to'))
                ->apply($item->getCallTo()),
        ];
    }

    private function getPersonsResultRecord(UkrWantedPersons $record, bool $full): string
    {
        $message = '🚨 ';
        $message .= $this->searchViewerHelper->wrapResultRecord(
            $this->searchViewerHelper->trans('persons_title'),
            $record->getItems(),
            $this->getPersonWrapResultRecordCallback($full),
            $full
        );

        return $message;
    }

    private function getPersonResultRecord(UkrWantedPerson $record, bool $full): string
    {
        $message = '🤔 ';
        $message .= $this->searchViewerHelper->wrapResultRecord(
            $this->searchViewerHelper->trans('person_title'),
            [$record],
            $this->getPersonWrapResultRecordCallback($full),
            $full
        );

        return $message;
    }
}
