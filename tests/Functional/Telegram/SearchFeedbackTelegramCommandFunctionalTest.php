<?php

declare(strict_types=1);

namespace App\Tests\Functional\Telegram;

use App\Entity\Feedback\Feedback;
use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\SearchFeedbackTelegramConversationState;
use App\Entity\Telegram\TelegramBot;
use App\Entity\Telegram\TelegramConversationState;
use App\Entity\User\User;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Messenger\Messenger;
use App\Object\Feedback\SearchTermTransfer;
use App\Object\Messenger\MessengerUserTransfer;
use App\Service\Telegram\Channel\FeedbackTelegramChannel;
use App\Service\Telegram\Conversation\SearchFeedbackTelegramConversation;
use App\Service\Telegram\TelegramAwareHelper;
use App\Tests\Fixtures;
use App\Tests\Traits\Feedback\FeedbackSearchRepositoryProviderTrait;
use Generator;

class SearchFeedbackTelegramCommandFunctionalTest extends TelegramCommandFunctionalTestCase
{
    use FeedbackSearchRepositoryProviderTrait;

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
                SearchFeedbackTelegramConversation::class,
                (new SearchFeedbackTelegramConversationState())
                    ->setStep(SearchFeedbackTelegramConversation::STEP_SEARCH_TERM_QUERIED)
            )
            ->shouldSeeReply(
                ...$shouldReply,
                ...[
                    'query.search_term',
                ]
            )
            ->shouldSeeButtons(
                'keyboard.cancel',
            )
        ;
    }

    public function startSuccessDataProvider(): Generator
    {
        yield 'button & no hints' => [
            'command' => 'icon.search command.search',
            'showHints' => false,
        ];

        yield 'button & hints' => [
            'command' => 'icon.search command.search',
            'showHints' => true,
        ];

        yield 'command & no hints' => [
            'command' => FeedbackTelegramChannel::SEARCH,
            'showHints' => false,
        ];

        yield 'command & hints' => [
            'command' => FeedbackTelegramChannel::SEARCH,
            'showHints' => true,
        ];
    }

    /**
     * @param callable $fn
     * @return void
     * @dataProvider gotSearchTermSuccessDataProvider
     */
    public function testGotSearchTermSuccess(callable $fn): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
        ]);

        extract($fn($this->getTg()));

        $state->setStep(SearchFeedbackTelegramConversation::STEP_SEARCH_TERM_QUERIED);
        $conversation = $this->createConversation(SearchFeedbackTelegramConversation::class, $state);

        $mocks && $mocks();

        $this
            ->type($command)
            ->shouldSeeActiveConversation($conversation->getClass(), $expectedState)
            ->shouldSeeReply(...$shouldSeeReply)
            ->shouldSeeButtons(...$shouldSeeButtons)
        ;
    }

    public function gotSearchTermSuccessDataProvider(): Generator
    {
        $state = (new SearchFeedbackTelegramConversationState())
            ->setStep(SearchFeedbackTelegramConversation::STEP_SEARCH_TERM_QUERIED)
        ;

        // messenger profile urls
        foreach (Fixtures::getMessengerUserProfileUrls() as $commandKey => [$messengerUser, $expectedSearchTermType]) {
            yield sprintf('%s profile url', $commandKey) => [
                fn ($tg) => [
                    'command' => $this->getMessengerUserProfileUrl($messengerUser),
                    'mocks' => null,
                    'state' => clone $state,
                    'expectedState' => (clone $state)
                        ->setStep(SearchFeedbackTelegramConversation::STEP_CONFIRM_QUERIED)
                        ->setSearchTerm(
                            $this->addSearchTermPossibleTypes($this->getMessengerProfileUrlSearchTerm($messengerUser))
                                ->setType($expectedSearchTermType)
                                ->setMessengerUser(null)
                        ),
                    'shouldSeeReply' => $this->getShouldSeeReplyOnConfirmQueried(),
                    'shouldSeeButtons' => $this->getShouldSeeKeyboardOnConfirmQueried(),
                ],
            ];
        }

        // normalized phone number
        yield sprintf('normalized %s', SearchTermType::phone_number->name) => [
            fn ($tg) => [
                'command' => $command = '15613145672',
                'mocks' => null,
                'state' => clone $state,
                'expectedState' => (clone $state)
                    ->setStep(SearchFeedbackTelegramConversation::STEP_CONFIRM_QUERIED)
                    ->setSearchTerm(
                        $this->addSearchTermPossibleTypes(new SearchTermTransfer($command))
                            ->setType(SearchTermType::phone_number)
                    ),
                'shouldSeeReply' => $this->getShouldSeeReplyOnConfirmQueried(),
                'shouldSeeButtons' => $this->getShouldSeeKeyboardOnConfirmQueried(),
            ],
        ];

        // normalized email
        yield sprintf('normalized %s', SearchTermType::email->name) => [
            fn ($tg) => [
                'command' => $command = 'example@gmail.com',
                'mocks' => null,
                'state' => clone $state,
                'expectedState' => (clone $state)
                    ->setStep(SearchFeedbackTelegramConversation::STEP_CONFIRM_QUERIED)
                    ->setSearchTerm(
                        $this->addSearchTermPossibleTypes(new SearchTermTransfer($command))
                            ->setType(SearchTermType::email),
                    ),
                'shouldSeeReply' => $this->getShouldSeeReplyOnConfirmQueried(),
                'shouldSeeButtons' => $this->getShouldSeeKeyboardOnConfirmQueried(),
            ],
        ];

        yield from $this->gotUnknownSearchTermSuccessDataProvider('search_term_step', $state);
    }

    public function gotUnknownSearchTermSuccessDataProvider(string $key, SearchFeedbackTelegramConversationState $state): Generator
    {
        /** @var MessengerUserTransfer $messengerUser */

        // unknown messenger profile url
        yield sprintf('%s & messenger profile url', $key) => [
            fn ($tg) => [
                'command' => $command = 'https://unknown.com/me',
                'mocks' => null,
                'state' => clone $state,
                'expectedState' => $expectedState = (clone $state)
                    ->setStep(SearchFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
                    ->setSearchTerm(
                        $this->addSearchTermPossibleTypes(new SearchTermTransfer($command), SearchTermType::messenger_profile_url)
                    ),
                'shouldSeeReply' => $this->getShouldSeeReplyOnSearchTermTypeQueried(),
                'shouldSeeButtons' => $this->getShouldSeeKeyboardOnSearchTermTypeQueried($tg, $expectedState),
            ],
        ];

        // messenger usernames
        foreach (Fixtures::getMessengerUserUsernames() as $commandKey => [$messengerUser, $expectedSearchTermPossibleType]) {
            yield sprintf('%s & %s username', $key, $commandKey) => [
                fn ($tg) => [
                    'command' => $command = $messengerUser->getUsername(),
                    'mocks' => null,
                    'state' => clone $state,
                    'expectedState' => $expectedState = (clone $state)
                        ->setStep(SearchFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
                        ->setSearchTerm(
                            $this->addSearchTermPossibleTypes(new SearchTermTransfer($command), $expectedSearchTermPossibleType)
                        ),
                    'shouldSeeReply' => $this->getShouldSeeReplyOnSearchTermTypeQueried(),
                    'shouldSeeButtons' => $this->getShouldSeeKeyboardOnSearchTermTypeQueried($tg, $expectedState),
                ],
            ];
        }

        // unknown messenger username
        yield sprintf('%s & messenger username', $key) => [
            fn ($tg) => [
                'command' => $command = 'me',
                'mocks' => null,
                'state' => clone $state,
                'expectedState' => $expectedState = (clone $state)
                    ->setStep(SearchFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
                    ->setSearchTerm(
                        $this->addSearchTermPossibleTypes(new SearchTermTransfer($command), SearchTermType::messenger_username)
                    ),
                'shouldSeeReply' => $this->getShouldSeeReplyOnSearchTermTypeQueried(),
                'shouldSeeButtons' => $this->getShouldSeeKeyboardOnSearchTermTypeQueried($tg, $expectedState),
            ],
        ];

        // person names
        foreach (Fixtures::PERSONS as $personKey => $personName) {
            yield sprintf('%s & person name & %s', $key, $personKey) => [
                fn ($tg) => [
                    'command' => $personName,
                    'mocks' => null,
                    'state' => clone $state,
                    'expectedState' => $expectedState = (clone $state)
                        ->setStep(SearchFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
                        ->setSearchTerm(
                            (new SearchTermTransfer($personName))
                                ->setPossibleTypes([
                                    SearchTermType::person_name,
                                    SearchTermType::organization_name,
                                    SearchTermType::place_name,
                                    SearchTermType::unknown,
                                ])
                        ),
                    'shouldSeeReply' => $this->getShouldSeeReplyOnSearchTermTypeQueried(),
                    'shouldSeeButtons' => $this->getShouldSeeKeyboardOnSearchTermTypeQueried($tg, $expectedState),
                ],
            ];
        }

        // place names
        foreach (Fixtures::PLACES as $placeKey => $placeName) {
            yield sprintf('%s & place name & %s', $key, $placeKey) => [
                fn ($tg) => [
                    'command' => $placeName,
                    'mocks' => null,
                    'state' => clone $state,
                    'expectedState' => $expectedState = (clone $state)
                        ->setStep(SearchFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
                        ->setSearchTerm(
                            (new SearchTermTransfer($placeName))
                                ->setPossibleTypes([
                                    SearchTermType::organization_name,
                                    SearchTermType::place_name,
                                    SearchTermType::unknown,
                                ])
                        ),
                    'shouldSeeReply' => $this->getShouldSeeReplyOnSearchTermTypeQueried(),
                    'shouldSeeButtons' => $this->getShouldSeeKeyboardOnSearchTermTypeQueried($tg, $expectedState),
                ],
            ];
        }

        // organizations names
        foreach (Fixtures::ORGANIZATIONS as $orgKey => $orgName) {
            yield sprintf('%s & organization name & %s', $key, $orgKey) => [
                fn ($tg) => [
                    'command' => $orgName,
                    'mocks' => null,
                    'state' => clone $state,
                    'expectedState' => $expectedState = (clone $state)
                        ->setStep(SearchFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
                        ->setSearchTerm(
                            (new SearchTermTransfer($orgName))
                                ->setPossibleTypes([
                                    SearchTermType::organization_name,
                                    SearchTermType::unknown,
                                ])
                        ),
                    'shouldSeeReply' => $this->getShouldSeeReplyOnSearchTermTypeQueried(),
                    'shouldSeeButtons' => $this->getShouldSeeKeyboardOnSearchTermTypeQueried($tg, $expectedState),
                ],
            ];
        }

        // phone number
        yield sprintf('%s & %s', $key, SearchTermType::phone_number->name) => [
            fn ($tg) => [
                'command' => $command = '+1 (561) 314-5672',
                'mocks' => null,
                'state' => clone $state,
                'expectedState' => $expectedState = (clone $state)
                    ->setStep(SearchFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
                    ->setSearchTerm(
                        (new SearchTermTransfer($command))
                            ->setPossibleTypes([
                                SearchTermType::phone_number,
                                SearchTermType::unknown,
                            ])
                    ),
                'shouldSeeReply' => $this->getShouldSeeReplyOnSearchTermTypeQueried(),
                'shouldSeeButtons' => $this->getShouldSeeKeyboardOnSearchTermTypeQueried($tg, $expectedState),
            ],
        ];

        // email
        yield sprintf('%s & %s', $key, SearchTermType::email->name) => [
            fn ($tg) => [
                'command' => $command = 'example+123@gma//il.com',
                'mocks' => null,
                'state' => clone $state,
                'expectedState' => $expectedState = (clone $state)
                    ->setStep(SearchFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
                    ->setSearchTerm(
                        (new SearchTermTransfer($command))
                            ->setPossibleTypes([
                                SearchTermType::email,
                                SearchTermType::unknown,
                            ])
                    ),
                'shouldSeeReply' => $this->getShouldSeeReplyOnSearchTermTypeQueried(),
                'shouldSeeButtons' => $this->getShouldSeeKeyboardOnSearchTermTypeQueried($tg, $expectedState),
            ],
        ];
    }

    /**
     * @param callable $fn
     * @return void
     * @dataProvider gotSearchTermChangeSuccessDataProvider
     */
    public function testGotSearchTermChangeSuccess(callable $fn): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
        ]);

        extract($fn($this->getTg()));

        $state
            ->setStep(SearchFeedbackTelegramConversation::STEP_SEARCH_TERM_QUERIED)
            ->setChange(true)
        ;

        $conversation = $this->createConversation(SearchFeedbackTelegramConversation::class, $state);

        $mocks && $mocks($this);

        $this
            ->type($command)
            ->shouldSeeActiveConversation($conversation->getClass(), $expectedState)
            ->shouldSeeReply(...$shouldSeeReply)
            ->shouldSeeButtons(...$shouldSeeButtons)
        ;
    }

    public function gotSearchTermChangeSuccessDataProvider(): Generator
    {
        $state = (new SearchFeedbackTelegramConversationState())
            ->setStep(SearchFeedbackTelegramConversation::STEP_SEARCH_TERM_QUERIED)
            ->setChange(true)
            ->setSearchTerm(new SearchTermTransfer('any'))
        ;

        // network messenger profile urls
        foreach (Fixtures::getNetworkMessengerUserProfileUrls() as $commandKey => [$messengerUser, $expectedSearchTermType, $mocks]) {
            yield sprintf('change_search_term_step_as_%s', $commandKey) => [
                fn ($tg) => [
                    'command' => $this->getMessengerUserProfileUrl($messengerUser),
                    'mocks' => $mocks,
                    'state' => clone $state,
                    'expectedState' => (clone $state)
                        ->setStep(SearchFeedbackTelegramConversation::STEP_CONFIRM_QUERIED)
                        ->setChange(false)
                        ->setSearchTerm(
                            $this->addSearchTermPossibleTypes(
                                $this->getMessengerProfileUrlSearchTerm($messengerUser)
                            )->setType($expectedSearchTermType)
                        ),
                    'shouldSeeReply' => $this->getShouldSeeReplyOnConfirmQueried(),
                    'shouldSeeButtons' => $this->getShouldSeeKeyboardOnConfirmQueried(),
                ],
            ];
        }

        // non-network messenger profile urls
        foreach (Fixtures::getNonNetworkMessengerUserProfileUrls() as $commandKey => [$messengerUser, $expectedSearchTermType]) {
            yield sprintf('change_search_term_step_as_%s', $commandKey) => [
                fn ($tg) => [
                    'command' => $this->getMessengerUserProfileUrl($messengerUser),
                    'mocks' => null,
                    'state' => clone $state,
                    'expectedState' => (clone $state)
                        ->setStep(SearchFeedbackTelegramConversation::STEP_CONFIRM_QUERIED)
                        ->setChange(false)
                        ->setSearchTerm(
                            $this->addSearchTermPossibleTypes(
                                $this->getMessengerProfileUrlSearchTerm($messengerUser)
                            )->setType($expectedSearchTermType)
                                ->setMessengerUser(null)
                        ),
                    'shouldSeeReply' => $this->getShouldSeeReplyOnConfirmQueried(),
                    'shouldSeeButtons' => $this->getShouldSeeKeyboardOnConfirmQueried(),
                ],
            ];
        }

        yield from $this->gotUnknownSearchTermSuccessDataProvider('change_search_term_step', $state);
    }

    /**
     * @param string $command
     * @param SearchFeedbackTelegramConversationState $state
     * @param SearchFeedbackTelegramConversationState $expectedState
     * @return void
     * @dataProvider gotSearchTermTypeSuccessDataProvider
     */
    public function testGotSearchTermTypeSuccess(
        string $command,
        SearchFeedbackTelegramConversationState $state,
        SearchFeedbackTelegramConversationState $expectedState
    ): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
        ]);
        $state->setStep(SearchFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED);
        $conversation = $this->createConversation(SearchFeedbackTelegramConversation::class, $state);

        $this
            ->type($command)
            ->shouldSeeActiveConversation($conversation->getClass(), $expectedState)
            ->shouldSeeReply(...$this->getShouldSeeReplyOnConfirmQueried())
            ->shouldSeeButtons(...$this->getShouldSeeKeyboardOnConfirmQueried())
        ;
    }

    public function gotSearchTermTypeSuccessDataProvider(): Generator
    {
        $searchTermTypes = SearchTermType::cases();

        /** @var MessengerUserTransfer $messengerUser */

        // unknown messenger profile url
        yield 'messenger profile url' => [
            'command' => sprintf('search_term_type.%s', SearchTermType::messenger_profile_url->name),
            'state' => $state = (new SearchFeedbackTelegramConversationState())
                ->setStep(SearchFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
                ->setSearchTerm(
                    $searchTerm = (new SearchTermTransfer('https://unknown.com/me'))
                        ->setPossibleTypes($searchTermTypes)
                ),
            'expectedState' => (clone $state)
                ->setStep(SearchFeedbackTelegramConversation::STEP_CONFIRM_QUERIED)
                ->setSearchTerm(
                    (clone $searchTerm)
                        ->setType(SearchTermType::messenger_username)
                        ->setNormalizedText('me')
                        ->setMessenger(Messenger::unknown)
                        ->setMessengerUsername('me')
                        ->setMessengerProfileUrl($searchTerm->getText())
                ),
        ];

        // messenger usernames
        foreach (Fixtures::getMessengerUserUsernames() as $commandKey => [$messengerUser, $searchTermType]) {
            yield sprintf('%s username', $commandKey) => [
                'command' => sprintf('search_term_type.%s', $searchTermType->name),
                'state' => $state = (new SearchFeedbackTelegramConversationState())
                    ->setStep(SearchFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
                    ->setSearchTerm(
                        $searchTerm = (new SearchTermTransfer($messengerUser->getUsername()))
                            ->setPossibleTypes($searchTermTypes)
                    ),
                'expectedState' => (clone $state)
                    ->setStep(SearchFeedbackTelegramConversation::STEP_CONFIRM_QUERIED)
                    ->setSearchTerm(
                        $this->getMessengerUsernameSearchTerm($messengerUser)
                            ->setType($searchTermType)
                            ->setMessengerUser(null)
                            ->setPossibleTypes($searchTerm->getPossibleTypes())
                    ),
            ];
        }

        // unknown messenger username
        yield 'messenger username' => [
            'command' => sprintf('search_term_type.%s', SearchTermType::messenger_username->name),
            'state' => $state = (new SearchFeedbackTelegramConversationState())
                ->setStep(SearchFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
                ->setSearchTerm(
                    $searchTerm = (new SearchTermTransfer('me'))
                        ->setPossibleTypes($searchTermTypes)
                ),
            'expectedState' => (clone $state)
                ->setStep(SearchFeedbackTelegramConversation::STEP_CONFIRM_QUERIED)
                ->setSearchTerm(
                    (clone $searchTerm)
                        ->setType(SearchTermType::messenger_username)
                        ->setMessenger(Messenger::unknown)
                        ->setMessengerUsername('me')
                ),
        ];

        // non-messengers
        foreach (Fixtures::NON_MESSENGER_SEARCH_TYPES as $typeKey => [$searchTermType, $searchTermText, $searchTermNormalizedText]) {
            yield sprintf('%s', $typeKey) => [
                'command' => sprintf('search_term_type.%s', $searchTermType->name),
                'state' => $state = (new SearchFeedbackTelegramConversationState())
                    ->setStep(SearchFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
                    ->setSearchTerm(
                        $searchTerm = (new SearchTermTransfer($searchTermText))
                            ->setPossibleTypes($searchTermTypes)
                    ),
                'expectedState' => (clone $state)
                    ->setStep(SearchFeedbackTelegramConversation::STEP_CONFIRM_QUERIED)
                    ->setSearchTerm(
                        (clone $searchTerm)
                            ->setType($searchTermType)
                            ->setNormalizedText($searchTermNormalizedText)
                    ),
            ];
        }
    }

    /**
     * @param string $command
     * @param SearchFeedbackTelegramConversationState $state
     * @param SearchFeedbackTelegramConversationState $expectedState
     * @return void
     * @dataProvider gotSearchTermTypeChangeSuccess
     */
    public function testGotSearchTermTypeChangeSuccess(
        string $command,
        SearchFeedbackTelegramConversationState $state,
        SearchFeedbackTelegramConversationState $expectedState
    ): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
        ]);
        $state
            ->setStep(SearchFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
            ->setChange(true)
        ;
        $conversation = $this->createConversation(SearchFeedbackTelegramConversation::class, $state);

        $this
            ->type($command)
            ->shouldSeeActiveConversation($conversation->getClass(), $expectedState)
            ->shouldSeeReply(...$this->getShouldSeeReplyOnConfirmQueried())
            ->shouldSeeButtons(...$this->getShouldSeeKeyboardOnConfirmQueried())
        ;
    }

    public function gotSearchTermTypeChangeSuccess(): Generator
    {
        $generalState = (new SearchFeedbackTelegramConversationState())
            ->setStep(SearchFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
            ->setChange(true)
        ;
        $searchTermTypes = SearchTermType::cases();

        /** @var MessengerUserTransfer $messengerUser */

        // unknown messenger profile url
        yield 'change & messenger profile url' => [
            'command' => sprintf('search_term_type.%s', SearchTermType::messenger_profile_url->name),
            'state' => $state = (clone $generalState)
                ->setSearchTerm(
                    $searchTerm = (new SearchTermTransfer('https://unknown.com/me'))
                        ->setPossibleTypes($searchTermTypes)
                ),
            'expectedState' => (clone $state)
                ->setStep(SearchFeedbackTelegramConversation::STEP_CONFIRM_QUERIED)
                ->setChange(false)
                ->setSearchTerm(
                    (clone $searchTerm)
                        ->setType(SearchTermType::messenger_username)
                        ->setNormalizedText('me')
                        ->setMessenger(Messenger::unknown)
                        ->setMessengerUsername('me')
                        ->setMessengerProfileUrl($searchTerm->getText())
                ),
        ];

        // messenger usernames
        foreach (Fixtures::getMessengerUserUsernames() as $commandKey => [$messengerUser, $searchTermType]) {
            yield sprintf('change & %s username', $commandKey) => [
                'command' => sprintf('search_term_type.%s', $searchTermType->name),
                'state' => $state = (clone $generalState)
                    ->setSearchTerm(
                        $searchTerm = (new SearchTermTransfer($messengerUser->getUsername()))
                            ->setPossibleTypes($searchTermTypes)
                    ),
                'expectedState' => (clone $state)
                    ->setStep(SearchFeedbackTelegramConversation::STEP_CONFIRM_QUERIED)
                    ->setChange(false)
                    ->setSearchTerm(
                        $this->getMessengerUsernameSearchTerm($messengerUser)
                            ->setType($searchTermType)
                            ->setMessengerUser(null)
                            ->setPossibleTypes($searchTerm->getPossibleTypes())
                    ),
            ];
        }

        // unknown messenger username
        yield 'change & messenger username' => [
            'command' => sprintf('search_term_type.%s', SearchTermType::messenger_username->name),
            'state' => $state = (clone $generalState)
                ->setSearchTerm(
                    $searchTerm = (new SearchTermTransfer('me'))
                        ->setPossibleTypes($searchTermTypes)
                ),
            'expectedState' => (clone $state)
                ->setStep(SearchFeedbackTelegramConversation::STEP_CONFIRM_QUERIED)
                ->setChange(false)
                ->setSearchTerm(
                    (clone $searchTerm)
                        ->setType(SearchTermType::messenger_username)
                        ->setMessenger(Messenger::unknown)
                        ->setMessengerUsername('me')
                ),
        ];

        // non-messengers
        foreach (Fixtures::NON_MESSENGER_SEARCH_TYPES as $typeKey => [$searchTermType, $searchTermText, $searchTermNormalizedText]) {
            yield sprintf('change & %s', $typeKey) => [
                'command' => sprintf('search_term_type.%s', $searchTermType->name),
                'state' => $state = (clone $generalState)
                    ->setSearchTerm(
                        $searchTerm = (new SearchTermTransfer($searchTermText))
                            ->setPossibleTypes($searchTermTypes)
                    ),
                'expectedState' => (clone $state)
                    ->setStep(SearchFeedbackTelegramConversation::STEP_CONFIRM_QUERIED)
                    ->setChange(false)
                    ->setSearchTerm(
                        (clone $searchTerm)
                            ->setType($searchTermType)
                            ->setNormalizedText($searchTermNormalizedText)
                    ),
            ];
        }
    }

    /**
     * @param string $command
     * @param TelegramConversationState $state
     * @param TelegramConversationState $expectedState
     * @param string $expectedReply
     * @param array $expectedButtons
     * @return void
     * @dataProvider gotConfirmChangeSuccessDataProvider
     */
    public function testGotConfirmChangeSuccess(
        string $command,
        TelegramConversationState $state,
        TelegramConversationState $expectedState,
        string $expectedReply,
        array $expectedButtons
    ): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
        ]);

        $state->setStep(SearchFeedbackTelegramConversation::STEP_CONFIRM_QUERIED);

        $conversation = $this->createConversation(SearchFeedbackTelegramConversation::class, $state);

        $this
            ->type($command)
            ->shouldSeeActiveConversation($conversation->getClass(), $expectedState)
            ->shouldSeeReply($expectedReply)
            ->shouldSeeButtons(...$expectedButtons)
        ;
    }

    public function gotConfirmChangeSuccessDataProvider(): Generator
    {
        $searchTerm = new SearchTermTransfer('any');

        $commands = [
            'search term change' => [
                '📝 keyboard.change_search_term',
                SearchFeedbackTelegramConversation::STEP_SEARCH_TERM_QUERIED,
                'query.search_term',
                [
                    'keyboard.leave_as',
                    'keyboard.cancel',
                ],
            ],
        ];

        foreach ($commands as $commandKey => [$command, $expectedStep, $expectedText, $expectedButtons]) {
            yield sprintf('%s', $commandKey) => [
                'command' => $command,
                'state' => $state = (new SearchFeedbackTelegramConversationState())
                    ->setStep(SearchFeedbackTelegramConversation::STEP_CONFIRM_QUERIED)
                    ->setSearchTerm($searchTerm),
                'expectedState' => (clone $state)
                    ->setStep($expectedStep)
                    ->setChange(true),
                'expectedReply' => $expectedText,
                'expectedButtons' => $expectedButtons,
            ];
        }
    }

    /**
     * @param int $step
     * @param bool|null $change
     * @return void
     * @dataProvider gotCancelSuccessDataProvider
     */
    public function testGotCancelSuccess(int $step, bool $change = null): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
        ]);

        $state = (new SearchFeedbackTelegramConversationState())
            ->setStep($step)
            ->setChange($change)
        ;
        $expectedState = (clone $state)
            ->setStep(SearchFeedbackTelegramConversation::STEP_CANCEL_PRESSED)
        ;
        $conversation = $this->createConversation(SearchFeedbackTelegramConversation::class, $state);

        $this
            ->type('keyboard.cancel')
            ->shouldSeeNotActiveConversation($conversation->getClass(), $expectedState)
            ->shouldSeeReply('reply.canceled')
            ->shouldSeeChooseAction()
        ;
    }

    public function gotCancelSuccessDataProvider(): Generator
    {
        yield 'search term' => [
            SearchFeedbackTelegramConversation::STEP_SEARCH_TERM_QUERIED,
        ];

        yield 'change search term' => [
            SearchFeedbackTelegramConversation::STEP_SEARCH_TERM_QUERIED,
            true,
        ];

        yield 'search term type' => [
            SearchFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED,
        ];

        yield 'change search term type' => [
            SearchFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED,
            true,
        ];

        yield 'confirm' => [
            SearchFeedbackTelegramConversation::STEP_CONFIRM_QUERIED,
        ];
    }

    /**
     * @param SearchFeedbackTelegramConversationState $state
     * @param array $shouldSeeReplyFeedbacks
     * @return void
     * @dataProvider gotConfirmSuccessDataProvider
     */
    public function testGotConfirmSuccess(
        SearchFeedbackTelegramConversationState $state,
        array $shouldSeeReplyFeedbacks
    ): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
            Feedback::class,
        ]);

        $conversation = $this->createConversation(SearchFeedbackTelegramConversation::class, $state);
        $shouldSeeReply = [];

        $count = count($shouldSeeReplyFeedbacks);
        if ($count === 0) {
            $shouldSeeReply[] = 'reply.empty_list';
        } else {
            $shouldSeeReply[] = 'reply.title';
            $shouldSeeReply = array_merge($shouldSeeReply, array_fill(0, count($shouldSeeReplyFeedbacks), 'somebody_from'));
        }

        $feedbackSearchRepository = $this->getFeedbackSearchRepository();
        $previousFeedbackSearchCount = $feedbackSearchRepository->count([]);

        $this
            ->type('keyboard.confirm')
            ->shouldSeeReply(...$shouldSeeReply)
            ->shouldSeeChooseAction()
        ;

        $this->assertFalse($conversation->active());

        $this->assertEquals($previousFeedbackSearchCount + 1, $feedbackSearchRepository->count([]));

        $feedbackSearch = $feedbackSearchRepository->findOneBy([
            'messengerUser' => $this->getUpdateMessengerUser(),
            'searchTermText' => $state->getSearchTerm()->getText(),
            'searchTermType' => $state->getSearchTerm()->getType(),
        ]);

        $this->assertNotNull($feedbackSearch);
    }

    public function gotConfirmSuccessDataProvider(): Generator
    {
        $generalState = (new SearchFeedbackTelegramConversationState())
            ->setStep(SearchFeedbackTelegramConversation::STEP_CONFIRM_QUERIED)
        ;

        yield 'unknown & empty' => [
            'state' => (clone $generalState)
                ->setStep(SearchFeedbackTelegramConversation::STEP_CONFIRM_QUERIED)
                ->setSearchTerm(
                    (new SearchTermTransfer('any'))
                        ->setType(SearchTermType::unknown)
                ),
            'shouldSeeReplyFeedbacks' => [],
        ];

        yield 'instagram username & not empty' => [
            'state' => (clone $generalState)
                ->setStep(SearchFeedbackTelegramConversation::STEP_CONFIRM_QUERIED)
                ->setSearchTerm(
                    $this->getMessengerUsernameSearchTerm(Fixtures::getInstagramMessengerUserTransferFixture(3))
                        ->setType(SearchTermType::instagram_username)
                ),
            'shouldSeeReplyFeedbacks' => [1, 2],
        ];
    }

    private function getShouldSeeReplyOnSearchTermTypeQueried(): array
    {
        return [
            'query.search_term_type',
        ];
    }

    private function getShouldSeeKeyboardOnSearchTermTypeQueried(TelegramAwareHelper $tg, SearchFeedbackTelegramConversationState $state): array
    {
        return [
            ...SearchFeedbackTelegramConversation::getSearchTermTypeButtons(SearchTermType::sort($state->getSearchTerm()->getPossibleTypes()), $tg),
            'keyboard.cancel',
        ];
    }

    private function getShouldSeeReplyOnConfirmQueried(): array
    {
        return [
            'query.confirm',
        ];
    }

    private function getShouldSeeKeyboardOnConfirmQueried(): array
    {
        return [
            'keyboard.confirm',
            '📝 keyboard.change_search_term',
            'keyboard.cancel',
        ];
    }
}
