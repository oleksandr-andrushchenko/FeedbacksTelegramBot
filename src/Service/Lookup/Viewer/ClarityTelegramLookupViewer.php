<?php

declare(strict_types=1);

namespace App\Service\Lookup\Viewer;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Lookup\ClarityProjectCourt;
use App\Entity\Lookup\ClarityProjectCourtsRecord;
use App\Entity\Lookup\ClarityProjectEdr;
use App\Entity\Lookup\ClarityProjectEdrsRecord;
use App\Entity\Lookup\ClarityProjectEnforcement;
use App\Entity\Lookup\ClarityProjectEnforcementsRecord;
use App\Entity\Lookup\ClarityProjectSecurity;
use App\Entity\Lookup\ClarityProjectSecurityRecord;
use Symfony\Contracts\Translation\TranslatorInterface;

class ClarityTelegramLookupViewer implements LookupViewerInterface
{
    public function __construct(
        private readonly LookupViewerHelper $lookupViewerHelper,
        private readonly TranslatorInterface $translator,
    )
    {
    }

    public function getOnSearchTitle(FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        return $this->trans('on_search_title');
    }

    public function getEmptyResultTitle(FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        return $this->trans('empty_result_title');
    }

    public function getResultTitle(FeedbackSearchTerm $searchTerm, int $count, array $context = []): string
    {
        return $this->trans('result_title');
    }

    public function getResultRecord($record, array $context = []): string
    {
        if (is_string($record)) {
            return $record;
        }

        return match (get_class($record)) {
            ClarityProjectEdrsRecord::class => $this->getEdrsResultRecord($record),
            ClarityProjectSecurityRecord::class => $this->getSecurityResultRecord($record),
            ClarityProjectCourtsRecord::class => $this->getCourtsResultRecord($record),
            ClarityProjectEnforcementsRecord::class => $this->getEnforcementsResultRecord($record),
        };
    }

    private function getEdrsResultRecord(ClarityProjectEdrsRecord $record): string
    {
        return $this->lookupViewerHelper->wrapResultRecord(
            $this->trans('edrs_title'),
            $record->getEdrs(),
            fn (ClarityProjectEdr $edr): array => [
                sprintf('<b>%s</b>', empty($edr->getHref()) ? $edr->getName() : sprintf('<a href="%s">%s</a>', $edr->getHref(), $edr->getName())),
                empty($edr->getType()) ? null : $edr->getType(),
                empty($edr->getNumber()) ? null : sprintf('%s [ %s ]', $edr->getNumber(), $this->trans('edr_number')),
                $edr->getActive() === null ? null : sprintf('%s %s', $edr->getActive() ? '🟢' : '⚪️', $this->trans($edr->getActive() ? 'active' : 'inactive')),
                empty($edr->getAddress()) ? null : $edr->getAddress(),
            ]
        );
    }

    private function getSecurityResultRecord(ClarityProjectSecurityRecord $record): string
    {
        return $this->lookupViewerHelper->wrapResultRecord(
            $this->trans('security_title'),
            $record->getSecurity(),
            fn (ClarityProjectSecurity $sec): array => [
                sprintf('<b>%s</b>', $sec->getName()),
                empty($sec->getBornAt()) ? null : sprintf('%s [ %s ]', $sec->getBornAt()->format('d.m.Y'), $this->trans('born_at')),
                empty($sec->getArchive()) ? null : sprintf('%s %s', $sec->getArchive() ? '⚪️' : '🔴', $this->trans($sec->getArchive() ? 'archive' : 'actual')),
                empty($sec->getCategory()) ? null : sprintf('<u>%s</u>', $sec->getCategory()),
//                    empty($sec->getRegion()) ? null : $sec->getRegion(),
                empty($sec->getAbsentAt()) ? null : sprintf('%s [ %s ]', $sec->getAbsentAt()->format('d.m.Y'), $this->trans('absent_at')),
                empty($sec->getAccusation()) ? null : sprintf('%s [ %s ]', $sec->getAccusation(), $this->trans('accusation')),
                empty($sec->getPrecaution()) ? null : sprintf('%s [ %s ]', $sec->getPrecaution(), $this->trans('precaution')),
            ]
        );
    }

    private function getCourtsResultRecord(ClarityProjectCourtsRecord $record): string
    {
        return $this->lookupViewerHelper->wrapResultRecord(
            $this->trans('courts_title'),
            $record->getCourts(),
            fn (ClarityProjectCourt $court): array => [
                sprintf('<b>%s</b> [ %s ]', $court->getNumber(), $this->trans('case_number')),
                empty($court->getState()) ? null : $court->getState(),
                empty($court->getSide()) ? null : sprintf('%s %s', str_contains($court->getSide(), 'заявник') ? '⚪️' : '🔴', $court->getSide()),
                empty($court->getDesc()) ? null : sprintf('<u>%s</u> [ %s ]', $court->getDesc(), $this->trans('desc')),
                empty($court->getPlace()) ? null : $court->getPlace(),
            ]
        );
    }

    private function getEnforcementsResultRecord(ClarityProjectEnforcementsRecord $record): string
    {
        return $this->lookupViewerHelper->wrapResultRecord(
            $this->trans('enforcements_title'),
            $record->getEnforcements(),
            fn (ClarityProjectEnforcement $enf): array => [
                sprintf('<b>%s</b> [ %s ]', $enf->getNumber(), $this->trans('enf_number')),
                empty($enf->getOpenedAt()) ? null : $enf->getOpenedAt()->format('d.m.Y'),
                empty($enf->getDebtor()) ? null : sprintf('%s [ %s ]', $enf->getDebtor(), $this->trans('debtor')),
                empty($enf->getBornAt()) ? null : sprintf('%s [ %s ]', $enf->getBornAt()->format('d.m.Y'), $this->trans('born_at')),
                empty($enf->getCollector()) ? null : sprintf('%s [ %s ]', $enf->getCollector(), $this->trans('collector')),
                empty($enf->getState()) ? null : sprintf('%s %s', str_contains($enf->getState(), 'Відкрито') ? '🔴' : '⚪️', $enf->getState()),
            ]
        );
    }

    private function trans($id, array $parameters = []): string
    {
        return $this->translator->trans($id, $parameters, 'lookups.tg.clarity');
    }
}
