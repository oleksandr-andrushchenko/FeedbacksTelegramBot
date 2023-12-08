<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer\Telegram;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Search\UkrMissedCar\UkrMissedCar;
use App\Service\Search\Viewer\SearchViewer;
use App\Service\Search\Viewer\SearchViewerCompose;
use App\Service\Search\Viewer\SearchViewerInterface;
use App\Service\Modifier;

class UkrMissedCarTelegramSearchViewer extends SearchViewer implements SearchViewerInterface
{
    public function __construct(SearchViewerCompose $searchViewerCompose, Modifier $modifier)
    {
        parent::__construct($searchViewerCompose->withTransDomain('ukr_missed_cars'), $modifier);
    }

    public function getResultMessage($record, FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        $full = $context['full'] ?? false;

        $m = $this->modifier;
        $message = '🚨 ';
        $message .= $this->implodeResult(
            $this->trans('missed_cars_title'),
            $record,
            fn (UkrMissedCar $item): array => [
                $m->create()
                    ->add($m->slashesModifier())
                    ->add($m->boldModifier())
                    ->add($m->bracketsModifier($this->trans('car_number')))
                    ->apply($item->getCarNumber()),
                $m->create()
                    ->add($m->appendModifier(' '))
                    ->add($m->appendModifier($item->getModel()))
                    ->add($m->slashesModifier())
                    ->apply($item->getColor()),
                $m->create()
                    ->add($m->slashesModifier())
                    ->add($m->bracketsModifier($this->trans('chassis_number')))
                    ->apply($item->getChassisNumber()),
                $m->create()
                    ->add($m->slashesModifier())
                    ->add($m->bracketsModifier($this->trans('body_number')))
                    ->apply($item->getBodyNumber()),
                $m->create()
                    ->add($m->slashesModifier())
                    ->apply($item->getRegion()),
            ],
            $full
        );

        return $message;
    }
}
