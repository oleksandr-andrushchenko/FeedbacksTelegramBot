<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Search\UkrCorrupt\UkrCorruptPerson;
use App\Entity\Search\UkrCorrupt\UkrCorruptPersonsRecord;

class UkrCorruptTelegramSearchViewer extends SearchViewer implements SearchViewerInterface
{
    public function __construct(SearchViewerHelper $searchViewerHelper)
    {
        parent::__construct($searchViewerHelper->withTransDomain('ukr_corrupt'));
    }

    public function getResultRecord($record, FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        if (is_string($record)) {
            return $record;
        }

        $full = $context['full'] ?? false;

        return match (get_class($record)) {
            UkrCorruptPersonsRecord::class => $this->getPersonsResultRecord($record, $full),
        };
    }

    public function getPersonsResultRecord(UkrCorruptPersonsRecord $record, bool $full): string
    {
        $h = $this->searchViewerHelper;
        $message = '‼️ ';
        $message .= $h->wrapResultRecord(
            $h->trans('persons_title', ['count' => count($record->getItems())]),
            $record->getItems(),
            static fn (UkrCorruptPerson $person): array => [
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
                    ->add($h->transBracketsModifier('court_case_number'))
                    ->apply($person->getCourtCaseNumber()),
                $h->modifier()
                    ->add($h->implodeModifier(';'))
                    ->add($h->transBracketsModifier('codex_articles'))
                    ->apply($person->getCodexArticles()),
                $person->getCourtName(),
                $h->modifier()
                    ->add($h->datetimeModifier('d.m.Y'))
                    ->add($h->transBracketsModifier('sentence_date'))
                    ->apply($person->getSentenceDate()),
                $h->modifier()
                    ->add($h->datetimeModifier('d.m.Y'))
                    ->add($h->transBracketsModifier('punishment_start'))
                    ->apply($person->getPunishmentStart()),
            ],
            $full
        );

        return $message;
    }
}
