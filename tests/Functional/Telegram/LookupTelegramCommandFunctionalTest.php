<?php

declare(strict_types=1);

namespace App\Tests\Functional\Telegram;

use App\Entity\Feedback\FeedbackSearch;
use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\LookupTelegramConversationState;
use App\Entity\Telegram\TelegramBot;
use App\Entity\User\User;
use App\Enum\Feedback\SearchTermType;
use App\Object\Feedback\SearchTermTransfer;
use App\Object\Messenger\MessengerUserTransfer;
use App\Service\Telegram\Channel\FeedbackTelegramChannel;
use App\Service\Telegram\Conversation\LookupTelegramConversation;
use App\Tests\Fixtures;
use App\Tests\Traits\Feedback\FeedbackSearchSearchRepositoryProviderTrait;
use DateTimeImmutable;
use Generator;

class LookupTelegramCommandFunctionalTest extends TelegramCommandFunctionalTestCase
{
    use FeedbackSearchSearchRepositoryProviderTrait;

    /**
     * @param string $command
     * @param bool $showHints
     * @return void
     * @dataProvider startSuccessDataProvider
     */
    public function testStartSuccess(string $command, bool $showHints): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
        ]);
        $this->getUpdateMessengerUser()->getUser()->setSubscriptionExpireAt(new DateTimeImmutable('+1 month'));
        $this->getUpdateMessengerUser()->setIsShowHints($showHints);

        if ($showHints) {
            $shouldReply = [
                'describe.title',
                'describe.limits',
                'describe.subscribe',
                'toggle_hints',
            ];
        } else {
            $shouldReply = [];
        }

        $this
            ->type($command)
            ->shouldSeeActiveConversation(
                LookupTelegramConversation::class,
                (new LookupTelegramConversationState())
                    ->setStep(LookupTelegramConversation::STEP_SEARCH_TERM_QUERIED)
            )
            ->shouldSeeReply(
                ...$shouldReply,
                ...['query.search_term']
            )
            ->shouldSeeButtons('keyboard.cancel')
        ;
    }

    public function startSuccessDataProvider(): Generator
    {
        yield 'button & no hints' => [
            'command' => 'icon.lookup command.lookup',
            'showHints' => false,
        ];

        yield 'button & hints' => [
            'command' => 'icon.lookup command.lookup',
            'showHints' => true,
        ];

        yield 'command & no hints' => [
            'command' => FeedbackTelegramChannel::LOOKUP,
            'showHints' => false,
        ];

        yield 'command & hints' => [
            'command' => FeedbackTelegramChannel::LOOKUP,
            'showHints' => true,
        ];
    }

    /**
     * @param LookupTelegramConversationState $state
     * @param string $command
     * @param array $shouldSeeReplyFeedbackSearches
     * @return void
     * @dataProvider gotSearchTermWithKnownTypeSuccessDataProvider
     */
    public function testGotSearchTermWithKnownTypeSuccess(
        LookupTelegramConversationState $state,
        string $command,
        array $shouldSeeReplyFeedbackSearches
    ): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
            FeedbackSearch::class,
        ]);

        $this->createConversation(LookupTelegramConversation::class, $state);

        $shouldSeeReply = [];

        $count = count($shouldSeeReplyFeedbackSearches);
        if ($count === 0) {
            $shouldSeeReply[] = 'reply.empty_list';
        } else {
            $shouldSeeReply[] = 'reply.title';
            $shouldSeeReply = array_merge($shouldSeeReply, array_fill(0, count($shouldSeeReplyFeedbackSearches), $this->getFeedbackSearchReply()));
        }

        $feedbackSearchSearchRepository = $this->getFeedbackSearchSearchRepository();
        $previousFeedbackSearchSearchCount = $feedbackSearchSearchRepository->count([]);

        $this
            ->type($command)
            ->shouldNotSeeActiveConversation()
            ->shouldSeeReply(...$shouldSeeReply)
            ->shouldSeeChooseAction()
        ;

        $this->assertEquals($previousFeedbackSearchSearchCount + 1, $feedbackSearchSearchRepository->count([]));

        $feedbackSearchSearch = $feedbackSearchSearchRepository->findOneBy([
            'messengerUser' => $this->getUpdateMessengerUser(),
        ]);

        $this->assertNotNull($feedbackSearchSearch);
    }

    public function gotSearchTermWithKnownTypeSuccessDataProvider(): Generator
    {
        yield 'unknown & empty' => [
            'state' => (new LookupTelegramConversationState())
                ->setStep(LookupTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
                ->setSearchTerm(new SearchTermTransfer('any')),
            'command' => 'search_term_type.unknown',
            'shouldSeeReplyFeedbacks' => [],
        ];

        yield 'instagram username & not empty' => [
            'state' => (new LookupTelegramConversationState())
                ->setStep(LookupTelegramConversation::STEP_SEARCH_TERM_QUERIED),
            'command' => $this->getMessengerUserProfileUrl(Fixtures::getInstagramMessengerUserTransferFixture(3)),
            'shouldSeeReplyFeedbackSearches' => [1],
        ];
    }

    private function getFeedbackSearchReply(): string
    {
        return 'somebody_from';
    }

    /**
     * @param string $command
     * @param LookupTelegramConversationState $state
     * @param array $shouldSeeReplyFeedbackSearches
     * @return void
     * @dataProvider gotSearchTermTypeSuccessDataProvider
     */
    public function testGotSearchTermTypeSuccess(
        string $command,
        LookupTelegramConversationState $state,
        array $shouldSeeReplyFeedbackSearches
    ): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
            FeedbackSearch::class,
        ]);

        $this->createConversation(LookupTelegramConversation::class, $state);

        $shouldSeeReply = [];

        $count = count($shouldSeeReplyFeedbackSearches);
        if ($count === 0) {
            $shouldSeeReply[] = 'reply.empty_list';
        } else {
            $shouldSeeReply[] = 'reply.title';
            $shouldSeeReply = array_merge($shouldSeeReply, array_fill(0, count($shouldSeeReplyFeedbackSearches), $this->getFeedbackSearchReply()));
        }

        $feedbackSearchSearchRepository = $this->getFeedbackSearchSearchRepository();
        $previousFeedbackSearchSearchCount = $feedbackSearchSearchRepository->count([]);

        $this
            ->type($command)
            ->shouldNotSeeActiveConversation()
            ->shouldSeeReply(...$shouldSeeReply)
            ->shouldSeeChooseAction()
        ;

        $this->assertEquals($previousFeedbackSearchSearchCount + 1, $feedbackSearchSearchRepository->count([]));

        $feedbackSearchSearch = $feedbackSearchSearchRepository->findOneBy([
            'messengerUser' => $this->getUpdateMessengerUser(),
        ]);

        $this->assertNotNull($feedbackSearchSearch);
    }

    public function gotSearchTermTypeSuccessDataProvider(): Generator
    {
        $searchTermTypes = SearchTermType::cases();
        // todo: update
        $messengerSearchTermTypes = $searchTermTypes;

        /** @var MessengerUserTransfer $messengerUser */

        // unknown messenger profile url
        yield 'messenger profile url & empty' => [
            'command' => sprintf('search_term_type.%s', SearchTermType::messenger_profile_url->name),
            'state' => (new LookupTelegramConversationState())
                ->setStep(LookupTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
                ->setSearchTerm(
                    (new SearchTermTransfer('https://unknown.com/me'))
                        ->setPossibleTypes($searchTermTypes)
                ),
            'shouldSeeReplyFeedbackSearches' => [],
        ];

        // messenger usernames
        foreach (Fixtures::getMessengerUserUsernames() as $commandKey => [$messengerUser, $searchTermType]) {
            yield sprintf('%s username & empty', $commandKey) => [
                'command' => sprintf('search_term_type.%s', $searchTermType->name),
                'state' => (new LookupTelegramConversationState())
                    ->setStep(LookupTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
                    ->setSearchTerm(
                        (new SearchTermTransfer($messengerUser->getUsername()))
                            ->setPossibleTypes($searchTermTypes)
                    ),
                'shouldSeeReplyFeedbackSearches' => [],
            ];
        }

        $instagramMessengerUser = Fixtures::getInstagramMessengerUserTransferFixture(3);

        yield 'instagram username & not empty' => [
            'command' => sprintf('search_term_type.%s', SearchTermType::instagram_username->name),
            'state' => (new LookupTelegramConversationState())
                ->setStep(LookupTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
                ->setSearchTerm(
                    (new SearchTermTransfer($instagramMessengerUser->getUsername()))
                        ->setPossibleTypes($messengerSearchTermTypes)
                ),
            'shouldSeeReplyFeedbackSearches' => [1],
        ];

        // unknown messenger username
        yield 'messenger username & empty' => [
            'command' => sprintf('search_term_type.%s', SearchTermType::messenger_username->name),
            'state' => (new LookupTelegramConversationState())
                ->setStep(LookupTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
                ->setSearchTerm(
                    (new SearchTermTransfer('me'))
                        ->setPossibleTypes($searchTermTypes)
                ),
            'shouldSeeReplyFeedbackSearches' => [],
        ];

        // non-messengers
        foreach (Fixtures::NON_MESSENGER_SEARCH_TYPES as $typeKey => [$searchTermType, $searchTermText, $searchTermNormalizedText]) {
            yield sprintf('%s & empty', $typeKey) => [
                'command' => sprintf('search_term_type.%s', $searchTermType->name),
                'state' => (new LookupTelegramConversationState())
                    ->setStep(LookupTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
                    ->setSearchTerm(
                        (new SearchTermTransfer($searchTermText))
                            ->setPossibleTypes($searchTermTypes)
                    ),
                'shouldSeeReplyFeedbackSearches' => [],
            ];
        }
    }

    /**
     * @param int $step
     * @return void
     * @dataProvider gotCancelSuccessDataProvider
     */
    public function testGotCancelSuccess(int $step): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
        ]);
        $state = (new LookupTelegramConversationState())
            ->setStep($step)
        ;
        $expectedState = (clone $state)
            ->setStep(LookupTelegramConversation::STEP_CANCEL_PRESSED)
        ;
        $conversation = $this->createConversation(LookupTelegramConversation::class, $state);

        $this
            ->type('keyboard.cancel')
            ->shouldNotSeeActiveConversation($conversation->getClass(), $expectedState)
            ->shouldSeeReply('reply.canceled')
            ->shouldSeeChooseAction()
        ;
    }

    public function gotCancelSuccessDataProvider(): Generator
    {
        yield 'search term queried' => [
            'step' => LookupTelegramConversation::STEP_SEARCH_TERM_QUERIED,
        ];

        yield 'search term type queried' => [
            'step' => LookupTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED,
        ];
    }
}
