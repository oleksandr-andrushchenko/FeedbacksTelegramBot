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
use App\Entity\Search\Clarity\ClarityPersons;
use App\Entity\Search\Clarity\ClarityPersonSecurities;
use App\Entity\Search\Clarity\ClarityPersonSecurity;
use App\Enum\Feedback\SearchTermType;
use App\Service\Search\Viewer\SearchViewer;
use App\Service\Search\Viewer\SearchViewerCompose;
use App\Service\Search\Viewer\SearchViewerInterface;
use App\Service\Modifier;
use DateTimeImmutable;

class ClarityTelegramSearchViewer extends SearchViewer implements SearchViewerInterface
{
    public function __construct(SearchViewerCompose $searchViewerCompose, Modifier $modifier)
    {
        parent::__construct($searchViewerCompose->withTransDomain('clarity'), $modifier);
    }

    public function getResultMessage($record, FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        if (is_string($record)) {
            return $record;
        }

        $full = $context['full'] ?? false;

        return match (get_class($record)) {
            ClarityPersons::class => $this->getPersonsMessage($record, $full),
            ClarityPersonEdrs::class => $this->getPersonEdrsMessage($record, $full),
            ClarityPersonSecurities::class => $this->getPersonSecurityMessage($record, $full),
            ClarityPersonCourts::class => $this->getPersonCourtsMessage($record, $full),
            ClarityPersonEnforcements::class => $this->getPersonEnforcementsMessage($record, $full),
            ClarityPersonDebtors::class => $this->getPersonDebtorsMessage($record, $full),
            ClarityPersonDeclarations::class => $this->getPersonDeclarationsMessage($record, $full),
            ClarityEdrs::class => $this->getEdrsMessage($record, $searchTerm->getType(), $full),
        };
    }

    private function getPersonsMessage(ClarityPersons $record, bool $full): string
    {
        $m = $this->modifier;
        $message = '💫 ';
        $message .= $this->implodeResult(
            $this->trans('persons_title'),
            $record->getItems(),
            fn (ClarityPerson $item): array => [
                $m->create()
                    ->add($m->slashesModifier())
                    ->add($full ? $m->nullModifier() : $m->secretsModifier())
                    ->add($full ? $m->linkModifier($item->getHref()) : $m->nullModifier())
                    ->add($m->boldModifier())
                    ->apply($item->getName()),
                $m->create()
                    ->add($m->conditionalModifier($item->getCount()))
                    ->add($m->italicModifier())
                    ->apply($this->trans('person_count', ['count' => $item->getCount()])),
            ],
            $full
        );

        return $message;
    }

    private function getPersonEdrsMessage(ClarityPersonEdrs $record, bool $full): string
    {
        $m = $this->modifier;
        $message = '💫 ';
        $message .= $this->implodeResult(
            $this->trans('person_edrs_title'),
            $record->getItems(),
            fn (ClarityPersonEdr $item): array => [
                $m->create()
                    ->add($m->slashesModifier())
                    ->add($full ? $m->linkModifier($item->getHref()) : $m->nullModifier())
                    ->add($m->boldModifier())
                    ->apply($item->getName()),
                $m->create()
                    ->add($m->slashesModifier())
                    ->apply($item->getType()),
                $m->create()
                    ->add($m->bracketsModifier($this->trans('edr_number')))
                    ->apply($item->getNumber()),
                $m->create()
                    ->add($m->greenWhiteModifier($this->trans('active'), $this->trans('not_active')))
                    ->apply($item->getActive()),
                $m->create()
                    ->add($m->slashesModifier())
                    ->apply($item->getAddress()),
            ],
            $full
        );

        return $message;
    }

    private function getPersonSecurityMessage(ClarityPersonSecurities $record, bool $full): string
    {
        $m = $this->modifier;
        $message = '🚨 ';
        $message .= $this->implodeResult(
            $this->trans('security_title'),
            $record->getItems(),
            fn (ClarityPersonSecurity $item): array => [
                $m->create()
                    ->add($m->slashesModifier())
                    ->add($m->boldModifier())
                    ->apply($item->getName()),
                $m->create()
                    ->add($m->datetimeModifier('d.m.Y'))
                    ->add($m->bracketsModifier($this->trans('born_at')))
                    ->add($m->slashesModifier())
                    ->add($m->underlineModifier())
                    ->apply($item->getCategory()),
                $m->create()
                    ->add($m->datetimeModifier('d.m.Y'))
                    ->add($m->bracketsModifier($this->trans('absent_at')))
                    ->apply($item->getAbsentAt()),
                $m->create()
                    ->add($m->slashesModifier())
                    ->add($m->bracketsModifier($this->trans('accusation')))
                    ->apply($item->getAccusation()),
                $m->create()
                    ->add($m->slashesModifier())
                    ->add($m->bracketsModifier($this->trans('precaution')))
                    ->apply($item->getPrecaution()),
            ],
            $full
        );

        return $message;
    }

    private function getPersonCourtsMessage(ClarityPersonCourts $record, bool $full): string
    {
        $m = $this->modifier;
        $message = '‼️ ';
        $message .= $this->implodeResult(
            $this->trans('courts_title'),
            $record->getItems(),
            fn (ClarityPersonCourt $item): array => [
                $m->create()
                    ->add($m->slashesModifier())
                    ->add($m->boldModifier())
                    ->add($m->bracketsModifier($this->trans('case_number')))
                    ->apply($item->getNumber()),
                $m->create()
                    ->add($m->slashesModifier())
                    ->apply($item->getState()),
                $m->create()
                    ->add($m->redWhiteModifier())
                    ->add($m->appendModifier(' '))
                    ->add(
                        $m->appendModifier(
                            $m->create()
                                ->add($m->slashesModifier())
                                ->apply($item->getSide())
                        )
                    )
                    ->apply(!str_contains($item->getSide(), 'заявник')),
                $m->create()
                    ->add($m->slashesModifier())
                    ->add($m->underlineModifier())
                    ->add($m->bracketsModifier($this->trans('desc')))
                    ->apply($item->getDesc()),
                $m->create()
                    ->add($m->slashesModifier())
                    ->apply($item->getPlace()),
            ],
            $full
        );

        return $message;
    }

    private function getPersonEnforcementsMessage(ClarityPersonEnforcements $record, bool $full): string
    {
        $m = $this->modifier;
        $message = '‼️ ';
        $message .= $this->implodeResult(
            $this->trans('enforcements_title'),
            $record->getItems(),
            fn (ClarityPersonEnforcement $item): array => [
                $m->create()
                    ->add($m->slashesModifier())
                    ->add($m->boldModifier())
                    ->add($m->bracketsModifier($this->trans('enf_number')))
                    ->apply($item->getNumber()),
                $m->create()
                    ->add($m->datetimeModifier('d.m.Y'))
                    ->apply($item->getOpenedAt()),
                $m->create()
                    ->add($m->slashesModifier())
                    ->add($m->bracketsModifier($this->trans('debtor')))
                    ->apply($item->getDebtor()),
                $m->create()
                    ->add($m->datetimeModifier('d.m.Y'))
                    ->add($m->bracketsModifier($this->trans('born_at')))
                    ->apply($item->getBornAt()),
                $m->create()
                    ->add($m->redWhiteModifier())
                    ->add($m->appendModifier(' '))
                    ->add(
                        $m->appendModifier(
                            $m->create()
                                ->add($m->slashesModifier())
                                ->apply($item->getState())
                        )
                    )
                    ->apply(str_contains($item->getState(), 'Відкрито')),
                $m->create()
                    ->add($m->slashesModifier())
                    ->add($m->bracketsModifier($this->trans('collector')))
                    ->apply($item->getCollector()),
            ],
            $full
        );

        return $message;
    }

    private function getPersonDebtorsMessage(ClarityPersonDebtors $record, bool $full): string
    {
        $m = $this->modifier;
        $message = '‼️ ';
        $message .= $this->implodeResult(
            $this->trans('debtors_title'),
            $record->getItems(),
            fn (ClarityPersonDebtor $item): array => [
                $m->create()
                    ->add($m->slashesModifier())
                    ->add($m->boldModifier())
                    ->apply($item->getName()),
                $m->create()
                    ->add($m->datetimeModifier('d.m.Y'))
                    ->add($m->bracketsModifier($this->trans('born_at')))
                    ->apply($item->getBornAt()),
                $m->create()
                    ->add($m->slashesModifier())
                    ->add($m->underlineModifier())
                    ->apply($item->getCategory()),
                $m->create()
                    ->add($m->redWhiteModifier($this->trans('actual'), $this->trans('not_actual')))
                    ->add($m->bracketsModifier($this->trans('actual_at')))
                    ->apply($item->getActualAt() > new DateTimeImmutable()),
            ],
            $full
        );

        return $message;
    }

    private function getPersonDeclarationsMessage(ClarityPersonDeclarations $record, bool $full): string
    {
        $m = $this->modifier;
        $message = '💫 ';
        $message .= $this->implodeResult(
            $this->trans('person_declarations_title'),
            $record->getItems(),
            fn (ClarityPersonDeclaration $item): array => [
                $m->create()
                    ->add($m->slashesModifier())
                    ->add($full ? $m->linkModifier($item->getHref()) : $m->nullModifier())
                    ->add($m->boldModifier())
                    ->apply($item->getName()),
                $m->create()
                    ->add($m->slashesModifier())
                    ->add($m->bracketsModifier($this->trans('year')))
                    ->apply($item->getYear()),
                $m->create()
                    ->add($m->slashesModifier())
                    ->add($m->bracketsModifier($this->trans('position')))
                    ->add($full ? $m->nullModifier() : $m->secretsModifier())
                    ->apply($item->getPosition()),
            ],
            $full
        );

        return $message;
    }

    private function getEdrsMessage(ClarityEdrs $record, SearchTermType $searchType, bool $full): string
    {
        $m = $this->modifier;
        $message = '💫 ';
        $message .= $this->implodeResult(
            $this->trans('edrs_title'),
            $record->getItems(),
            fn (ClarityEdr $item): array => [
                $m->create()
                    ->add($m->slashesModifier())
                    ->add($full ? $m->nullModifier() : $m->secretsModifier())
                    ->add($full ? $m->linkModifier($item->getHref()) : $m->nullModifier())
                    ->add($m->boldModifier())
                    ->apply($item->getName()),
                $m->create()
                    ->add($full ? $m->nullModifier() : $m->secretsModifier())
                    ->add($m->slashesModifier())
                    ->apply($item->getType()),
                $m->create()
                    ->add($full ? $m->nullModifier() : $m->secretsModifier())
                    ->add($m->greenWhiteModifier($this->trans('active'), $this->trans('not_active')))
                    ->apply($item->getActive()),
                $m->create()
                    ->add($m->slashesModifier())
                    ->add($full ? $m->nullModifier() : $m->secretsModifier())
                    ->add($full ? $m->nullModifier() : $m->bracketsModifier($this->trans('address')))
                    ->apply($item->getAddress()),
            ],
            $full
        );

        return $message;
    }
}
