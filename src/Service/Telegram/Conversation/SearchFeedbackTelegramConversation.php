<?php

declare(strict_types=1);

namespace App\Service\Telegram\Conversation;

use App\Entity\Telegram\CreateFeedbackTelegramConversationState;
use App\Entity\Telegram\SearchFeedbackTelegramConversationState;
use App\Enum\Feedback\SearchTermType;
use App\Exception\CommandLimitExceeded;
use App\Exception\ValidatorException;
use App\Object\Feedback\FeedbackSearchTransfer;
use App\Object\Feedback\SearchTermTransfer;
use App\Service\Feedback\FeedbackSearcher;
use App\Service\Feedback\FeedbackSearchCreator;
use App\Service\Feedback\SearchTerm\SearchTermParserOnlyInterface;
use App\Service\Feedback\View\FeedbackTelegramViewProvider;
use App\Service\Feedback\View\SearchTermTelegramViewProvider;
use App\Service\Telegram\Chat\ChooseActionTelegramChatSender;
use App\Service\Telegram\TelegramAwareHelper;
use App\Entity\Telegram\TelegramConversation as Entity;
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
    public const STEP_CONFIRM_QUERIED = 30;
    public const STEP_CREATE_CONFIRM_QUERIED = 35;
    public const STEP_CREATE_CONFIRMED = 35;
    public const STEP_CANCEL_PRESSED = 50;

    public function __construct(
        private readonly Validator $validator,
        private readonly FeedbackSearchCreator $creator,
        private readonly FeedbackSearcher $searcher,
        private readonly SearchTermParserOnlyInterface $searchTermParser,
        private readonly EntityManagerInterface $entityManager,
        private readonly ChooseActionTelegramChatSender $chooseActionChatSender,
        private readonly SearchTermTelegramViewProvider $searchTermViewProvider,
        private readonly FeedbackTelegramViewProvider $feedbackViewProvider,
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
        $this->describe($tg);

        return $this->querySearchTerm($tg);
    }

    public function describe(TelegramAwareHelper $tg): void
    {
        if (!$tg->getTelegram()->getMessengerUser()->showHints()) {
            return;
        }

        $tg->reply($tg->view('describe_search', [
            'limits' => $this->creator->getOptions()->getLimits(),
        ]));
    }

    public function querySearchTerm(TelegramAwareHelper $tg): null
    {
        $this->state->setStep(self::STEP_SEARCH_TERM_QUERIED);

        $buttons = [];

        if ($this->state->isChange()) {
            $buttons[] = $this->getLeaveAsButton(strip_tags($this->searchTermViewProvider->getSearchTermTelegramView($this->state->getSearchTerm())), $tg);
        }

        $buttons[] = $this->getCancelButton($tg);

        $tg->reply(
            $this->getStep(1) . $tg->trans('query.search_term', domain: 'tg.search'),
            $tg->keyboard(...$buttons)
        );

        return null;
    }

    public function gotCancel(TelegramAwareHelper $tg, Entity $entity): null
    {
        $this->state->setStep(self::STEP_CANCEL_PRESSED);

        $tg->stopConversation($entity)->replyUpset($tg->trans('reply.canceled', domain: 'tg.search'));

        return $this->chooseActionChatSender->sendActions($tg);
    }

    public function gotSearchTerm(TelegramAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchText(null)) {
            $tg->replyWrong($tg->trans('reply.wrong'));

            return $this->querySearchTerm($tg);
        }
        if ($tg->matchText($this->getCancelButton($tg)->getText())) {
            return $this->gotCancel($tg, $entity);
        }
        if ($this->state->isChange()) {
            $searchTermText = $this->searchTermViewProvider->getSearchTermTelegramView($this->state->getSearchTerm());
            $leaveAsButton = $this->getLeaveAsButton(strip_tags($searchTermText), $tg);
            if ($tg->matchText($leaveAsButton->getText())) {
                return $this->queryConfirm($tg);
            }
        }
        if ($tg->matchText($this->state->getSearchTerm()?->getText())) {
            if ($this->state->isChange()) {
                return $this->queryConfirm($tg);
            }

            return $this->queryConfirm($tg);
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

            $searchTerm->setType(SearchTermType::unknown);
            return $this->queryConfirm($tg);

            return $this->querySearchTermType($tg);
        }

        if ($this->state->isChange()) {
            return $this->queryConfirm($tg);
        }

        return $this->queryConfirm($tg);
    }

    public function querySearchTermType(TelegramAwareHelper $tg): null
    {
        $this->state->setStep(self::STEP_SEARCH_TERM_TYPE_QUERIED);

        $possibleTypes = $this->state->getSearchTerm()->getPossibleTypes();
        $sortedPossibleTypes = SearchTermType::sort($possibleTypes);

        $tg->reply(
            $tg->trans(
                'query.search_term_type',
                [
                    'search_term' => sprintf('<u>%s</u>', $this->state->getSearchTerm()->getText()),
                ],
                domain: 'tg.search'
            ),
            $tg->keyboard(...[
                ...$this->getSearchTermTypeButtons($sortedPossibleTypes, $tg),
                $this->getBackButton($tg),
                $this->getCancelButton($tg),
            ])
        );

        return null;
    }

    public function gotSearchTermType(TelegramAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchText(null)) {
            $tg->replyWrong($tg->trans('reply.wrong'));

            return $this->querySearchTermType($tg);
        }
        if ($tg->matchText($this->getBackButton($tg)->getText())) {
            return $this->querySearchTerm($tg);
        }
        if ($tg->matchText($this->getCancelButton($tg)->getText())) {
            return $this->gotCancel($tg, $entity);
        }

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

        return $this->queryConfirm($tg);
    }

    public function queryConfirm(TelegramAwareHelper $tg): null
    {
        $this->state->setChange(false);
        $this->state->setStep(self::STEP_CONFIRM_QUERIED);

        $this->searchTermParser->parseWithNetwork($this->state->getSearchTerm());

        $tg->reply(
            $tg->trans(
                'query.confirm',
                [
                    'search_term' => sprintf('<u>%s</u>', $this->searchTermViewProvider->getSearchTermTelegramView($this->state->getSearchTerm())),
                ],
                domain: 'tg.search'
            ),
            $tg->keyboard(
                $this->getConfirmButton($tg),
                $this->getChangeSearchTermButton($tg),
                $this->getBackButton($tg),
                $this->getCancelButton($tg)
            )
        );

        return null;
    }

    public function gotConfirm(TelegramAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchText(null)) {
            $tg->replyWrong($tg->trans('reply.wrong'));

            return $this->queryConfirm($tg);
        }
        if ($tg->matchText($this->getBackButton($tg)->getText())) {
            if ($this->state->getSearchTerm()->getPossibleTypes() === null) {
                return $this->querySearchTerm($tg);
            }

            return $this->querySearchTermType($tg);
        }
        if ($tg->matchText($this->getCancelButton($tg)->getText())) {
            return $this->gotCancel($tg, $entity);
        }
        switch ($tg->getText()) {
            case $this->getChangeSearchTermButton($tg)->getText():
                $this->state->setChange(true);
                return $this->querySearchTerm($tg);
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

            $feedbackSearch = $this->creator->createFeedbackSearch(
                new FeedbackSearchTransfer(
                    $entity->getMessengerUser(),
                    $this->state->getSearchTerm()
                )
            );
            $this->entityManager->flush();

            $feedbacks = $this->searcher->searchFeedbacks($feedbackSearch);
            $count = count($feedbacks);

            if ($count === 0) {
                return $this->queryCreateConfirm($tg);
            }

            $searchTermText = $this->searchTermViewProvider->getSearchTermTelegramView($this->state->getSearchTerm());
            $tg->reply($tg->trans('reply.title', ['search_term' => sprintf('<u>%s</u>', $searchTermText), 'count' => $count], domain: 'tg.search'));

            foreach ($feedbacks as $index => $feedback) {
                $tg->reply(
                    $this->feedbackViewProvider->getFeedbackTelegramView($tg, $feedback, $index + 1),
                    protectContent: true
                );
            }

            $tg->stopConversation($entity);

            return $this->chooseActionChatSender->sendActions($tg);
        } catch (ValidatorException $exception) {
            if ($exception->isFirstProperty('search_term')) {
                $tg->reply($exception->getFirstMessage());

                return $this->querySearchTerm($tg);
            }

            return $tg->replyFail($tg->trans('reply.fail.unknown'))->null();
        } catch (CommandLimitExceeded $exception) {
            $tg->replyFail(
                $tg->trans('reply.limit_exceeded', [
                    'period' => sprintf('<b>1 %s</b>', $tg->trans($exception->getLimit()->getPeriod())),
                    'count' => sprintf('<b>%s</b>', $exception->getLimit()->getCount()),
                    'subscribe_command' => $tg->command('subscribe', html: true),
                ], domain: 'tg.search')
            );

            $tg->stopConversation($entity);

            return $this->chooseActionChatSender->sendActions($tg);
        }
    }

    public function queryCreateConfirm(TelegramAwareHelper $tg): null
    {
        $this->state->setStep(self::STEP_CREATE_CONFIRM_QUERIED);

        $searchTermText = $this->searchTermViewProvider->getSearchTermTelegramView($this->state->getSearchTerm());

        $buttons = [];

        $buttons[] = $this->getCreateConfirmYesButton($tg);
        $buttons[] = $this->getCreateConfirmNoButton($tg);

        $tg->replyUpset(
            join(' ', [
                $tg->trans('reply.empty_list', ['search_term' => sprintf('<u>%s</u>', $searchTermText)], domain: 'tg.search'),
                $tg->trans('query.create_confirm', domain: 'tg.search'),
            ]),
            keyboard: $tg->keyboard(...$buttons)
        );

        return null;
    }

    public static function getCreateConfirmYesButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.yes'));
    }

    public static function getCreateConfirmNoButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.no'));
    }

    public function gotCreateConfirm(TelegramAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchText($this->getCreateConfirmNoButton($tg)->getText())) {
            $tg->stopConversation($entity);
            $searchTermText = $this->searchTermViewProvider->getSearchTermTelegramView($this->state->getSearchTerm());
            $replyText = $tg->okText($tg->trans('reply.will_notify', ['search_term' => sprintf('<u>%s</u>', $searchTermText)], domain: 'tg.search'));

            return $this->chooseActionChatSender->sendActions($tg, $replyText);
        }
        if (!$tg->matchText($this->getCreateConfirmYesButton($tg)->getText())) {
            $tg->replyWrong($tg->trans('reply.wrong'));

            return $this->queryCreateConfirm($tg);
        }

        $this->state->setStep(self::STEP_CREATE_CONFIRMED);

        $tg->stopConversation($entity)->executeConversation(
            CreateFeedbackTelegramConversation::class,
            (new CreateFeedbackTelegramConversationState())
                ->setStep(CreateFeedbackTelegramConversation::STEP_RATING_QUERIED)
                ->setSearchTerm($this->state->getSearchTerm())
                ->setSearchTermStep(false),
            'queryRating'
        );

        return null;
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
        return $tg->button(sprintf('%s %s', '📝', $tg->trans('keyboard.change_search_term', domain: 'tg.search')));
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

    public function getStep(int $num): string
    {
        if ($this->state->isChange()) {
            return '';
        }

        return "[{$num}/1] ";
    }
}