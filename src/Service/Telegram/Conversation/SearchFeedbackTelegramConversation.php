<?php

declare(strict_types=1);

namespace App\Service\Telegram\Conversation;

use App\Entity\Telegram\SearchFeedbackTelegramConversationState;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Telegram\TelegramView;
use App\Exception\Feedback\CreateFeedbackSearchLimitExceeded;
use App\Exception\ValidatorException;
use App\Object\Feedback\FeedbackSearchTransfer;
use App\Object\Feedback\SearchTermTransfer;
use App\Object\Messenger\MessengerUserTransfer;
use App\Service\Feedback\FeedbackSearcher;
use App\Service\Feedback\FeedbackSearchCreator;
use App\Service\Feedback\SearchTerm\SearchTermParserOnlyInterface;
use App\Service\Telegram\Chat\ChooseActionTelegramChatSender;
use App\Service\Telegram\TelegramAwareHelper;
use App\Entity\Telegram\TelegramConversation as Conversation;
use App\Service\Validator;
use Doctrine\ORM\EntityManagerInterface;
use Longman\TelegramBot\Entities\KeyboardButton;

/**
 * @property SearchFeedbackTelegramConversationState $state
 */
class SearchFeedbackTelegramConversation extends TelegramConversation implements TelegramConversationInterface
{
    public const STEP_SEARCH_TERM_ASKED = 10;
    public const STEP_SEARCH_TERM_TYPE_ASKED = 20;
    public const STEP_CONFIRM_ASKED = 30;
    public const STEP_CANCEL_PRESSED = 40;

    public function __construct(
        readonly TelegramAwareHelper $awareHelper,
        private readonly Validator $validator,
        private readonly FeedbackSearchCreator $feedbackSearchCreator,
        private readonly FeedbackSearcher $feedbackSearcher,
        private readonly SearchTermParserOnlyInterface $searchTermParser,
        private readonly EntityManagerInterface $entityManager,
        private readonly ChooseActionTelegramChatSender $chooseActionChatSender,
    )
    {
        parent::__construct($awareHelper, new SearchFeedbackTelegramConversationState());
    }

    public function invoke(TelegramAwareHelper $tg, Conversation $conversation): null
    {
        if ($this->state->getStep() === null) {
            $this->describe($tg);

            return $this->askSearchTerm($tg);
        }

        if ($tg->matchText(null)) {
            return $tg->replyWrong($tg->trans('reply.wrong'))->null();
        }

        if ($tg->matchText($this->getBackButton($tg)->getText())) {
            if ($this->state->getStep() === self::STEP_SEARCH_TERM_TYPE_ASKED) {
                return $this->askSearchTerm($tg);
            }

            if ($this->state->getStep() === self::STEP_CONFIRM_ASKED) {
                if ($this->state->getSearchTerm()->getPossibleTypes() === null) {
                    return $this->askSearchTerm($tg);
                }

                return $this->askSearchTermType($tg);
            }
        }

        if ($tg->matchText($this->getCancelButton($tg)->getText())) {
            $this->state->setStep(self::STEP_CANCEL_PRESSED);

            $tg->stopConversation($conversation)->replyUpset($tg->trans('reply.search.canceled'));

            return $this->chooseActionChatSender->sendActions($tg);
        }

        if ($this->state->getStep() === self::STEP_SEARCH_TERM_ASKED) {
            return $this->onSearchTermAnswer($tg);
        }

        if ($this->state->getStep() === self::STEP_SEARCH_TERM_TYPE_ASKED) {
            return $this->onSearchTermTypeAnswer($tg);
        }

        if ($this->state->getStep() === self::STEP_CONFIRM_ASKED) {
            return $this->onConfirmAnswer($tg, $conversation);
        }

        return null;
    }

    public function describe(TelegramAwareHelper $tg): void
    {
        if (!$tg->getTelegram()->getMessengerUser()->isShowHints()) {
            return;
        }

        $options = $this->feedbackSearchCreator->getOptions();

        $tg->replyView(TelegramView::SEARCH, [
            'limits' => [
                'day' => $options->userPerDayLimit(),
                'month' => $options->userPerMonthLimit(),
                'year' => $options->userPerYearLimit(),
            ],
        ]);
    }

    public function askSearchTerm(TelegramAwareHelper $tg, bool $change = null): null
    {
        if ($change !== null) {
            $this->state->setChange($change);
        }
        $this->state->setStep(self::STEP_SEARCH_TERM_ASKED);

        $buttons = [];

        if ($change) {
            $buttons[] = $this->getLeaveAsButton($this->state->getSearchTerm()->getText(), $tg);
        }

        $buttons[] = $this->getCancelButton($tg);

        $tg->reply(
            ($change ? '' : $this->getStep(1)) . $tg->trans('ask.search.search_term'),
            $tg->keyboard(...$buttons)
        );

        return null;
    }

    public function onSearchTermAnswer(TelegramAwareHelper $tg): null
    {
        if ($this->state->isChange()) {
            if ($tg->matchText($this->getLeaveAsButton($this->state->getSearchTerm()->getText(), $tg)->getText())) {
                return $this->askConfirm($tg);
            }
        }

        if ($tg->matchText($this->state->getSearchTerm()?->getText())) {
            if ($this->state->isChange()) {
                return $this->askConfirm($tg);
            }

            return $this->askConfirm($tg);
        }

        $searchTerm = new SearchTermTransfer($tg->getText());

        try {
            $this->validator->validate($searchTerm, groups: 'text');
        } catch (ValidatorException $exception) {
            $tg->reply($exception->getFirstMessage());

            return $this->askSearchTerm($tg);
        }

        $this->searchTermParser->parseWithGuessType($searchTerm);

        try {
            $this->validator->validate($searchTerm);
        } catch (ValidatorException $exception) {
            $tg->reply($exception->getFirstMessage());

            return $this->askSearchTerm($tg);
        }

        $this->state->setSearchTerm($searchTerm);

        if ($searchTerm->getType() === null) {
            if ($searchTerm->getPossibleTypes() === null) {
                $tg->replyWrong($tg->trans('reply.wrong'));

                return $this->askSearchTerm($tg);
            }

            return $this->askSearchTermType($tg);
        }

        if ($this->state->isChange()) {
            return $this->askConfirm($tg);
        }

        return $this->askConfirm($tg);
    }

    public function askSearchTermType(TelegramAwareHelper $tg): null
    {
        $this->state->setStep(self::STEP_SEARCH_TERM_TYPE_ASKED);

        $possibleTypes = $this->state->getSearchTerm()->getPossibleTypes();
        $sortedPossibleTypes = SearchTermType::sort($possibleTypes);

        $tg->reply(
            $tg->trans('ask.search.search_term_type'),
            $tg->keyboard(...[
                ...$this->getSearchTermTypeButtons($sortedPossibleTypes, $tg),
                $this->getBackButton($tg),
                $this->getCancelButton($tg),
            ])
        );

        return null;
    }

    public function onSearchTermTypeAnswer(TelegramAwareHelper $tg): null
    {
        $type = $this->getSearchTermTypeByButton($tg->getText(), $tg);

        if ($type === null) {
            $tg->replyWrong($tg->trans('reply.wrong'));

            return $this->askSearchTermType($tg);
        }

        $searchTerm = $this->state->getSearchTerm();
        $searchTerm->setType($type);

        $this->searchTermParser->parseWithKnownType($searchTerm);
        try {
            $this->validator->validate($searchTerm);
        } catch (ValidatorException $exception) {
            $tg->reply($exception->getFirstMessage());

            return $this->askSearchTerm($tg);
        }

        return $this->askConfirm($tg);
    }

    public function askConfirm(TelegramAwareHelper $tg): null
    {
        $this->state->setChange(false);
        $this->state->setStep(self::STEP_CONFIRM_ASKED);

        $this->searchTermParser->parseWithNetwork($this->state->getSearchTerm());

        $tg->reply($tg->trans('ask.search.confirm'))
            ->replyView(
                TelegramView::FEEDBACK,
                [
                    'search_term' => $this->state->getSearchTerm(),
                ],
                $tg->keyboard(
                    $this->getConfirmButton($tg),
                    $this->getChangeSearchTermButton($tg),
                    $this->getBackButton($tg),
                    $this->getCancelButton($tg)
                )
            )
        ;

        return null;
    }

    public function onConfirmAnswer(TelegramAwareHelper $tg, Conversation $conversation): null
    {
        switch ($tg->getText()) {
            case $this->getChangeSearchTermButton($tg)->getText():
                return $this->askSearchTerm($tg, true);
            case $this->getConfirmButton($tg)->getText():
                // nothing, continue
                break;
            default:
                $tg->replyWrong($tg->trans('reply.wrong'));

                return $this->askConfirm($tg);
        }

        try {
            $this->validator->validate($this->state->getSearchTerm());
            $this->validator->validate($this->state);

            $feedbackSearch = $this->feedbackSearchCreator->createFeedbackSearch(
                new FeedbackSearchTransfer(
                    $conversation->getMessengerUser(),
                    $this->state->getSearchTerm()
                )
            );
            $this->entityManager->flush();

            $feedbacks = $this->feedbackSearcher->searchFeedbacks($feedbackSearch);
            $count = count($feedbacks);

            if ($count === 0) {
                $tg->stopConversation($conversation)->replyUpset($tg->trans('reply.search.empty_list', [
                    'search_term' => $feedbackSearch->getSearchTermText(),
                ]));

                return $this->chooseActionChatSender->sendActions($tg);
            }

            $tg->reply(
                $tg->trans('reply.search.title', [
                    'search_term' => $feedbackSearch->getSearchTermNormalizedText() ?? $feedbackSearch->getSearchTermText(),
                    'search_term_type' => $this->getSearchTermTypeButton($feedbackSearch->getSearchTermType(), $tg)->getText(),
                    'count' => $count,
                ]),
                disableWebPagePreview: true
            );

            foreach ($feedbacks as $index => $feedback) {
                $tg->replyView(
                    TelegramView::FEEDBACK,
                    [
                        'number' => $index + 1,
                        'search_term' => (new SearchTermTransfer($feedback->getSearchTermText()))
                            ->setType($feedback->getSearchTermType())
                            ->setMessenger($feedback->getSearchTermMessenger())
                            // todo:
                            ->setMessengerProfileUrl(null)
                            ->setMessengerUsername($feedback->getSearchTermMessengerUsername())
                            ->setMessengerUser(
                                $feedback->getSearchTermMessengerUser() === null ? null : new MessengerUserTransfer(
                                    $feedback->getSearchTermMessengerUser()->getMessenger(),
                                    $feedback->getSearchTermMessengerUser()->getIdentifier(),
                                    $feedback->getSearchTermMessengerUser()->getUsername(),
                                    $feedback->getSearchTermMessengerUser()->getName(),
                                    $feedback->getSearchTermMessengerUser()->getLanguageCode()
                                )
                            ),
                        'rating' => $feedback->getRating(),
                        'description' => $feedback->getDescription(),
                    ],
                    protectContent: true,
                    disableWebPagePreview: true
                );
            }

            $tg->stopConversation($conversation);

            return $this->chooseActionChatSender->sendActions($tg);
        } catch (ValidatorException $exception) {
            if ($exception->isFirstProperty('search_term')) {
                $tg->reply($exception->getFirstMessage());

                return $this->askSearchTerm($tg);
            }

            return $tg->replyFail($tg->trans('reply.fail'))->null();
        } catch (CreateFeedbackSearchLimitExceeded $exception) {
            $tg->replyFail($tg->trans('reply.search.fail.limit_exceeded', [
                'period' => $tg->trans($exception->getPeriodKey()),
                'limit' => $exception->getLimit(),
                'premium_command' => $tg->view(TelegramView::COMMAND, [
                    'name' => 'premium',
                ]),
            ]), parseMode: 'HTML');

            $tg->stopConversation($conversation);

            return $this->chooseActionChatSender->sendActions($tg);
        }
    }

    /**
     * @param SearchTermType[] $types
     * @param TelegramAwareHelper $tg
     * @return KeyboardButton[]
     */
    public static function getSearchTermTypeButtons(array $types, TelegramAwareHelper $tg): array
    {
        return array_map(fn (SearchTermType $type) => static::getSearchTermTypeButton($type, $tg), $types);
    }

    public static function getSearchTermTypeButton(SearchTermType $type, TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans(sprintf('search_term_type.%s', $type->name)));
    }

    public static function getSearchTermTypeByButton(string $button, TelegramAwareHelper $tg): ?SearchTermType
    {
        foreach (SearchTermType::cases() as $type) {
            if (static::getSearchTermTypeButton($type, $tg)->getText() === $button) {
                return $type;
            }
        }

        return null;
    }

    public static function getLeaveAsButton(string $text, TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.leave_as', ['text' => $text]));
    }

    public static function getChangeSearchTermButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.search.change_search_term'));
    }

    public static function getConfirmButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.confirm'));
    }

    public static function getBackButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.back'));
    }

    public static function getCancelButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.cancel'));
    }

    private function getStep(int|string $num): string
    {
        return "[{$num}/1] ";
    }
}