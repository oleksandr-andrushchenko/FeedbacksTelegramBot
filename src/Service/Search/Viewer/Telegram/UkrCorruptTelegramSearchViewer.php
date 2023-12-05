<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer\Telegram;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Search\UkrCorrupt\UkrCorruptPerson;
use App\Entity\Search\UkrCorrupt\UkrCorruptPersons;
use App\Service\Search\Viewer\SearchViewer;
use App\Service\Search\Viewer\SearchViewerCompose;
use App\Service\Search\Viewer\SearchViewerInterface;
use App\Service\Modifier;

class UkrCorruptTelegramSearchViewer extends SearchViewer implements SearchViewerInterface
{
    public function __construct(SearchViewerCompose $searchViewerCompose, Modifier $modifier)
    {
        parent::__construct($searchViewerCompose->withTransDomain('ukr_corrupt'), $modifier);
    }

    public function getResultMessage($record, FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        if (is_string($record)) {
            return $record;
        }

        $full = $context['full'] ?? false;

        return match (get_class($record)) {
            UkrCorruptPersons::class => $this->getPersonsMessage($record, $full),
        };
    }

    public function getPersonsMessage(UkrCorruptPersons $record, bool $full): string
    {
        $m = $this->modifier;
        $message = '‼️ ';
        $message .= $this->implodeResult(
            $this->trans('persons_title'),
            $record->getItems(),
            fn (UkrCorruptPerson $item): array => [
                $m->create()
                    ->add($m->conditionalModifier($item->getFirstName() || $item->getPatronymic()))
                    ->add($m->slashesModifier())
                    ->add($m->appendModifier($item->getFirstName()))
                    ->add($m->appendModifier($item->getPatronymic()))
                    ->apply($item->getLastName()),
                $m->create()
                    ->add($m->slashesModifier())
                    ->apply($item->getEntityType()),
                $m->create()
                    ->add($m->redModifier())
                    ->add(
                        $m->appendModifier(
                            $m->create()
                                ->add($m->slashesModifier())
                                ->apply($item->getOffenseName())
                        )
                    )
                    ->apply(true),
                $m->create()
                    ->add($m->conditionalModifier($item->getPunishment()))
                    ->add($m->slashesModifier())
                    ->add(
                        $m->appendModifier(
                            $m->create()
                                ->add($m->bracketsModifier($item->getPunishmentType()))
                                ->add($m->trimModifier())
                                ->apply(' ')
                        )
                    )
                    ->apply($item->getPunishment()),
                $m->create()
                    ->add($m->bracketsModifier($this->trans('court_case_number')))
                    ->add($m->slashesModifier())
                    ->apply($item->getCourtCaseNumber()),
                $m->create()
                    ->add($m->implodeModifier('; '))
                    ->add($m->slashesModifier())
                    ->add($m->bracketsModifier($this->trans('codex_articles')))
                    ->apply($item->getCodexArticles()),
                $item->getCourtName(),
                $m->create()
                    ->add($m->datetimeModifier('d.m.Y'))
                    ->add($m->bracketsModifier($this->trans('sentence_date')))
                    ->apply($item->getSentenceDate()),
                $m->create()
                    ->add($m->datetimeModifier('d.m.Y'))
                    ->add($m->bracketsModifier($this->trans('punishment_start')))
                    ->apply($item->getPunishmentStart()),
            ],
            $full
        );

        return $message;
    }
}
