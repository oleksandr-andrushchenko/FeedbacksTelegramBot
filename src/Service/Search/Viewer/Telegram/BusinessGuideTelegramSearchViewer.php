<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer\Telegram;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Search\BusinessGuide\BusinessGuideEnterprise;
use App\Entity\Search\BusinessGuide\BusinessGuideEnterprises;
use App\Service\Search\Viewer\SearchViewer;
use App\Service\Search\Viewer\SearchViewerHelper;
use App\Service\Search\Viewer\SearchViewerInterface;

class BusinessGuideTelegramSearchViewer extends SearchViewer implements SearchViewerInterface
{
    public function __construct(SearchViewerHelper $searchViewerHelper)
    {
        parent::__construct($searchViewerHelper->withTransDomain('business_guide'));
    }

    public function getResultRecord($record, FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        if (is_string($record)) {
            return $record;
        }

        $full = $context['full'] ?? false;

        return match (get_class($record)) {
            BusinessGuideEnterprises::class => $this->getEnterprisesResultRecord($record, $full),
            BusinessGuideEnterprise::class => $this->getEnterpriseResultRecord($record, $full),
        };
    }

    public function getEnterpriseWrapResultRecordCallback(bool $full): callable
    {
        $h = $this->searchViewerHelper;

        return static fn (BusinessGuideEnterprise $item): array => [
            $h->modifier()
                ->add($h->slashesModifier())
                ->add($full ? $h->nullModifier() : $h->secretsModifier())
                ->add($full ? $h->linkModifier($item->getHref()) : $h->nullModifier())
                ->add($h->boldModifier())
                ->apply($item->getName()),
            $h->modifier()
                ->add($h->slashesModifier())
                ->add($h->appendModifier($item->getAddress()))
                ->add($full ? $h->nullModifier() : $h->secretsModifier())
                ->apply($item->getCountry()),
            $h->modifier()
                ->add($h->slashesModifier())
                ->add($full ? $h->nullModifier() : $h->secretsModifier())
                ->apply($item->getPhone()),
            $h->modifier()
                ->add($h->conditionalModifier($full))
                ->add($h->slashesModifier())
                ->add($h->transBracketsModifier('ceo'))
                ->apply($item->getCeo()),
            $h->modifier()
                ->add($h->conditionalModifier($full))
                ->add($h->slashesModifier())
                ->add($h->transBracketsModifier('number'))
                ->apply($item->getNumber()),
            $h->modifier()
                ->add($h->conditionalModifier($full))
                ->add($h->slashesModifier())
                ->add($h->transBracketsModifier('desc'))
                ->apply($item->getDesc()),
            $h->modifier()
                ->add($h->conditionalModifier($full))
                ->add($h->implodeModifier('; '))
                ->add($h->slashesModifier())
                ->add($h->transBracketsModifier('sectors'))
                ->apply($item->getSectors()),
        ];
    }

    private function getEnterprisesResultRecord(BusinessGuideEnterprises $record, bool $full): string
    {
        $message = '💫 ';
        $message .= $this->searchViewerHelper->wrapResultRecord(
            $this->searchViewerHelper->trans('enterprises_title'),
            $record->getItems(),
            $this->getEnterpriseWrapResultRecordCallback($full),
            $full
        );

        return $message;
    }

    private function getEnterpriseResultRecord(BusinessGuideEnterprise $record, bool $full): string
    {
        $message = '🤔 ';
        $message .= $this->searchViewerHelper->wrapResultRecord(
            $this->searchViewerHelper->trans('enterprise_title'),
            [$record],
            $this->getEnterpriseWrapResultRecordCallback($full),
            $full
        );

        return $message;
    }
}
