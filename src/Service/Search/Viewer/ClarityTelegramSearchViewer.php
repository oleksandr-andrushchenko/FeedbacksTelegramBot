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
use App\Service\Util\String\SecretsAdder;
use DateTimeImmutable;
use Symfony\Contracts\Translation\TranslatorInterface;

class ClarityTelegramSearchViewer extends SearchViewer implements SearchViewerInterface
{
    public function __construct(
        TranslatorInterface $translator,
        SecretsAdder $secretsAdder
    )
    {
        parent::__construct($translator, $secretsAdder, 'clarity');
    }

    public function getOnSearchTitle(FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        return $this->trans('on_search');
    }

    public function getEmptyResultTitle(FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        return $this->trans('empty_result');
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
        $message = '🤔 ';
        $message .= $this->wrapResultRecord(
            $this->trans('persons_title', ['count' => count($record->getPersons())]),
            $record->getPersons(),
            fn (ClarityPerson $person): array => match (true) {
                $full => [
                    $this->modifier()->add($this->slashesModifier())->add($this->linkModifier($person->getHref()))->add($this->boldModifier())->apply($person->getName()),
                    $this->modifier()->add($this->conditionalModifier($person->getCount()))->add($this->italicModifier())->apply($this->trans('person_count', ['count' => $person->getCount()])),
                ],
                default => [
                    $this->modifier()->add($this->slashesModifier())->add($this->boldModifier())->apply($person->getName()),
                    $this->modifier()->add($this->conditionalModifier($person->getCount()))->add($this->italicModifier())->apply($this->trans('person_count', ['count' => $person->getCount()])),
                ],
            },
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
            fn (ClarityPersonEdr $edr): array => match (true) {
                $full => [
                    $this->modifier()->add($this->slashesModifier())->add($this->linkModifier($edr->getHref()))->add($this->boldModifier())->apply($edr->getName()),
                    $edr->getType(),
                    $this->modifier()->add($this->bracketsModifier('edr_number'))->apply($edr->getNumber()),
                    $this->modifier()->add($this->greenWhiteModifier('active'))->apply($edr->getActive()),
                    $edr->getAddress(),
                ],
                default => [
                    $this->modifier()->add($this->slashesModifier())->add($this->boldModifier())->apply($edr->getName()),
                    $edr->getType(),
                    $this->modifier()->add($this->bracketsModifier('edr_number'))->apply($edr->getNumber()),
                    $this->modifier()->add($this->greenWhiteModifier('active'))->apply($edr->getActive()),
                    $edr->getAddress(),
                ],
            },
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
                $this->modifier()->add($this->boldModifier())->apply($sec->getName()),
                $this->modifier()->add($this->datetimeModifier('d.m.Y'))->add($this->bracketsModifier('born_at'))->apply($sec->getBornAt()),
                $this->modifier()->add($this->redWhiteModifier('actual'))->apply(!$sec->getArchive()),
                $this->modifier()->add($this->underlineModifier())->apply($sec->getCategory()),
                $this->modifier()->add($this->datetimeModifier('d.m.Y'))->add($this->bracketsModifier('absent_at'))->apply($sec->getAbsentAt()),
                $this->modifier()->add($this->bracketsModifier('accusation'))->apply($sec->getAccusation()),
                $this->modifier()->add($this->bracketsModifier('precaution'))->apply($sec->getPrecaution()),
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
                $this->modifier()->add($this->boldModifier())->add($this->bracketsModifier('case_number'))->apply($court->getNumber()),
                $court->getState(),
                $this->modifier()->add($this->redWhiteModifier())->add($this->appendModifier($court->getSide()))->apply(!str_contains($court->getSide(), 'заявник')),
                $this->modifier()->add($this->underlineModifier())->add($this->bracketsModifier('desc'))->apply($court->getDesc()),
                $court->getPlace(),
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
                $this->modifier()->add($this->boldModifier())->add($this->bracketsModifier('enf_number'))->apply($enf->getNumber()),
                $this->modifier()->add($this->datetimeModifier('d.m.Y'))->apply($enf->getOpenedAt()),
                $this->modifier()->add($this->bracketsModifier('debtor'))->apply($enf->getDebtor()),
                $this->modifier()->add($this->datetimeModifier('d.m.Y'))->add($this->bracketsModifier('born_at'))->apply($enf->getBornAt()),
                $this->modifier()->add($this->redWhiteModifier())->add($this->appendModifier($enf->getState()))->apply(str_contains($enf->getState(), 'Відкрито')),
                $this->modifier()->add($this->bracketsModifier('collector'))->apply($enf->getCollector()),
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
                $this->modifier()->add($this->slashesModifier())->add($this->boldModifier())->apply($debtor->getName()),
                $this->modifier()->add($this->datetimeModifier('d.m.Y'))->add($this->bracketsModifier('born_at'))->apply($debtor->getBornAt()),
                $this->modifier()->add($this->underlineModifier())->apply($debtor->getCategory()),
                $this->modifier()->add($this->redWhiteModifier('actual'))->add($this->bracketsModifier('actual_at'))->apply($debtor->getActualAt() > new DateTimeImmutable()),
            ],
            $full
        );

        return $message;
    }

    private function getEdrsResultRecord(ClarityEdrsRecord $record, SearchTermType $searchType, bool $full): string
    {
        $message = '🤔 ';
        $message .= $this->wrapResultRecord(
            $this->trans('edrs_title', ['count' => count($record->getEdrs())]),
            $record->getEdrs(),
            fn (ClarityEdr $edr): array => match (true) {
                $full => [
                    $this->modifier()->add($this->slashesModifier())->add($this->linkModifier($edr->getHref()))->add($this->boldModifier())->apply($edr->getName()),
                    $edr->getType(),
                    $this->modifier()->add($this->greenWhiteModifier('active'))->apply($edr->getActive()),
                    $edr->getAddress(),
                ],
                $searchType === SearchTermType::phone_number => [
                    $this->modifier()->add($this->slashesModifier())->add($this->secretsModifier())->add($this->boldModifier())->apply($edr->getName()),
                    $this->modifier()->add($this->hiddenModifier('address'))->apply($edr->getAddress()),
                ],
                default => [
                    $this->modifier()->add($this->slashesModifier())->add($this->boldModifier())->apply($edr->getName()),
                    $edr->getType(),
                    $this->modifier()->add($this->greenWhiteModifier('active'))->apply($edr->getActive()),
                    $edr->getAddress(),
                ]
            },
            $full
        );

        return $message;
    }
}
