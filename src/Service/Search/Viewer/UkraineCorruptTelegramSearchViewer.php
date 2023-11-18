<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Search\UkraineCorrupt\UkraineCorruptPerson;
use App\Entity\Search\UkraineCorrupt\UkraineCorruptPersonsRecord;

class UkraineCorruptTelegramSearchViewer extends SearchViewer implements SearchViewerInterface
{
    public function __construct(SearchViewerHelper $searchViewerHelper)
    {
        parent::__construct($searchViewerHelper->withTransDomain('ukraine_corrupt'));
    }

    public function getOnSearchTitle(FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        return $this->searchViewerHelper->trans('on_search');
    }

    public function getEmptyResultTitle(FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        return $this->searchViewerHelper->trans('empty_result');
    }

    public function getResultRecord($record, FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        if (is_string($record)) {
            return $record;
        }

        $full = $context['full'] ?? false;

        return match (get_class($record)) {
            UkraineCorruptPersonsRecord::class => $this->getPersonsResultRecord($record, $full),
        };
    }

    public function getPersonsResultRecord(UkraineCorruptPersonsRecord $record, bool $full): string
    {
        $h = $this->searchViewerHelper;
        $message = '‼️ ';
        $message .= $h->wrapResultRecord(
            $h->trans('persons_title', ['count' => count($record->getPersons())]),
            $record->getPersons(),
            static fn (UkraineCorruptPerson $person): array => [
                $h->modifier()
                    ->add($h->conditionalModifier($person->getLastName() && $person->getFirstName() && $person->getPatronymic()))
                    ->add($h->appendModifier($person->getFirstName()))
                    ->add($h->appendModifier($person->getPatronymic()))
                    ->apply($person->getLastName()),
                $person->getEntityType(),
                $h->modifier()
                    ->add($h->redModifier())
                    ->add($h->appendModifier($person->getOffenseName()))
                    ->apply(true),
                $h->modifier()
                    ->add($h->conditionalModifier($person->getPunishment()))
                    ->add(
                        $h->appendModifier(
                            $h->modifier()
                                ->add($h->bracketsModifier($person->getPunishmentType()))
                                ->add($h->trimModifier())
                                ->apply(' ')
                        )
                    )
                    ->apply($person->getPunishment()),
                $h->modifier()
                    ->add($h->bracketsModifier($h->trans('court_case_number')))
                    ->apply($person->getCourtCaseNumber()),
                $h->modifier()
                    ->add($h->implodeModifier(';'))
                    ->add($h->bracketsModifier($h->trans('codex_articles')))
                    ->apply($person->getCodexArticles()),
                $person->getCourtName(),
                $h->modifier()
                    ->add($h->datetimeModifier('d.m.Y'))
                    ->add($h->bracketsModifier('sentence_date'))
                    ->apply($person->getSentenceDate()),
                $h->modifier()
                    ->add($h->datetimeModifier('d.m.Y'))
                    ->add($h->bracketsModifier('punishment_start'))
                    ->apply($person->getPunishmentStart()),
            ],
            $full
        );

        return $message;
    }
}
