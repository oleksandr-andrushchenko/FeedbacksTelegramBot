<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer\Telegram;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Search\BusinessGuide\BusinessGuideEnterprise;
use App\Entity\Search\BusinessGuide\BusinessGuideEnterprises;
use App\Enum\Feedback\SearchTermType;
use App\Service\Search\Viewer\SearchViewer;
use App\Service\Search\Viewer\SearchViewerCompose;
use App\Service\Search\Viewer\SearchViewerInterface;
use App\Service\Modifier;

class BusinessGuideTelegramSearchViewer extends SearchViewer implements SearchViewerInterface
{
    public function __construct(SearchViewerCompose $searchViewerCompose, Modifier $modifier)
    {
        parent::__construct($searchViewerCompose->withTransDomain('business_guide'), $modifier);
    }

    public function getResultMessage($record, FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        if (is_string($record)) {
            return $record;
        }

        $full = $context['full'] ?? false;

        return match (get_class($record)) {
            BusinessGuideEnterprises::class => $this->getEnterprisesMessage($record, $searchTerm, $full),
            BusinessGuideEnterprise::class => $this->getEnterpriseMessage($record, $searchTerm, $full),
        };
    }

    public function getEnterpriseWrapMessageCallback(FeedbackSearchTerm $searchTerm, bool $full): callable
    {
        $m = $this->modifier;

        $term = $searchTerm->getNormalizedText();
        $personSearch = $searchTerm->getType() === SearchTermType::person_name;
        $phoneSearch = $searchTerm->getType() === SearchTermType::phone_number;
        $orgSearch = $searchTerm->getType() === SearchTermType::organization_name;
        $placeSearch = $searchTerm->getType() === SearchTermType::place_name;

        return fn (BusinessGuideEnterprise $item): array => [
            $m->create()
                ->add($m->emptyNullModifier())
                ->add($full ? $m->nullModifier() : $m->wordSecretsModifier(excepts: $personSearch || $orgSearch || $placeSearch ? $term : null))
                ->add($m->slashesModifier())
                ->add($full ? $m->linkModifier($item->getHref()) : $m->nullModifier())
                ->add($m->boldModifier())
                ->add($m->bracketsModifier($this->trans('name')))
                ->apply($item->getName()),
            $m->create()
                ->add($m->filterModifier())
                ->add($m->implodeModifier(' '))
                ->add($m->emptyNullModifier())
                ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                ->add($m->slashesModifier())
                ->add($m->bracketsModifier($this->trans('address')))
                ->apply([$item->getCountry(), $item->getAddress()]),
            $m->create()
                ->add($m->emptyNullModifier())
                ->add($full ? $m->nullModifier() : $m->wordSecretsModifier(excepts: $phoneSearch ? $term : null))
                ->add($m->slashesModifier())
                ->add($m->bracketsModifier($this->trans('phone')))
                ->apply($item->getPhone()),
            $m->create()
                ->add($m->emptyNullModifier())
                ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                ->add($m->slashesModifier())
                ->add($m->bracketsModifier($this->trans('ceo')))
                ->apply($item->getCeo()),
            $m->create()
                ->add($m->emptyNullModifier())
                ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                ->add($m->slashesModifier())
                ->add($m->bracketsModifier($this->trans('number')))
                ->apply($item->getNumber()),
            $m->create()
                ->add($m->emptyNullModifier())
                ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                ->add($m->slashesModifier())
                ->add($m->bracketsModifier($this->trans('desc')))
                ->apply($item->getDesc()),
            $m->create()
                ->add($m->filterModifier())
                ->add($m->implodeModifier('; '))
                ->add($m->emptyNullModifier())
                ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                ->add($m->slashesModifier())
                ->add($m->bracketsModifier($this->trans('sectors')))
                ->apply($item->getSectors()),
        ];
    }

    private function getEnterprisesMessage(BusinessGuideEnterprises $record, FeedbackSearchTerm $searchTerm, bool $full): string
    {
        $message = '💫 ';
        $message .= $this->implodeResult(
            $this->trans('enterprises_title'),
            $record->getItems(),
            $this->getEnterpriseWrapMessageCallback($searchTerm, $full),
            $full
        );

        return $message;
    }

    private function getEnterpriseMessage(BusinessGuideEnterprise $record, FeedbackSearchTerm $searchTerm, bool $full): string
    {
        $message = '💫 ';
        $message .= $this->implodeResult(
            $this->trans('enterprise_title'),
            [$record],
            $this->getEnterpriseWrapMessageCallback($searchTerm, $full),
            $full
        );

        return $message;
    }
}
