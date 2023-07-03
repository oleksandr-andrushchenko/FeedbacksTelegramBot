<?php

declare(strict_types=1);

namespace App\Service\Telegram\Conversation;

use App\Entity\Telegram\SearchFeedbackTelegramConversationState;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Telegram\TelegramView;
use App\Exception\ValidatorException;
use App\Object\Feedback\FeedbackSearchTransfer;
use App\Object\Feedback\SearchTermTransfer;
use App\Object\Messenger\MessengerUserTransfer;
use App\Service\Feedback\FeedbackSearcher;
use App\Service\Feedback\FeedbackSearchCreator;
use App\Service\Feedback\SearchTerm\SearchTermParserOnlyInterface;
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
    )
    {
        parent::__construct($awareHelper, new SearchFeedbackTelegramConversationState());
    }

    public function invoke(TelegramAwareHelper $tg, Conversation $conversation): null
    {
        if ($this->state->getStep() === null) {
            return $this->askSearchTerm($tg);
        }

        if ($tg->matchText(null)) {
            return $tg->replyWrong()->null();
        }

        if ($tg->matchText($this->getCancelButton($tg)->getText())) {
            $this->state->setStep(self::STEP_CANCEL_PRESSED);

            return $tg->cancelConversation($conversation)
                ->replyUpset('feedbacks.reply.search.canceled')
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

        if ($this->state->getStep() === self::STEP_CONFIRM_ASKED) {
            return $this->onConfirmAnswer($tg, $conversation);
        }

        return null;
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
            ($change ? '' : $this->getStep(1)) . $tg->trans('feedbacks.ask.search.search_term'),
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
                $tg->replyWrong();

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
            $tg->trans('feedbacks.ask.search.search_term_type'),
            $tg->keyboard(...[
                ...$this->getSearchTermTypeButtons($sortedPossibleTypes, $tg),
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

        return $this->askConfirm($tg);
    }

    public function askConfirm(TelegramAwareHelper $tg): null
    {
        $this->state->setChange(false);
        $this->state->setStep(self::STEP_CONFIRM_ASKED);

        $this->searchTermParser->parseWithNetwork($this->state->getSearchTerm());

        $tg->reply($tg->trans('feedbacks.ask.search.confirm'))
            ->replyView(
                TelegramView::ENTITY_FEEDBACK,
                [
                    'search_term' => $this->state->getSearchTerm(),
                ],
                $tg->keyboard(
                    $this->getConfirmButton($tg),
                    $this->getChangeSearchTermButton($tg),
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
                $tg->replyWrong();

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
                return $tg->replyUpset('feedbacks.reply.search.empty_list', ['search_term' => $feedbackSearch->getSearchTermText()])
                    ->finishConversation($conversation)
                    ->startConversation(ChooseFeedbackActionTelegramConversation::class)
                    ->null()
                ;
            }

            $tg->reply(
                $tg->trans('feedbacks.reply.search.ok_1', [
                    'search_term' => $feedbackSearch->getSearchTermNormalizedText() ?? $feedbackSearch->getSearchTermText(),
                    'search_term_type' => $this->getSearchTermTypeButton($feedbackSearch->getSearchTermType(), $tg)->getText(),
                ]),
                disableWebPagePreview: true
            );

            foreach ($feedbacks as $index => $feedback) {
                $tg->replyView(
                    TelegramView::ENTITY_FEEDBACK,
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

            return $tg->replyOk('feedbacks.reply.search.ok_2', ['count' => $count])
                ->finishConversation($conversation)
                ->startConversation(ChooseFeedbackActionTelegramConversation::class)
                ->null()
            ;
        } catch (ValidatorException $exception) {
            if ($exception->isFirstProperty('search_term')) {
                $tg->reply($exception->getFirstMessage());

                return $this->askSearchTerm($tg);
            }

            return $tg->replyFail()->null();
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
        return $tg->button(sprintf('feedbacks.search_term_type.%s', $type->name));
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
        return $tg->button('keyboard.leave_as', ['text' => $text]);
    }

    public static function getChangeSearchTermButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button('feedbacks.keyboard.search.change_search_term');
    }

    public static function getConfirmButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button('keyboard.confirm');
    }

    public static function getCancelButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button('keyboard.cancel');
    }

    private function getStep(int|string $num): string
    {
        return "[{$num}/1] ";
    }
}