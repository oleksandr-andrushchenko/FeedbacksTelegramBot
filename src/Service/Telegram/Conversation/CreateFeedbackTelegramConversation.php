<?php

declare(strict_types=1);

namespace App\Service\Telegram\Conversation;

use App\Entity\Telegram\CreateFeedbackTelegramConversationState;
use App\Enum\Feedback\Rating;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Telegram\TelegramView;
use App\Exception\Feedback\CreateFeedbackLimitExceeded;
use App\Exception\Messenger\SameMessengerUserException;
use App\Exception\ValidatorException;
use App\Object\Feedback\FeedbackTransfer;
use App\Object\Feedback\SearchTermTransfer;
use App\Service\Feedback\FeedbackCreator;
use App\Service\Feedback\SearchTerm\SearchTermParserOnlyInterface;
use App\Service\Telegram\Channel\FeedbackTelegramChannel;
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
    public const STEP_SEARCH_TERM_ASKED = 10;
    public const STEP_SEARCH_TERM_TYPE_ASKED = 20;
    public const STEP_RATING_ASKED = 30;
    public const STEP_DESCRIPTION_ASKED = 40;
    public const STEP_CONFIRM_ASKED = 50;
    public const STEP_CANCEL_PRESSED = 60;

    public function __construct(
        readonly TelegramAwareHelper $awareHelper,
        private readonly Validator $validator,
        private readonly FeedbackCreator $feedbackCreator,
        private readonly SearchTermParserOnlyInterface $searchTermParser,
        private readonly ArrayValueEraser $arrayValueEraser,
    )
    {
        parent::__construct($awareHelper, new CreateFeedbackTelegramConversationState());
    }

    public function invoke(TelegramAwareHelper $tg, Conversation $conversation): null
    {
        if ($this->state->getStep() === null) {
            $this->describe($tg);

            return $this->askSearchTerm($tg);
        }

        if ($tg->matchText(null)) {
            return $tg->replyWrong()->null();
        }

        if ($tg->matchText($this->getBackButton($tg)->getText())) {
            if ($this->state->getStep() === self::STEP_SEARCH_TERM_TYPE_ASKED) {
                return $this->askSearchTerm($tg);
            }

            if ($this->state->getStep() === self::STEP_RATING_ASKED) {
                if ($this->state->getSearchTerm()->getPossibleTypes() === null) {
                    return $this->askSearchTerm($tg);
                }

                return $this->askSearchTermType($tg);
            }

            if ($this->state->getStep() === self::STEP_DESCRIPTION_ASKED) {
                return $this->askRating($tg);
            }

            if ($this->state->getStep() === self::STEP_CONFIRM_ASKED) {
                return $this->askDescription($tg);
            }
        }

        if ($tg->matchText($this->getCancelButton($tg)->getText())) {
            $this->state->setStep(self::STEP_CANCEL_PRESSED);

            return $tg->stopConversation($conversation)
                ->replyUpset('reply.create.canceled')
                ->startConversation(ChooseFeedbackActionTelegramConversation::class)
                ->null()
            ;
        }

        if ($this->state->getStep() === self::STEP_SEARCH_TERM_ASKED) {
            return $this->onSearchTermAnswer($tg);
        }

        if ($this->state->getStep() === self::STEP_SEARCH_TERM_TYPE_ASKED) {
            return $this->onSearchTermTypeAnswer($tg);
        }

        if ($this->state->getStep() === self::STEP_RATING_ASKED) {
            return $this->onRatingAnswer($tg);
        }

        if ($this->state->getStep() === self::STEP_DESCRIPTION_ASKED) {
            return $this->onDescriptionAnswer($tg);
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

        $options = $this->feedbackCreator->getOptions();

        $tg->replyView(TelegramView::CREATE, [
            'limits' => [
                'day' => $options->userPerDayLimit(),
                'month' => $options->userPerMonthLimit(),
                'year' => $options->userPerYearLimit(),
            ],
            'premium_command' => FeedbackTelegramChannel::GET_PREMIUM,
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
            ($change ? '' : $this->getStep(1)) . $tg->trans('ask.create.search_term'),
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

            return $this->askRating($tg);
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
                $tg->replyWrong();

                return $this->askSearchTerm($tg);
            }

            return $this->askSearchTermType($tg);
        }

        if ($this->state->isChange()) {
            return $this->askConfirm($tg);
        }

        return $this->askRating($tg);
    }

    public function askSearchTermType(TelegramAwareHelper $tg): null
    {
        $this->state->setStep(self::STEP_SEARCH_TERM_TYPE_ASKED);

        $possibleTypes = $this->state->getSearchTerm()->getPossibleTypes();
        $sortedPossibleTypes = SearchTermType::sort($possibleTypes);

        if (in_array(SearchTermType::unknown, $sortedPossibleTypes, true)) {
            $sortedPossibleTypes = $this->arrayValueEraser->eraseValue($sortedPossibleTypes, SearchTermType::unknown);
            $sortedPossibleTypes[] = SearchTermType::unknown;
        }

        $tg->reply(
            $tg->trans('ask.create.search_term_type'),
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
            $tg->replyWrong();

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

        if ($this->state->isChange()) {
            return $this->askConfirm($tg);
        }

        return $this->askRating($tg);
    }

    public function askRating(TelegramAwareHelper $tg, bool $change = null): null
    {
        if ($change !== null) {
            $this->state->setChange($change);
        }
        $this->state->setStep(self::STEP_RATING_ASKED);

        $tg->reply(
            ($change ? '' : $this->getStep(2)) . $tg->trans('ask.create.rating'),
            $tg->keyboard(...[
                ...$this->getRatingButtons($tg),
                $this->getBackButton($tg),
                $this->getCancelButton($tg),
            ])
        );

        return null;
    }

    public function onRatingAnswer(TelegramAwareHelper $tg): null
    {
        $rating = $this->getRatingByButton($tg->getText(), $tg);

        if ($rating === null) {
            $tg->replyWrong();

            return $this->askRating($tg);
        }

        $this->state->setRating($rating);
        try {
            $this->validator->validate($this->state, groups: 'rating');
        } catch (ValidatorException $exception) {
            $tg->reply($exception->getFirstMessage());

            return $this->askRating($tg);
        }
        if ($this->state->isChange()) {
            return $this->askConfirm($tg);
        }

        return $this->askDescription($tg);
    }

    public function askDescription(TelegramAwareHelper $tg, bool $change = null): null
    {
        if ($change !== null) {
            $this->state->setChange($change);
        }
        $this->state->setStep(self::STEP_DESCRIPTION_ASKED);

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
            ($change ? '' : $this->getStep(3)) . $tg->trans('ask.create.description'),
            $tg->keyboard(...$buttons)
        );

        return null;
    }

    public function onDescriptionAnswer(TelegramAwareHelper $tg): null
    {
        if ($this->state->isChange() && $this->state->getDescription() !== null) {
            if ($tg->matchText($this->getLeaveAsButton($this->state->getDescription(), $tg)->getText())) {
                return $this->askConfirm($tg);
            }

            if ($tg->matchText($this->getMakeEmptyButton($tg)->getText())) {
                $this->state->setDescription(null);

                return $this->askConfirm($tg);
            }
        }

        if ($this->state->getDescription() === null) {
            if ($tg->matchText($this->getLeaveEmptyButton($tg)->getText())) {
                $this->state->setDescription(null);

                return $this->askConfirm($tg);
            }
        }

        $this->state->setDescription($tg->getText());

        try {
            $this->validator->validate($this->state, groups: 'description');
        } catch (ValidatorException $exception) {
            $tg->reply($exception->getFirstMessage());

            return $this->askDescription($tg);
        }

        return $this->askConfirm($tg);
    }

    public function askConfirm(TelegramAwareHelper $tg): null
    {
        $this->state->setChange(false);
        $this->state->setStep(self::STEP_CONFIRM_ASKED);

        $this->searchTermParser->parseWithNetwork($this->state->getSearchTerm());

        $tg->reply($tg->trans('ask.create.confirm'))
            ->replyView(
                TelegramView::FEEDBACK,
                [
                    'search_term' => $this->state->getSearchTerm(),
                    'rating' => $this->state->getRating(),
                    'description' => $this->state->getDescription(),
                ],
                $tg->keyboard(
                    $this->getConfirmButton($tg),
                    $this->getChangeSearchTermButton($tg),
                    $this->getChangeRatingButton($tg),
                    $this->state->getDescription() === null ? $this->getAddDescriptionButton($tg) : $this->getChangeDescriptionButton($tg),
                    $this->getBackButton($tg),
                    $this->getCancelButton($tg)
                ),
                disableWebPagePreview: true
            )
        ;

        return null;
    }

    public function onConfirmAnswer(TelegramAwareHelper $tg, Conversation $conversation): null
    {
        switch ($tg->getText()) {
            case $this->getChangeSearchTermButton($tg)->getText():
                return $this->askSearchTerm($tg, true);
            case $this->getChangeRatingButton($tg)->getText():
                return $this->askRating($tg, true);
            case $this->getAddDescriptionButton($tg)->getText():
            case $this->getChangeDescriptionButton($tg)->getText():
                return $this->askDescription($tg, true);
            case $this->getConfirmButton($tg)->getText():
                // nothing, continue
                break;
            default:
                $tg->replyWrong();

                return $this->askConfirm($tg);
        }

        try {
            $this->validator->validate($this->state->getSearchTerm());
            $this->validator->validate($this->state);

            $this->feedbackCreator->createFeedback(
                new FeedbackTransfer(
                    $conversation->getMessengerUser(),
                    $this->state->getSearchTerm(),
                    $this->state->getRating(),
                    $this->state->getDescription()
                )
            );

            // todo: change text to something like: "want to add more?"
            return $tg->replyOk('reply.create.ok')
                ->stopConversation($conversation)->startConversation(ChooseFeedbackActionTelegramConversation::class)
                ->null()
            ;
        } catch (ValidatorException $exception) {
            if ($exception->isFirstProperty('search_term') || $exception->isFirstProperty('type')) {
                $tg->reply($exception->getFirstMessage());

                return $this->askSearchTerm($tg);
            } elseif ($exception->isFirstProperty('rating')) {
                $tg->reply($exception->getFirstMessage());

                return $this->askRating($tg);
            } elseif ($exception->isFirstProperty('description')) {
                $tg->reply($exception->getFirstMessage());

                return $this->askDescription($tg);
            }

            $tg->replyFail();

            return $this->askConfirm($tg);
        } catch (SameMessengerUserException) {
            $tg->replyFail('reply.create.fail.same_messenger_user');

            return $this->askConfirm($tg);
        } catch (CreateFeedbackLimitExceeded $exception) {
            $tg->replyFail('reply.create.fail.limit_exceeded', [
                'period' => $tg->trans($exception->getPeriodKey()),
                'limit' => $exception->getLimit(),
                'premium_command' => FeedbackTelegramChannel::GET_PREMIUM,
            ]);

            return $tg->stopConversation($conversation)->startConversation(ChooseFeedbackCountryTelegramConversation::class)->null();
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
        return $tg->button($tg->trans('keyboard.create.change_search_term'));
    }

    public static function getChangeRatingButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.create.change_rating'));
    }

    public static function getAddDescriptionButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.create.add_description'));
    }

    public static function getChangeDescriptionButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.create.change_description'));
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
        return "[{$num}/3] ";
    }
}