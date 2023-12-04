<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer\Telegram;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Search\Blackbox\BlackboxFeedback;
use App\Entity\Search\Blackbox\BlackboxFeedbacks;
use App\Service\Search\Viewer\SearchViewer;
use App\Service\Search\Viewer\SearchViewerCompose;
use App\Service\Search\Viewer\SearchViewerInterface;
use App\Service\Modifier;

class BlackboxTelegramSearchViewer extends SearchViewer implements SearchViewerInterface
{
    public function __construct(SearchViewerCompose $searchViewerCompose, Modifier $modifier)
    {
        parent::__construct($searchViewerCompose->withTransDomain('blackbox'), $modifier);
    }

    public function getResultMessage($record, FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        if (is_string($record)) {
            return $record;
        }

        $full = $context['full'] ?? false;

        $m = $this->modifier;

        $message = '‼️ ';
        $message .= $this->implodeResult(
            $this->trans('feedbacks_title'),
            $record instanceof BlackboxFeedbacks ? $record->getItems() : [$record],
            fn (BlackboxFeedback $item): array => [
                $m->create()
                    ->add($m->slashesModifier())
                    ->add($full ? $m->nullModifier() : $m->secretsModifier())
                    ->add($full ? $m->linkModifier($item->getHref()) : $m->nullModifier())
                    ->add($m->boldModifier())
                    ->apply($item->getName()),
                $m->create()
                    ->add($m->redModifier())
                    ->add($m->appendModifier($item->getPhoneFormatted()))
                    ->add($m->slashesModifier())
                    ->add($full ? $m->nullModifier() : $m->secretsModifier())
                    ->add($m->bracketsModifier($this->trans('phone')))
                    ->apply(true),
                $m->create()
                    ->add($m->slashesModifier())
                    ->add($m->italicModifier())
                    ->apply($item->getComment()),
                $m->create()
                    ->add($m->filterModifier())
                    ->add($m->implodeModifier(', '))
                    ->add($m->bracketsModifier($item->getType()))
                    ->add($m->slashesModifier())
                    ->apply([$item->getCity(), $item->getWarehouse()]),
                $m->create()
                    ->add($m->datetimeModifier('d.m.Y'))
                    ->add($m->bracketsModifier($this->trans('date')))
                    ->apply($item->getDate()),
            ],
            $full
        );

        return $message;
    }
}
