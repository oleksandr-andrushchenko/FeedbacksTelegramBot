<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Bot\Conversation;

use App\Entity\Feedback\Command\FeedbackCommandLimit;
use App\Entity\Feedback\Telegram\Bot\CreateFeedbackTelegramBotConversationState;
use App\Entity\Feedback\Telegram\Bot\SearchFeedbackTelegramBotConversationState;
use App\Entity\Telegram\TelegramBotConversation as Entity;
use App\Enum\Feedback\SearchTermType;
use App\Exception\Feedback\FeedbackCommandLimitExceededException;
use App\Exception\ValidatorException;
use App\Service\Feedback\FeedbackSearchCreator;
use App\Service\Feedback\FeedbackSearcher;
use App\Service\Feedback\SearchTerm\SearchTermParserOnlyInterface;
use App\Service\Feedback\SearchTerm\SearchTermTypeProvider;
use App\Service\Feedback\Telegram\Bot\Chat\ChooseActionTelegramChatSender;
use App\Service\Feedback\Telegram\Bot\View\FeedbackTelegramViewProvider;
use App\Service\Feedback\Telegram\View\SearchTermTelegramViewProvider;
use App\Service\Telegram\Bot\Conversation\TelegramBotConversation;
use App\Service\Telegram\Bot\Conversation\TelegramBotConversationInterface;
use App\Service\Telegram\Bot\TelegramBotAwareHelper;
use App\Service\Util\String\MbLcFirster;
use App\Service\Validator;
use App\Transfer\Feedback\FeedbackSearchTransfer;
use App\Transfer\Feedback\SearchTermTransfer;
use Longman\TelegramBot\Entities\KeyboardButton;

/**
 * @property SearchFeedbackTelegramBotConversationState $state
 */
class SearchFeedbackTelegramBotConversation extends TelegramBotConversation implements TelegramBotConversationInterface
{
    public const STEP_SEARCH_TERM_QUERIED = 10;
    public const STEP_SEARCH_TERM_TYPE_QUERIED = 20;
    public const STEP_CANCEL_PRESSED = 30;
    public const STEP_CONFIRM_QUERIED = 40;
    public const STEP_CREATE_CONFIRM_QUERIED = 50;
    public const STEP_CREATE_CONFIRMED = 60;

    public function __construct(
        private readonly Validator $validator,
        private readonly SearchTermParserOnlyInterface $searchTermParser,
        private readonly ChooseActionTelegramChatSender $chooseActionChatSender,
        private readonly SearchTermTelegramViewProvider $searchTermViewProvider,
        private readonly SearchTermTypeProvider $searchTermTypeProvider,
        private readonly FeedbackSearchCreator $creator,
        private readonly FeedbackSearcher $searcher,
        private readonly FeedbackTelegramViewProvider $feedbackViewProvider,
        private readonly MbLcFirster $lcFirster,
        private readonly bool $searchTermTypeStep,
        private readonly bool $confirmStep,
    )
    {
        parent::__construct(new SearchFeedbackTelegramBotConversationState());
    }

    public function invoke(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        return match ($this->state->getStep()) {
            default => $this->start($tg),
            self::STEP_SEARCH_TERM_QUERIED => $this->gotSearchTerm($tg, $entity),
            self::STEP_SEARCH_TERM_TYPE_QUERIED => $this->gotSearchTermType($tg, $entity),
            self::STEP_CONFIRM_QUERIED => $this->gotConfirm($tg, $entity),
            self::STEP_CREATE_CONFIRM_QUERIED => $this->gotCreateConfirm($tg, $entity),
        };
    }

    public function start(TelegramBotAwareHelper $tg): ?string
    {
        return $this->querySearchTerm($tg);
    }

    public function getStep(int $num): string
    {
        $originalNum = $num;
        $total = 2;

        if (!$this->confirmStep) {
            if ($originalNum > 1) {
                $num--;
            }

            $total--;
        }

        return sprintf('[%d/%d] ', $num, $total);
    }

    public function getSearchTermQuery(TelegramBotAwareHelper $tg, bool $help = false): string
    {
        $query = $this->getStep(1);
        $query .= $tg->trans('query.search_term', domain: 'search');
        $query = $tg->queryText($query);

        if (!$help) {
            $parameters = [
                'should_be' => sprintf('<b>%s</b>', $tg->trans('query.search_term_should_be', domain: 'search')),
            ];
            $query .= $tg->queryTipText($tg->trans('query.search_term_tip', parameters: $parameters, domain: 'search'));
            $searchTermTypeNames = array_map(
                fn (SearchTermType $type): string => $this->lcFirster->mbLcFirst(
                    $this->searchTermTypeProvider->getSearchTermTypeName($type)
                ),
                [
                    SearchTermType::messenger_profile_url,
                    SearchTermType::phone_number,
                    SearchTermType::car_number,
                    SearchTermType::email,
                ]
            );
            $parameters = [
                'put_one' => sprintf('<b>%s</b>', $tg->trans('query.search_term_put_one', domain: 'search')),
                'types' => implode(', ', $searchTermTypeNames),
            ];
            $query .= $tg->queryTipText($tg->trans('query.search_term_tip_example', parameters: $parameters, domain: 'search'));
        }

        $searchTerm = $this->state->getSearchTerm();

        if ($searchTerm !== null) {
            $searchTermView = $this->searchTermViewProvider->getSearchTermTelegramView($searchTerm);
            $query .= $tg->alreadyAddedText($searchTermView, false);
        }

        if ($help) {
            $query = $tg->view('search_search_term_help', [
                'query' => $query,
            ]);
        } else {
            $query .= $tg->queryTipText($tg->useText(true));
        }

        return $query;
    }

    public function getRemoveTermButton(SearchTermTransfer $searchTerm, TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->removeButton($searchTerm->getNormalizedText() ?? $searchTerm->getText());
    }

    public function querySearchTerm(TelegramBotAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_SEARCH_TERM_QUERIED);

        $message = $this->getSearchTermQuery($tg, $help);

        $buttons = [];

        $searchTerm = $this->state->getSearchTerm();

        if ($searchTerm !== null) {
            $buttons[] = $this->getRemoveTermButton($searchTerm, $tg);

            if ($this->confirmStep) {
                $buttons[] = $tg->nextButton();
            }
        }

        $buttons[] = $tg->helpButton();
        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function gotCancel(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        $this->state->setStep(self::STEP_CANCEL_PRESSED);

        $tg->stopConversation($entity);

        $message = $tg->trans('reply.canceled', domain: 'search');
        $message = $tg->upsetText($message);

        return $this->chooseActionChatSender->sendActions($tg, text: $message, appendDefault: true);
    }

    public function gotSearchTerm(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchInput(null)) {
            $tg->replyWrong(true);

            return $this->querySearchTerm($tg);
        }

        $searchTerm = $this->state->getSearchTerm();

        if ($tg->matchInput($tg->nextButton()->getText()) && $searchTerm !== null) {
            if ($this->confirmStep) {
                return $this->queryConfirm($tg);
            }

            return $this->searchAndReply($tg, $entity);
        }

        if ($searchTerm !== null && $tg->matchInput($this->getRemoveTermButton($searchTerm, $tg)->getText())) {
            $this->state->setSearchTerm(null);

            return $this->querySearchTerm($tg);
        }

        if ($tg->matchInput($tg->helpButton()->getText())) {
            return $this->querySearchTerm($tg, true);
        }

        if ($tg->matchInput($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }

        $searchTerm = new SearchTermTransfer($tg->getInput());

        $this->searchTermParser->parseWithGuessType($searchTerm);

        try {
            $this->validator->validate($searchTerm);
        } catch (ValidatorException $exception) {
            $tg->replyWarning($exception->getFirstMessage());

            return $this->querySearchTerm($tg);
        }

        $this->state->setSearchTerm($searchTerm);

        if ($searchTerm->getType() === null) {
            $types = $searchTerm->getTypes() ?? [];

            if (count($types) === 1) {
                $searchTerm
                    ->setType($types[0])
                    ->setTypes(null)
                ;
                $this->searchTermParser->parseWithKnownType($searchTerm);
            } elseif ($this->searchTermTypeStep) {
                return $this->querySearchTermType($tg);
            } else {
                $searchTerm
                    ->setType(SearchTermType::unknown)
                    ->setTypes(null)
                ;
            }
        }

        if ($this->confirmStep) {
            return $this->queryConfirm($tg);
        }

        return $this->searchAndReply($tg, $entity);
    }

    public function getSearchTermTypeQuery(TelegramBotAwareHelper $tg, bool $help = false): string
    {
        $searchTerm = $this->state->getSearchTerm()->getText();
        $parameters = [
            'search_term' => sprintf('<u>%s</u>', $searchTerm),
        ];
        $query = $tg->trans('query.search_term_type', parameters: $parameters, domain: 'search');
        $query = $tg->queryText($query);

        if ($help) {
            return $tg->view('search_search_term_type_help', [
                'query' => $query,
                'search_term' => $searchTerm,
            ]);
        } else {
            $query .= $tg->queryTipText($tg->useText(false));
        }

        return $query;
    }

    public function querySearchTermType(TelegramBotAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_SEARCH_TERM_TYPE_QUERIED);

        $message = $this->getSearchTermTypeQuery($tg, $help);

        $types = $this->getSearchTermTypes($this->state->getSearchTerm());

        $buttons = $this->getSearchTermTypeButtons($types, $tg);
        $buttons[] = $this->getRemoveTermButton($this->state->getSearchTerm(), $tg);
        $buttons[] = $tg->helpButton();
        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function gotSearchTermType(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchInput(null)) {
            $tg->replyWrong(false);

            return $this->querySearchTermType($tg);
        }

        $searchTerm = $this->state->getSearchTerm();

        if ($tg->matchInput($this->getRemoveTermButton($searchTerm, $tg)->getText())) {
            $this->state->setSearchTerm(null);

            return $this->querySearchTerm($tg);
        }

        if ($tg->matchInput($tg->helpButton()->getText())) {
            return $this->querySearchTermType($tg, true);
        }

        if ($tg->matchInput($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }

        $type = $this->getSearchTermTypeByButton($tg->getInput(), $searchTerm, $tg);

        if ($type === null) {
            $tg->replyWrong(false);

            return $this->querySearchTermType($tg);
        }

        $searchTerm
            ->setType($type)
            ->setTypes(null)
        ;

        $this->searchTermParser->parseWithKnownType($searchTerm);

        try {
            $this->validator->validate($searchTerm);
        } catch (ValidatorException $exception) {
            $tg->replyWarning($exception->getFirstMessage());

            return $this->querySearchTerm($tg);
        }

        if ($this->confirmStep) {
            return $this->queryConfirm($tg);
        }

        return $this->searchAndReply($tg, $entity);
    }

    /**
     * @param SearchTermType[] $types
     * @param TelegramBotAwareHelper $tg
     * @return KeyboardButton[]
     */
    public function getSearchTermTypeButtons(array $types, TelegramBotAwareHelper $tg): array
    {
        return array_map(fn (SearchTermType $type) => $this->getSearchTermTypeButton($type, $tg), $types);
    }

    public function getSearchTermTypeButton(SearchTermType $type, TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->button($this->searchTermTypeProvider->getSearchTermTypeComposeName($type));
    }

    public function getSearchTermTypes(SearchTermTransfer $searchTerm): array
    {
        $types = $searchTerm->getTypes() ?? [];
        $types = $this->searchTermTypeProvider->sortSearchTermTypes($types);
        array_unshift($types, SearchTermType::unknown);

        return $types;
    }

    public function getSearchTermTypeByButton(
        string $button,
        SearchTermTransfer $searchTerm,
        TelegramBotAwareHelper $tg
    ): ?SearchTermType
    {
        foreach ($this->getSearchTermTypes($searchTerm) as $type) {
            if ($this->getSearchTermTypeButton($type, $tg)->getText() === $button) {
                return $type;
            }
        }

        return null;
    }

    public function getConfirmQuery(TelegramBotAwareHelper $tg, bool $help = false): string
    {
        $query = $this->getStep(2);
        $searchTerm = $this->searchTermViewProvider->getSearchTermTelegramView($this->state->getSearchTerm());
        $parameters = [
            'search_term' => $searchTerm,
        ];
        $query .= $tg->trans('query.confirm', parameters: $parameters, domain: 'search');
        $query = $tg->queryText($query);

        if ($help) {
            $query = $tg->view('search_confirm_help', [
                'query' => $query,
                'search_term' => $searchTerm,
            ]);
        } else {
            $query .= $tg->queryTipText($tg->useText(false));
        }

        return $query;
    }

    public function queryConfirm(TelegramBotAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_CONFIRM_QUERIED);

        $this->searchTermParser->parseWithNetwork($this->state->getSearchTerm());

        $message = $this->getConfirmQuery($tg, $help);

        $buttons = [];
        $buttons[] = [$tg->yesButton()];
        $buttons[] = $tg->prevButton();
        $buttons[] = $tg->helpButton();
        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function getEmptyListReply(TelegramBotAwareHelper $tg): string
    {
        $parameters = [
            'search_term' => $this->searchTermViewProvider->getSearchTermTelegramView($this->state->getSearchTerm()),
        ];
        $message = $tg->trans('reply.empty_list', $parameters, domain: 'search');

        return $tg->upsetText($message);
    }

    public function getListReply(TelegramBotAwareHelper $tg, int $count): string
    {
        $parameters = [
            'search_term' => $this->searchTermViewProvider->getSearchTermTelegramView($this->state->getSearchTerm()),
            'count' => $count,
        ];

        return $tg->trans('reply.title', $parameters, domain: 'search');
    }

    public function getLimitExceededReply(TelegramBotAwareHelper $tg, FeedbackCommandLimit $limit): string
    {
        return $tg->view('command_limit_exceeded', [
            'command' => 'search',
            'period' => $limit->getPeriod(),
            'count' => $limit->getCount(),
            'limits' => $this->creator->getOptions()->getLimits(),
        ]);
    }

    public function searchAndReply(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        try {
            $this->validator->validate($this->state->getSearchTerm());
            $this->validator->validate($this->state);

            // todo: use command bus
            $feedbackSearch = $this->creator->createFeedbackSearch(
                new FeedbackSearchTransfer(
                    $tg->getBot()->getMessengerUser(),
                    $this->state->getSearchTerm(),
                    $tg->getBot()->getEntity()
                )
            );

            $feedbacks = $this->searcher->searchFeedbacks($feedbackSearch);
            $count = count($feedbacks);

            if ($count === 0) {
                return $this->queryCreateConfirm($tg);
            }

            $message = $this->getListReply($tg, $count);

            $tg->reply($message);

            foreach ($feedbacks as $index => $feedback) {
                $message = $this->feedbackViewProvider->getFeedbackTelegramView($tg->getBot(), $feedback, $index + 1);

                $tg->reply($message);
            }

            $tg->stopConversation($entity);

            return $this->chooseActionChatSender->sendActions($tg);
        } catch (ValidatorException $exception) {
            $tg->replyWarning($exception->getFirstMessage());

            return $this->querySearchTerm($tg);
        } catch (FeedbackCommandLimitExceededException $exception) {
            $message = $this->getLimitExceededReply($tg, $exception->getLimit());

            $tg->reply($message);

            $tg->stopConversation($entity);

            return $this->chooseActionChatSender->sendActions($tg);
        }
    }

    public function gotConfirm(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchInput(null)) {
            $tg->replyWrong(false);

            return $this->queryConfirm($tg);
        }

        if ($tg->matchInput($tg->helpButton()->getText())) {
            return $this->queryConfirm($tg, true);
        }

        if ($tg->matchInput($tg->prevButton()->getText())) {
            return $this->querySearchTerm($tg);
        }

        if ($tg->matchInput($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }

        if (!$tg->matchInput($tg->yesButton()->getText())) {
            $tg->replyWrong(false);

            return $this->queryConfirm($tg);
        }

        return $this->searchAndReply($tg, $entity);
    }

    public function getCreateConfirmQuery(TelegramBotAwareHelper $tg, bool $help = false): string
    {
        $query = $tg->trans('query.create_confirm', domain: 'search');
        $query = $tg->queryText($query);

        if ($help) {
            $query = $tg->view('search_create_confirm_help', [
                'query' => $query,
            ]);
        } else {
            $query .= $tg->queryTipText($tg->useText(false));
        }

        return $query;
    }

    public function queryCreateConfirm(TelegramBotAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_CREATE_CONFIRM_QUERIED);

        $message = $this->getEmptyListReply($tg);
        $message .= "\n\n";
        $message .= $this->getCreateConfirmQuery($tg, $help);

        $buttons = [];
        $buttons[] = [$tg->yesButton(), $tg->noButton()];
        $buttons[] = $tg->helpButton();
        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function getWillNotifyReply(TelegramBotAwareHelper $tg): string
    {
        $parameters = [
            'search_term' => $this->searchTermViewProvider->getSearchTermTelegramView($this->state->getSearchTerm()),
        ];
        $message = $tg->trans('reply.will_notify', $parameters, domain: 'search');

        return $tg->okText($message);
    }

    public function gotCreateConfirm(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchInput($tg->noButton()->getText())) {
            $tg->stopConversation($entity);

            $message = $this->getWillNotifyReply($tg);

            return $this->chooseActionChatSender->sendActions($tg, $message);
        }

        if ($tg->matchInput($tg->helpButton()->getText())) {
            return $this->queryCreateConfirm($tg, true);
        }

        if ($tg->matchInput($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }

        if (!$tg->matchInput($tg->yesButton()->getText())) {
            $tg->replyWrong(false);

            return $this->queryCreateConfirm($tg);
        }

        $this->state->setStep(self::STEP_CREATE_CONFIRMED);

        $tg->stopConversation($entity)->executeConversation(
            CreateFeedbackTelegramBotConversation::class,
            (new CreateFeedbackTelegramBotConversationState())
                ->setStep(CreateFeedbackTelegramBotConversation::STEP_RATING_QUERIED)
                ->addSearchTerm($this->state->getSearchTerm()),
            'queryRating'
        );

        return null;
    }
}