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
use App\Service\Feedback\FeedbackCreator;
use App\Service\Feedback\Rating\FeedbackRatingProvider;
use App\Service\Feedback\SearchTerm\SearchTermTypeProvider;
use App\Service\Feedback\SearchTerm\SearchTermParserOnlyInterface;
use App\Service\Feedback\Telegram\Bot\Activity\TelegramChannelFeedbackActivityPublisher;
use App\Service\Feedback\Telegram\Bot\Chat\ChooseActionTelegramChatSender;
use App\Service\Feedback\Telegram\Bot\View\FeedbackTelegramViewProvider;
use App\Service\Feedback\Telegram\View\MultipleSearchTermTelegramViewProvider;
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
        private readonly MultipleSearchTermTelegramViewProvider $searchTermViewProvider,
        private readonly SearchTermTypeProvider $searchTermTypeProvider,
        private readonly FeedbackCreator $creator,
        private readonly FeedbackRatingProvider $ratingProvider,
        private readonly TelegramChannelFeedbackActivityPublisher $channelActivityPublisher,
        private readonly EntityManagerInterface $entityManager,
        private readonly FeedbackRepository $feedbackRepository,
        private readonly FeedbackTelegramViewProvider $feedbackViewProvider,
        private readonly TelegramChannelMatchesProvider $channelMatchesProvider,
        private readonly TelegramChannelLinkViewProvider $channelLinkViewProvider,
        private readonly bool $searchTermTypeStep,
        private readonly bool $extraSearchTermStep,
        private readonly bool $descriptionStep,
        private readonly bool $confirmStep,
        private readonly bool $sendToChannelConfirmStep,
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
        $originalNum = $num;
        $total = 5;

//        if (!$this->extraSearchTermStep) {
//            if ($originalNum > 1) {
//                $num--;
//            }
//
//            $total--;
//        }

        if (!$this->descriptionStep) {
            if ($originalNum > 2) {
                $num--;
            }

            $total--;
        }

        if (!$this->confirmStep) {
            if ($originalNum > 3) {
                $num--;
            }

            $total--;
        }

        if (!$this->sendToChannelConfirmStep) {
            if ($originalNum > 4) {
                $num--;
            }

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

    public function getExtraSearchTermQuery(TelegramBotAwareHelper $tg, bool $help = false): string
    {
        $query = $this->getStep(1);

        $searchTerms = $this->state->getSearchTerms();
        $primarySearchTerm = array_shift($searchTerms);
        $primarySearchTermView = $this->searchTermViewProvider->getSearchTermTelegramMainView($primarySearchTerm);
        $parameters = [
            'search_term' => $primarySearchTermView,
        ];
        $query .= $tg->trans('query.extra_search_term', parameters: $parameters, domain: 'create');

        if (count($searchTerms) > 0) {
            $query .= "\n\n";
            $searchTermViews = array_map(
                fn (SearchTermTransfer $searchTerm) => $this->searchTermViewProvider->getSearchTermTelegramView($searchTerm),
                $searchTerms
            );
            $parameters = [
                'search_terms' => "\n" . implode("\n", $searchTermViews),
            ];
            $query .= $tg->trans('query.added_extra_search_terms', parameters: $parameters, domain: 'create');
        }

        if ($help) {
            $query = $tg->view('create_extra_search_term_help', [
                'query' => $query,
                'search_term' => $primarySearchTermView,
            ]);
        }

        return $query;
    }

    public function getRemoveTermButton(SearchTermTransfer $searchTerm, TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->removeButton($searchTerm->getNormalizedText() ?? $searchTerm->getText());
    }

    /**
     * @param SearchTermTransfer[] $searchTerms
     * @param TelegramBotAwareHelper $tg
     * @return KeyboardButton[]
     */
    public function getRemoveTermButtons(array $searchTerms, TelegramBotAwareHelper $tg): array
    {
        return array_map(
            fn (SearchTermTransfer $searchTerm): KeyboardButton => $this->getRemoveTermButton($searchTerm, $tg),
            $searchTerms
        );
    }

    public function getTermByRemoveTermButton(string $button, TelegramBotAwareHelper $tg): ?SearchTermTransfer
    {
        foreach ($this->state->getSearchTerms() ?? [] as $searchTerm) {
            if ($this->getRemoveTermButton($searchTerm, $tg)->getText() === $button) {
                return $searchTerm;
            }
        }

        return null;
    }

    public function querySearchTerm(TelegramBotAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_SEARCH_TERM_QUERIED);

        $buttons = [];

        $searchTerms = $this->state->getSearchTerms() ?? [];
        $termCount = count($searchTerms);

        if ($this->extraSearchTermStep && $termCount > 0) {
            $message = $this->getExtraSearchTermQuery($tg, $help);
        } else {
            $message = $this->getSearchTermQuery($tg, $help);
        }

        if ($termCount > 0) {
            $buttons[] = $this->getRemoveTermButtons($searchTerms, $tg);
            $buttons[] = $tg->nextButton();
        }

        $buttons[] = $tg->helpButton();
        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function gotCancel(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        $this->state->setStep(self::STEP_CANCEL_PRESSED);

        $tg->stopConversation($entity);

        $message = $tg->trans('reply.canceled', domain: 'create');
        $message = $tg->upsetText($message);
        $message .= "\n\n";

        return $this->chooseActionChatSender->sendActions($tg, text: $message, prependDefault: true);
    }

    public function gotSearchTerm(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchText(null)) {
            $message = $tg->trans('reply.wrong');
            $message = $tg->wrongText($message);

            $tg->reply($message);

            return $this->querySearchTerm($tg);
        }

        $searchTerms = $this->state->getSearchTerms() ?? [];
        $termCount = count($searchTerms);

        if ($tg->matchText($tg->nextButton()->getText()) && $termCount > 0) {
            return $this->queryRating($tg, $entity);
        }

        $searchTerm = $this->getTermByRemoveTermButton($tg->getText(), $tg);

        if ($searchTerm !== null) {
            $this->state->removeSearchTerm($searchTerm);

            return $this->querySearchTerm($tg);
        }

        if ($tg->matchText($tg->helpButton()->getText())) {
            return $this->querySearchTerm($tg, true);
        }

        if ($tg->matchText($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
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

        $this->state->addSearchTerm($searchTerm);

        if ($searchTerm->getType() === null) {
            $types = $searchTerm->getPossibleTypes() ?? [];

            if (count($types) === 1) {
                $searchTerm->setType($types[0]);
                $this->searchTermParser->parseWithKnownType($searchTerm);
            } elseif ($this->searchTermTypeStep) {
                return $this->querySearchTermType($tg);
            } else {
                $searchTerm->setType(SearchTermType::unknown);
            }
        }

        if ($this->extraSearchTermStep) {
            return $this->querySearchTerm($tg);
        }

        return $this->queryRating($tg, $entity);
    }

    public function getSearchTermTypeQuery(TelegramBotAwareHelper $tg, bool $help = false): string
    {
        $query = $this->getStep(1);
        $searchTerm = $this->state->getLastSearchTerm();
        $searchTermView = $searchTerm->getText();
        $parameters = [
            'search_term' => sprintf('<u>%s</u>', $searchTermView),
        ];
        $query .= $tg->trans('query.search_term_type', parameters: $parameters, domain: 'create');

        if ($help) {
            return $tg->view('create_search_term_type_help', [
                'query' => $query,
                'search_term' => $searchTermView,
            ]);
        }

        return $query;
    }

    public function getSearchTermTypes(): array
    {
        $types = $this->state->getLastSearchTerm()->getPossibleTypes() ?? [];
        $types = $this->searchTermTypeProvider->sortSearchTermTypes($types);
        $types[] = SearchTermType::unknown;

        return $types;
    }

    public function querySearchTermType(TelegramBotAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_SEARCH_TERM_TYPE_QUERIED);

        $searchTerm = $this->state->getLastSearchTerm();
        $message = $this->getSearchTermTypeQuery($tg, $help);

        $buttons = $this->getSearchTermTypeButtons($tg);
        $buttons[] = $this->getRemoveTermButton($searchTerm, $tg);
        $buttons[] = $tg->helpButton();
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

        $searchTerm = $this->state->getLastSearchTerm();

        if ($tg->matchText($this->getRemoveTermButton($searchTerm, $tg)->getText())) {
            $this->state->removeSearchTerm($searchTerm);

            return $this->querySearchTerm($tg);
        }

        if ($tg->matchText($tg->helpButton()->getText())) {
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

        $searchTerm->setType($type);

        $this->searchTermParser->parseWithKnownType($searchTerm);

        try {
            $this->validator->validate($searchTerm);
        } catch (ValidatorException $exception) {
            $tg->reply($exception->getFirstMessage());

            return $this->querySearchTerm($tg);
        }

        if ($this->extraSearchTermStep) {
            return $this->querySearchTerm($tg);
        }

        return $this->queryRating($tg, $entity);
    }

    /**
     * @param TelegramBotAwareHelper $tg
     * @return KeyboardButton[]
     */
    public function getSearchTermTypeButtons(TelegramBotAwareHelper $tg): array
    {
        return array_map(
            fn (SearchTermType $type): KeyboardButton => $this->getSearchTermTypeButton($type, $tg),
            $this->getSearchTermTypes()
        );
    }

    public function getSearchTermTypeButton(SearchTermType $type, TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->button($this->searchTermTypeProvider->getSearchTermTypeComposeName($type));
    }

    /**
     * @param string $button
     * @param TelegramBotAwareHelper $tg
     * @return SearchTermType|null
     */
    public function getSearchTermTypeByButton(string $button, TelegramBotAwareHelper $tg): ?SearchTermType
    {
        foreach ($this->getSearchTermTypes() as $type) {
            if ($this->getSearchTermTypeButton($type, $tg)->getText() === $button) {
                return $type;
            }
        }

        return null;
    }

    public function getRatingQuery(TelegramBotAwareHelper $tg, bool $help = false): string
    {
        $query = $this->getStep(2);
        $searchTermView = $this->searchTermViewProvider->getSearchTermTelegramMainView($this->state->getFirstSearchTerm());
        $parameters = [
            'search_term' => $searchTermView,
        ];
        $query .= $tg->trans('query.rating', $parameters, domain: 'create');

        if ($help) {
            $query = $tg->view('create_rating_help', [
                'query' => $query,
                'search_term' => $searchTermView,
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
        return array_map(fn (Rating $rating): KeyboardButton => $this->getRatingButton($rating, $tg), Rating::cases());
    }

    public function getRatingButton(Rating $rating, TelegramBotAwareHelper $tg): KeyboardButton
    {
        $name = $this->ratingProvider->getRatingComposeName($rating);

        if ($rating === $this->state->getRating()) {
            $name = $tg->selectedText($name);
        }

        return $tg->button($name);
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

        $rating = $this->state->getRating();

        $buttons = [];
        $buttons[] = $this->getRatingButtons($tg);

        if ($rating === null) {
            $buttons[] = $tg->prevButton();
        } else {
            $buttons[] = [$tg->prevButton(), $tg->nextButton()];
        }

        $buttons[] = $tg->helpButton();
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

        if ($tg->matchText($tg->prevButton()->getText())) {
            return $this->querySearchTerm($tg);
        }

        if ($tg->matchText($tg->nextButton()->getText()) && $this->state->getRating() !== null) {
            return $this->queryDescription($tg);
        }

        if ($tg->matchText($tg->helpButton()->getText())) {
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
        $searchTermView = $this->searchTermViewProvider->getSearchTermTelegramMainView($this->state->getFirstSearchTerm());
        $parameters = [
            'search_term' => $searchTermView,
        ];
        $query .= $tg->trans('query.description', $parameters, domain: 'create');

        if ($help) {
            $query = $tg->view('create_description_help', [
                'query' => $query,
                'search_term' => $searchTermView,
            ]);
        }

        return $query;
    }

    public function getCreateConfirmButton(TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.create_confirm', domain: 'create'));
    }

    public function queryDescription(TelegramBotAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_DESCRIPTION_QUERIED);

        $message = $this->getDescriptionQuery($tg, $help);

        $buttons = [];

        if ($this->state->getDescription() !== null) {
            $buttons[] = $tg->removeButton($this->state->getDescription());
        }

        $buttons[] = [$tg->prevButton(), $this->confirmStep ? $tg->nextButton() : $this->getCreateConfirmButton($tg)];
        $buttons[] = $tg->helpButton();
        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function gotDescription(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchText(null)) {
            $message = $tg->trans('reply.wrong');
            $message = $tg->wrongText($message);

            $tg->reply($message);

            return $this->queryDescription($tg);
        }

        if ($tg->matchText($tg->prevButton()->getText())) {
            return $this->queryRating($tg, $entity);
        }

        if ($this->confirmStep) {
            if ($tg->matchText($tg->nextButton()->getText())) {
                return $this->queryConfirm($tg);
            }
        } else {
            if ($this->getCreateConfirmButton($tg)->getText()) {
                return $this->createAndReply($tg, $entity);
            }
        }

        if ($tg->matchText($tg->helpButton()->getText())) {
            return $this->queryDescription($tg, true);
        }

        if ($tg->matchText($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }

        if ($this->state->getDescription() !== null) {
            if ($tg->matchText($tg->removeButton($this->state->getDescription())->getText())) {
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
        $query = $this->getStep(4);
        $feedbackView = $this->feedbackViewProvider->getFeedbackTelegramView(
            $tg->getBot(),
            $this->creator->constructFeedback($this->constructTransfer($tg)),
            localeCode: $tg->getBot()->getEntity()->getLocaleCode(),
            showSign: false,
            showTime: false
        );

        $searchTermView = $this->searchTermViewProvider->getSearchTermTelegramMainView($this->state->getFirstSearchTerm());
        $parameters = [
            'search_term' => $searchTermView,
            'feedback' => sprintf('<i>%s</i>', $feedbackView),
        ];
        $query .= $tg->trans('query.confirm', parameters: $parameters, domain: 'create');

        if ($help) {
            $query = $tg->view('create_confirm_help', [
                'query' => $query,
                'search_term' => $searchTermView,
            ]);
        }

        return $query;
    }

    public function queryConfirm(TelegramBotAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_CONFIRM_QUERIED);

        array_map(
            fn (SearchTermTransfer $searchTerm) => $this->searchTermParser->parseWithNetwork($searchTerm),
            $this->state->getSearchTerms()
        );

        $message = $this->getConfirmQuery($tg, $help);

        $buttons = [];
        $buttons[] = $tg->yesButton();
        $buttons[] = $tg->prevButton();
        $buttons[] = $tg->helpButton();
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
        $query = $this->getStep(5);
        $channels = $this->channelMatchesProvider->getTelegramChannelMatches(
            $tg->getBot()->getMessengerUser()->getUser(),
            $tg->getBot()->getEntity()
        );
        $channelNamesView = implode(
            ', ',
            array_map(
                fn (TelegramChannel $channel): string => $this->channelLinkViewProvider->getTelegramChannelLinkView($channel),
                $channels
            )
        );

        $parameters = [
            'channels' => $channelNamesView,
        ];
        $query .= $tg->trans('query.send_to_channel_confirm', parameters: $parameters, domain: 'create');

        if ($help) {
            $feedback = $this->feedbackRepository->find($this->state->getCreatedId());
            $feedbackView = $this->feedbackViewProvider->getFeedbackTelegramView(
                $tg->getBot(),
                $feedback,
                localeCode: $tg->getBot()->getEntity()->getLocaleCode(),
                showSign: false,
                showTime: false
            );

            $query = $tg->view('create_send_to_channel_confirm_help', [
                'query' => $query,
                'channels' => $channelNamesView,
                'feedback' => $feedbackView,
            ]);
        }

        return $query;
    }

    public function querySendToChannelConfirm(TelegramBotAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_SEND_TO_CHANNEL_CONFIRM_QUERIED);

        $message = $this->getSendToChannelConfirmQuery($tg, $help);

        $buttons = [];
        $buttons[] = [$tg->yesButton(), $tg->noButton()];
        $buttons[] = $tg->helpButton();
        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function constructTransfer(TelegramBotAwareHelper $tg): FeedbackTransfer
    {
        return new FeedbackTransfer(
            messengerUser: $tg->getBot()->getMessengerUser(),
            searchTerms: $this->state->getSearchTerms(),
            rating: $this->state->getRating(),
            description: $this->state->getDescription(),
            telegramBot: $tg->getBot()->getEntity()
        );
    }

    public function createAndReply(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        try {
            foreach ($this->state->getSearchTerms() as $searchTerm) {
                $this->validator->validate($searchTerm);
            }

            $this->validator->validate($this->state);

            $feedback = $this->creator->createFeedback($this->constructTransfer($tg));

            $message = $tg->trans('reply.created', domain: 'create');
            $message = $tg->okText($message);

            // todo: replace with custom generated feedback IDS and remove this
            $this->entityManager->flush();
            $this->state->setCreatedId($feedback->getId());

            $tg->reply($message);

            if ($this->sendToChannelConfirmStep) {
                return $this->querySendToChannelConfirm($tg);
            }

            $tg->stopConversation($entity);

            return $this->chooseActionChatSender->sendActions($tg);
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
            return $this->queryConfirm($tg, true);
        }

        if ($tg->matchText($tg->prevButton()->getText())) {
            if ($this->descriptionStep) {
                return $this->queryDescription($tg);
            }

            return $this->queryRating($tg, $entity);
        }

        if ($tg->matchText($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }

        if (!$tg->matchText($tg->yesButton()->getText())) {
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
            return $this->querySendToChannelConfirm($tg, true);
        }

        if ($tg->matchText($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }

        if (!$tg->matchText($tg->yesButton()->getText())) {
            $message = $tg->trans('reply.wrong');
            $message = $tg->wrongText($message);

            $tg->reply($message);

            return $this->querySendToChannelConfirm($tg);
        }

        $tg->stopConversation($entity);

        $feedback = $this->feedbackRepository->find($this->state->getCreatedId());

        $this->channelActivityPublisher->publishTelegramChannelFeedbackActivity($tg->getBot(), $feedback);

        $message = $tg->trans('reply.sent_to_channel', domain: 'create');
        $message = $tg->okText($message);

        return $this->chooseActionChatSender->sendActions($tg, $message);
    }
}