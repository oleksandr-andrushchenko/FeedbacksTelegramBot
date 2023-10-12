<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Bot\Conversation;

use App\Entity\CommandLimit;
use App\Entity\Feedback\Telegram\Bot\CreateFeedbackTelegramBotConversationState;
use App\Entity\Telegram\TelegramBotConversation as Entity;
use App\Entity\Telegram\TelegramChannel;
use App\Enum\Feedback\Rating;
use App\Enum\Feedback\SearchTermType;
use App\Exception\CommandLimitExceededException;
use App\Exception\Messenger\SameMessengerUserException;
use App\Exception\ValidatorException;
use App\Repository\Feedback\FeedbackRepository;
use App\Repository\Telegram\Channel\TelegramChannelRepository;
use App\Service\Feedback\FeedbackCreator;
use App\Service\Feedback\Rating\FeedbackRatingProvider;
use App\Service\Feedback\SearchTerm\FeedbackSearchTermTypeProvider;
use App\Service\Feedback\SearchTerm\SearchTermParserOnlyInterface;
use App\Service\Feedback\Telegram\Bot\Activity\TelegramChannelFeedbackActivityPublisher;
use App\Service\Feedback\Telegram\Bot\Chat\ChooseActionTelegramChatSender;
use App\Service\Feedback\Telegram\Bot\View\FeedbackTelegramViewProvider;
use App\Service\Feedback\Telegram\View\SearchTermTelegramViewProvider;
use App\Service\Telegram\Bot\Conversation\TelegramBotConversation;
use App\Service\Telegram\Bot\Conversation\TelegramBotConversationInterface;
use App\Service\Telegram\Bot\TelegramBotAwareHelper;
use App\Service\Telegram\Channel\TelegramChannelMatchesProvider;
use App\Service\Telegram\Channel\View\TelegramChannelLinkViewProvider;
use App\Service\Validator;
use App\Transfer\Feedback\FeedbackTransfer;
use App\Transfer\Feedback\SearchTermTransfer;
use Doctrine\ORM\EntityManagerInterface;
use Longman\TelegramBot\Entities\KeyboardButton;

/**
 * /**
 * @property CreateFeedbackTelegramBotConversationState $state
 */
class CreateFeedbackTelegramBotConversation extends TelegramBotConversation implements TelegramBotConversationInterface
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
        private readonly TelegramChannelFeedbackActivityPublisher $channelActivityPublisher,
        private readonly EntityManagerInterface $entityManager,
        private readonly FeedbackRepository $feedbackRepository,
        private readonly FeedbackTelegramViewProvider $feedbackViewProvider,
        private readonly TelegramChannelMatchesProvider $channelMatchesProvider,
        private readonly TelegramChannelLinkViewProvider $channelLinkViewProvider,
        private readonly bool $searchTermTypeStep,
        private readonly bool $descriptionStep,
        private readonly bool $changeSearchTermButton,
        private readonly bool $changeRatingButton,
        private readonly bool $changeDescriptionButton,
        private readonly bool $confirmStep,
    )
    {
        parent::__construct(new CreateFeedbackTelegramBotConversationState());
    }

    public function invoke(TelegramBotAwareHelper $tg, Entity $entity): null
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

    public function start(TelegramBotAwareHelper $tg): ?string
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

    public function getSearchTermQuery(TelegramBotAwareHelper $tg, bool $help = false): string
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

    public function querySearchTerm(TelegramBotAwareHelper $tg, bool $help = false): null
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

    public function getCancelReply(TelegramBotAwareHelper $tg): string
    {
        $message = $tg->trans('reply.canceled', domain: 'create');

        return $tg->upsetText($message);
    }

    public function gotCancel(TelegramBotAwareHelper $tg, Entity $entity): null
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

    public function gotSearchTerm(TelegramBotAwareHelper $tg, Entity $entity): null
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

    public function getSearchTermTypeQuery(TelegramBotAwareHelper $tg, bool $help = false): string
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

    public function querySearchTermType(TelegramBotAwareHelper $tg, bool $help = false): null
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

    public function gotSearchTermType(TelegramBotAwareHelper $tg, Entity $entity): null
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

    public function getSearchTermTypeByButton(string $button, TelegramBotAwareHelper $tg): ?SearchTermType
    {
        $types = $this->searchTermTypeProvider->getSearchTermTypes();

        foreach ($types as $type) {
            if ($this->getSearchTermTypeButton($type, $tg)->getText() === $button) {
                return $type;
            }
        }

        return null;
    }

    public function getRatingQuery(TelegramBotAwareHelper $tg, bool $help = false): string
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
     * @param TelegramBotAwareHelper $tg
     * @return KeyboardButton[]
     */
    public function getRatingButtons(TelegramBotAwareHelper $tg): array
    {
        return array_map(fn (Rating $rating) => $this->getRatingButton($rating, $tg), Rating::cases());
    }

    public function getRatingButton(Rating $rating, TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->button($this->ratingProvider->getRatingComposeName($rating));
    }

    public function getRatingByButton(string $button, TelegramBotAwareHelper $tg): ?Rating
    {
        foreach (Rating::cases() as $rating) {
            if ($this->getRatingButton($rating, $tg)->getText() === $button) {
                return $rating;
            }
        }

        return null;
    }

    public function queryRating(TelegramBotAwareHelper $tg, Entity $entity, bool $help = false): null
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

    public function gotRating(TelegramBotAwareHelper $tg, Entity $entity): null
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

    public function getDescriptionQuery(TelegramBotAwareHelper $tg, bool $help = false): string
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

    public function queryDescription(TelegramBotAwareHelper $tg, bool $help = false): null
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

    public function getLeaveEmptyButton(TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.leave_empty'));
    }

    public function getMakeEmptyButton(TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.make_empty'));
    }

    public function gotDescription(TelegramBotAwareHelper $tg, Entity $entity): null
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

    public function getConfirmQuery(TelegramBotAwareHelper $tg, bool $help = false): string
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

    public function getChangeSearchTermButton(TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->button('📝 ' . $tg->trans('keyboard.change_search_term', domain: 'create'));
    }

    public function getChangeRatingButton(TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->button('📝 ' . $tg->trans('keyboard.change_rating', domain: 'create'));
    }

    public function getAddDescriptionButton(TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->button('📝 ' . $tg->trans('keyboard.add_description', domain: 'create'));
    }

    public function getChangeDescriptionButton(TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->button('📝 ' . $tg->trans('keyboard.change_description', domain: 'create'));
    }

    public function queryConfirm(TelegramBotAwareHelper $tg, bool $help = false): null
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

    public function getLimitExceededReply(TelegramBotAwareHelper $tg, CommandLimit $limit): string
    {
        return $tg->view('command_limit_exceeded', [
            'command' => 'create',
            'period' => $limit->getPeriod(),
            'count' => $limit->getCount(),
            'limits' => $this->creator->getOptions()->getLimits(),
        ]);
    }

    public function getSendToChannelConfirmQuery(TelegramBotAwareHelper $tg, bool $help = false): string
    {
        $channels = $this->channelMatchesProvider->getTelegramChannelMatches(
            $tg->getBot()->getMessengerUser()->getUser(),
            $tg->getBot()->getEntity()
        );
        $channelNames = array_map(
            fn (TelegramChannel $channel): string => $this->channelLinkViewProvider->getTelegramChannelLinkView($channel),
            $channels
        );

        $parameters = [
            'channels' => implode(', ', $channelNames),
        ];
        $query = $tg->trans('query.send_to_channel_confirm', parameters: $parameters, domain: 'create');

        if ($help) {
            $feedback = $this->feedbackRepository->find($this->state->getFeedbackId());
            $feedbackView = $this->feedbackViewProvider->getFeedbackTelegramView(
                $tg->getBot(),
                $feedback,
                localeCode: $tg->getBot()->getEntity()->getLocaleCode(),
                showSign: false,
                showTime: false
            );

            $query = $tg->view('create_send_to_channel_confirm_help', [
                'query' => $query,
                'channels' => $channelNames,
                'feedback' => $feedbackView,
            ]);
        }

        return $query;
    }

    public function querySendToChannelConfirm(TelegramBotAwareHelper $tg, bool $help = false): null
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

    public function createAndReply(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        try {
            $this->validator->validate($this->state->getSearchTerm());
            $this->validator->validate($this->state);

            $feedback = $this->creator->createFeedback(
                new FeedbackTransfer(
                    $tg->getBot()->getMessengerUser(),
                    $this->state->getSearchTerm(),
                    $this->state->getRating(),
                    $this->state->getDescription(),
                    $tg->getBot()->getEntity()
                )
            );

            $message = $tg->trans('reply.created', domain: 'create');
            $message = $tg->okText($message);

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
        } catch (CommandLimitExceededException $exception) {
            $message = $this->getLimitExceededReply($tg, $exception->getLimit());

            $tg->reply($message);

            $tg->stopConversation($entity);

            return $this->chooseActionChatSender->sendActions($tg);
        }
    }

    public function gotConfirm(TelegramBotAwareHelper $tg, Entity $entity): null
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

    public function gotSendToChannelConfirm(TelegramBotAwareHelper $tg, Entity $entity): null
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

        $bot = $tg->getBot();
        $feedback = $this->feedbackRepository->find($this->state->getFeedbackId());

        $this->channelActivityPublisher->publishTelegramChannelFeedbackActivity($bot, $feedback);

        $message = $tg->trans('reply.sent_to_channel', domain: 'create');
        $message = $tg->okText($message);

        return $this->chooseActionChatSender->sendActions($tg, $message);
    }
}