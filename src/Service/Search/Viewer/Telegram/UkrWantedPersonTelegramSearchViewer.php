<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer\Telegram;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Search\UkrWantedPerson\UkrWantedPerson;
use App\Entity\Search\UkrWantedPerson\UkrWantedPersons;
use App\Service\Search\Viewer\SearchViewer;
use App\Service\Search\Viewer\SearchViewerCompose;
use App\Service\Search\Viewer\SearchViewerInterface;
use App\Service\Modifier;

class UkrWantedPersonTelegramSearchViewer extends SearchViewer implements SearchViewerInterface
{
    public function __construct(SearchViewerCompose $searchViewerCompose, Modifier $modifier)
    {
        parent::__construct($searchViewerCompose->withTransDomain('ukr_wanted_persons'), $modifier);
    }

    public function getResultMessage($record, FeedbackSearchTerm $searchTerm, array $context = []): string
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
        $m = $this->modifier;

        return fn (UkrWantedPerson $item): array => [
            $m->create()
                ->add($m->appendModifier($item->getUkrName()))
                ->add($m->appendModifier($item->getUkrPatronymic()))
                ->add(
                    $m->bracketsModifier(
                        $m->create()
                            ->add($m->appendModifier($item->getRusSurname()))
                            ->add($m->appendModifier($item->getRusName()))
                            ->add($m->appendModifier($item->getRusPatronymic()))
                            ->apply($this->trans('rus_name') . ':')
                    )
                )
                ->add($m->slashesModifier())
                ->add($full ? $m->nullModifier() : $m->secretsModifier())
                ->add($full ? $m->linkModifier($item->getHref()) : $m->nullModifier())
                ->add($m->boldModifier())
                ->apply($item->getUkrSurname()),
            $m->create()
                ->add($m->slashesModifier())
                ->add($m->bracketsModifier($this->trans('gender')))
                ->apply($item->getGender()),
            $m->create()
                ->add($m->datetimeModifier('d.m.Y'))
                ->add($m->bracketsModifier($this->trans('born_at')))
                ->apply($item->getBornAt()),
            $m->create()
                ->add($m->redModifier())
                ->add(
                    $m->appendModifier(
                        $m->create()
                            ->add($m->slashesModifier())
                            ->apply($item->getCategory())
                    )
                )
                ->add($m->bracketsModifier($item->getCodexArticle()))
                ->apply(true),
            $m->create()
                ->add($m->datetimeModifier('d.m.Y'))
                ->add($m->bracketsModifier($this->trans('absent_at')))
                ->apply($item->getAbsentAt()),
            $m->create()
                ->add($m->slashesModifier())
                ->add($m->bracketsModifier($this->trans('absent_place')))
                ->apply($item->getAbsentPlace()),
            $m->create()
                ->add($m->slashesModifier())
                ->add($m->bracketsModifier($this->trans('precaution')))
                ->apply($item->getPrecaution()),
            $m->create()
                ->add($m->slashesModifier())
                ->add($m->bracketsModifier($this->trans('region')))
                ->apply($item->getRegion()),
            $m->create()
                ->add($m->slashesModifier())
                ->add($m->bracketsModifier($this->trans('call_to')))
                ->apply($item->getCallTo()),
        ];
    }

    private function getPersonsResultRecord(UkrWantedPersons $record, bool $full): string
    {
        $message = '🚨 ';
        $message .= $this->implodeResult(
            $this->trans('persons_title'),
            $record->getItems(),
            $this->getPersonWrapResultRecordCallback($full),
            $full
        );

        return $message;
    }

    private function getPersonResultRecord(UkrWantedPerson $record, bool $full): string
    {
        $message = '💫 ';
        $message .= $this->implodeResult(
            $this->trans('person_title'),
            [$record],
            $this->getPersonWrapResultRecordCallback($full),
            $full
        );

        return $message;
    }
}
