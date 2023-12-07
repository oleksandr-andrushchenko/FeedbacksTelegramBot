<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer\Telegram;

use App\Entity\Feedback\Feedback;
use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Telegram\TelegramBot;
use App\Entity\Telegram\TelegramChannel;
use App\Service\Feedback\Telegram\Bot\View\FeedbackTelegramReplySignViewProvider;
use App\Service\Feedback\Telegram\View\MultipleSearchTermTelegramViewProvider;
use App\Service\Intl\TimeProvider;
use App\Service\Search\Viewer\SearchViewer;
use App\Service\Search\Viewer\SearchViewerCompose;
use App\Service\Search\Viewer\SearchViewerInterface;
use App\Service\Modifier;

class FeedbackTelegramSearchViewer extends SearchViewer implements SearchViewerInterface
{
    public function __construct(
        SearchViewerCompose $searchViewerCompose,
        Modifier $modifier,
        private readonly MultipleSearchTermTelegramViewProvider $multipleSearchTermTelegramViewProvider,
        private readonly FeedbackTelegramReplySignViewProvider $feedbackTelegramReplySignViewProvider,
    )
    {
        parent::__construct($searchViewerCompose->withTransDomain('feedback'), $modifier);
    }

    public function getResultMessage($record, FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        $message = '💫 ';

        $full = $context['full'] ?? false;
        $locale = $context['locale'] ?? null;
        $addCountry = $context['addCountry'] ?? false;
        $addTime = $context['addTime'] ?? false;

        $message .= $this->implodeResult(
            $this->trans('feedbacks_title'),
            $record,
            $this->getFeedbackWrapMessageCallback(full: $full, addCountry: $addCountry, addTime: $addTime, locale: $locale),
            $full
        );

        return $message;
    }

    public function getFeedbackTelegramView(
        TelegramBot $bot,
        Feedback $feedback,
        bool $addSecrets = false,
        bool $addSign = false,
        bool $addCountry = false,
        bool $addTime = false,
        bool $addQuotes = false,
        TelegramChannel $channel = null,
        string $locale = null,
    ): string
    {
        $m = $this->modifier;

        return $m->create()
            ->add($addQuotes ? $m->italicModifier() : $m->nullModifier())
            ->add($addSign ? $m->appendModifier("\n\n" . $this->feedbackTelegramReplySignViewProvider->getFeedbackTelegramReplySignView($bot, channel: $channel, localeCode: $locale)) : $m->nullModifier())
            ->apply(
                $this->makeResultMessage(
                    call_user_func(
                        $this->getFeedbackWrapMessageCallback(
                            full: !$addSecrets,
                            addCountry: $addCountry,
                            addTime: $addTime,
                            locale: $locale
                        ),
                        $feedback
                    )
                )
            )
        ;
    }

    private function getFeedbackWrapMessageCallback(
        bool $full = false,
        bool $addCountry = false,
        bool $addTime = false,
        string $locale = null
    ): callable
    {
        $m = $this->modifier;

        return fn (Feedback $item): array => [
            $m->create()
                ->apply(
                    $this->multipleSearchTermTelegramViewProvider->getFeedbackSearchTermsTelegramView(
                        $item->getSearchTerms()->toArray(),
                        addSecrets: !$full,
                        locale: $locale
                    )
                ),
            $m->create()
                ->add($m->markModifier())
                ->add($m->appendModifier($this->trans('mark_' . ($item->getRating()->value > 0 ? '+1' : $item->getRating()->value))))
                ->add($m->bracketsModifier($this->trans('mark')))
                ->apply($item->getRating()->value),
            $m->create()
                ->add($m->slashesModifier())
                ->add($m->spoilerModifier())
                ->add($m->bracketsModifier($this->trans('description')))
                ->apply($item->getDescription()),
            $m->create()
                ->add($m->conditionalModifier($addCountry))
                ->add($m->slashesModifier())
                ->add($m->countryModifier(locale: $locale))
                ->add($m->bracketsModifier($this->trans('country')))
                ->apply($item->getCountryCode()),
            $m->create()
                ->add($m->conditionalModifier($addTime))
                ->add($m->datetimeModifier(TimeProvider::DATE, timezone: $item->getUser()->getTimezone(), locale: $locale))
                ->add($m->bracketsModifier($this->trans('created_at')))
                ->apply($item->getCreatedAt()),
        ];
    }
}
