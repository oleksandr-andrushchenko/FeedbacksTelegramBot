<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer\Telegram;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Search\TwentySecondFloor\TwentySecondFloorBlogger;
use App\Entity\Search\TwentySecondFloor\TwentySecondFloorBloggers;
use App\Entity\Search\TwentySecondFloor\TwentySecondFloorFeedback;
use App\Entity\Search\TwentySecondFloor\TwentySecondFloorFeedbacks;
use App\Service\Intl\TimeProvider;
use App\Service\Search\Viewer\SearchViewer;
use App\Service\Search\Viewer\SearchViewerCompose;
use App\Service\Search\Viewer\SearchViewerInterface;
use App\Service\Modifier;

class TwentySecondFloorTelegramSearchViewer extends SearchViewer implements SearchViewerInterface
{
    public function __construct(
        SearchViewerCompose $searchViewerCompose,
        Modifier $modifier,
    )
    {
        parent::__construct($searchViewerCompose->withTransDomain('22nd_floor'), $modifier);
    }

    public function getResultMessage($record, FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        if (is_string($record)) {
            return $record;
        }

        $full = $context['full'] ?? false;

        return match (get_class($record)) {
            TwentySecondFloorBloggers::class => $this->getBloggersMessage($record, $full),
            TwentySecondFloorFeedbacks::class => $this->getFeedbacksMessage($record, $full),
        };
    }

    private function getBloggersMessage(TwentySecondFloorBloggers $record, bool $full): string
    {
        $m = $this->modifier;
        $message = '💫 ';
        $message .= $this->implodeResult(
            $this->trans('bloggers_title'),
            $record->getItems(),
            fn (TwentySecondFloorBlogger $item): array => [
                $m->create()
                    ->add($m->slashesModifier())
                    ->add($full ? $m->linkModifier($item->getHref()) : $m->nullModifier())
                    ->add($m->boldModifier())
                    ->apply($item->getName()),
                $m->create()
                    ->add($m->numberFormatModifier(thousandsSeparator: ' '))
                    ->add($m->bracketsModifier($this->trans('follower_count')))
                    ->apply($item->getFollowers()),
                $m->create()
                    ->add($m->slashesModifier())
                    ->apply($item->getDesc()),
            ],
            $full
        );

        return $message;
    }

    private function getFeedbacksMessage(TwentySecondFloorFeedbacks $record, bool $full): string
    {
        $m = $this->modifier;
        $message = '💫 ';
        $message .= $this->implodeResult(
            $this->trans('feedbacks_title'),
            $record->getItems(),
            fn (TwentySecondFloorFeedback $item): array => [
                $m->create()
                    ->add($m->slashesModifier())
                    ->add($m->boldModifier())
                    ->apply($item->getHeader()),
                $m->create()
                    ->add($m->slashesModifier())
                    ->add($m->boldModifier())
                    ->add($m->spoilerModifier())
                    ->apply($item->getText()),
                $m->create()
                    ->add($m->markModifier())
                    ->add($m->appendModifier($this->trans('mark_' . ($item->getMark() > 0 ? '+1' : $item->getMark()))))
                    ->add($m->bracketsModifier($this->trans('mark')))
                    ->apply($item->getMark()),
                $m->create()
                    ->add($m->slashesModifier())
                    ->add($m->bracketsModifier($this->trans('author')))
                    ->apply($item->getAuthor()),
                $m->create()
                    ->add($m->slashesModifier())
                    ->add($m->countryModifier())
                    ->add($m->bracketsModifier($this->trans('country')))
                    ->apply('ua'),
                $m->create()
                    ->add($m->datetimeModifier(TimeProvider::MONTH_YEAR))
                    ->add($m->bracketsModifier($this->trans('date')))
                    ->apply($item->getDate()),
            ],
            $full
        );

        return $message;
    }
}
