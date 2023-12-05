<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer\Telegram;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Search\BusinessGuide\BusinessGuideEnterprise;
use App\Entity\Search\BusinessGuide\BusinessGuideEnterprises;
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
            BusinessGuideEnterprises::class => $this->getEnterprisesMessage($record, $full),
            BusinessGuideEnterprise::class => $this->getEnterpriseMessage($record, $full),
        };
    }

    public function getEnterpriseWrapMessageCallback(bool $full): callable
    {
        $m = $this->modifier;

        return fn (BusinessGuideEnterprise $item): array => [
            $m->create()
                ->add($m->slashesModifier())
                ->add($full ? $m->nullModifier() : $m->secretsModifier())
                ->add($full ? $m->linkModifier($item->getHref()) : $m->nullModifier())
                ->add($m->boldModifier())
                ->apply($item->getName()),
            $m->create()
                ->add($m->slashesModifier())
                ->add($m->appendModifier($item->getAddress()))
                ->add($full ? $m->nullModifier() : $m->secretsModifier())
                ->apply($item->getCountry()),
            $m->create()
                ->add($m->slashesModifier())
                ->add($full ? $m->nullModifier() : $m->secretsModifier())
                ->apply($item->getPhone()),
            $m->create()
                ->add($m->conditionalModifier($full))
                ->add($m->slashesModifier())
                ->add($m->bracketsModifier($this->trans('ceo')))
                ->apply($item->getCeo()),
            $m->create()
                ->add($m->conditionalModifier($full))
                ->add($m->slashesModifier())
                ->add($m->bracketsModifier($this->trans('number')))
                ->apply($item->getNumber()),
            $m->create()
                ->add($m->conditionalModifier($full))
                ->add($m->slashesModifier())
                ->add($m->bracketsModifier($this->trans('desc')))
                ->apply($item->getDesc()),
            $m->create()
                ->add($m->conditionalModifier($full))
                ->add($m->implodeModifier('; '))
                ->add($m->slashesModifier())
                ->add($m->bracketsModifier($this->trans('sectors')))
                ->apply($item->getSectors()),
        ];
    }

    private function getEnterprisesMessage(BusinessGuideEnterprises $record, bool $full): string
    {
        $message = '💫 ';
        $message .= $this->implodeResult(
            $this->trans('enterprises_title'),
            $record->getItems(),
            $this->getEnterpriseWrapMessageCallback($full),
            $full
        );

        return $message;
    }

    private function getEnterpriseMessage(BusinessGuideEnterprise $record, bool $full): string
    {
        $message = '💫 ';
        $message .= $this->implodeResult(
            $this->trans('enterprise_title'),
            [$record],
            $this->getEnterpriseWrapMessageCallback($full),
            $full
        );

        return $message;
    }
}
