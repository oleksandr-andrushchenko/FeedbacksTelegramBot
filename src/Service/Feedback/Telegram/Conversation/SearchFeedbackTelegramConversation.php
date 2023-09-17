<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Conversation;

use App\Entity\CommandLimit;
use App\Entity\Feedback\Telegram\CreateFeedbackTelegramConversationState;
use App\Entity\Feedback\Telegram\SearchFeedbackTelegramConversationState;
use App\Entity\Telegram\TelegramConversation as Entity;
use App\Enum\Feedback\SearchTermType;
use App\Exception\CommandLimitExceeded;
use App\Exception\ValidatorException;
use App\Object\Feedback\FeedbackSearchTransfer;
use App\Object\Feedback\SearchTermTransfer;
use App\Service\Feedback\FeedbackSearchCreator;
use App\Service\Feedback\FeedbackSearcher;
use App\Service\Feedback\SearchTerm\FeedbackSearchTermTypeProvider;
use App\Service\Feedback\SearchTerm\SearchTermParserOnlyInterface;
use App\Service\Feedback\Telegram\Chat\ChooseActionTelegramChatSender;
use App\Service\Feedback\Telegram\View\FeedbackTelegramViewProvider;
use App\Service\Feedback\Telegram\View\SearchTermTelegramViewProvider;
use App\Service\Telegram\Conversation\TelegramConversation;
use App\Service\Telegram\Conversation\TelegramConversationInterface;
use App\Service\Telegram\TelegramAwareHelper;
use App\Service\Validator;
use Doctrine\ORM\EntityManagerInterface;
use Longman\TelegramBot\Entities\KeyboardButton;

/**
 * @property SearchFeedbackTelegramConversationState $state
 */
class SearchFeedbackTelegramConversation extends TelegramConversation implements TelegramConversationInterface
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
        private readonly FeedbackSearchTermTypeProvider $searchTermTypeProvider,
        private readonly FeedbackSearchCreator $creator,
        private readonly FeedbackSearcher $searcher,
        private readonly EntityManagerInterface $entityManager,
        private readonly FeedbackTelegramViewProvider $feedbackViewProvider,
        private readonly bool $searchTermTypeStep,
        private readonly bool $changeSearchTermButton,
        private readonly bool $confirmStep,
    )
    {
        parent::__construct(new SearchFeedbackTelegramConversationState());
    }

    public function invoke(TelegramAwareHelper $tg, Entity $entity): null
    {
        return match ($this->state->getStep()) {
            default => $this->start($tg),
            self::STEP_SEARCH_TERM_QUERIED => $this->gotSearchTerm($tg, $entity),
            self::STEP_SEARCH_TERM_TYPE_QUERIED => $this->gotSearchTermType($tg, $entity),
            self::STEP_CONFIRM_QUERIED => $this->gotConfirm($tg, $entity),
            self::STEP_CREATE_CONFIRM_QUERIED => $this->gotCreateConfirm($tg, $entity),
        };
    }

    public function start(TelegramAwareHelper $tg): ?string
    {
        return $this->querySearchTerm($tg);
    }

    public function getSearchTermQuery(TelegramAwareHelper $tg, bool $help = false): string
    {
        $query = $tg->trans('query.search_term', domain: 'search');

        if ($help) {
            $query = $tg->view('search_search_term_help', [
                'query' => $query,
            ]);
        }

        return $query;
    }

    public function querySearchTerm(TelegramAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_SEARCH_TERM_QUERIED);

        $message = $this->getSearchTermQuery($tg, $help);

        $buttons = [];

        if ($this->state->getSearchTerm() !== null) {
            $buttons[] = $tg->leaveAsButton($this->state->getSearchTerm()->getText());
        }
        if ($this->state->hasNotSkipHelpButton('search_term')) {
            $buttons[] = $tg->helpButton();
        }

        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function getCancelReply(TelegramAwareHelper $tg): string
    {
        $message = $tg->trans('reply.canceled', domain: 'search');

        return $tg->upsetText($message);
    }

    public function gotCancel(TelegramAwareHelper $tg, Entity $entity): null
    {
        $this->state->setStep(self::STEP_CANCEL_PRESSED);

        $tg->stopConversation($entity);

        $message = $this->getCancelReply($tg);

        return $this->chooseActionChatSender->sendActions($tg, $message, true);
    }

    public function getSearchTermView(): string
    {
        $searchTermView = $this->searchTermViewProvider->getSearchTermTelegramView($this->state->getSearchTerm());

//        return sprintf('<u>%s</u>', $searchTermView);
        return $searchTermView;
    }

    public function gotSearchTerm(TelegramAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchText(null)) {
            $message = $tg->trans('reply.wrong');
            $message = $tg->wrongText($message);

            $tg->reply($message);

            return $this->querySearchTerm($tg);
        }
        if ($tg->matchText($tg->helpButton()->getText())) {
            $this->state->addSkipHelpButton('search_term');

            return $this->querySearchTerm($tg, true);
        }
        if ($tg->matchText($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }
        if ($this->state->getSearchTerm() !== null) {
            $leaveAsButton = $tg->leaveAsButton($this->state->getSearchTerm()->getText());

            if ($tg->matchText($leaveAsButton->getText())) {
                $searchTerm = $this->state->getSearchTerm();
            }
        }

        $searchTerm = $searchTerm ?? new SearchTermTransfer($tg->getText());
        $searchTerm->setType(null)->setPossibleTypes(null);

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
            $types = $searchTerm->getPossibleTypes();

            if ($types === null) {
                $message = $tg->trans('reply.wrong');
                $message = $tg->wrongText($message);

                $tg->reply($message);

                return $this->querySearchTerm($tg);
            }
            if (count($types) === 1) {
                $searchTerm->setType($types[0]);

                if ($this->confirmStep) {
                    return $this->queryConfirm($tg);
                }

                return $this->searchAndReply($tg, $entity);
            }
            if ($this->searchTermTypeStep) {
                return $this->querySearchTermType($tg);
            }

            $searchTerm->setType(SearchTermType::unknown);
        }

        if ($this->confirmStep) {
            return $this->queryConfirm($tg);
        }

        return $this->searchAndReply($tg, $entity);
    }

    public function getSearchTermTypeQuery(TelegramAwareHelper $tg, bool $help = false): string
    {
        $searchTerm = $this->state->getSearchTerm()->getText();
        $parameters = [
            'search_term' => sprintf('<u>%s</u>', $searchTerm),
        ];
        $query = $tg->trans('query.search_term_type', parameters: $parameters, domain: 'search');

        if ($help) {
            return $tg->view('search_search_term_type_help', [
                'query' => $query,
                'search_term' => $searchTerm,
            ]);
        }

        return $query;
    }

    public function querySearchTermType(TelegramAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_SEARCH_TERM_TYPE_QUERIED);

        $message = $this->getSearchTermTypeQuery($tg, $help);

        $types = $this->state->getSearchTerm()->getPossibleTypes() ?? [];
        $types = $this->searchTermTypeProvider->sortSearchTermTypes($types);

        $buttons = $this->getSearchTermTypeButtons($types, $tg);
        $buttons[] = $tg->backButton();

        if ($this->state->hasNotSkipHelpButton('search_term_type')) {
            $buttons[] = $tg->helpButton();
        }

        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function gotSearchTermType(TelegramAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchText(null)) {
            $message = $tg->trans('reply.wrong');
            $message = $tg->wrongText($message);

            $tg->reply($message);

            return $this->querySearchTermType($tg);
        }
        if ($tg->matchText($tg->backButton()->getText())) {
            return $this->querySearchTerm($tg);
        }
        if ($tg->matchText($tg->helpButton()->getText())) {
            $this->state->addSkipHelpButton('search_term_type');

            return $this->querySearchTermType($tg, true);
        }
        if ($tg->matchText($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }

        $type = $this->getSearchTermTypeByButton($tg->getText(), $tg);

        if ($type === null) {
            $message = $tg->trans('reply.wrong');
            $message = $tg->wrongText($message);

            $tg->reply($message);

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

        if ($this->confirmStep) {
            return $this->queryConfirm($tg);
        }

        return $this->searchAndReply($tg, $entity);
    }

    /**
     * @param SearchTermType[] $types
     * @param TelegramAwareHelper $tg
     * @return KeyboardButton[]
     */
    public function getSearchTermTypeButtons(array $types, TelegramAwareHelper $tg): array
    {
        return array_map(fn (SearchTermType $type) => $this->getSearchTermTypeButton($type, $tg), $types);
    }

    public function getSearchTermTypeButton(SearchTermType $type, TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($this->searchTermTypeProvider->getSearchTermTypeComposeName($type));
    }

    public function getSearchTermTypeByButton(string $button, TelegramAwareHelper $tg): ?SearchTermType
    {
        $types = $this->searchTermTypeProvider->getSearchTermTypes(countryCode: $tg->getCountryCode());

        foreach ($types as $type) {
            if ($this->getSearchTermTypeButton($type, $tg)->getText() === $button) {
                return $type;
            }
        }

        return null;
    }

    public function getConfirmQuery(TelegramAwareHelper $tg, bool $help = false): string
    {
        $searchTerm = $this->getSearchTermView();
        $parameters = [
            'search_term' => $searchTerm,
        ];
        $query = $tg->trans('query.confirm', parameters: $parameters, domain: 'search');

        if ($help) {
            $query = $tg->view('search_confirm_help', [
                'query' => $query,
                'search_term' => $searchTerm,
            ]);
        }

        return $query;
    }

    public function queryConfirm(TelegramAwareHelper $tg, bool $help = false): null
    {
        $this->state->setChange(false);
        $this->state->setStep(self::STEP_CONFIRM_QUERIED);

        $this->searchTermParser->parseWithNetwork($this->state->getSearchTerm());

        $message = $this->getConfirmQuery($tg, $help);

        $buttons = [
            $tg->confirmButton(),
        ];

        if ($this->changeSearchTermButton) {
            $buttons[] = $this->getChangeSearchTermButton($tg);
        }

        $buttons[] = $tg->backButton();

        if ($this->state->hasNotSkipHelpButton('confirm')) {
            $buttons[] = $tg->helpButton();
        }

        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function getEmptyListReply(TelegramAwareHelper $tg): string
    {
        $parameters = [
            'search_term' => $this->getSearchTermView(),
        ];
        $message = $tg->trans('reply.empty_list', $parameters, domain: 'search');

        return $tg->upsetText($message);
    }

    public function getListReply(TelegramAwareHelper $tg, int $count): string
    {
        $parameters = [
            'search_term' => $this->getSearchTermView(),
            'count' => $count,
        ];

        return $tg->trans('reply.title', $parameters, domain: 'search');
    }

    public function getLimitExceededReply(TelegramAwareHelper $tg, CommandLimit $limit): string
    {
        return $tg->view('command_limit_exceeded', [
            'command' => 'search',
            'period' => $limit->getPeriod(),
            'count' => $limit->getCount(),
            'limits' => $this->creator->getOptions()->getLimits(),
        ]);
    }

    public function searchAndReply(TelegramAwareHelper $tg, Entity $entity): null
    {
        try {
            $this->validator->validate($this->state->getSearchTerm());
            $this->validator->validate($this->state);

            $feedbackSearch = $this->creator->createFeedbackSearch(
                new FeedbackSearchTransfer(
                    $tg->getTelegram()->getMessengerUser(),
                    $this->state->getSearchTerm(),
                    $tg->getTelegram()->getBot()
                )
            );
            $this->entityManager->flush();

            $feedbacks = $this->searcher->searchFeedbacks($feedbackSearch);
            $count = count($feedbacks);

            if ($count === 0) {
                return $this->queryCreateConfirm($tg);
            }

            $message = $this->getListReply($tg, $count);

            $tg->reply($message);

            foreach ($feedbacks as $index => $feedback) {
                $message = $this->feedbackViewProvider->getFeedbackTelegramView($tg->getTelegram(), $feedback, $index + 1);

                $tg->reply($message);
            }

            $tg->stopConversation($entity);

            return $this->chooseActionChatSender->sendActions($tg);
        } catch (ValidatorException $exception) {
            $tg->reply($exception->getFirstMessage());

            return $this->querySearchTerm($tg);
        } catch (CommandLimitExceeded $exception) {
            $message = $this->getLimitExceededReply($tg, $exception->getLimit());

            $tg->reply($message);

            $tg->stopConversation($entity);

            return $this->chooseActionChatSender->sendActions($tg);
        }
    }

    public function gotConfirm(TelegramAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchText(null)) {
            $message = $tg->trans('reply.wrong');
            $message = $tg->wrongText($message);

            $tg->reply($message);

            return $this->queryConfirm($tg);
        }
        if ($tg->matchText($tg->helpButton()->getText())) {
            $this->state->addSkipHelpButton('confirm');

            return $this->queryConfirm($tg, true);
        }
        if ($tg->matchText($tg->backButton()->getText())) {
            $types = $this->state->getSearchTerm()->getPossibleTypes();

            if (!$this->searchTermTypeStep || $types === null || count($types) === 1) {
                return $this->querySearchTerm($tg);
            }

            return $this->querySearchTermType($tg);
        }
        if ($tg->matchText($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }
        if ($this->changeSearchTermButton && $tg->matchText($this->getChangeSearchTermButton($tg)->getText())) {
            $this->state->setChange(true);

            return $this->querySearchTerm($tg);
        }
        if (!$tg->matchText($tg->confirmButton()->getText())) {
            $message = $tg->trans('reply.wrong');
            $message = $tg->wrongText($message);

            $tg->reply($message);

            return $this->queryConfirm($tg);
        }

        return $this->searchAndReply($tg, $entity);
    }

    public function getCreateConfirmQuery(TelegramAwareHelper $tg, bool $help = false): string
    {
        $query = $tg->trans('query.create_confirm', domain: 'search');

        if ($help) {
            $query = $tg->view('search_create_confirm_help', [
                'query' => $query,
            ]);
        }

        return $query;
    }

    public function queryCreateConfirm(TelegramAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_CREATE_CONFIRM_QUERIED);

        $message = $this->getEmptyListReply($tg);
        $message .= "\n\n";
        $message .= $this->getCreateConfirmQuery($tg, $help);

        $buttons = [
            $tg->yesButton(),
            $tg->noButton(),
        ];

        if ($this->state->hasNotSkipHelpButton('create_confirm')) {
            $buttons[] = $tg->helpButton();
        }

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function getWillNotifyReply(TelegramAwareHelper $tg): string
    {
        $parameters = [
            'search_term' => $this->getSearchTermView(),
        ];
        $message = $tg->trans('reply.will_notify', $parameters, domain: 'search');

        return $tg->okText($message);
    }

    public function gotCreateConfirm(TelegramAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchText($tg->noButton()->getText())) {
            $tg->stopConversation($entity);

            $message = $this->getWillNotifyReply($tg);

            return $this->chooseActionChatSender->sendActions($tg, $message);
        }
        if ($tg->matchText($tg->helpButton()->getText())) {
            $this->state->addSkipHelpButton('create_confirm');

            return $this->queryCreateConfirm($tg, true);
        }
        if (!$tg->matchText($tg->yesButton()->getText())) {
            $message = $tg->trans('reply.wrong');
            $message = $tg->wrongText($message);

            $tg->reply($message);

            return $this->queryCreateConfirm($tg);
        }

        $this->state->setStep(self::STEP_CREATE_CONFIRMED);

        $tg->stopConversation($entity)->executeConversation(
            CreateFeedbackTelegramConversation::class,
            (new CreateFeedbackTelegramConversationState())
                ->setStep(CreateFeedbackTelegramConversation::STEP_RATING_QUERIED)
                ->setSearchTerm($this->state->getSearchTerm()),
            'queryRating'
        );

        return null;
    }

    public function getChangeSearchTermButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button('📝 ' . $tg->trans('keyboard.change_search_term', domain: 'search'));
    }
}