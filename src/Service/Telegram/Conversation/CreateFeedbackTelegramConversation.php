<?php

declare(strict_types=1);

namespace App\Service\Telegram\Conversation;

use App\Entity\Telegram\CreateFeedbackTelegramConversationState;
use App\Enum\Feedback\Rating;
use App\Enum\Feedback\SearchTermType;
use App\Exception\Feedback\CreateFeedbackLimitExceeded;
use App\Exception\Messenger\SameMessengerUserException;
use App\Exception\ValidatorException;
use App\Object\Feedback\FeedbackTransfer;
use App\Object\Feedback\SearchTermTransfer;
use App\Service\Feedback\FeedbackCreator;
use App\Service\Feedback\SearchTerm\SearchTermParserOnlyInterface;
use App\Service\Feedback\View\SearchTermTelegramViewProvider;
use App\Service\Telegram\Chat\ChooseActionTelegramChatSender;
use App\Service\Telegram\TelegramAwareHelper;
use App\Service\Util\Array\ArrayValueEraser;
use App\Service\Validator;
use Longman\TelegramBot\Entities\KeyboardButton;
use App\Entity\Telegram\TelegramConversation as Conversation;

/**
 * @property CreateFeedbackTelegramConversationState $state
 */
class CreateFeedbackTelegramConversation extends TelegramConversation implements TelegramConversationInterface
{
    public const STEP_SEARCH_TERM_QUERIED = 10;
    public const STEP_SEARCH_TERM_TYPE_QUERIED = 20;
    public const STEP_RATING_QUERIED = 30;
    public const STEP_DESCRIPTION_QUERIED = 40;
    public const STEP_CONFIRM_QUERIED = 50;
    public const STEP_CANCEL_PRESSED = 60;

    public function __construct(
        readonly TelegramAwareHelper $awareHelper,
        private readonly Validator $validator,
        private readonly FeedbackCreator $creator,
        private readonly SearchTermParserOnlyInterface $searchTermParser,
        private readonly ArrayValueEraser $arrayValueEraser,
        private readonly ChooseActionTelegramChatSender $chooseActionChatSender,
        private readonly SearchTermTelegramViewProvider $searchTermViewProvider,
    )
    {
        parent::__construct($awareHelper, new CreateFeedbackTelegramConversationState());
    }

    public function invoke(TelegramAwareHelper $tg, Conversation $conversation): null
    {
        if ($this->state->getStep() === null) {
            $this->describe($tg);

            return $this->querySearchTerm($tg);
        }

        if ($tg->matchText(null)) {
            return $tg->replyWrong($tg->trans('reply.wrong'))->null();
        }

        if ($tg->matchText($this->getBackButton($tg)->getText())) {
            if ($this->state->getStep() === self::STEP_SEARCH_TERM_TYPE_QUERIED) {
                return $this->querySearchTerm($tg);
            }

            if ($this->state->getStep() === self::STEP_RATING_QUERIED) {
                if ($this->state->getSearchTerm()->getPossibleTypes() === null) {
                    return $this->querySearchTerm($tg);
                }

                return $this->querySearchTermType($tg);
            }

            if ($this->state->getStep() === self::STEP_DESCRIPTION_QUERIED) {
                return $this->queryRating($tg);
            }

            if ($this->state->getStep() === self::STEP_CONFIRM_QUERIED) {
                return $this->queryDescription($tg);
            }
        }

        if ($tg->matchText($this->getCancelButton($tg)->getText())) {
            $this->state->setStep(self::STEP_CANCEL_PRESSED);

            $tg->stopConversation($conversation)->replyUpset($tg->trans('reply.canceled', domain: 'tg.create'));

            return $this->chooseActionChatSender->sendActions($tg);
        }

        if ($this->state->getStep() === self::STEP_SEARCH_TERM_QUERIED) {
            return $this->gotSearchTerm($tg);
        }

        if ($this->state->getStep() === self::STEP_SEARCH_TERM_TYPE_QUERIED) {
            return $this->gotSearchTermType($tg);
        }

        if ($this->state->getStep() === self::STEP_RATING_QUERIED) {
            return $this->gotRating($tg);
        }

        if ($this->state->getStep() === self::STEP_DESCRIPTION_QUERIED) {
            return $this->gotDescription($tg);
        }

        if ($this->state->getStep() === self::STEP_CONFIRM_QUERIED) {
            return $this->gotConfirm($tg, $conversation);
        }

        return null;
    }

    public function describe(TelegramAwareHelper $tg): void
    {
        if (!$tg->getTelegram()->getMessengerUser()->showHints()) {
            return;
        }

        $options = $this->creator->getOptions();

        $tg->reply($tg->view('describe_create', [
            'accept_payments' => $tg->getTelegram()->getBot()->acceptPayments(),
            'limits' => [
                'day' => $options->userPerDayLimit(),
                'month' => $options->userPerMonthLimit(),
                'year' => $options->userPerYearLimit(),
            ],
        ]));
    }

    public function querySearchTerm(TelegramAwareHelper $tg, bool $change = null): null
    {
        if ($change !== null) {
            $this->state->setChange($change);
        }
        $this->state->setStep(self::STEP_SEARCH_TERM_QUERIED);

        $buttons = [];

        if ($change) {
            $buttons[] = $this->getLeaveAsButton(strip_tags($this->searchTermViewProvider->getSearchTermTelegramView($this->state->getSearchTerm())), $tg);
        }

        $buttons[] = $this->getCancelButton($tg);

        $tg->reply(
            ($change ? '' : $this->getStep(1)) . $tg->trans('query.search_term', domain: 'tg.create'),
            $tg->keyboard(...$buttons)
        );

        return null;
    }

    public function gotSearchTerm(TelegramAwareHelper $tg): null
    {
        if ($this->state->isChange()) {
            if ($tg->matchText($this->getLeaveAsButton(strip_tags($this->searchTermViewProvider->getSearchTermTelegramView($this->state->getSearchTerm())), $tg)->getText())) {
                return $this->queryConfirm($tg);
            }
        }

        if ($tg->matchText($this->state->getSearchTerm()?->getText())) {
            if ($this->state->isChange()) {
                return $this->queryConfirm($tg);
            }

            return $this->queryRating($tg);
        }

        $searchTerm = new SearchTermTransfer($tg->getText());

        try {
            $this->validator->validate($searchTerm, groups: 'text');
        } catch (ValidatorException $exception) {
            $tg->reply($exception->getFirstMessage());

            return $this->querySearchTerm($tg);
        }

        $this->searchTermParser->parseWithGuessType($searchTerm);

        try {
            $this->validator->validate($searchTerm);
        } catch (ValidatorException $exception) {
            $tg->reply($exception->getFirstMessage());

            return $this->querySearchTerm($tg);
        }

        $this->state->setSearchTerm($searchTerm);

        if ($searchTerm->getType() === null) {
            if ($searchTerm->getPossibleTypes() === null) {
                $tg->replyWrong($tg->trans('reply.wrong'));

                return $this->querySearchTerm($tg);
            }

            return $this->querySearchTermType($tg);
        }

        if ($this->state->isChange()) {
            return $this->queryConfirm($tg);
        }

        return $this->queryRating($tg);
    }

    public function querySearchTermType(TelegramAwareHelper $tg): null
    {
        $this->state->setStep(self::STEP_SEARCH_TERM_TYPE_QUERIED);

        $possibleTypes = $this->state->getSearchTerm()->getPossibleTypes();
        $sortedPossibleTypes = SearchTermType::sort($possibleTypes);

        if (in_array(SearchTermType::unknown, $sortedPossibleTypes, true)) {
            $sortedPossibleTypes = $this->arrayValueEraser->eraseValue($sortedPossibleTypes, SearchTermType::unknown);
            $sortedPossibleTypes[] = SearchTermType::unknown;
        }

        $tg->reply(
            $tg->trans('query.search_term_type', ['search_term' => sprintf('<u>%s</u>', $this->state->getSearchTerm()->getText())], domain: 'tg.create'),
            $tg->keyboard(...[
                ...$this->getSearchTermTypeButtons($sortedPossibleTypes, $tg),
                $this->getBackButton($tg),
                $this->getCancelButton($tg),
            ])
        );

        return null;
    }

    public function gotSearchTermType(TelegramAwareHelper $tg): null
    {
        $type = $this->getSearchTermTypeByButton($tg->getText(), $tg);

        if ($type === null) {
            $tg->replyWrong($tg->trans('reply.wrong'));

            return $this->querySearchTermType($tg);
        }

        $searchTerm = $this->state->getSearchTerm();
        $searchTerm->setType($type);

        $this->searchTermParser->parseWithKnownType($searchTerm);
        try {
            $this->validator->validate($searchTerm);
        } catch (ValidatorException $exception) {
            $tg->reply($exception->getFirstMessage());

            return $this->querySearchTerm($tg);
        }

        if ($this->state->isChange()) {
            return $this->queryConfirm($tg);
        }

        return $this->queryRating($tg);
    }

    public function queryRating(TelegramAwareHelper $tg, bool $change = null): null
    {
        if ($change !== null) {
            $this->state->setChange($change);
        }
        $this->state->setStep(self::STEP_RATING_QUERIED);

        $tg->reply(
            ($change ? '' : $this->getStep(2)) . $tg->trans(
                'query.rating',
                [
                    'search_term' => sprintf('<u>%s</u>', $this->searchTermViewProvider->getSearchTermTelegramView($this->state->getSearchTerm())),
                ],
                domain: 'tg.create',
            ),
            $tg->keyboard(...[
                ...$this->getRatingButtons($tg),
                $this->getBackButton($tg),
                $this->getCancelButton($tg),
            ])
        );

        return null;
    }

    public function gotRating(TelegramAwareHelper $tg): null
    {
        $rating = $this->getRatingByButton($tg->getText(), $tg);

        if ($rating === null) {
            $tg->replyWrong($tg->trans('reply.wrong'));

            return $this->queryRating($tg);
        }

        $this->state->setRating($rating);
        try {
            $this->validator->validate($this->state, groups: 'rating');
        } catch (ValidatorException $exception) {
            $tg->reply($exception->getFirstMessage());

            return $this->queryRating($tg);
        }
        if ($this->state->isChange()) {
            return $this->queryConfirm($tg);
        }

        return $this->queryDescription($tg);
    }

    public function queryDescription(TelegramAwareHelper $tg, bool $change = null): null
    {
        if ($change !== null) {
            $this->state->setChange($change);
        }
        $this->state->setStep(self::STEP_DESCRIPTION_QUERIED);

        $buttons = [];

        if ($this->state->getDescription() === null) {
            $buttons[] = $this->getLeaveEmptyButton($tg);
        } else {
            $buttons[] = $this->getLeaveAsButton($this->state->getDescription(), $tg);
            $buttons[] = $this->getMakeEmptyButton($tg);
        }

        $buttons[] = $this->getBackButton($tg);
        $buttons[] = $this->getCancelButton($tg);

        $tg->reply(
            ($change ? '' : $this->getStep(3)) . $tg->trans(
                'query.description',
                [
                    'search_term' => sprintf('<u>%s</u>', $this->searchTermViewProvider->getSearchTermTelegramView($this->state->getSearchTerm())),
                ],
                domain: 'tg.create'
            ),
            $tg->keyboard(...$buttons)
        );

        return null;
    }

    public function gotDescription(TelegramAwareHelper $tg): null
    {
        if ($this->state->isChange() && $this->state->getDescription() !== null) {
            if ($tg->matchText($this->getLeaveAsButton($this->state->getDescription(), $tg)->getText())) {
                return $this->queryConfirm($tg);
            }

            if ($tg->matchText($this->getMakeEmptyButton($tg)->getText())) {
                $this->state->setDescription(null);

                return $this->queryConfirm($tg);
            }
        }

        if ($this->state->getDescription() === null) {
            if ($tg->matchText($this->getLeaveEmptyButton($tg)->getText())) {
                $this->state->setDescription(null);

                return $this->queryConfirm($tg);
            }
        }

        $this->state->setDescription($tg->getText());

        try {
            $this->validator->validate($this->state, groups: 'description');
        } catch (ValidatorException $exception) {
            $tg->reply($exception->getFirstMessage());

            return $this->queryDescription($tg);
        }

        return $this->queryConfirm($tg);
    }

    public function queryConfirm(TelegramAwareHelper $tg): null
    {
        $this->state->setChange(false);
        $this->state->setStep(self::STEP_CONFIRM_QUERIED);

        $this->searchTermParser->parseWithNetwork($this->state->getSearchTerm());

        $ratingText = $tg->trans(sprintf('rating.%s', $this->state->getRating()->name), ['rating' => $this->state->getRating()->value]);
        $descriptionText = $this->state->getDescription();

        $tg->reply(
            $tg->trans(
                'query.confirm',
                [
                    'search_term' => sprintf('<u>%s</u>', $this->searchTermViewProvider->getSearchTermTelegramView($this->state->getSearchTerm())),
                    'feedback' => sprintf("\r\n\r\n\"<b>%s</b>\"", trim(implode(' ', [$descriptionText, $ratingText]))),
                ],
                domain: 'tg.create'
            ),
            $tg->keyboard(
                $this->getConfirmButton($tg),
                $this->getChangeSearchTermButton($tg),
                $this->getChangeRatingButton($tg),
                $this->state->getDescription() === null ? $this->getAddDescriptionButton($tg) : $this->getChangeDescriptionButton($tg),
                $this->getBackButton($tg),
                $this->getCancelButton($tg)
            )
        );

        return null;
    }

    public function gotConfirm(TelegramAwareHelper $tg, Conversation $conversation): null
    {
        switch ($tg->getText()) {
            case $this->getChangeSearchTermButton($tg)->getText():
                return $this->querySearchTerm($tg, true);
            case $this->getChangeRatingButton($tg)->getText():
                return $this->queryRating($tg, true);
            case $this->getAddDescriptionButton($tg)->getText():
            case $this->getChangeDescriptionButton($tg)->getText():
                return $this->queryDescription($tg, true);
            case $this->getConfirmButton($tg)->getText():
                // nothing, continue
                break;
            default:
                $tg->replyWrong($tg->trans('reply.wrong'));

                return $this->queryConfirm($tg);
        }

        try {
            $this->validator->validate($this->state->getSearchTerm());
            $this->validator->validate($this->state);

            $this->creator->createFeedback($this->getFeedbackTransfer($tg));

            // todo: change text to something like: "want to add more?"
            $tg->stopConversation($conversation)->replyOk($tg->trans('reply.ok', domain: 'tg.create'));

            return $this->chooseActionChatSender->sendActions($tg);
        } catch (ValidatorException $exception) {
            if ($exception->isFirstProperty('search_term') || $exception->isFirstProperty('type')) {
                $tg->reply($exception->getFirstMessage());

                return $this->querySearchTerm($tg);
            } elseif ($exception->isFirstProperty('rating')) {
                $tg->reply($exception->getFirstMessage());

                return $this->queryRating($tg);
            } elseif ($exception->isFirstProperty('description')) {
                $tg->reply($exception->getFirstMessage());

                return $this->queryDescription($tg);
            }

            $tg->replyFail($tg->trans('reply.fail.unknown'));

            return $this->queryConfirm($tg);
        } catch (SameMessengerUserException) {
            $tg->replyFail($tg->trans('reply.fail.same_messenger_user', domain: 'tg.create'));

            return $this->queryConfirm($tg);
        } catch (CreateFeedbackLimitExceeded $exception) {
            $tg->replyFail(
                $tg->trans('reply.fail.limit_exceeded.main', [
                    'period' => $tg->trans($exception->getPeriodKey()),
                    'limit' => $exception->getLimit(),
                    'or_subscribe' => $tg->getTelegram()->getBot()->acceptPayments()
                        ? ' ' . $tg->trans('reply.fail.limit_exceeded.or_subscribe', [
                            'command' => $tg->command('subscribe', html: true),
                        ], domain: 'tg.create')
                        : '',
                ], domain: 'tg.create')
            );

            $tg->stopConversation($conversation);

            return $this->chooseActionChatSender->sendActions($tg);
        }
    }

    public function getFeedbackTransfer(TelegramAwareHelper $tg): FeedbackTransfer
    {
        return new FeedbackTransfer(
            $tg->getTelegram()->getMessengerUser(),
            $this->state->getSearchTerm(),
            $this->state->getRating(),
            $this->state->getDescription()
        );
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

    /**
     * @param TelegramAwareHelper $tg
     * @return KeyboardButton[]
     */
    public static function getRatingButtons(TelegramAwareHelper $tg): array
    {
        return array_map(fn (Rating $rating) => static::getRatingButton($rating, $tg), Rating::cases());
    }

    public static function getRatingButton(Rating $rating, TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans(sprintf('rating.%s', $rating->name), ['rating' => $rating->value]));
    }

    public static function getRatingByButton(string $button, TelegramAwareHelper $tg): ?Rating
    {
        foreach (Rating::cases() as $rating) {
            if (static::getRatingButton($rating, $tg)->getText() === $button) {
                return $rating;
            }
        }

        return null;
    }

    public static function getLeaveAsButton(string $text, TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.leave_as', ['text' => $text]));
    }

    public static function getLeaveEmptyButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.leave_empty'));
    }

    public static function getMakeEmptyButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.make_empty'));
    }

    public static function getChangeSearchTermButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button(sprintf('%s %s', '📝', $tg->trans('keyboard.change_search_term', domain: 'tg.create')));
    }

    public static function getChangeRatingButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button(sprintf('%s %s', '📝', $tg->trans('keyboard.change_rating', domain: 'tg.create')));
    }

    public static function getAddDescriptionButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button(sprintf('%s %s', '📝', $tg->trans('keyboard.add_description', domain: 'tg.create')));
    }

    public static function getChangeDescriptionButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button(sprintf('%s %s', '📝', $tg->trans('keyboard.change_description', domain: 'tg.create')));
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

    private function getStep(int $num): string
    {
        return "[{$num}/3] ";
    }
}