<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer\Telegram;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Search\UkrMissedCar\UkrMissedCar;
use App\Service\Search\Viewer\SearchViewer;
use App\Service\Search\Viewer\SearchViewerHelper;
use App\Service\Search\Viewer\SearchViewerInterface;

class UkrMissedCarTelegramSearchViewer extends SearchViewer implements SearchViewerInterface
{
    public function __construct(SearchViewerHelper $searchViewerHelper)
    {
        parent::__construct($searchViewerHelper->withTransDomain('ukr_missed_cars'));
    }

    public function getResultRecord($record, FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        $full = $context['full'] ?? false;

        $h = $this->searchViewerHelper;
        $message = '🚨 ';
        $message .= $h->wrapResultRecord(
            $h->trans('missed_cars_title'),
            $record,
            static fn (UkrMissedCar $item): array => [
                $h->modifier()
                    ->add($h->slashesModifier())
                    ->add($h->boldModifier())
                    ->add($h->transBracketsModifier('car_number'))
                    ->apply($item->getCarNumber()),
                $h->modifier()
                    ->add($h->appendModifier($item->getModel()))
                    ->add($h->slashesModifier())
                    ->apply($item->getColor()),
                $h->modifier()
                    ->add($h->slashesModifier())
                    ->add($h->transBracketsModifier('chassis_number'))
                    ->apply($item->getChassisNumber()),
                $h->modifier()
                    ->add($h->slashesModifier())
                    ->add($h->transBracketsModifier('body_number'))
                    ->apply($item->getBodyNumber()),
                $h->modifier()
                    ->add($h->slashesModifier())
                    ->apply($item->getRegion()),
            ],
            $full
        );

        return $message;
    }
}
