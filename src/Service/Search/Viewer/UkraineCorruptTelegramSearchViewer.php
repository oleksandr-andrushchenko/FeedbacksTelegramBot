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
            fn (UkraineCorruptPerson $person): array => [
                empty($person->getLastName() && $person->getFirstName() && $person->getPatronymic()) ? null : sprintf('<b>%s</b>', $person->getLastName() . ' ' . $person->getFirstName() . ' ' . $person->getPatronymic()),
                empty($person->getEntityType()) ? null : $person->getEntityType(),
                empty($person->getOffenseName()) ? null : sprintf('🔴 %s', $person->getOffenseName()),
                empty($person->getPunishment()) ? null : sprintf('%s %s', $person->getPunishment(), empty($person->getPunishmentType()) ? null : sprintf('[ %s ]', $person->getPunishmentType())),
                empty($person->getCourtCaseNumber()) ? null : sprintf('%s [ %s ]', $person->getCourtCaseNumber(), $h->trans('court_case_number')),
                empty($person->getCodexArticles()) ? null : sprintf('%s [ %s ]', implode('; ', $person->getCodexArticles()), $h->trans('codex_articles')),
                empty($person->getCourtName()) ? null : $person->getCourtName(),
                empty($person->getSentenceDate()) ? null : sprintf('%s [ %s ]', $person->getSentenceDate()->format('d.m.Y'), $h->trans('sentence_date')),
                empty($person->getPunishmentStart()) ? null : sprintf('%s [ %s ]', $person->getPunishmentStart()->format('d.m.Y'), $h->trans('punishment_start')),
            ],
            $full
        );

        return $message;
    }
}
