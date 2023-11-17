<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Search\Clarity\ClarityEdr;
use App\Entity\Search\Clarity\ClarityEdrsRecord;
use App\Entity\Search\Clarity\ClarityPerson;
use App\Entity\Search\Clarity\ClarityPersonCourt;
use App\Entity\Search\Clarity\ClarityPersonCourtsRecord;
use App\Entity\Search\Clarity\ClarityPersonDebtor;
use App\Entity\Search\Clarity\ClarityPersonDebtorsRecord;
use App\Entity\Search\Clarity\ClarityPersonEdr;
use App\Entity\Search\Clarity\ClarityPersonEdrsRecord;
use App\Entity\Search\Clarity\ClarityPersonEnforcement;
use App\Entity\Search\Clarity\ClarityPersonEnforcementsRecord;
use App\Entity\Search\Clarity\ClarityPersonSecurity;
use App\Entity\Search\Clarity\ClarityPersonSecurityRecord;
use App\Entity\Search\Clarity\ClarityPersonsRecord;
use DateTimeImmutable;
use Symfony\Contracts\Translation\TranslatorInterface;

class ClarityTelegramSearchViewer extends SearchViewer implements SearchViewerInterface
{
    public function __construct(TranslatorInterface $translator)
    {
        parent::__construct($translator, 'clarity');
    }

    public function getOnSearchTitle(FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        return $this->trans('on_search');
    }

    public function getEmptyResultTitle(FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        return $this->trans('empty_result');
    }

    public function getResultRecord($record, array $context = []): string
    {
        if (is_string($record)) {
            return $record;
        }

        $full = $context['full'] ?? false;

        return match (get_class($record)) {
            ClarityPersonsRecord::class => $this->getPersonsResultRecord($record, $full),
            ClarityPersonEdrsRecord::class => $this->getPersonEdrsResultRecord($record, $full),
            ClarityPersonSecurityRecord::class => $this->getPersonSecurityResultRecord($record, $full),
            ClarityPersonCourtsRecord::class => $this->getPersonCourtsResultRecord($record, $full),
            ClarityPersonEnforcementsRecord::class => $this->getPersonEnforcementsResultRecord($record, $full),
            ClarityPersonDebtorsRecord::class => $this->getPersonDebtorsResultRecord($record, $full),
            ClarityEdrsRecord::class => $this->getEdrsResultRecord($record, $full),
        };
    }

    private function getPersonsResultRecord(ClarityPersonsRecord $record, bool $full): string
    {
        $message = '🤔 ';
        $message .= $this->wrapResultRecord(
            $this->trans('persons_title', ['count' => count($record->getPersons())]),
            $record->getPersons(),
            fn (ClarityPerson $person): array => [
                sprintf('<b>%s</b>', empty($person->getHref()) || !$full ? $person->getName() : sprintf('<a href="%s">%s</a>', $person->getHref(), $person->getName())),
                empty($person->getCount()) ? null : sprintf('<i>%s</i>', $this->trans('person_count', ['count' => $person->getCount()])),
            ],
            $full
        );

        return $message;
    }

    private function getPersonEdrsResultRecord(ClarityPersonEdrsRecord $record, bool $full): string
    {
        $message = '💫 ';
        $message .= $this->wrapResultRecord(
            $this->trans('person_edrs_title', ['count' => count($record->getEdrs())]),
            $record->getEdrs(),
            fn (ClarityPersonEdr $edr): array => [
                sprintf('<b>%s</b>', empty($edr->getHref()) || !$full ? $edr->getName() : sprintf('<a href="%s">%s</a>', $edr->getHref(), $edr->getName())),
                empty($edr->getType()) ? null : $edr->getType(),
                empty($edr->getNumber()) ? null : sprintf('%s [ %s ]', $edr->getNumber(), $this->trans('edr_number')),
                $edr->getActive() === null ? null : sprintf('%s %s', $edr->getActive() ? '🟢' : '⚪️', $this->trans($edr->getActive() ? 'active' : 'inactive')),
                empty($edr->getAddress()) ? null : $edr->getAddress(),
            ],
            $full
        );

        return $message;
    }

    private function getPersonSecurityResultRecord(ClarityPersonSecurityRecord $record, bool $full): string
    {
        $message = '🚨 ';
        $message .= $this->wrapResultRecord(
            $this->trans('security_title', ['count' => count($record->getSecurity())]),
            $record->getSecurity(),
            fn (ClarityPersonSecurity $sec): array => [
                sprintf('<b>%s</b>', $sec->getName()),
                empty($sec->getBornAt()) ? null : sprintf('%s [ %s ]', $sec->getBornAt()->format('d.m.Y'), $this->trans('born_at')),
                empty($sec->getArchive()) ? null : sprintf('%s %s', $sec->getArchive() ? '⚪️' : '🔴', $this->trans($sec->getArchive() ? 'archive' : 'actual')),
                empty($sec->getCategory()) ? null : sprintf('<u>%s</u>', $sec->getCategory()),
                empty($sec->getAbsentAt()) ? null : sprintf('%s [ %s ]', $sec->getAbsentAt()->format('d.m.Y'), $this->trans('absent_at')),
                empty($sec->getAccusation()) ? null : sprintf('%s [ %s ]', $sec->getAccusation(), $this->trans('accusation')),
                empty($sec->getPrecaution()) ? null : sprintf('%s [ %s ]', $sec->getPrecaution(), $this->trans('precaution')),
            ],
            $full
        );

        return $message;
    }

    private function getPersonCourtsResultRecord(ClarityPersonCourtsRecord $record, bool $full): string
    {
        $message = '‼️ ';
        $message .= $this->wrapResultRecord(
            $this->trans('courts_title', ['count' => count($record->getCourts())]),
            $record->getCourts(),
            fn (ClarityPersonCourt $court): array => [
                sprintf('<b>%s</b> [ %s ]', $court->getNumber(), $this->trans('case_number')),
                empty($court->getState()) ? null : $court->getState(),
                empty($court->getSide()) ? null : sprintf('%s %s', str_contains($court->getSide(), 'заявник') ? '⚪️' : '🔴', $court->getSide()),
                empty($court->getDesc()) ? null : sprintf('<u>%s</u> [ %s ]', $court->getDesc(), $this->trans('desc')),
                empty($court->getPlace()) ? null : $court->getPlace(),
            ],
            $full
        );

        return $message;
    }

    private function getPersonEnforcementsResultRecord(ClarityPersonEnforcementsRecord $record, bool $full): string
    {
        $message = '‼️ ';
        $message .= $this->wrapResultRecord(
            $this->trans('enforcements_title', ['count' => count($record->getEnforcements())]),
            $record->getEnforcements(),
            fn (ClarityPersonEnforcement $enf): array => [
                sprintf('<b>%s</b> [ %s ]', $enf->getNumber(), $this->trans('enf_number')),
                empty($enf->getOpenedAt()) ? null : $enf->getOpenedAt()->format('d.m.Y'),
                empty($enf->getDebtor()) ? null : sprintf('%s [ %s ]', $enf->getDebtor(), $this->trans('debtor')),
                empty($enf->getBornAt()) ? null : sprintf('%s [ %s ]', $enf->getBornAt()->format('d.m.Y'), $this->trans('born_at')),
                empty($enf->getState()) ? null : sprintf('%s %s', str_contains($enf->getState(), 'Відкрито') ? '🔴' : '⚪️', $enf->getState()),
                empty($enf->getCollector()) ? null : sprintf('%s [ %s ]', $enf->getCollector(), $this->trans('collector')),
            ],
            $full
        );

        return $message;
    }

    private function getPersonDebtorsResultRecord(ClarityPersonDebtorsRecord $record, bool $full): string
    {
        $message = '‼️ ';
        $message .= $this->wrapResultRecord(
            $this->trans('debtors_title', ['count' => count($record->getDebtors())]),
            $record->getDebtors(),
            fn (ClarityPersonDebtor $debtor): array => [
                sprintf('<b>%s</b>', $debtor->getName()),
                empty($debtor->getBornAt()) ? null : sprintf('%s [ %s ]', $debtor->getBornAt()->format('d.m.Y'), $this->trans('born_at')),
                empty($debtor->getCategory()) ? null : sprintf('<u>%s</u>', $debtor->getCategory()),
                empty($debtor->getActualAt()) ? null : sprintf('%s %s [ %s ]', ($archive = $debtor->getActualAt() < new DateTimeImmutable()) ? '⚪️' : '🔴', $debtor->getActualAt()->format('d.m.Y'), $this->trans($archive ? 'archive' : 'actual')),
            ],
            $full
        );

        return $message;
    }

    private function getEdrsResultRecord(ClarityEdrsRecord $record, bool $full): string
    {
        $message = '🤔 ';
        $message .= $this->wrapResultRecord(
            $this->trans('edrs_title', ['count' => count($record->getEdrs())]),
            $record->getEdrs(),
            fn (ClarityEdr $edr): array => [
                sprintf('<b>%s</b>', empty($edr->getHref()) || !$full ? $edr->getName() : sprintf('<a href="%s">%s</a>', $edr->getHref(), $edr->getName())),
                empty($edr->getType()) ? null : $edr->getType(),
                empty($edr->getNumber()) ? null : sprintf('%s [ %s ]', $edr->getNumber(), $this->trans('edr_number')),
                $edr->getActive() === null ? null : sprintf('%s %s', $edr->getActive() ? '🟢' : '⚪️', $this->trans($edr->getActive() ? 'active' : 'inactive')),
                empty($edr->getAddress()) ? null : $edr->getAddress(),
            ],
            $full
        );

        return $message;
    }
}
