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
use App\Enum\Feedback\SearchTermType;
use DateTimeImmutable;

class ClarityTelegramSearchViewer extends SearchViewer implements SearchViewerInterface
{
    public function __construct(SearchViewerHelper $searchViewerHelper)
    {
        parent::__construct($searchViewerHelper->withTransDomain('clarity'));
    }

    public function getOnSearchTitle(FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        return $this->searchViewerHelper->trans('on_search');
    }

    public function getResultRecord($record, FeedbackSearchTerm $searchTerm, array $context = []): string
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
            ClarityEdrsRecord::class => $this->getEdrsResultRecord($record, $searchTerm->getType(), $full),
        };
    }

    private function getPersonsResultRecord(ClarityPersonsRecord $record, bool $full): string
    {
        $h = $this->searchViewerHelper;
        $message = '🤔 ';
        $message .= $h->wrapResultRecord(
            $h->trans('persons_title', ['count' => count($record->getPersons())]),
            $record->getPersons(),
            fn (ClarityPerson $person): array => match (true) {
                $full => [
                    $h->modifier()
                        ->add($h->slashesModifier())
                        ->add($h->linkModifier($person->getHref()))
                        ->add($h->boldModifier())
                        ->apply($person->getName()),
                    $h->modifier()
                        ->add($h->conditionalModifier($person->getCount()))
                        ->add($h->italicModifier())
                        ->apply($h->trans('person_count', ['count' => $person->getCount()])),
                ],
                default => [
                    $h->modifier()
                        ->add($h->slashesModifier())
                        ->add($h->boldModifier())
                        ->apply($person->getName()),
                    $h->modifier()
                        ->add($h->conditionalModifier($person->getCount()))
                        ->add($h->italicModifier())
                        ->apply($h->trans('person_count', ['count' => $person->getCount()])),
                ],
            },
            $full
        );

        return $message;
    }

    private function getPersonEdrsResultRecord(ClarityPersonEdrsRecord $record, bool $full): string
    {
        $h = $this->searchViewerHelper;
        $message = '💫 ';
        $message .= $h->wrapResultRecord(
            $h->trans('person_edrs_title', ['count' => count($record->getEdrs())]),
            $record->getEdrs(),
            static fn (ClarityPersonEdr $edr): array => match (true) {
                $full => [
                    $h->modifier()
                        ->add($h->slashesModifier())
                        ->add($h->linkModifier($edr->getHref()))
                        ->add($h->boldModifier())
                        ->apply($edr->getName()),
                    $edr->getType(),
                    $h->modifier()
                        ->add($h->bracketsModifier('edr_number'))
                        ->apply($edr->getNumber()),
                    $h->modifier()
                        ->add($h->greenWhiteModifier('active'))
                        ->apply($edr->getActive()),
                    $edr->getAddress(),
                ],
                default => [
                    $h->modifier()
                        ->add($h->slashesModifier())
                        ->add($h->boldModifier())
                        ->apply($edr->getName()),
                    $edr->getType(),
                    $h->modifier()
                        ->add($h->bracketsModifier('edr_number'))
                        ->apply($edr->getNumber()),
                    $h->modifier()
                        ->add($h->greenWhiteModifier('active'))
                        ->apply($edr->getActive()),
                    $edr->getAddress(),
                ],
            },
            $full
        );

        return $message;
    }

    private function getPersonSecurityResultRecord(ClarityPersonSecurityRecord $record, bool $full): string
    {
        $h = $this->searchViewerHelper;
        $message = '🚨 ';
        $message .= $h->wrapResultRecord(
            $h->trans('security_title', ['count' => count($record->getSecurity())]),
            $record->getSecurity(),
            static fn (ClarityPersonSecurity $sec): array => [
                $h->modifier()
                    ->add($h->boldModifier())
                    ->apply($sec->getName()),
                $h->modifier()
                    ->add($h->datetimeModifier('d.m.Y'))
                    ->add($h->bracketsModifier('born_at'))
                    ->apply($sec->getBornAt()),
                $h->modifier()
                    ->add($h->redWhiteModifier('actual'))
                    ->apply(!$sec->getArchive()),
                $h->modifier()
                    ->add($h->underlineModifier())
                    ->apply($sec->getCategory()),
                $h->modifier()
                    ->add($h->datetimeModifier('d.m.Y'))
                    ->add($h->bracketsModifier('absent_at'))
                    ->apply($sec->getAbsentAt()),
                $h->modifier()
                    ->add($h->bracketsModifier('accusation'))
                    ->apply($sec->getAccusation()),
                $h->modifier()
                    ->add($h->bracketsModifier('precaution'))
                    ->apply($sec->getPrecaution()),
            ],
            $full
        );

        return $message;
    }

    private function getPersonCourtsResultRecord(ClarityPersonCourtsRecord $record, bool $full): string
    {
        $h = $this->searchViewerHelper;
        $message = '‼️ ';
        $message .= $h->wrapResultRecord(
            $h->trans('courts_title', ['count' => count($record->getCourts())]),
            $record->getCourts(),
            static fn (ClarityPersonCourt $court): array => [
                $h->modifier()
                    ->add($h->boldModifier())
                    ->add($h->bracketsModifier('case_number'))
                    ->apply($court->getNumber()),
                $court->getState(),
                $h->modifier()
                    ->add($h->redWhiteModifier())
                    ->add($h->appendModifier($court->getSide()))
                    ->apply(!str_contains($court->getSide(), 'заявник')),
                $h->modifier()
                    ->add($h->underlineModifier())
                    ->add($h->bracketsModifier('desc'))
                    ->apply($court->getDesc()),
                $court->getPlace(),
            ],
            $full
        );

        return $message;
    }

    private function getPersonEnforcementsResultRecord(ClarityPersonEnforcementsRecord $record, bool $full): string
    {
        $h = $this->searchViewerHelper;
        $message = '‼️ ';
        $message .= $h->wrapResultRecord(
            $h->trans('enforcements_title', ['count' => count($record->getEnforcements())]),
            $record->getEnforcements(),
            static fn (ClarityPersonEnforcement $enf): array => [
                $h->modifier()
                    ->add($h->boldModifier())
                    ->add($h->bracketsModifier('enf_number'))
                    ->apply($enf->getNumber()),
                $h->modifier()
                    ->add($h->datetimeModifier('d.m.Y'))
                    ->apply($enf->getOpenedAt()),
                $h->modifier()
                    ->add($h->bracketsModifier('debtor'))
                    ->apply($enf->getDebtor()),
                $h->modifier()
                    ->add($h->datetimeModifier('d.m.Y'))
                    ->add($h->bracketsModifier('born_at'))
                    ->apply($enf->getBornAt()),
                $h->modifier()
                    ->add($h->redWhiteModifier())
                    ->add($h->appendModifier($enf->getState()))
                    ->apply(str_contains($enf->getState(), 'Відкрито')),
                $h->modifier()
                    ->add($h->bracketsModifier('collector'))
                    ->apply($enf->getCollector()),
            ],
            $full
        );

        return $message;
    }

    private function getPersonDebtorsResultRecord(ClarityPersonDebtorsRecord $record, bool $full): string
    {
        $h = $this->searchViewerHelper;
        $message = '‼️ ';
        $message .= $h->wrapResultRecord(
            $h->trans('debtors_title', ['count' => count($record->getDebtors())]),
            $record->getDebtors(),
            static fn (ClarityPersonDebtor $debtor): array => [
                $h->modifier()
                    ->add($h->slashesModifier())
                    ->add($h->boldModifier())
                    ->apply($debtor->getName()),
                $h->modifier()
                    ->add($h->datetimeModifier('d.m.Y'))
                    ->add($h->bracketsModifier('born_at'))
                    ->apply($debtor->getBornAt()),
                $h->modifier()
                    ->add($h->underlineModifier())
                    ->apply($debtor->getCategory()),
                $h->modifier()
                    ->add($h->redWhiteModifier('actual'))
                    ->add($h->bracketsModifier('actual_at'))
                    ->apply($debtor->getActualAt() > new DateTimeImmutable()),
            ],
            $full
        );

        return $message;
    }

    private function getEdrsResultRecord(ClarityEdrsRecord $record, SearchTermType $searchType, bool $full): string
    {
        $h = $this->searchViewerHelper;
        $message = '🤔 ';
        $message .= $h->wrapResultRecord(
            $h->trans('edrs_title', ['count' => count($record->getEdrs())]),
            $record->getEdrs(),
            static fn (ClarityEdr $edr): array => match (true) {
                $full => [
                    $h->modifier()
                        ->add($h->slashesModifier())
                        ->add($h->linkModifier($edr->getHref()))
                        ->add($h->boldModifier())
                        ->apply($edr->getName()),
                    $edr->getType(),
                    $h->modifier()
                        ->add($h->greenWhiteModifier('active'))
                        ->apply($edr->getActive()),
                    $edr->getAddress(),
                ],
                $searchType === SearchTermType::phone_number => [
                    $h->modifier()
                        ->add($h->slashesModifier())
                        ->add($h->secretsModifier())
                        ->add($h->boldModifier())
                        ->apply($edr->getName()),
                    $h->modifier()
                        ->add($h->hiddenModifier('address'))
                        ->apply($edr->getAddress()),
                ],
                default => [
                    $h->modifier()
                        ->add($h->slashesModifier())
                        ->add($h->boldModifier())
                        ->apply($edr->getName()),
                    $edr->getType(),
                    $h->modifier()
                        ->add($h->greenWhiteModifier('active'))
                        ->apply($edr->getActive()),
                    $edr->getAddress(),
                ]
            },
            $full
        );

        return $message;
    }
}
