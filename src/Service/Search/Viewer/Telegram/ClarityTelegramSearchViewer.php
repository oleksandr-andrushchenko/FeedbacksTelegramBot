<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer\Telegram;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Search\Clarity\ClarityEdr;
use App\Entity\Search\Clarity\ClarityEdrs;
use App\Entity\Search\Clarity\ClarityPerson;
use App\Entity\Search\Clarity\ClarityPersonCourt;
use App\Entity\Search\Clarity\ClarityPersonCourts;
use App\Entity\Search\Clarity\ClarityPersonDebtor;
use App\Entity\Search\Clarity\ClarityPersonDebtors;
use App\Entity\Search\Clarity\ClarityPersonDeclaration;
use App\Entity\Search\Clarity\ClarityPersonDeclarations;
use App\Entity\Search\Clarity\ClarityPersonEdr;
use App\Entity\Search\Clarity\ClarityPersonEdrs;
use App\Entity\Search\Clarity\ClarityPersonEnforcement;
use App\Entity\Search\Clarity\ClarityPersonEnforcements;
use App\Entity\Search\Clarity\ClarityPersonSecurity;
use App\Entity\Search\Clarity\ClarityPersonSecurities;
use App\Entity\Search\Clarity\ClarityPersons;
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
            ClarityPersons::class => $this->getPersonsResultRecord($record, $full),
            ClarityPersonEdrs::class => $this->getPersonEdrsResultRecord($record, $full),
            ClarityPersonSecurities::class => $this->getPersonSecurityResultRecord($record, $full),
            ClarityPersonCourts::class => $this->getPersonCourtsResultRecord($record, $full),
            ClarityPersonEnforcements::class => $this->getPersonEnforcementsResultRecord($record, $full),
            ClarityPersonDebtors::class => $this->getPersonDebtorsResultRecord($record, $full),
            ClarityPersonDeclarations::class => $this->getPersonDeclarationsResultRecord($record, $full),
            ClarityEdrs::class => $this->getEdrsResultRecord($record, $searchTerm->getType(), $full),
        };
    }

    private function getPersonsResultRecord(ClarityPersons $record, bool $full): string
    {
        $h = $this->searchViewerHelper;
        $message = '🤔 ';
        $message .= $h->wrapResultRecord(
            $h->trans('persons_title'),
            $record->getItems(),
            static fn (ClarityPerson $item): array => [
                $h->modifier()
                    ->add($h->slashesModifier())
                    ->add($full ? $h->linkModifier($item->getHref()) : $h->nullModifier())
                    ->add($h->boldModifier())
                    ->apply($item->getName()),
                $h->modifier()
                    ->add($h->conditionalModifier($item->getCount()))
                    ->add($h->italicModifier())
                    ->apply($h->trans('person_count', ['count' => $item->getCount()])),
            ],
            $full
        );

        return $message;
    }

    private function getPersonEdrsResultRecord(ClarityPersonEdrs $record, bool $full): string
    {
        $h = $this->searchViewerHelper;
        $message = '💫 ';
        $message .= $h->wrapResultRecord(
            $h->trans('person_edrs_title'),
            $record->getItems(),
            static fn (ClarityPersonEdr $item): array => [
                $h->modifier()
                    ->add($h->slashesModifier())
                    ->add($full ? $h->linkModifier($item->getHref()) : $h->nullModifier())
                    ->add($h->boldModifier())
                    ->apply($item->getName()),
                $h->modifier()
                    ->add($h->slashesModifier())
                    ->apply($item->getType()),
                $h->modifier()
                    ->add($h->transBracketsModifier('edr_number'))
                    ->apply($item->getNumber()),
                $h->modifier()
                    ->add($h->greenWhiteModifier('active'))
                    ->apply($item->getActive()),
                $h->modifier()
                    ->add($h->slashesModifier())
                    ->apply($item->getAddress()),
            ],
            $full
        );

        return $message;
    }

    private function getPersonSecurityResultRecord(ClarityPersonSecurities $record, bool $full): string
    {
        $h = $this->searchViewerHelper;
        $message = '🚨 ';
        $message .= $h->wrapResultRecord(
            $h->trans('security_title'),
            $record->getItems(),
            static fn (ClarityPersonSecurity $item): array => [
                $h->modifier()
                    ->add($h->slashesModifier())
                    ->add($h->boldModifier())
                    ->apply($item->getName()),
                $h->modifier()
                    ->add($h->datetimeModifier('d.m.Y'))
                    ->add($h->transBracketsModifier('born_at'))
                    ->apply($item->getBornAt()),
                $h->modifier()
                    ->add($h->redWhiteModifier('actual'))
                    ->apply(!$item->getArchive()),
                $h->modifier()
                    ->add($h->slashesModifier())
                    ->add($h->underlineModifier())
                    ->apply($item->getCategory()),
                $h->modifier()
                    ->add($h->datetimeModifier('d.m.Y'))
                    ->add($h->transBracketsModifier('absent_at'))
                    ->apply($item->getAbsentAt()),
                $h->modifier()
                    ->add($h->slashesModifier())
                    ->add($h->transBracketsModifier('accusation'))
                    ->apply($item->getAccusation()),
                $h->modifier()
                    ->add($h->slashesModifier())
                    ->add($h->transBracketsModifier('precaution'))
                    ->apply($item->getPrecaution()),
            ],
            $full
        );

        return $message;
    }

    private function getPersonCourtsResultRecord(ClarityPersonCourts $record, bool $full): string
    {
        $h = $this->searchViewerHelper;
        $message = '‼️ ';
        $message .= $h->wrapResultRecord(
            $h->trans('courts_title'),
            $record->getItems(),
            static fn (ClarityPersonCourt $item): array => [
                $h->modifier()
                    ->add($h->slashesModifier())
                    ->add($h->boldModifier())
                    ->add($h->transBracketsModifier('case_number'))
                    ->apply($item->getNumber()),
                $h->modifier()
                    ->add($h->slashesModifier())
                    ->apply($item->getState()),
                $h->modifier()
                    ->add($h->redWhiteModifier())
                    ->add(
                        $h->appendModifier(
                            $h->modifier()
                                ->add($h->slashesModifier())
                                ->apply($item->getSide())
                        )
                    )
                    ->apply(!str_contains($item->getSide(), 'заявник')),
                $h->modifier()
                    ->add($h->slashesModifier())
                    ->add($h->underlineModifier())
                    ->add($h->transBracketsModifier('desc'))
                    ->apply($item->getDesc()),
                $h->modifier()
                    ->add($h->slashesModifier())
                    ->apply($item->getPlace()),
            ],
            $full
        );

        return $message;
    }

    private function getPersonEnforcementsResultRecord(ClarityPersonEnforcements $record, bool $full): string
    {
        $h = $this->searchViewerHelper;
        $message = '‼️ ';
        $message .= $h->wrapResultRecord(
            $h->trans('enforcements_title'),
            $record->getItems(),
            static fn (ClarityPersonEnforcement $item): array => [
                $h->modifier()
                    ->add($h->slashesModifier())
                    ->add($h->boldModifier())
                    ->add($h->transBracketsModifier('enf_number'))
                    ->apply($item->getNumber()),
                $h->modifier()
                    ->add($h->datetimeModifier('d.m.Y'))
                    ->apply($item->getOpenedAt()),
                $h->modifier()
                    ->add($h->slashesModifier())
                    ->add($h->transBracketsModifier('debtor'))
                    ->apply($item->getDebtor()),
                $h->modifier()
                    ->add($h->datetimeModifier('d.m.Y'))
                    ->add($h->transBracketsModifier('born_at'))
                    ->apply($item->getBornAt()),
                $h->modifier()
                    ->add($h->redWhiteModifier())
                    ->add(
                        $h->appendModifier(
                            $h->modifier()
                                ->add($h->slashesModifier())
                                ->apply($item->getState())
                        )
                    )
                    ->apply(str_contains($item->getState(), 'Відкрито')),
                $h->modifier()
                    ->add($h->slashesModifier())
                    ->add($h->transBracketsModifier('collector'))
                    ->apply($item->getCollector()),
            ],
            $full
        );

        return $message;
    }

    private function getPersonDebtorsResultRecord(ClarityPersonDebtors $record, bool $full): string
    {
        $h = $this->searchViewerHelper;
        $message = '‼️ ';
        $message .= $h->wrapResultRecord(
            $h->trans('debtors_title'),
            $record->getItems(),
            static fn (ClarityPersonDebtor $item): array => [
                $h->modifier()
                    ->add($h->slashesModifier())
                    ->add($h->boldModifier())
                    ->apply($item->getName()),
                $h->modifier()
                    ->add($h->datetimeModifier('d.m.Y'))
                    ->add($h->transBracketsModifier('born_at'))
                    ->apply($item->getBornAt()),
                $h->modifier()
                    ->add($h->slashesModifier())
                    ->add($h->underlineModifier())
                    ->apply($item->getCategory()),
                $h->modifier()
                    ->add($h->redWhiteModifier('actual'))
                    ->add($h->transBracketsModifier('actual_at'))
                    ->apply($item->getActualAt() > new DateTimeImmutable()),
            ],
            $full
        );

        return $message;
    }

    private function getPersonDeclarationsResultRecord(ClarityPersonDeclarations $record, bool $full): string
    {
        $h = $this->searchViewerHelper;
        $message = '💫 ';
        $message .= $h->wrapResultRecord(
            $h->trans('person_declarations_title'),
            $record->getItems(),
            static fn (ClarityPersonDeclaration $item): array => [
                $h->modifier()
                    ->add($h->slashesModifier())
                    ->add($full ? $h->linkModifier($item->getHref()) : $h->nullModifier())
                    ->add($h->boldModifier())
                    ->apply($item->getName()),
                $h->modifier()
                    ->add($h->slashesModifier())
                    ->add($h->transBracketsModifier('year'))
                    ->apply($item->getYear()),
                $h->modifier()
                    ->add($h->slashesModifier())
                    ->add($h->transBracketsModifier('position'))
                    ->add($full ? $h->nullModifier() : $h->secretsModifier())
                    ->apply($item->getPosition()),
            ],
            $full
        );

        return $message;
    }

    private function getEdrsResultRecord(ClarityEdrs $record, SearchTermType $searchType, bool $full): string
    {
        $h = $this->searchViewerHelper;
        $message = '🤔 ';
        $message .= $h->wrapResultRecord(
            $h->trans('edrs_title'),
            $record->getItems(),
            static fn (ClarityEdr $item): array => [
                $h->modifier()
                    ->add($h->slashesModifier())
                    ->add($full ? $h->nullModifier() : $h->secretsModifier())
                    ->add($full ? $h->linkModifier($item->getHref()) : $h->nullModifier())
                    ->add($h->boldModifier())
                    ->apply($item->getName()),
                $h->modifier()
                    ->add($full ? $h->nullModifier() : $h->secretsModifier())
                    ->add($h->slashesModifier())
                    ->apply($item->getType()),
                $h->modifier()
                    ->add($full ? $h->nullModifier() : $h->secretsModifier())
                    ->add($h->greenWhiteModifier('active'))
                    ->apply($item->getActive()),
                $h->modifier()
                    ->add($h->slashesModifier())
                    ->add($full ? $h->nullModifier() : $h->secretsModifier())
                    ->add($full ? $h->nullModifier() : $h->transBracketsModifier('address'))
                    ->apply($item->getAddress()),
            ],
            $full
        );

        return $message;
    }
}
