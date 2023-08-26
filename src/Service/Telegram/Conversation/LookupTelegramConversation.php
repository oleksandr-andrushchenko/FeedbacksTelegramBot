<?php

declare(strict_types=1);

namespace App\Service\Telegram\Conversation;

use App\Entity\Telegram\LookupTelegramConversationState;
use App\Enum\Feedback\SearchTermType;
use App\Exception\CommandLimitExceeded;
use App\Exception\ValidatorException;
use App\Object\Feedback\FeedbackSearchSearchTransfer;
use App\Object\Feedback\SearchTermTransfer;
use App\Service\Feedback\FeedbackSearchSearchCreator;
use App\Service\Feedback\FeedbackSearchSearcher;
use App\Service\Feedback\SearchTerm\SearchTermParserOnlyInterface;
use App\Service\Feedback\View\FeedbackSearchTelegramViewProvider;
use App\Service\Feedback\View\SearchTermTelegramViewProvider;
use App\Service\Telegram\Chat\ChooseActionTelegramChatSender;
use App\Service\Telegram\TelegramAwareHelper;
use App\Entity\Telegram\TelegramConversation as Entity;
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
        private readonly Validator $validator,
        private readonly FeedbackSearchSearchCreator $creator,
        private readonly FeedbackSearchSearcher $searcher,
        private readonly SearchTermParserOnlyInterface $searchTermParser,
        private readonly EntityManagerInterface $entityManager,
        private readonly ChooseActionTelegramChatSender $chooseActionChatSender,
        private readonly SearchTermTelegramViewProvider $searchTermViewProvider,
        private readonly FeedbackSearchTelegramViewProvider $feedbackSearchViewProvider,
    )
    {
        parent::__construct(new LookupTelegramConversationState());
    }

    public function invoke(TelegramAwareHelper $tg, Entity $entity): null
    {
        return match ($this->state->getStep()) {
            default => $this->start($tg),
            self::STEP_SEARCH_TERM_QUERIED => $this->gotSearchTerm($tg, $entity),
            self::STEP_SEARCH_TERM_TYPE_QUERIED => $this->gotSearchTermType($tg, $entity),
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

        $tg->reply($tg->view('describe_lookup', [
            'limits' => $this->creator->getOptions()->getLimits(),
        ]));
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

    public function gotCancel(TelegramAwareHelper $tg, Entity $entity): null
    {
        $this->state->setStep(self::STEP_CANCEL_PRESSED);

        $tg->stopConversation($entity)->replyUpset($tg->trans('reply.canceled', domain: 'tg.lookup'));

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
            return $this->searchFeedbackSearches($tg, $entity);

            return $this->querySearchTermType($tg);
        }

        return $this->searchFeedbackSearches($tg, $entity);
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

    public function gotSearchTermType(TelegramAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchText($this->getBackButton($tg)->getText())) {
            return $this->querySearchTerm($tg);
        }
        if ($tg->matchText($this->getCancelButton($tg)->getText())) {
            return $this->gotCancel($tg, $entity);
        }
        if ($tg->matchText(null)) {
            $type = null;
        } else {
            $type = $this->getSearchTermTypeByButton($tg->getText(), $tg);
        }
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

        return $this->searchFeedbackSearches($tg, $entity);
    }

    public function searchFeedbackSearches(TelegramAwareHelper $tg, Entity $entity): null
    {
        try {
            $this->validator->validate($this->state->getSearchTerm());
            $this->validator->validate($this->state);

            $feedbackSearchSearch = $this->creator->createFeedbackSearchSearch(
                new FeedbackSearchSearchTransfer(
                    $entity->getMessengerUser(),
                    $this->state->getSearchTerm()
                )
            );
            $this->entityManager->flush();

            $feedbackSearches = $this->searcher->searchFeedbackSearches($feedbackSearchSearch);
            $count = count($feedbackSearches);

            $searchTermText = $this->searchTermViewProvider->getSearchTermTelegramView($this->state->getSearchTerm());

            if ($count === 0) {
                $tg->stopConversation($entity);
                $replyText = join(' ', [
                    $tg->upsetText($tg->trans('reply.empty_list', ['search_term' => sprintf('<u>%s</u>', $searchTermText)], domain: 'tg.lookup')),
                    $tg->okText($tg->trans('reply.will_notify', ['search_term' => sprintf('<u>%s</u>', $this->state->getSearchTerm()->getText())], domain: 'tg.lookup'))
                ]);

                return $this->chooseActionChatSender->sendActions($tg, $replyText);
            }

            $tg->reply($tg->trans('reply.title', ['search_term' => sprintf('<u>%s</u>', $searchTermText), 'count' => $count], domain: 'tg.lookup'));

            foreach ($feedbackSearches as $index => $feedbackSearch) {
                $tg->reply(
                    $this->feedbackSearchViewProvider->getFeedbackSearchTelegramView($tg, $feedbackSearch, $index + 1),
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
                ], domain: 'tg.lookup')
            );

            $tg->stopConversation($entity);

            return $this->chooseActionChatSender->sendActions($tg);
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

    public function getStep(int $num): string
    {
        return "[{$num}/1] ";
    }
}