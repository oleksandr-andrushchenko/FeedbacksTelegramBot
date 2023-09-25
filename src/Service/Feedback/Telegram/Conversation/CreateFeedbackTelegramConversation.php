<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Conversation;

use App\Entity\CommandLimit;
use App\Entity\Feedback\Telegram\CreateFeedbackTelegramConversationState;
use App\Entity\Telegram\TelegramConversation as Entity;
use App\Enum\Feedback\Rating;
use App\Enum\Feedback\SearchTermType;
use App\Exception\CommandLimitExceeded;
use App\Exception\Messenger\SameMessengerUserException;
use App\Exception\ValidatorException;
use App\Object\Feedback\FeedbackTransfer;
use App\Object\Feedback\SearchTermTransfer;
use App\Repository\Feedback\FeedbackRepository;
use App\Repository\Telegram\TelegramBotRepository;
use App\Service\Feedback\FeedbackCreator;
use App\Service\Feedback\Rating\FeedbackRatingProvider;
use App\Service\Feedback\SearchTerm\FeedbackSearchTermTypeProvider;
use App\Service\Feedback\SearchTerm\SearchTermParserOnlyInterface;
use App\Service\Feedback\Telegram\Activity\FeedbackActivityTelegramChatSender;
use App\Service\Feedback\Telegram\Chat\ChooseActionTelegramChatSender;
use App\Service\Feedback\Telegram\View\FeedbackTelegramViewProvider;
use App\Service\Feedback\Telegram\View\SearchTermTelegramViewProvider;
use App\Service\Telegram\Conversation\TelegramConversation;
use App\Service\Telegram\Conversation\TelegramConversationInterface;
use App\Service\Telegram\TelegramAwareHelper;
use App\Service\Telegram\TelegramRegistry;
use App\Service\Validator;
use Doctrine\ORM\EntityManagerInterface;
use Longman\TelegramBot\Entities\KeyboardButton;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * /**
 * @property CreateFeedbackTelegramConversationState $state
 */
class CreateFeedbackTelegramConversation extends TelegramConversation implements TelegramConversationInterface
{
    public const STEP_SEARCH_TERM_QUERIED = 10;
    public const STEP_SEARCH_TERM_TYPE_QUERIED = 20;
    public const STEP_CANCEL_PRESSED = 30;
    public const STEP_RATING_QUERIED = 40;
    public const STEP_DESCRIPTION_QUERIED = 50;
    public const STEP_CONFIRM_QUERIED = 60;
    public const STEP_SEND_TO_CHANNEL_CONFIRM_QUERIED = 70;

    public function __construct(
        private readonly Validator $validator,
        private readonly SearchTermParserOnlyInterface $searchTermParser,
        private readonly ChooseActionTelegramChatSender $chooseActionChatSender,
        private readonly SearchTermTelegramViewProvider $searchTermViewProvider,
        private readonly FeedbackSearchTermTypeProvider $searchTermTypeProvider,
        private readonly FeedbackCreator $creator,
        private readonly FeedbackRatingProvider $ratingProvider,
        private readonly FeedbackActivityTelegramChatSender $activityTelegramChatSender,
        private readonly EntityManagerInterface $entityManager,
        private readonly FeedbackRepository $feedbackRepository,
        private readonly FeedbackTelegramViewProvider $feedbackViewProvider,
        private readonly TelegramBotRepository $botRepository,
        private readonly LoggerInterface $logger,
        private readonly bool $searchTermTypeStep,
        private readonly bool $descriptionStep,
        private readonly bool $changeSearchTermButton,
        private readonly bool $changeRatingButton,
        private readonly bool $changeDescriptionButton,
        private readonly bool $confirmStep,
    )
    {
        parent::__construct(new CreateFeedbackTelegramConversationState());
    }

    public function invoke(TelegramAwareHelper $tg, Entity $entity): null
    {
        return match ($this->state->getStep()) {
            default => $this->start($tg),
            self::STEP_SEARCH_TERM_QUERIED => $this->gotSearchTerm($tg, $entity),
            self::STEP_SEARCH_TERM_TYPE_QUERIED => $this->gotSearchTermType($tg, $entity),
            self::STEP_RATING_QUERIED => $this->gotRating($tg, $entity),
            self::STEP_DESCRIPTION_QUERIED => $this->gotDescription($tg, $entity),
            self::STEP_CONFIRM_QUERIED => $this->gotConfirm($tg, $entity),
            self::STEP_SEND_TO_CHANNEL_CONFIRM_QUERIED => $this->gotSendToChannelConfirm($tg, $entity),
        };
    }

    public function start(TelegramAwareHelper $tg): ?string
    {
        return $this->querySearchTerm($tg);
    }

    public function getStep(int $num): string
    {
        if ($this->state->isChange()) {
            return '';
        }

        $total = 3;

        if (!$this->descriptionStep) {
            $total--;
        }

        return sprintf('[%d/%d] ', $num, $total);
    }

    public function getSearchTermQuery(TelegramAwareHelper $tg, bool $help = false): string
    {
        $query = $this->getStep(1);
        $query .= $tg->trans('query.search_term', domain: 'create');

        if ($help) {
            $query = $tg->view('create_search_term_help', [
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
        $message = $tg->trans('reply.canceled', domain: 'create');

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

                if ($this->state->isChange()) {
                    return $this->queryConfirm($tg);
                }

                return $this->queryRating($tg, $entity);
            }
            if ($this->searchTermTypeStep) {
                return $this->querySearchTermType($tg);
            }

            $searchTerm->setType(SearchTermType::unknown);

            if ($this->state->isChange()) {
                return $this->queryConfirm($tg);
            }

            return $this->queryRating($tg, $entity);
        }

        if ($this->state->isChange()) {
            return $this->queryConfirm($tg);
        }

        return $this->queryRating($tg, $entity);
    }

    public function getSearchTermTypeQuery(TelegramAwareHelper $tg, bool $help = false): string
    {
        $searchTerm = $this->state->getSearchTerm()->getText();
        $parameters = [
            'search_term' => sprintf('<u>%s</u>', $searchTerm),
        ];
        $query = $tg->trans('query.search_term_type', parameters: $parameters, domain: 'create');

        if ($help) {
            return $tg->view('create_search_term_type_help', [
                'query' => $query,
                'search_term' => $searchTerm,
            ]);
        }

        return $query;
    }

    public function getSearchTermTypes(): array
    {
        $types = $this->state->getSearchTerm()->getPossibleTypes() ?? [];
        $types = $this->searchTermTypeProvider->sortSearchTermTypes($types);
        $types = $this->searchTermTypeProvider->moveUnknownToEnd($types);

        return $types;
    }

    public function querySearchTermType(TelegramAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_SEARCH_TERM_TYPE_QUERIED);

        $message = $this->getSearchTermTypeQuery($tg, $help);

        $types = $this->getSearchTermTypes();

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

        if ($this->state->isChange()) {
            return $this->queryConfirm($tg);
        }

        return $this->queryRating($tg, $entity);
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
        $types = $this->searchTermTypeProvider->getSearchTermTypes();

        foreach ($types as $type) {
            if ($this->getSearchTermTypeButton($type, $tg)->getText() === $button) {
                return $type;
            }
        }

        return null;
    }

    public function getRatingQuery(TelegramAwareHelper $tg, bool $help = false): string
    {
        $query = $this->getStep(2);
        $searchTerm = $this->getSearchTermView();
        $parameters = [
            'search_term' => $searchTerm,
        ];
        $query .= $tg->trans('query.rating', $parameters, domain: 'create');

        if ($help) {
            $query = $tg->view('create_rating_help', [
                'query' => $query,
                'search_term' => $searchTerm,
            ]);
        }

        return $query;
    }

    /**
     * @param TelegramAwareHelper $tg
     * @return KeyboardButton[]
     */
    public function getRatingButtons(TelegramAwareHelper $tg): array
    {
        return array_map(fn (Rating $rating) => $this->getRatingButton($rating, $tg), Rating::cases());
    }

    public function getRatingButton(Rating $rating, TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($this->ratingProvider->getRatingComposeName($rating));
    }

    public function getRatingByButton(string $button, TelegramAwareHelper $tg): ?Rating
    {
        foreach (Rating::cases() as $rating) {
            if ($this->getRatingButton($rating, $tg)->getText() === $button) {
                return $rating;
            }
        }

        return null;
    }

    public function queryRating(TelegramAwareHelper $tg, Entity $entity, bool $help = false): null
    {
        $this->state->setStep(self::STEP_RATING_QUERIED);

        $message = $this->getRatingQuery($tg, $help);

        $buttons = $this->getRatingButtons($tg);
        $buttons[] = $tg->backButton();

        if ($this->state->hasNotSkipHelpButton('rating')) {
            $buttons[] = $tg->helpButton();
        }

        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function gotRating(TelegramAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchText(null)) {
            $message = $tg->trans('reply.wrong');
            $message = $tg->wrongText($message);

            $tg->reply($message);

            return $this->queryRating($tg, $entity);
        }
        if ($tg->matchText($tg->backButton()->getText())) {
            $types = $this->state->getSearchTerm()->getPossibleTypes();

            if (!$this->searchTermTypeStep || $types === null || count($types) === 1) {
                return $this->querySearchTerm($tg);
            }

            return $this->querySearchTermType($tg);
        }
        if ($tg->matchText($tg->helpButton()->getText())) {
            $this->state->addSkipHelpButton('rating');

            return $this->queryRating($tg, $entity, true);
        }
        if ($tg->matchText($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }

        $rating = $this->getRatingByButton($tg->getText(), $tg);

        if ($rating === null) {
            $message = $tg->trans('reply.wrong');
            $message = $tg->wrongText($message);

            $tg->reply($message);

            return $this->queryRating($tg, $entity);
        }

        $this->state->setRating($rating);

        try {
            $this->validator->validate($this->state, groups: 'rating');
        } catch (ValidatorException $exception) {
            $tg->reply($exception->getFirstMessage());

            return $this->queryRating($tg, $entity);
        }

        if ($this->state->isChange()) {
            return $this->queryConfirm($tg);
        }
        if ($this->descriptionStep) {
            return $this->queryDescription($tg);
        }
        if ($this->confirmStep) {
            return $this->queryConfirm($tg);
        }

        return $this->createAndReply($tg, $entity);
    }

    public function getDescriptionQuery(TelegramAwareHelper $tg, bool $help = false): string
    {
        $query = $this->getStep(3);
        $searchTerm = $this->getSearchTermView();
        $parameters = [
            'search_term' => $searchTerm,
        ];
        $query .= $tg->trans('query.description', $parameters, domain: 'create');

        if ($help) {
            $query = $tg->view('create_description_help', [
                'query' => $query,
                'search_term' => $searchTerm,
            ]);
        }

        return $query;
    }

    public function queryDescription(TelegramAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_DESCRIPTION_QUERIED);

        $message = $this->getDescriptionQuery($tg, $help);

        $buttons = [];

        if ($this->state->getDescription() === null) {
            $buttons[] = $this->getLeaveEmptyButton($tg);
        } else {
            $buttons[] = $tg->leaveAsButton($this->state->getDescription(), $tg);
            $buttons[] = $this->getMakeEmptyButton($tg);
        }

        $buttons[] = $tg->backButton();

        if ($this->state->hasNotSkipHelpButton('description')) {
            $buttons[] = $tg->helpButton();
        }

        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function getLeaveEmptyButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.leave_empty'));
    }

    public function getMakeEmptyButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.make_empty'));
    }

    public function gotDescription(TelegramAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchText(null)) {
            $message = $tg->trans('reply.wrong');
            $message = $tg->wrongText($message);

            $tg->reply($message);

            return $this->queryDescription($tg);
        }
        if ($tg->matchText($tg->backButton()->getText())) {
            return $this->queryRating($tg, $entity);
        }
        if ($tg->matchText($tg->helpButton()->getText())) {
            $this->state->addSkipHelpButton('description');

            return $this->queryDescription($tg, true);
        }
        if ($tg->matchText($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }
        if ($this->state->getDescription() === null) {
            if ($tg->matchText($this->getLeaveEmptyButton($tg)->getText())) {
                $this->state->setDescription(null);

                if ($this->confirmStep) {
                    return $this->queryConfirm($tg);
                }

                return $this->createAndReply($tg, $entity);
            }
        } elseif ($this->state->isChange()) {
            if ($tg->matchText($tg->leaveAsButton($this->state->getDescription())->getText())) {
                return $this->queryConfirm($tg);
            }

            if ($tg->matchText($this->getMakeEmptyButton($tg)->getText())) {
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

        if ($this->confirmStep) {
            return $this->queryConfirm($tg);
        }

        return $this->createAndReply($tg, $entity);
    }

    public function getConfirmQuery(TelegramAwareHelper $tg, bool $help = false): string
    {
        $searchTerm = $this->getSearchTermView();
        $parameters = [
            'search_term' => $this->getSearchTermView(),
        ];
        $query = $tg->trans('query.confirm', parameters: $parameters, domain: 'create');
        $query .= "\n\n";
        $query .= sprintf('<b>%s</b>', trim(implode(' ', [
            $this->state->getDescription(),
            $this->ratingProvider->getRatingComposeName($this->state->getRating()),
        ])));

        if ($help) {
            $query = $tg->view('create_confirm_help', [
                'query' => $query,
                'search_term' => $searchTerm,
            ]);
        }

        return $query;
    }

    public function getChangeSearchTermButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button('📝 ' . $tg->trans('keyboard.change_search_term', domain: 'create'));
    }

    public function getChangeRatingButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button('📝 ' . $tg->trans('keyboard.change_rating', domain: 'create'));
    }

    public function getAddDescriptionButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button('📝 ' . $tg->trans('keyboard.add_description', domain: 'create'));
    }

    public function getChangeDescriptionButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button('📝 ' . $tg->trans('keyboard.change_description', domain: 'create'));
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
        if ($this->changeRatingButton) {
            $buttons[] = $this->getChangeRatingButton($tg);
        }
        if ($this->changeDescriptionButton && $this->descriptionStep) {
            if ($this->state->getDescription() === null) {
                $buttons[] = $this->getAddDescriptionButton($tg);
            } else {
                $buttons[] = $this->getChangeDescriptionButton($tg);
            }
        }

        $buttons[] = $tg->backButton();

        if ($this->state->hasNotSkipHelpButton('confirm')) {
            $buttons[] = $tg->helpButton();
        }

        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function getLimitExceededReply(TelegramAwareHelper $tg, CommandLimit $limit): string
    {
        return $tg->view('command_limit_exceeded', [
            'command' => 'create',
            'period' => $limit->getPeriod(),
            'count' => $limit->getCount(),
            'limits' => $this->creator->getOptions()->getLimits(),
        ]);
    }

    public function getSendToChannelConfirmQuery(TelegramAwareHelper $tg, bool $help = false): string
    {
        $channel = '@' . $tg->getTelegram()->getBot()->getChannelUsername();
        $parameters = [
            'channel' => $channel,
        ];
        $query = $tg->trans('query.send_to_channel_confirm', parameters: $parameters, domain: 'create');

        if ($help) {
            $feedback = $this->feedbackRepository->find($this->state->getFeedbackId());
            $feedbackView = $this->feedbackViewProvider->getFeedbackTelegramView(
                $tg->getTelegram(),
                $feedback,
                localeCode: $tg->getTelegram()->getBot()->getLocaleCode(),
                showSign: false,
                showTime: false
            );

            $query = $tg->view('create_send_to_channel_confirm_help', [
                'query' => $query,
                'channel' => $channel,
                'feedback' => $feedbackView,
            ]);
        }

        return $query;
    }

    public function querySendToChannelConfirm(TelegramAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_SEND_TO_CHANNEL_CONFIRM_QUERIED);

        $message = $this->getSendToChannelConfirmQuery($tg, $help);

        $buttons = [
            $tg->yesButton(),
            $tg->noButton(),
        ];

        if ($this->state->hasNotSkipHelpButton('send_to_channel_confirm')) {
            $buttons[] = $tg->helpButton();
        }

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function createAndReply(TelegramAwareHelper $tg, Entity $entity): null
    {
        try {
            $this->validator->validate($this->state->getSearchTerm());
            $this->validator->validate($this->state);

            $feedback = $this->creator->createFeedback(
                new FeedbackTransfer(
                    $tg->getTelegram()->getMessengerUser(),
                    $this->state->getSearchTerm(),
                    $this->state->getRating(),
                    $this->state->getDescription(),
                    $tg->getTelegram()->getBot()
                )
            );

            $message = $tg->trans('reply.created', domain: 'create');
            $message = $tg->okText($message);

            if ($tg->getTelegram()->getBot()->getChannelUsername() === null) {
                // todo: change text to something like: "want to add more?"
                $tg->stopConversation($entity);

                return $this->chooseActionChatSender->sendActions($tg, $message);
            }

            $this->entityManager->flush();
            $this->state->setFeedbackId($feedback->getId());

            $tg->reply($message);

            return $this->querySendToChannelConfirm($tg);
        } catch (ValidatorException $exception) {
            if ($exception->isFirstProperty('rating')) {
                $tg->reply($exception->getFirstMessage());

                return $this->queryRating($tg, $entity);
            } elseif ($exception->isFirstProperty('description')) {
                $tg->reply($exception->getFirstMessage());

                return $this->queryDescription($tg);
            }

            $tg->reply($exception->getFirstMessage());

            return $this->querySearchTerm($tg);
        } catch (SameMessengerUserException) {
            $message = $tg->trans('reply.on_self_forbidden', domain: 'create');
            $message = $tg->failText($message);

            $tg->reply($message);

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
            if ($this->descriptionStep) {
                return $this->queryDescription($tg);
            }

            return $this->queryRating($tg, $entity);
        }
        if ($tg->matchText($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }
        if ($this->changeSearchTermButton && $tg->matchText($this->getChangeSearchTermButton($tg)->getText())) {
            $this->state->setChange(true);

            return $this->querySearchTerm($tg);
        }
        if ($this->changeRatingButton && $tg->matchText($this->getChangeRatingButton($tg)->getText())) {
            $this->state->setChange(true);

            return $this->queryRating($tg, $entity);
        }
        if (
            $this->changeDescriptionButton
            && (
                $tg->matchText($this->getAddDescriptionButton($tg)->getText())
                || $tg->matchText($this->getChangeDescriptionButton($tg)->getText())
            )
        ) {
            $this->state->setChange(true);

            return $this->queryDescription($tg);
        }
        if (!$tg->matchText($tg->confirmButton()->getText())) {
            $message = $tg->trans('reply.wrong');
            $message = $tg->wrongText($message);

            $tg->reply($message);

            return $this->queryConfirm($tg);
        }

        return $this->createAndReply($tg, $entity);
    }

    public function gotSendToChannelConfirm(TelegramAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchText($tg->noButton()->getText())) {
            $tg->stopConversation($entity);

            return $this->chooseActionChatSender->sendActions($tg);
        }
        if ($tg->matchText($tg->helpButton()->getText())) {
            $this->state->addSkipHelpButton('send_to_channel_confirm');

            return $this->querySendToChannelConfirm($tg, true);
        }
        if (!$tg->matchText($tg->yesButton()->getText())) {
            $message = $tg->trans('reply.wrong');
            $message = $tg->wrongText($message);

            $tg->reply($message);

            return $this->querySendToChannelConfirm($tg);
        }

        $tg->stopConversation($entity);

        $feedback = $this->feedbackRepository->find($this->state->getFeedbackId());

        $telegram = $tg->getTelegram();
        $bot = $telegram->getBot();

        if ($bot->singleChannel()) {
            $bots = [$bot];
        } else {
            $bots = $this->botRepository->findByGroupAndCountry($bot->getGroup(), $bot->getCountryCode());
        }

        foreach ($bots as $bot) {
            try {
                $sentMessage = $this->activityTelegramChatSender->sendFeedbackActivityToTelegramChat(
                    $telegram,
                    $feedback,
                    channelUsername: $bot->getChannelUsername()
                );

                if ($sentMessage->getMessageId() !== null) {
                    $feedback->addChannelMessageId($sentMessage->getMessageId());
                }
            } catch (Throwable $exception) {
                $this->logger->error($exception);
            }
        }

        $message = $tg->trans('reply.sent_to_channel', domain: 'create');
        $message = $tg->okText($message);

        return $this->chooseActionChatSender->sendActions($tg, $message);
    }
}