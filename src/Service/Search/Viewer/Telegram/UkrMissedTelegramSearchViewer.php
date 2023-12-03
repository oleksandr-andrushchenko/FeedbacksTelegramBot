<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer\Telegram;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Search\UkrMissed\UkrMissedDisappearedPersons;
use App\Entity\Search\UkrMissed\UkrMissedPerson;
use App\Entity\Search\UkrMissed\UkrMissedWantedPersons;
use App\Service\Search\Viewer\SearchViewer;
use App\Service\Search\Viewer\SearchViewerCompose;
use App\Service\Search\Viewer\SearchViewerInterface;
use App\Service\Modifier;

class UkrMissedTelegramSearchViewer extends SearchViewer implements SearchViewerInterface
{
    public function __construct(SearchViewerCompose $searchViewerCompose, Modifier $modifier)
    {
        parent::__construct($searchViewerCompose->withTransDomain('ukr_missed'), $modifier);
    }

    public function getResultMessage($record, FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        if (is_string($record)) {
            return $record;
        }

        $full = $context['full'] ?? false;

        return match (get_class($record)) {
            UkrMissedDisappearedPersons::class => $this->getDisappearedPersonsResultRecord($record, $full),
            UkrMissedWantedPersons::class => $this->getWantedPersonsResultRecord($record, $full),
        };
    }

    private function getDisappearedPersonsResultRecord(UkrMissedDisappearedPersons $record, bool $full): string
    {
        $message = '😐 ';
        $message .= $this->implodeResult(
            $this->trans('disappeared_persons_title'),
            $record->getItems(),
            $this->getWrapResultRecordCallback($full),
            $full
        );

        return $message;
    }

    private function getWantedPersonsResultRecord(UkrMissedWantedPersons $record, bool $full): string
    {
        $message = '🚨 ';
        $message .= $this->implodeResult(
            $this->trans('wanted_persons_title'),
            $record->getItems(),
            $this->getWrapResultRecordCallback($full),
            $full
        );

        return $message;
    }

    public function getWrapResultRecordCallback(bool $full): callable
    {
        $m = $this->modifier;

        return fn (UkrMissedPerson $item): array => [
            $m->create()
                ->add($m->appendModifier($item->getName()))
                ->add($m->appendModifier($item->getMiddleName()))
                ->add($m->slashesModifier())
                ->add($m->boldModifier())
                ->apply($item->getSurname()),
            $item->getSex(),
            $m->create()
                ->add($m->datetimeModifier('d.m.Y'))
                ->add($full ? $m->nullModifier() : $m->secretsModifier())
                ->add($m->bracketsModifier($this->trans('born_at')))
                ->apply($item->getBirthday()),
            $m->create()
                ->add($m->slashesModifier())
                ->apply($item->getPrecaution()),
            $m->create()
                ->add($m->redWhiteModifier())
                ->add(
                    $m->appendModifier(
                        $m->create()
                            ->add($m->slashesModifier())
                            ->apply($item->getCategory())
                    )
                )
                ->add(
                    $m->appendModifier(
                        $m->create()
                            ->add($m->implodeModifier('; '))
                            ->add($m->slashesModifier())
                            ->apply($item->getArticles())
                    )
                )
                ->apply($item->getDisappeared() === false),
            $m->create()
                ->add($m->slashesModifier())
                ->apply($item->getOrgan()),
            $m->create()
                ->add($m->datetimeModifier('d.m.Y'))
                ->add($m->bracketsModifier($this->trans('absent_at')))
                ->apply($item->getDate()),
        ];
    }
}
