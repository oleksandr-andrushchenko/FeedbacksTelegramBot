<?php

declare(strict_types=1);

namespace App\Service\Telegram\Conversation;

use App\Entity\Telegram\LookupTelegramConversationState;
use App\Enum\Feedback\SearchTermType;
use App\Exception\ValidatorException;
use App\Object\Feedback\FeedbackSearchSearchTransfer;
use App\Object\Feedback\SearchTermTransfer;
use App\Service\Feedback\FeedbackSearchSearchCreator;
use App\Service\Feedback\FeedbackSearchSearcher;
use App\Service\Feedback\FeedbackSubscriptionManager;
use App\Service\Feedback\SearchTerm\SearchTermParserOnlyInterface;
use App\Service\Feedback\View\FeedbackSearchTelegramViewProvider;
use App\Service\Feedback\View\SearchTermTelegramViewProvider;
use App\Service\Telegram\Chat\ChooseActionTelegramChatSender;
use App\Service\Telegram\TelegramAwareHelper;
use App\Entity\Telegram\TelegramConversation as Conversation;
use App\Service\Validator;
use Doctrine\ORM\EntityManagerInterface;
use Longman\TelegramBot\Entities\KeyboardButton;

/**
 * @property LookupTelegramConversationState $state
 */
class LookupTelegramConversation extends TelegramConversation implements TelegramConversationInterface
{
    public const STEP_SEARCH_TERM_QUERIED = 10;
    public const STEP_SEARCH_TERM_TYPE_QUERIED = 20;
    public const STEP_CANCEL_PRESSED = 30;

    public function __construct(
        readonly TelegramAwareHelper $awareHelper,
        private readonly Validator $validator,
        private readonly FeedbackSearchSearchCreator $creator,
        private readonly FeedbackSearchSearcher $searcher,
        private readonly SearchTermParserOnlyInterface $searchTermParser,
        private readonly EntityManagerInterface $entityManager,
        private readonly ChooseActionTelegramChatSender $chooseActionChatSender,
        private readonly FeedbackSubscriptionManager $subscriptionManager,
        private readonly SearchTermTelegramViewProvider $searchTermViewProvider,
        private readonly FeedbackSearchTelegramViewProvider $feedbackSearchViewProvider,
    )
    {
        parent::__construct($awareHelper, new LookupTelegramConversationState());
    }

    public function invoke(TelegramAwareHelper $tg, Conversation $conversation): null
    {
        if ($this->state->getStep() === null) {
            $this->describe($tg);

            // todo: after this - finalize Payments requirements
            $this->replyCurrentSubscription($tg);

            if (!$this->subscriptionManager->hasActiveSubscription($tg->getTelegram()->getMessengerUser())) {
                $tg->stopConversation($conversation);

                return $this->chooseActionChatSender->sendActions($tg);
            }

            return $this->querySearchTerm($tg);
        }

        if ($tg->matchText(null)) {
            return $tg->replyWrong($tg->trans('reply.wrong'))->null();
        }

        if ($tg->matchText($this->getBackButton($tg)->getText())) {
            if ($this->state->getStep() === self::STEP_SEARCH_TERM_TYPE_QUERIED) {
                return $this->querySearchTerm($tg);
            }
        }

        if ($tg->matchText($this->getCancelButton($tg)->getText())) {
            $this->state->setStep(self::STEP_CANCEL_PRESSED);

            $tg->stopConversation($conversation)->replyUpset($tg->trans('reply.canceled', domain: 'tg.lookup'));

            return $this->chooseActionChatSender->sendActions($tg);
        }

        if ($this->state->getStep() === self::STEP_SEARCH_TERM_QUERIED) {
            return $this->gotSearchTerm($tg, $conversation);
        }

        if ($this->state->getStep() === self::STEP_SEARCH_TERM_TYPE_QUERIED) {
            return $this->gotSearchTermType($tg, $conversation);
        }

        return null;
    }

    public function describe(TelegramAwareHelper $tg): void
    {
        if (!$tg->getTelegram()->getMessengerUser()->showHints()) {
            return;
        }

        $tg->reply($tg->view('describe_lookup', [
            'accept_payments' => $tg->getTelegram()->getBot()->acceptPayments(),
        ]));
    }

    public function replyCurrentSubscription(TelegramAwareHelper $tg): void
    {
        if (!$this->subscriptionManager->hasActiveSubscription($tg->getTelegram()->getMessengerUser())) {
            $tg->reply($tg->trans('reply.no_active_subscription', [
                'subscribe_command' => $tg->command('subscribe', html: true),
            ], domain: 'tg.lookup'));
        }
    }

    public function querySearchTerm(TelegramAwareHelper $tg): null
    {
        $this->state->setStep(self::STEP_SEARCH_TERM_QUERIED);

        $buttons = [];
        $buttons[] = $this->getCancelButton($tg);

        $tg->reply(
            $this->getStep(1) . $tg->trans('query.search_term', domain: 'tg.lookup'),
            $tg->keyboard(...$buttons)
        );

        return null;
    }

    public function gotSearchTerm(TelegramAwareHelper $tg, Conversation $conversation): null
    {
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

        return $this->searchFeedbackSearches($tg, $conversation);
    }

    public function querySearchTermType(TelegramAwareHelper $tg): null
    {
        $this->state->setStep(self::STEP_SEARCH_TERM_TYPE_QUERIED);

        $possibleTypes = $this->state->getSearchTerm()->getPossibleTypes();
        $sortedPossibleTypes = SearchTermType::sort($possibleTypes);

        $tg->reply(
            $tg->trans('query.search_term_type', ['search_term' => sprintf('<u>%s</u>', $this->state->getSearchTerm()->getText())], domain: 'tg.lookup'),
            $tg->keyboard(...[
                ...$this->getSearchTermTypeButtons($sortedPossibleTypes, $tg),
                $this->getBackButton($tg),
                $this->getCancelButton($tg),
            ])
        );

        return null;
    }

    public function gotSearchTermType(TelegramAwareHelper $tg, Conversation $conversation): null
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

        return $this->searchFeedbackSearches($tg, $conversation);
    }

    public function searchFeedbackSearches(TelegramAwareHelper $tg, Conversation $conversation): null
    {
        try {
            $this->validator->validate($this->state->getSearchTerm());
            $this->validator->validate($this->state);

            $feedbackSearchSearch = $this->creator->createFeedbackSearchSearch(
                new FeedbackSearchSearchTransfer(
                    $conversation->getMessengerUser(),
                    $this->state->getSearchTerm()
                )
            );
            $this->entityManager->flush();

            $feedbackSearches = $this->searcher->searchFeedbackSearches($feedbackSearchSearch);
            $count = count($feedbackSearches);

            $searchTermText = $this->searchTermViewProvider->getSearchTermTelegramView($this->state->getSearchTerm());

            if ($count === 0) {
                $tg->stopConversation($conversation)
                    ->replyUpset($tg->trans('reply.empty_list', ['search_term' => sprintf('<u>%s</u>', $searchTermText)], domain: 'tg.lookup'))
                ;

                return $this->chooseActionChatSender->sendActions($tg);
            }

            $tg->reply($tg->trans('reply.title', ['search_term' => sprintf('<u>%s</u>', $searchTermText), 'count' => $count], domain: 'tg.lookup'));

            foreach ($feedbackSearches as $index => $feedbackSearch) {
                $tg->reply(
                    $this->feedbackSearchViewProvider->getFeedbackSearchTelegramView($tg, $feedbackSearch, $index + 1),
                    protectContent: true
                );
            }

            $tg->stopConversation($conversation);

            return $this->chooseActionChatSender->sendActions($tg);
        } catch (ValidatorException $exception) {
            if ($exception->isFirstProperty('search_term')) {
                $tg->reply($exception->getFirstMessage());

                return $this->querySearchTerm($tg);
            }

            return $tg->replyFail($tg->trans('reply.fail.unknown'))->null();
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
        return "[{$num}/1] ";
    }
}