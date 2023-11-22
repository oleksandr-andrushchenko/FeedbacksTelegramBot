<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer\Telegram;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Search\Clarity\ClarityEdr;
use App\Entity\Search\Clarity\ClarityEdrsRecord;
use App\Entity\Search\Clarity\ClarityPerson;
use App\Entity\Search\Clarity\ClarityPersonCourt;
use App\Entity\Search\Clarity\ClarityPersonCourtsRecord;
use App\Entity\Search\Clarity\ClarityPersonDebtor;
use App\Entity\Search\Clarity\ClarityPersonDebtorsRecord;
use App\Entity\Search\Clarity\ClarityPersonDeclaration;
use App\Entity\Search\Clarity\ClarityPersonDeclarationsRecord;
use App\Entity\Search\Clarity\ClarityPersonEdr;
use App\Entity\Search\Clarity\ClarityPersonEdrsRecord;
use App\Entity\Search\Clarity\ClarityPersonEnforcement;
use App\Entity\Search\Clarity\ClarityPersonEnforcementsRecord;
use App\Entity\Search\Clarity\ClarityPersonSecurity;
use App\Entity\Search\Clarity\ClarityPersonSecurityRecord;
use App\Entity\Search\Clarity\ClarityPersonsRecord;
use App\Enum\Feedback\SearchTermType;
use App\Service\Search\Viewer\SearchViewer;
use App\Service\Search\Viewer\SearchViewerHelper;
use App\Service\Search\Viewer\SearchViewerInterface;
use DateTimeImmutable;

class ClarityTelegramSearchViewer extends SearchViewer implements SearchViewerInterface
{
    public function __construct(SearchViewerHelper $searchViewerHelper)
    {
        parent::__construct($searchViewerHelper->withTransDomain('clarity'));
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
            ClarityPersonDeclarationsRecord::class => $this->getPersonDeclarationsResultRecord($record, $full),
            ClarityEdrsRecord::class => $this->getEdrsResultRecord($record, $searchTerm->getType(), $full),
        };
    }

    private function getPersonsResultRecord(ClarityPersonsRecord $record, bool $full): string
    {
        $h = $this->searchViewerHelper;
        $message = '🤔 ';
        $message .= $h->wrapResultRecord(
            $h->trans('persons_title', ['count' => count($record->getItems())]),
            $record->getItems(),
            static fn (ClarityPerson $person): array => [
                $h->modifier()
                    ->add($h->slashesModifier())
                    ->add($full ? $h->linkModifier($person->getHref()) : $h->nullModifier())
                    ->add($h->boldModifier())
                    ->apply($person->getName()),
                $h->modifier()
                    ->add($h->conditionalModifier($person->getCount()))
                    ->add($h->italicModifier())
                    ->apply($h->trans('person_count', ['count' => $person->getCount()])),
            ],
            $full
        );

        return $message;
    }

    private function getPersonEdrsResultRecord(ClarityPersonEdrsRecord $record, bool $full): string
    {
        $h = $this->searchViewerHelper;
        $message = '💫 ';
        $message .= $h->wrapResultRecord(
            $h->trans('person_edrs_title', ['count' => count($record->getItems())]),
            $record->getItems(),
            static fn (ClarityPersonEdr $edr): array => [
                $h->modifier()
                    ->add($h->slashesModifier())
                    ->add($full ? $h->linkModifier($edr->getHref()) : $h->nullModifier())
                    ->add($h->boldModifier())
                    ->apply($edr->getName()),
                $edr->getType(),
                $h->modifier()
                    ->add($h->transBracketsModifier('edr_number'))
                    ->apply($edr->getNumber()),
                $h->modifier()
                    ->add($h->greenWhiteModifier('active'))
                    ->apply($edr->getActive()),
                $edr->getAddress(),
            ],
            $full
        );

        return $message;
    }

    private function getPersonSecurityResultRecord(ClarityPersonSecurityRecord $record, bool $full): string
    {
        $h = $this->searchViewerHelper;
        $message = '🚨 ';
        $message .= $h->wrapResultRecord(
            $h->trans('security_title', ['count' => count($record->getItems())]),
            $record->getItems(),
            static fn (ClarityPersonSecurity $sec): array => [
                $h->modifier()
                    ->add($h->boldModifier())
                    ->apply($sec->getName()),
                $h->modifier()
                    ->add($h->datetimeModifier('d.m.Y'))
                    ->add($h->transBracketsModifier('born_at'))
                    ->apply($sec->getBornAt()),
                $h->modifier()
                    ->add($h->redWhiteModifier('actual'))
                    ->apply(!$sec->getArchive()),
                $h->modifier()
                    ->add($h->underlineModifier())
                    ->apply($sec->getCategory()),
                $h->modifier()
                    ->add($h->datetimeModifier('d.m.Y'))
                    ->add($h->transBracketsModifier('absent_at'))
                    ->apply($sec->getAbsentAt()),
                $h->modifier()
                    ->add($h->transBracketsModifier('accusation'))
                    ->apply($sec->getAccusation()),
                $h->modifier()
                    ->add($h->transBracketsModifier('precaution'))
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
            $h->trans('courts_title', ['count' => count($record->getItems())]),
            $record->getItems(),
            static fn (ClarityPersonCourt $court): array => [
                $h->modifier()
                    ->add($h->boldModifier())
                    ->add($h->transBracketsModifier('case_number'))
                    ->apply($court->getNumber()),
                $court->getState(),
                $h->modifier()
                    ->add($h->redWhiteModifier())
                    ->add($h->appendModifier($court->getSide()))
                    ->apply(!str_contains($court->getSide(), 'заявник')),
                $h->modifier()
                    ->add($h->underlineModifier())
                    ->add($h->transBracketsModifier('desc'))
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
            $h->trans('enforcements_title', ['count' => count($record->getItems())]),
            $record->getItems(),
            static fn (ClarityPersonEnforcement $enf): array => [
                $h->modifier()
                    ->add($h->boldModifier())
                    ->add($h->transBracketsModifier('enf_number'))
                    ->apply($enf->getNumber()),
                $h->modifier()
                    ->add($h->datetimeModifier('d.m.Y'))
                    ->apply($enf->getOpenedAt()),
                $h->modifier()
                    ->add($h->transBracketsModifier('debtor'))
                    ->apply($enf->getDebtor()),
                $h->modifier()
                    ->add($h->datetimeModifier('d.m.Y'))
                    ->add($h->transBracketsModifier('born_at'))
                    ->apply($enf->getBornAt()),
                $h->modifier()
                    ->add($h->redWhiteModifier())
                    ->add($h->appendModifier($enf->getState()))
                    ->apply(str_contains($enf->getState(), 'Відкрито')),
                $h->modifier()
                    ->add($h->transBracketsModifier('collector'))
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
            $h->trans('debtors_title', ['count' => count($record->getItems())]),
            $record->getItems(),
            static fn (ClarityPersonDebtor $debtor): array => [
                $h->modifier()
                    ->add($h->slashesModifier())
                    ->add($h->boldModifier())
                    ->apply($debtor->getName()),
                $h->modifier()
                    ->add($h->datetimeModifier('d.m.Y'))
                    ->add($h->transBracketsModifier('born_at'))
                    ->apply($debtor->getBornAt()),
                $h->modifier()
                    ->add($h->underlineModifier())
                    ->apply($debtor->getCategory()),
                $h->modifier()
                    ->add($h->redWhiteModifier('actual'))
                    ->add($h->transBracketsModifier('actual_at'))
                    ->apply($debtor->getActualAt() > new DateTimeImmutable()),
            ],
            $full
        );

        return $message;
    }

    private function getPersonDeclarationsResultRecord(ClarityPersonDeclarationsRecord $record, bool $full): string
    {
        $h = $this->searchViewerHelper;
        $message = '💫 ';
        $message .= $h->wrapResultRecord(
            $h->trans('person_declarations_title', ['count' => count($record->getItems())]),
            $record->getItems(),
            static fn (ClarityPersonDeclaration $item): array => [
                $h->modifier()
                    ->add($h->slashesModifier())
                    ->add($full ? $h->linkModifier($item->getHref()) : $h->nullModifier())
                    ->add($h->boldModifier())
                    ->apply($item->getName()),
                $h->modifier()
                    ->add($h->transBracketsModifier('year'))
                    ->apply($item->getYear()),
                $h->modifier()
                    ->add($h->transBracketsModifier('position'))
                    ->add($full ? $h->nullModifier() : $h->secretsModifier())
                    ->apply($item->getPosition()),
            ],
            $full
        );

        return $message;
    }

    private function getEdrsResultRecord(ClarityEdrsRecord $record, SearchTermType $searchType, bool $full): string
    {
        $h = $this->searchViewerHelper;
        $message = '🤔 ';
        $phoneSearch = $searchType === SearchTermType::phone_number;
        $message .= $h->wrapResultRecord(
            $h->trans('edrs_title', ['count' => count($record->getItems())]),
            $record->getItems(),
            static fn (ClarityEdr $edr): array => [
                $h->modifier()
                    ->add($h->slashesModifier())
                    ->add($full ? $h->nullModifier() : ($phoneSearch ? $h->secretsModifier() : $h->nullModifier()))
                    ->add($full ? $h->linkModifier($edr->getHref()) : $h->nullModifier())
                    ->add($h->boldModifier())
                    ->apply($edr->getName()),
                $h->modifier()
                    ->add($h->conditionalModifier($full || !$phoneSearch))
                    ->apply($edr->getType()),
                $h->modifier()
                    ->add($h->conditionalModifier($full || !$phoneSearch))
                    ->add($h->greenWhiteModifier('active'))
                    ->apply($edr->getActive()),
                $h->modifier()
                    ->add($h->slashesModifier())
                    ->add($full ? $h->nullModifier() : ($phoneSearch ? $h->secretsModifier() : $h->nullModifier()))
                    ->add($full ? $h->nullModifier() : ($phoneSearch ? $h->transBracketsModifier('address') : $h->nullModifier()))
                    ->apply($edr->getAddress()),
            ],
            $full
        );

        return $message;
    }
}
