<?php

declare(strict_types=1);

namespace App\Tests\Functional\Telegram;

use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\CreateFeedbackTelegramConversationState;
use App\Entity\Telegram\TelegramBot;
use App\Entity\User\User;
use App\Enum\Feedback\Rating;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Messenger\Messenger;
use App\Object\Feedback\SearchTermTransfer;
use App\Object\Messenger\MessengerUserTransfer;
use App\Service\Telegram\Channel\FeedbackTelegramChannel;
use App\Service\Telegram\Conversation\CreateFeedbackTelegramConversation;
use App\Service\Telegram\TelegramAwareHelper;
use App\Tests\Fixtures;
use App\Tests\Traits\Feedback\FeedbackRepositoryProviderTrait;
use App\Tests\Traits\User\UserRepositoryProviderTrait;
use Generator;

class CreateFeedbackTelegramCommandFunctionalTest extends TelegramCommandFunctionalTestCase
{
    use FeedbackRepositoryProviderTrait;
    use UserRepositoryProviderTrait;

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
                CreateFeedbackTelegramConversation::class,
                (new CreateFeedbackTelegramConversationState())
                    ->setSearchTermStep(true)
                    ->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_QUERIED)
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
            'command' => 'icon.create command.create',
            'showHints' => false,
        ];

        yield 'button & hints' => [
            'command' => 'icon.create command.create',
            'showHints' => true,
        ];

        yield 'command & no hints' => [
            'command' => FeedbackTelegramChannel::CREATE,
            'showHints' => false,
        ];

        yield 'command & hints' => [
            'command' => FeedbackTelegramChannel::CREATE,
            'showHints' => true,
        ];
    }

    /**
     * @param callable $fn
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

        $state->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_QUERIED);
        $conversation = $this->createConversation(CreateFeedbackTelegramConversation::class, $state);

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
        $state = (new CreateFeedbackTelegramConversationState())
            ->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_QUERIED)
        ;

        // messenger profile urls
        foreach (Fixtures::getMessengerUserProfileUrls() as $commandKey => [$messengerUser, $expectedSearchTermType]) {
            yield sprintf('%s profile url', $commandKey) => [
                fn ($tg) => [
                    'command' => $this->getMessengerUserProfileUrl($messengerUser),
                    'mocks' => null,
                    'state' => clone $state,
                    'expectedState' => (clone $state)
                        ->setStep(CreateFeedbackTelegramConversation::STEP_RATING_QUERIED)
                        ->setSearchTerm(
                            $this->addSearchTermPossibleTypes($this->getMessengerProfileUrlSearchTerm($messengerUser))
                                ->setType($expectedSearchTermType)
                                ->setMessengerUser(null)
                        ),
                    'shouldSeeReply' => $this->getShouldSeeReplyOnRatingQueried(),
                    'shouldSeeButtons' => $this->getShouldSeeKeyboardOnRatingQueried($tg),
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
                    ->setStep(CreateFeedbackTelegramConversation::STEP_RATING_QUERIED)
                    ->setSearchTerm(
                        $this->addSearchTermPossibleTypes(new SearchTermTransfer($command))
                            ->setType(SearchTermType::phone_number)
                    ),
                'shouldSeeReply' => $this->getShouldSeeReplyOnRatingQueried(),
                'shouldSeeButtons' => $this->getShouldSeeKeyboardOnRatingQueried($tg),
            ],
        ];

        // normalized email
        yield sprintf('normalized %s', SearchTermType::email->name) => [
            fn ($tg) => [
                'command' => $command = 'example@gmail.com',
                'mocks' => null,
                'state' => clone $state,
                'expectedState' => (clone $state)
                    ->setStep(CreateFeedbackTelegramConversation::STEP_RATING_QUERIED)
                    ->setSearchTerm(
                        $this->addSearchTermPossibleTypes(new SearchTermTransfer($command))
                            ->setType(SearchTermType::email),
                    ),
                'shouldSeeReply' => $this->getShouldSeeReplyOnRatingQueried(),
                'shouldSeeButtons' => $this->getShouldSeeKeyboardOnRatingQueried($tg),
            ],
        ];
    }

    /**
     * @param callable $fn
     * @dataProvider gotSearchTermWithUnknownTypeSuccessDataProvider
     */
    public function testGotSearchTermWithUnknownTypeSuccess(callable $fn): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
        ]);

        extract($fn($this->getTg()));

        $state->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_QUERIED);
        $conversation = $this->createConversation(CreateFeedbackTelegramConversation::class, $state);

        $mocks && $mocks();

        $this
            ->type($command)
            ->shouldSeeActiveConversation($conversation->getClass(), $expectedState)
            ->shouldSeeReply(...$shouldSeeReply)
            ->shouldSeeButtons(...$shouldSeeButtons)
        ;
    }

    public function gotSearchTermWithUnknownTypeSuccessDataProvider(): Generator
    {
        $state = (new CreateFeedbackTelegramConversationState())
            ->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_QUERIED)
        ;

        yield from $this->gotUnknownSearchTermSuccessDataProvider('', $state);
    }

    public function gotUnknownSearchTermSuccessDataProvider(string $key, CreateFeedbackTelegramConversationState $state): Generator
    {
        /** @var MessengerUserTransfer $messengerUser */

        // unknown messenger profile url
        yield sprintf('%s & messenger profile url', $key) => [
            fn ($tg) => [
                'command' => $command = 'https://unknown.com/me',
                'mocks' => null,
                'state' => clone $state,
                'expectedState' => $expectedState = (clone $state)
                    ->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
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
                        ->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
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
                    ->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
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
                        ->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
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
                        ->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
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
                        ->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
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
                    ->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
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
                    ->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
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
            ->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_QUERIED)
            ->setChange(true)
        ;

        $conversation = $this->createConversation(CreateFeedbackTelegramConversation::class, $state);

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
        $state = (new CreateFeedbackTelegramConversationState())
            ->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_QUERIED)
            ->setChange(true)
            ->setSearchTerm(new SearchTermTransfer('any'))
            ->setRating(Rating::random())
            ->setDescription(mt_rand(0, 1) === 0 ? null : 'any')
        ;

        // network messenger profile urls
        foreach (Fixtures::getNetworkMessengerUserProfileUrls() as $commandKey => [$messengerUser, $expectedSearchTermType, $mocks]) {
            yield sprintf('change & %s', $commandKey) => [
                fn ($tg) => [
                    'command' => $this->getMessengerUserProfileUrl($messengerUser),
                    'mocks' => $mocks,
                    'state' => clone $state,
                    'expectedState' => $expectedState = (clone $state)
                        ->setStep(CreateFeedbackTelegramConversation::STEP_CONFIRM_QUERIED)
                        ->setChange(false)
                        ->setSearchTerm(
                            $this->addSearchTermPossibleTypes(
                                $this->getMessengerProfileUrlSearchTerm($messengerUser)
                            )->setType($expectedSearchTermType)
                        ),
                    'shouldSeeReply' => $this->getShouldSeeReplyOnConfirmQueried(),
                    'shouldSeeButtons' => $this->getShouldSeeKeyboardOnConfirmQueried($expectedState),
                ],
            ];
        }

        // non-network messenger profile urls
        foreach (Fixtures::getNonNetworkMessengerUserProfileUrls() as $commandKey => [$messengerUser, $expectedSearchTermType]) {
            yield sprintf('change & %s', $commandKey) => [
                fn ($tg) => [
                    'command' => $this->getMessengerUserProfileUrl($messengerUser),
                    'mocks' => null,
                    'state' => clone $state,
                    'expectedState' => $expectedState = (clone $state)
                        ->setStep(CreateFeedbackTelegramConversation::STEP_CONFIRM_QUERIED)
                        ->setChange(false)
                        ->setSearchTerm(
                            $this->addSearchTermPossibleTypes(
                                $this->getMessengerProfileUrlSearchTerm($messengerUser)
                            )->setType($expectedSearchTermType)
                                ->setMessengerUser(null)
                        ),
                    'shouldSeeReply' => $this->getShouldSeeReplyOnConfirmQueried(),
                    'shouldSeeButtons' => $this->getShouldSeeKeyboardOnConfirmQueried($expectedState),
                ],
            ];
        }
    }

    /**
     * @param callable $fn
     * @return void
     * @dataProvider gotSearchTermChangeWithUnknownTypeSuccessDataProvider
     */
    public function testGotSearchTermChangeWithUnknownTypeSuccess(callable $fn): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
        ]);

        extract($fn($this->getTg()));

        $state
            ->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_QUERIED)
            ->setChange(true)
        ;

        $conversation = $this->createConversation(CreateFeedbackTelegramConversation::class, $state);

        $mocks && $mocks($this);

        $this
            ->type($command)
            ->shouldSeeActiveConversation($conversation->getClass(), $expectedState)
            ->shouldSeeReply(...$shouldSeeReply)
            ->shouldSeeButtons(...$shouldSeeButtons)
        ;
    }

    public function gotSearchTermChangeWithUnknownTypeSuccessDataProvider(): Generator
    {
        $state = (new CreateFeedbackTelegramConversationState())
            ->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_QUERIED)
            ->setChange(true)
            ->setSearchTerm(new SearchTermTransfer('any'))
            ->setRating(Rating::random())
            ->setDescription(mt_rand(0, 1) === 0 ? null : 'any')
        ;

        yield from $this->gotUnknownSearchTermSuccessDataProvider('change', $state);
    }

    /**
     * @param string $command
     * @param CreateFeedbackTelegramConversationState $state
     * @param CreateFeedbackTelegramConversationState $expectedState
     * @return void
     * @dataProvider gotSearchTermTypeSuccessDataProvider
     */
    public function testGotSearchTermTypeSuccess(
        string $command,
        CreateFeedbackTelegramConversationState $state,
        CreateFeedbackTelegramConversationState $expectedState
    ): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
        ]);
        $state->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED);
        $conversation = $this->createConversation(CreateFeedbackTelegramConversation::class, $state);

        $this
            ->type($command)
            ->shouldSeeActiveConversation($conversation->getClass(), $expectedState)
            ->shouldSeeReply(...$this->getShouldSeeReplyOnRatingQueried())
            ->shouldSeeButtons(...$this->getShouldSeeKeyboardOnRatingQueried($this->getTg()))
        ;
    }

    public function gotSearchTermTypeSuccessDataProvider(): Generator
    {
        $searchTermTypes = SearchTermType::cases();

        /** @var MessengerUserTransfer $messengerUser */

        // unknown messenger profile url
        yield 'messenger profile url' => [
            'command' => sprintf('search_term_type.%s', SearchTermType::messenger_profile_url->name),
            'state' => $state = (new CreateFeedbackTelegramConversationState())
                ->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
                ->setSearchTerm(
                    $searchTerm = (new SearchTermTransfer('https://unknown.com/me'))
                        ->setPossibleTypes($searchTermTypes)
                ),
            'expectedState' => (clone $state)
                ->setStep(CreateFeedbackTelegramConversation::STEP_RATING_QUERIED)
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
                'state' => $state = (new CreateFeedbackTelegramConversationState())
                    ->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
                    ->setSearchTerm(
                        $searchTerm = (new SearchTermTransfer($messengerUser->getUsername()))
                            ->setPossibleTypes($searchTermTypes)
                    ),
                'expectedState' => (clone $state)
                    ->setStep(CreateFeedbackTelegramConversation::STEP_RATING_QUERIED)
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
            'state' => $state = (new CreateFeedbackTelegramConversationState())
                ->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
                ->setSearchTerm(
                    $searchTerm = (new SearchTermTransfer('me'))
                        ->setPossibleTypes($searchTermTypes)
                ),
            'expectedState' => (clone $state)
                ->setStep(CreateFeedbackTelegramConversation::STEP_RATING_QUERIED)
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
                'state' => $state = (new CreateFeedbackTelegramConversationState())
                    ->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
                    ->setSearchTerm(
                        $searchTerm = (new SearchTermTransfer($searchTermText))
                            ->setPossibleTypes($searchTermTypes)
                    ),
                'expectedState' => (clone $state)
                    ->setStep(CreateFeedbackTelegramConversation::STEP_RATING_QUERIED)
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
     * @param callable|null $mocks
     * @param CreateFeedbackTelegramConversationState $state
     * @param CreateFeedbackTelegramConversationState $expectedState
     * @return void
     * @dataProvider gotSearchTermTypeChangeSuccess
     */
    public function testGotSearchTermTypeChangeSuccess(
        string $command,
        ?callable $mocks,
        CreateFeedbackTelegramConversationState $state,
        CreateFeedbackTelegramConversationState $expectedState
    ): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
        ]);
        $state
            ->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
            ->setChange(true)
        ;
        $conversation = $this->createConversation(CreateFeedbackTelegramConversation::class, $state);

        $mocks && $mocks($this);

        $this
            ->type($command)
            ->shouldSeeActiveConversation($conversation->getClass(), $expectedState)
            ->shouldSeeReply(...$this->getShouldSeeReplyOnConfirmQueried())
            ->shouldSeeButtons(...$this->getShouldSeeKeyboardOnConfirmQueried($expectedState))
        ;
    }

    public function gotSearchTermTypeChangeSuccess(): Generator
    {
        $generalState = (new CreateFeedbackTelegramConversationState())
            ->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
            ->setChange(true)
            ->setRating(Rating::random())
            ->setDescription('any')
        ;
        $searchTermTypes = SearchTermType::cases();

        /** @var MessengerUserTransfer $messengerUser */

        // unknown messenger profile url
        yield 'change & messenger profile url' => [
            'command' => sprintf('search_term_type.%s', SearchTermType::messenger_profile_url->name),
            'mocks' => null,
            'state' => $state = (clone $generalState)
                ->setSearchTerm(
                    $searchTerm = (new SearchTermTransfer('https://unknown.com/me'))
                        ->setPossibleTypes($searchTermTypes)
                ),
            'expectedState' => (clone $state)
                ->setStep(CreateFeedbackTelegramConversation::STEP_CONFIRM_QUERIED)
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
        foreach (Fixtures::getMessengerUserUsernames() as $commandKey => [$messengerUser, $searchTermType, $mocks]) {
            yield sprintf('change & %s username', $commandKey) => [
                'command' => sprintf('search_term_type.%s', $searchTermType->name),
                'mocks' => $mocks,
                'state' => $state = (clone $generalState)
                    ->setSearchTerm(
                        $searchTerm = (new SearchTermTransfer($messengerUser->getUsername()))
                            ->setPossibleTypes($searchTermTypes)
                    ),
                'expectedState' => (clone $state)
                    ->setStep(CreateFeedbackTelegramConversation::STEP_CONFIRM_QUERIED)
                    ->setChange(false)
                    ->setSearchTerm(
                        $this->getMessengerUsernameSearchTerm($messengerUser)
                            ->setType($searchTermType)
                            ->setPossibleTypes($searchTerm->getPossibleTypes())
                    ),
            ];
        }

        // unknown messenger username
        yield 'change & messenger username' => [
            'command' => sprintf('search_term_type.%s', SearchTermType::messenger_username->name),
            'mocks' => null,
            'state' => $state = (clone $generalState)
                ->setSearchTerm(
                    $searchTerm = (new SearchTermTransfer('me'))
                        ->setPossibleTypes($searchTermTypes)
                ),
            'expectedState' => (clone $state)
                ->setStep(CreateFeedbackTelegramConversation::STEP_CONFIRM_QUERIED)
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
                'mocks' => null,
                'state' => $state = (clone $generalState)
                    ->setSearchTerm(
                        $searchTerm = (new SearchTermTransfer($searchTermText))
                            ->setPossibleTypes($searchTermTypes)
                    ),
                'expectedState' => (clone $state)
                    ->setStep(CreateFeedbackTelegramConversation::STEP_CONFIRM_QUERIED)
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
     * @param Rating $rating
     * @return void
     * @dataProvider gotRatingSuccessDataProvider
     */
    public function testGotRatingSuccess(Rating $rating): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
        ]);
        $command = CreateFeedbackTelegramConversation::getRatingButton($rating, $this->getTg())->getText();
        $state = (new CreateFeedbackTelegramConversationState())
            ->setStep(CreateFeedbackTelegramConversation::STEP_RATING_QUERIED)
            ->setSearchTerm(new SearchTermTransfer('any'))
        ;
        $conversation = $this->createConversation(CreateFeedbackTelegramConversation::class, $state);
        $expectedState = (clone $state)
            ->setStep(CreateFeedbackTelegramConversation::STEP_DESCRIPTION_QUERIED)
            ->setRating($rating)
        ;

        $this
            ->type($command)
            ->shouldSeeActiveConversation($conversation->getClass(), $expectedState)
            ->shouldSeeReply('query.description')
            ->shouldSeeButtons(
                'keyboard.leave_empty',
                'keyboard.cancel',
            )
        ;
    }

    public function gotRatingSuccessDataProvider(): Generator
    {
        foreach (Rating::cases() as $rating) {
            yield sprintf('%s', $rating->name) => ['rating' => $rating];
        }
    }

    /**
     * @param Rating $rating
     * @return void
     * @dataProvider gotRatingChangeSuccessDataProvider
     */
    public function testGotRatingChangeSuccess(Rating $rating): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
        ]);
        $command = CreateFeedbackTelegramConversation::getRatingButton($rating, $this->getTg())->getText();
        $state = (new CreateFeedbackTelegramConversationState())
            ->setStep(CreateFeedbackTelegramConversation::STEP_RATING_QUERIED)
            ->setChange(true)
            ->setSearchTerm(
                (new SearchTermTransfer('any'))
                    ->setType(SearchTermType::unknown)
            )
            ->setRating(Rating::random())
            ->setDescription('any')
        ;
        $conversation = $this->createConversation(CreateFeedbackTelegramConversation::class, $state);
        $expectedState = (clone $state)
            ->setStep(CreateFeedbackTelegramConversation::STEP_CONFIRM_QUERIED)
            ->setRating($rating)
            ->setChange(false)
        ;

        $this
            ->type($command)
            ->shouldSeeActiveConversation($conversation->getClass(), $expectedState)
            ->shouldSeeReply(...$this->getShouldSeeReplyOnConfirmQueried())
            ->shouldSeeButtons(...$this->getShouldSeeKeyboardOnConfirmQueried($expectedState))
        ;
    }

    public function gotRatingChangeSuccessDataProvider(): Generator
    {
        foreach (Rating::cases() as $rating) {
            yield sprintf('change & %s', $rating->name) => ['rating' => $rating];
        }
    }

    /**
     * @param string $command
     * @param callable|null $mocks
     * @param CreateFeedbackTelegramConversationState $state
     * @param string|null $expectedDescription
     * @return void
     * @dataProvider gotDescriptionSuccessDataProvider
     */
    public function testGotDescriptionSuccess(
        string $command,
        ?callable $mocks,
        CreateFeedbackTelegramConversationState $state,
        ?string $expectedDescription
    ): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
        ]);
        $state->setStep(CreateFeedbackTelegramConversation::STEP_DESCRIPTION_QUERIED);
        $conversation = $this->createConversation(CreateFeedbackTelegramConversation::class, $state);
        $expectedState = (clone $state)
            ->setStep(CreateFeedbackTelegramConversation::STEP_CONFIRM_QUERIED)
            ->setDescription($expectedDescription)
        ;

        $mocks && $mocks($this);

        $this
            ->type($command)
            ->shouldSeeActiveConversation($conversation->getClass(), $expectedState)
            ->shouldSeeReply(...$this->getShouldSeeReplyOnConfirmQueried())
            ->shouldSeeButtons(...$this->getShouldSeeKeyboardOnConfirmQueried($expectedState))
        ;
    }

    public function gotDescriptionSuccessDataProvider(): Generator
    {
        $generalState = (new CreateFeedbackTelegramConversationState())
            ->setStep(CreateFeedbackTelegramConversation::STEP_DESCRIPTION_QUERIED)
        ;

        $description = 'any';

        /** @var MessengerUserTransfer $messengerUser */

        $commands = [
            'leave_empty' => [
                'keyboard.leave_empty',
                null,
            ],
            'type_something' => [
                $description,
                $description,
            ],
        ];
        $ratings = [
            Rating::random(),
        ];

        foreach ($commands as $commandKey => [$command, $expectedDescription]) {
            foreach ($ratings as $rating) {

                // messenger profile urls
                foreach (Fixtures::getMessengerUserProfileUrls() as $messengerProfileUrlKey => [$messengerUser, $searchTermType, $mocks]) {
                    yield sprintf('%s & %s profile url & %s', $commandKey, $messengerProfileUrlKey, $rating->name) => [
                        'command' => $command,
                        'mocks' => $mocks,
                        'state' => (clone $generalState)
                            ->setSearchTerm($this->getMessengerProfileUrlSearchTerm($messengerUser)
                                ->setType($searchTermType))
                            ->setRating($rating),
                        'expectedDescription' => $expectedDescription,
                    ];
                }

                // unknown messenger profile url
                yield sprintf('%s & unknown profile url & %s', $commandKey, $rating->name) => [
                    'command' => $command,
                    'mocks' => null,
                    'state' => (clone $generalState)
                        ->setSearchTerm(
                            (new SearchTermTransfer('https://unknown.com/me'))
                                ->setType(SearchTermType::messenger_profile_url)
                                ->setMessenger(Messenger::unknown)
                        )
                        ->setRating($rating),
                    'expectedDescription' => $expectedDescription,
                ];

                // messenger usernames
                foreach (Fixtures::getMessengerUserUsernames() as $messengerUsernameKey => [$messengerUser, $searchTermType, $mocks]) {
                    yield sprintf('%s & %s username & %s', $commandKey, $messengerUsernameKey, $rating->name) => [
                        'command' => $command,
                        'mocks' => $mocks,
                        'state' => (clone $generalState)
                            ->setSearchTerm(
                                $this->getMessengerUsernameSearchTerm($messengerUser)
                                    ->setType($searchTermType)
                            )
                            ->setRating($rating),
                        'expectedDescription' => $expectedDescription,
                    ];
                }

                // unknown messenger username
                yield sprintf('%s & unknown username & %s', $commandKey, $rating->name) => [
                    'command' => $command,
                    'mocks' => null,
                    'state' => (clone $generalState)
                        ->setSearchTerm(
                            (new SearchTermTransfer('me'))
                                ->setType(SearchTermType::messenger_username)
                                ->setMessenger(Messenger::unknown)
                                ->setMessengerUsername('me')
                        )
                        ->setRating($rating),
                    'expectedDescription' => $expectedDescription,
                ];

                // non-messengers
                foreach (Fixtures::NON_MESSENGER_SEARCH_TYPES as $simpleKey => [$searchTermType, $searchTermText, $searchTermNormalizedText]) {
                    yield sprintf('%s & %s & %s', $commandKey, $simpleKey, $rating->name) => [
                        'command' => $command,
                        'mocks' => null,
                        'state' => (clone $generalState)
                            ->setSearchTerm(
                                (new SearchTermTransfer($searchTermText))
                                    ->setType($searchTermType)
                                    ->setNormalizedText($searchTermNormalizedText)
                            )
                            ->setRating($rating),
                        'expectedDescription' => $expectedDescription,
                    ];
                }
            }
        }
    }

    /**
     * @param string $command
     * @param callable|null $mocks
     * @param CreateFeedbackTelegramConversationState $state
     * @param string|null $expectedDescription
     * @return void
     * @dataProvider gotDescriptionChangeSuccessDataProvider
     */
    public function testGotDescriptionChangeSuccess(
        string $command,
        ?callable $mocks,
        CreateFeedbackTelegramConversationState $state,
        ?string $expectedDescription
    ): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
        ]);
        $state
            ->setStep(CreateFeedbackTelegramConversation::STEP_DESCRIPTION_QUERIED)
            ->setChange(true)
        ;
        $conversation = $this->createConversation(CreateFeedbackTelegramConversation::class, $state);
        $expectedState = (clone $state)
            ->setStep(CreateFeedbackTelegramConversation::STEP_CONFIRM_QUERIED)
            ->setChange(false)
            ->setDescription($expectedDescription)
        ;

        $mocks && $mocks($this);

        $this
            ->type($command)
            ->shouldSeeActiveConversation($conversation->getClass(), $expectedState)
            ->shouldSeeReply(...$this->getShouldSeeReplyOnConfirmQueried())
            ->shouldSeeButtons(...$this->getShouldSeeKeyboardOnConfirmQueried($expectedState))
        ;
    }

    public function gotDescriptionChangeSuccessDataProvider(): Generator
    {
        $generalState = (new CreateFeedbackTelegramConversationState())
            ->setStep(CreateFeedbackTelegramConversation::STEP_DESCRIPTION_QUERIED)
        ;

        /** @var MessengerUserTransfer $messengerUser */

        $commands = [
            'empty_leave_empty' => [
                null,
                'keyboard.leave_empty',
                null,
            ],
            'empty_type_something' => [
                null,
                'any',
                'any',
            ],
            'make_empty' => [
                'any',
                'keyboard.make_empty',
                null,
            ],
            'change' => [
                'any1',
                'any2',
                'any2',
            ],
        ];
        $ratings = [
            Rating::random(),
        ];

        foreach ($commands as $commandKey => [$description, $command, $expectedDescription]) {
            foreach ($ratings as $rating) {

                // messenger profile urls
                foreach (Fixtures::getMessengerUserProfileUrls() as $messengerProfileUrlKey => [$messengerUser, $searchTermType, $mocks]) {
                    yield sprintf('change & %s & %s profile url & %s', $commandKey, $messengerProfileUrlKey, $rating->name) => [
                        'command' => $command,
                        'mocks' => $mocks,
                        'state' => (clone $generalState)
                            ->setSearchTerm($this->getMessengerProfileUrlSearchTerm($messengerUser)
                                ->setType($searchTermType))
                            ->setRating($rating)
                            ->setDescription($description),
                        'expectedDescription' => $expectedDescription,
                    ];
                }

                // unknown messenger profile url
                yield sprintf('change & %s unknown profile url & %s', $commandKey, $rating->name) => [
                    'command' => $command,
                    'mocks' => null,
                    'state' => (clone $generalState)
                        ->setSearchTerm(
                            (new SearchTermTransfer('https://unknown.com/me'))
                                ->setType(SearchTermType::messenger_profile_url)
                                ->setMessenger(Messenger::unknown)
                        )
                        ->setRating($rating)
                        ->setDescription($description),
                    'expectedDescription' => $expectedDescription,
                ];

                // messenger usernames
                foreach (Fixtures::getMessengerUserUsernames() as $messengerUsernameKey => [$messengerUser, $searchTermType, $mocks]) {
                    yield sprintf('change & %s & %s username & %s', $commandKey, $messengerUsernameKey, $rating->name) => [
                        'command' => $command,
                        'mocks' => $mocks,
                        'state' => (clone $generalState)
                            ->setSearchTerm(
                                $this->getMessengerUsernameSearchTerm($messengerUser)
                                    ->setType($searchTermType)
                            )
                            ->setRating($rating)
                            ->setDescription($description),
                        'expectedDescription' => $expectedDescription,
                    ];
                }

                // unknown messenger username
                yield sprintf('change & %s & unknown username & %s', $commandKey, $rating->name) => [
                    'command' => $command,
                    'mocks' => null,
                    'state' => (clone $generalState)
                        ->setSearchTerm(
                            (new SearchTermTransfer('me'))
                                ->setType(SearchTermType::messenger_username)
                                ->setMessenger(Messenger::unknown)
                                ->setMessengerUsername('me')
                        )
                        ->setRating($rating)
                        ->setDescription($description),
                    'expectedDescription' => $expectedDescription,
                ];

                // non-messengers
                foreach (Fixtures::NON_MESSENGER_SEARCH_TYPES as $simpleKey => [$searchTermType, $searchTermText, $searchTermNormalizedText]) {
                    yield sprintf('change & %s & %s & %s', $commandKey, $simpleKey, $rating->name) => [
                        'command' => $command,
                        'mocks' => null,
                        'state' => (clone $generalState)
                            ->setSearchTerm(
                                (new SearchTermTransfer($searchTermText))
                                    ->setType($searchTermType)
                                    ->setNormalizedText($searchTermNormalizedText)
                            )
                            ->setRating($rating)
                            ->setDescription($description),
                        'expectedDescription' => $expectedDescription,
                    ];
                }
            }
        }
    }

    /**
     * @param callable $fn
     * @return void
     * @dataProvider gotConfirmChangeSuccessDataProvider
     */
    public function testGotConfirmChangeSuccess(callable $fn): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
        ]);
        extract($fn($this->getTg()));
        $state->setStep(CreateFeedbackTelegramConversation::STEP_CONFIRM_QUERIED);
        $conversation = $this->createConversation(CreateFeedbackTelegramConversation::class, $state);

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
        $description = 'whatever';

        $commands = [
            'search term change' => [
                '📝 keyboard.change_search_term',
                $description,
                CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_QUERIED,
                'query.search_term',
                fn ($tg) => [
                    'keyboard.leave_as',
                    'keyboard.cancel',
                ],
            ],
            'rating change' => [
                '📝 keyboard.change_rating',
                $description,
                CreateFeedbackTelegramConversation::STEP_RATING_QUERIED,
                'query.rating',
                fn ($tg) => [
                    ...CreateFeedbackTelegramConversation::getRatingButtons($tg),
                    'keyboard.cancel',
                ],
            ],
            'empty description change' => [
                '📝 keyboard.add_description',
                null,
                CreateFeedbackTelegramConversation::STEP_DESCRIPTION_QUERIED,
                'query.description',
                fn ($tg) => [
                    'keyboard.leave_empty',
                    'keyboard.cancel',
                ],
            ],
            'description change' => [
                '📝 keyboard.change_description',
                $description,
                CreateFeedbackTelegramConversation::STEP_DESCRIPTION_QUERIED,
                'query.description',
                fn ($tg) => [
                    'keyboard.leave_as',
                    'keyboard.make_empty',
                    'keyboard.cancel',
                ],
            ],
        ];

        foreach ($commands as $commandKey => [$command, $description, $expectedStep, $expectedText, $expectedButtons]) {
            yield sprintf('%s', $commandKey) => [
                fn ($tg) => [
                    'command' => $command,
                    'state' => $state = (new CreateFeedbackTelegramConversationState())
                        ->setStep(CreateFeedbackTelegramConversation::STEP_CONFIRM_QUERIED)
                        ->setSearchTerm($searchTerm)
                        ->setRating(Rating::random())
                        ->setDescription($description),
                    'expectedState' => (clone $state)
                        ->setStep($expectedStep)
                        ->setChange(true),
                    'expectedReply' => $expectedText,
                    'expectedButtons' => $expectedButtons($tg),
                ],
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
        $state = (new CreateFeedbackTelegramConversationState())
            ->setStep($step)
            ->setChange($change)
        ;
        $conversation = $this->createConversation(CreateFeedbackTelegramConversation::class, $state);
        $expectedState = (clone $state)
            ->setStep(CreateFeedbackTelegramConversation::STEP_CANCEL_PRESSED)
        ;

        $this
            ->type('keyboard.cancel')
            ->shouldNotSeeActiveConversation($conversation->getClass(), $expectedState)
            ->shouldSeeReply('reply.canceled')
            ->shouldSeeChooseAction()
        ;
    }

    public function gotCancelSuccessDataProvider(): Generator
    {
        yield 'search term' => [
            CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_QUERIED,
        ];

        yield 'change search term' => [
            CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_QUERIED,
            true,
        ];

        yield 'search term type' => [
            CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED,
        ];

        yield 'change search term type' => [
            CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED,
            true,
        ];

        yield 'rating' => [
            CreateFeedbackTelegramConversation::STEP_RATING_QUERIED,
        ];

        yield 'change rating' => [
            CreateFeedbackTelegramConversation::STEP_RATING_QUERIED,
            true,
        ];

        yield 'description' => [
            CreateFeedbackTelegramConversation::STEP_DESCRIPTION_QUERIED,
        ];

        yield 'change description' => [
            CreateFeedbackTelegramConversation::STEP_DESCRIPTION_QUERIED,
            true,
        ];

        yield 'confirm' => [
            CreateFeedbackTelegramConversation::STEP_CONFIRM_QUERIED,
        ];
    }

    /**
     * @param CreateFeedbackTelegramConversationState $state
     * @return void
     * @dataProvider gotConfirmSuccessDataProvider
     */
    public function testGotConfirmSuccess(CreateFeedbackTelegramConversationState $state): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
        ]);
        $this->createConversation(CreateFeedbackTelegramConversation::class, $state);

        $feedbackRepository = $this->getFeedbackRepository();
        $previousFeedbackCount = $feedbackRepository->count([]);

        $this
            ->type('keyboard.confirm')
            ->shouldNotSeeActiveConversation()
            ->shouldSeeChooseAction('reply.ok')
        ;

        $this->assertEquals($previousFeedbackCount + 1, $feedbackRepository->count([]));

        $feedback = $feedbackRepository->findOneBy([
            'messengerUser' => $this->getUpdateMessengerUser(),
            'searchTermText' => $state->getSearchTerm()->getText(),
            'searchTermType' => $state->getSearchTerm()->getType(),
        ]);
        $this->assertNotNull($feedback);
    }

    public function gotConfirmSuccessDataProvider(): Generator
    {
        $generalState = (new CreateFeedbackTelegramConversationState())
            ->setStep(CreateFeedbackTelegramConversation::STEP_CONFIRM_QUERIED)
            ->setRating(Rating::random())
            ->setDescription(mt_rand(0, 1) === 0 ? null : 'any')
        ;

        // network messenger profile urls
        foreach (Fixtures::getNetworkMessengerUserProfileUrls(2) as $commandKey => [$messengerUser, $expectedSearchTermType, $mocks]) {
            yield sprintf('%s', $commandKey) => [
                'state' => (clone $generalState)
                    ->setStep(CreateFeedbackTelegramConversation::STEP_CONFIRM_QUERIED)
                    ->setSearchTerm(
                        $this->addSearchTermPossibleTypes(
                            $this->getMessengerProfileUrlSearchTerm($messengerUser)
                        )->setType($expectedSearchTermType)
                    ),
            ];
        }
    }

    private function getShouldSeeReplyOnSearchTermTypeQueried(): array
    {
        return [
            'query.search_term_type',
        ];
    }

    private function getShouldSeeKeyboardOnSearchTermTypeQueried(TelegramAwareHelper $tg, CreateFeedbackTelegramConversationState $state): array
    {
        return [
            ...CreateFeedbackTelegramConversation::getSearchTermTypeButtons(SearchTermType::sort($state->getSearchTerm()->getPossibleTypes()), $tg),
            'keyboard.cancel',
        ];
    }

    private function getShouldSeeReplyOnRatingQueried(): array
    {
        return [
            'query.rating',
        ];
    }

    private function getShouldSeeKeyboardOnRatingQueried(TelegramAwareHelper $tg): array
    {
        return [
            ...CreateFeedbackTelegramConversation::getRatingButtons($tg),
            'keyboard.cancel',
        ];
    }

    private function getShouldSeeReplyOnConfirmQueried(): array
    {
        return [
            'query.confirm',
        ];
    }

    private function getShouldSeeKeyboardOnConfirmQueried(CreateFeedbackTelegramConversationState $state): array
    {
        return [
            'keyboard.confirm',
            '📝 keyboard.change_search_term',
            '📝 keyboard.change_rating',
            $state->getDescription() === null ? '📝 keyboard.add_description' : '📝 keyboard.change_description',
            'keyboard.cancel',
        ];
    }
}
