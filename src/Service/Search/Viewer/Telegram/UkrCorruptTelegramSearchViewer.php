<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer\Telegram;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Search\UkrCorrupt\UkrCorruptPerson;
use App\Entity\Search\UkrCorrupt\UkrCorruptPersonsRecord;
use App\Service\Search\Viewer\SearchViewer;
use App\Service\Search\Viewer\SearchViewerHelper;
use App\Service\Search\Viewer\SearchViewerInterface;

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
            static fn (UkrCorruptPerson $item): array => [
                $h->modifier()
                    ->add($h->conditionalModifier($item->getLastName() && $item->getFirstName() && $item->getPatronymic()))
                    ->add($h->slashesModifier())
                    ->add($h->appendModifier($item->getFirstName()))
                    ->add($h->appendModifier($item->getPatronymic()))
                    ->apply($item->getLastName()),
                $h->modifier()
                    ->add($h->slashesModifier())
                    ->apply($item->getEntityType()),
                $h->modifier()
                    ->add($h->redModifier())
                    ->add(
                        $h->appendModifier(
                            $h->modifier()
                                ->add($h->slashesModifier())
                                ->apply($item->getOffenseName())
                        )
                    )
                    ->apply(true),
                $h->modifier()
                    ->add($h->conditionalModifier($item->getPunishment()))
                    ->add($h->slashesModifier())
                    ->add(
                        $h->appendModifier(
                            $h->modifier()
                                ->add($h->bracketsModifier($item->getPunishmentType()))
                                ->add($h->trimModifier())
                                ->apply(' ')
                        )
                    )
                    ->apply($item->getPunishment()),
                $h->modifier()
                    ->add($h->transBracketsModifier('court_case_number'))
                    ->add($h->slashesModifier())
                    ->apply($item->getCourtCaseNumber()),
                $h->modifier()
                    ->add($h->implodeModifier(';'))
                    ->add($h->slashesModifier())
                    ->add($h->transBracketsModifier('codex_articles'))
                    ->apply($item->getCodexArticles()),
                $item->getCourtName(),
                $h->modifier()
                    ->add($h->datetimeModifier('d.m.Y'))
                    ->add($h->transBracketsModifier('sentence_date'))
                    ->apply($item->getSentenceDate()),
                $h->modifier()
                    ->add($h->datetimeModifier('d.m.Y'))
                    ->add($h->transBracketsModifier('punishment_start'))
                    ->apply($item->getPunishmentStart()),
            ],
            $full
        );

        return $message;
    }
}
