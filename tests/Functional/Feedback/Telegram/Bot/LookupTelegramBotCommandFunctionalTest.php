<?php

declare(strict_types=1);

namespace App\Tests\Functional\Feedback\Telegram\Bot;

use App\Entity\Feedback\FeedbackSearch;
use App\Entity\Feedback\Telegram\Bot\LookupTelegramBotConversationState;
use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Entity\Telegram\TelegramBotConversationState;
use App\Entity\User\User;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Messenger\Messenger;
use App\Service\Feedback\Telegram\Bot\Conversation\LookupTelegramBotConversation;
use App\Service\Feedback\Telegram\Bot\FeedbackTelegramBotGroup;
use App\Service\Telegram\Bot\TelegramBotAwareHelper;
use App\Tests\Fixtures;
use App\Tests\Functional\Telegram\Bot\TelegramBotCommandFunctionalTestCase;
use App\Tests\Traits\Feedback\FeedbackSearchSearchRepositoryProviderTrait;
use App\Tests\Traits\Feedback\FeedbackSearchTermTypeProviderTrait;
use App\Transfer\Feedback\SearchTermTransfer;
use App\Transfer\Messenger\MessengerUserTransfer;
use Generator;

class LookupTelegramBotCommandFunctionalTest extends TelegramBotCommandFunctionalTestCase
{
    use FeedbackSearchSearchRepositoryProviderTrait;
    use FeedbackSearchTermTypeProviderTrait;

    /**
     * @param string $command
     * @return void
     * @dataProvider startSuccessDataProvider
     */
    public function testStartSuccess(string $command): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
        ]);

        $this
            ->type($command)
            ->shouldSeeActiveConversation(
                LookupTelegramBotConversation::class,
                (new LookupTelegramBotConversationState())
                    ->setStep(LookupTelegramBotConversation::STEP_SEARCH_TERM_QUERIED)
            )
            ->shouldSeeReply(
                'query.search_term',
            )
            ->shouldSeeButtons(
                $this->helpButton(),
                $this->cancelButton(),
            )
        ;
    }

    public function startSuccessDataProvider(): Generator
    {
        yield 'button' => [
            'command' => $this->command('lookup'),
        ];

        yield 'command' => [
            'command' => FeedbackTelegramBotGroup::LOOKUP,
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

        $state->setStep(LookupTelegramBotConversation::STEP_SEARCH_TERM_QUERIED);
        $conversation = $this->createConversation(LookupTelegramBotConversation::class, $state);

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
        $state = (new LookupTelegramBotConversationState())
            ->setStep(LookupTelegramBotConversation::STEP_SEARCH_TERM_QUERIED)
        ;

        // messenger profile urls
        foreach (Fixtures::getMessengerUserProfileUrls() as $commandKey => [$messengerUser, $expectedSearchTermType]) {
            yield sprintf('%s profile url', $commandKey) => [
                fn ($tg) => [
                    'command' => $this->getMessengerUserProfileUrl($messengerUser),
                    'mocks' => null,
                    'state' => clone $state,
                    'expectedState' => (clone $state)
                        ->setStep(LookupTelegramBotConversation::STEP_CONFIRM_QUERIED)
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
                    ->setStep(LookupTelegramBotConversation::STEP_CONFIRM_QUERIED)
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
                    ->setStep(LookupTelegramBotConversation::STEP_CONFIRM_QUERIED)
                    ->setSearchTerm(
                        $this->addSearchTermPossibleTypes(new SearchTermTransfer($command))
                            ->setType(SearchTermType::email),
                    ),
                'shouldSeeReply' => $this->getShouldSeeReplyOnConfirmQueried(),
                'shouldSeeButtons' => $this->getShouldSeeKeyboardOnConfirmQueried(),
            ],
        ];
    }

    public function gotUnknownSearchTermSuccessDataProvider(string $key, TelegramBotConversationState $state): Generator
    {
        /** @var MessengerUserTransfer $messengerUser */

        // unknown messenger profile url
        yield sprintf('%s & messenger profile url', $key) => [
            fn ($tg) => [
                'command' => $command = 'https://unknown.com/me',
                'mocks' => null,
                'state' => clone $state,
                'expectedState' => $expectedState = (clone $state)
                    ->setStep(LookupTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
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
                        ->setStep(LookupTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
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
                    ->setStep(LookupTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
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
                        ->setStep(LookupTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
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
                        ->setStep(LookupTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
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
                        ->setStep(LookupTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
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
                    ->setStep(LookupTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
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
                    ->setStep(LookupTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
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
            ->setStep(LookupTelegramBotConversation::STEP_SEARCH_TERM_QUERIED)
            ->setChange(true)
        ;

        $conversation = $this->createConversation(LookupTelegramBotConversation::class, $state);

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
        $state = (new LookupTelegramBotConversationState())
            ->setStep(LookupTelegramBotConversation::STEP_SEARCH_TERM_QUERIED)
            ->setChange(true)
            ->setSearchTerm(new SearchTermTransfer('any'))
        ;

        // network messenger profile urls
        foreach (Fixtures::getNetworkMessengerUserProfileUrls() as $commandKey => [$messengerUser, $expectedSearchTermType, $mocks]) {
            yield sprintf('change & %s', $commandKey) => [
                fn ($tg) => [
                    'command' => $this->getMessengerUserProfileUrl($messengerUser),
                    'mocks' => $mocks,
                    'state' => clone $state,
                    'expectedState' => (clone $state)
                        ->setStep(LookupTelegramBotConversation::STEP_CONFIRM_QUERIED)
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
            yield sprintf('change & %s', $commandKey) => [
                fn ($tg) => [
                    'command' => $this->getMessengerUserProfileUrl($messengerUser),
                    'mocks' => null,
                    'state' => clone $state,
                    'expectedState' => (clone $state)
                        ->setStep(LookupTelegramBotConversation::STEP_CONFIRM_QUERIED)
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
    }

    /**
     * @param string $command
     * @param LookupTelegramBotConversationState $state
     * @param LookupTelegramBotConversationState $expectedState
     * @return void
     * @dataProvider gotSearchTermTypeSuccessDataProvider
     */
    public function testGotSearchTermTypeSuccess(
        string $command,
        LookupTelegramBotConversationState $state,
        LookupTelegramBotConversationState $expectedState
    ): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
        ]);

        $state->setStep(LookupTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED);
        $conversation = $this->createConversation(LookupTelegramBotConversation::class, $state);

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
        yield 'messenger profile url & empty' => [
            'command' => $this->getFeedbackSearchTermTypeProvider()->getSearchTermTypeComposeName(SearchTermType::messenger_profile_url),
            'state' => $state = (new LookupTelegramBotConversationState())
                ->setStep(LookupTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
                ->setSearchTerm(
                    $searchTerm = (new SearchTermTransfer('https://unknown.com/me'))
                        ->setPossibleTypes($searchTermTypes)
                ),
            'expectedState' => (clone $state)
                ->setStep(LookupTelegramBotConversation::STEP_CONFIRM_QUERIED)
                ->setSearchTerm(
                    (clone $searchTerm)
                        ->setType(SearchTermType::messenger_username)
                        ->setNormalizedText('me')
                        ->setMessenger(Messenger::unknown)
                        ->setMessengerUsername('me')
                        ->setMessengerProfileUrl($searchTerm->getText())
                ),
            'shouldSeeReplyFeedbackSearches' => [],
        ];

        // messenger usernames
        foreach (Fixtures::getMessengerUserUsernames() as $commandKey => [$messengerUser, $searchTermType]) {
            yield sprintf('%s username & empty', $commandKey) => [
                'command' => $this->getFeedbackSearchTermTypeProvider()->getSearchTermTypeComposeName($searchTermType),
                'state' => $state = (new LookupTelegramBotConversationState())
                    ->setStep(LookupTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
                    ->setSearchTerm(
                        $searchTerm = (new SearchTermTransfer($messengerUser->getUsername()))
                            ->setPossibleTypes($searchTermTypes)
                    ),
                'expectedState' => (clone $state)
                    ->setStep(LookupTelegramBotConversation::STEP_CONFIRM_QUERIED)
                    ->setSearchTerm(
                        $this->getMessengerUsernameSearchTerm($messengerUser)
                            ->setType($searchTermType)
                            ->setMessengerUser(null)
                            ->setPossibleTypes($searchTerm->getPossibleTypes())
                    ),
                'shouldSeeReplyFeedbackSearches' => [],
            ];
        }

        $instagramMessengerUser = Fixtures::getInstagramMessengerUserTransferFixture(3);

        yield 'instagram username & not empty' => [
            'command' => $this->getFeedbackSearchTermTypeProvider()->getSearchTermTypeComposeName(SearchTermType::instagram_username),
            'state' => $state = (new LookupTelegramBotConversationState())
                ->setStep(LookupTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
                ->setSearchTerm(
                    $searchTerm = (new SearchTermTransfer($instagramMessengerUser->getUsername()))
                        ->setPossibleTypes($searchTermTypes)
                ),
            'expectedState' => (clone $state)
                ->setStep(LookupTelegramBotConversation::STEP_CONFIRM_QUERIED)
                ->setSearchTerm(
                    $this->getMessengerUsernameSearchTerm($instagramMessengerUser)
                        ->setType(SearchTermType::instagram_username)
                        ->setMessengerUser(null)
                        ->setPossibleTypes($searchTerm->getPossibleTypes())
                ),
            'shouldSeeReplyFeedbackSearches' => [1],
        ];

        // unknown messenger username
        yield 'messenger username & empty' => [
            'command' => $this->getFeedbackSearchTermTypeProvider()->getSearchTermTypeComposeName(SearchTermType::messenger_username),
            'state' => $state = (new LookupTelegramBotConversationState())
                ->setStep(LookupTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
                ->setSearchTerm(
                    $searchTerm = (new SearchTermTransfer('me'))
                        ->setPossibleTypes($searchTermTypes)
                ),
            'expectedState' => (clone $state)
                ->setStep(LookupTelegramBotConversation::STEP_CONFIRM_QUERIED)
                ->setSearchTerm(
                    (clone $searchTerm)
                        ->setType(SearchTermType::messenger_username)
                        ->setMessenger(Messenger::unknown)
                        ->setMessengerUsername('me')
                ),
            'shouldSeeReplyFeedbackSearches' => [],
        ];

        // non-messengers
        foreach (Fixtures::NON_MESSENGER_SEARCH_TYPES as $typeKey => [$searchTermType, $searchTermText, $searchTermNormalizedText]) {
            yield sprintf('%s & empty', $typeKey) => [
                'command' => $this->getFeedbackSearchTermTypeProvider()->getSearchTermTypeComposeName($searchTermType),
                'state' => $state = (new LookupTelegramBotConversationState())
                    ->setStep(LookupTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
                    ->setSearchTerm(
                        $searchTerm = (new SearchTermTransfer($searchTermText))
                            ->setPossibleTypes($searchTermTypes)
                    ),
                'expectedState' => (clone $state)
                    ->setStep(LookupTelegramBotConversation::STEP_CONFIRM_QUERIED)
                    ->setSearchTerm(
                        (clone $searchTerm)
                            ->setType($searchTermType)
                            ->setNormalizedText($searchTermNormalizedText)
                    ),
                'shouldSeeReplyFeedbackSearches' => [],
            ];
        }
    }

    /**
     * @param string $command
     * @param LookupTelegramBotConversationState $state
     * @param LookupTelegramBotConversationState $expectedState
     * @return void
     * @dataProvider gotSearchTermTypeChangeSuccess
     */
    public function testGotSearchTermTypeChangeSuccess(
        string $command,
        LookupTelegramBotConversationState $state,
        LookupTelegramBotConversationState $expectedState
    ): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
        ]);

        $state
            ->setStep(LookupTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
            ->setChange(true)
        ;
        $conversation = $this->createConversation(LookupTelegramBotConversation::class, $state);

        $this
            ->type($command)
            ->shouldSeeActiveConversation($conversation->getClass(), $expectedState)
            ->shouldSeeReply(...$this->getShouldSeeReplyOnConfirmQueried())
            ->shouldSeeButtons(...$this->getShouldSeeKeyboardOnConfirmQueried())
        ;
    }

    public function gotSearchTermTypeChangeSuccess(): Generator
    {
        $generalState = (new LookupTelegramBotConversationState())
            ->setStep(LookupTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
            ->setChange(true)
        ;
        $searchTermTypes = SearchTermType::cases();

        /** @var MessengerUserTransfer $messengerUser */

        // unknown messenger profile url
        yield 'change & messenger profile url' => [
            'command' => $this->getFeedbackSearchTermTypeProvider()->getSearchTermTypeComposeName(SearchTermType::messenger_profile_url),
            'state' => $state = (clone $generalState)
                ->setSearchTerm(
                    $searchTerm = (new SearchTermTransfer('https://unknown.com/me'))
                        ->setPossibleTypes($searchTermTypes)
                ),
            'expectedState' => (clone $state)
                ->setStep(LookupTelegramBotConversation::STEP_CONFIRM_QUERIED)
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
                'command' => $this->getFeedbackSearchTermTypeProvider()->getSearchTermTypeComposeName($searchTermType),
                'state' => $state = (clone $generalState)
                    ->setSearchTerm(
                        $searchTerm = (new SearchTermTransfer($messengerUser->getUsername()))
                            ->setPossibleTypes($searchTermTypes)
                    ),
                'expectedState' => (clone $state)
                    ->setStep(LookupTelegramBotConversation::STEP_CONFIRM_QUERIED)
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
            'command' => $this->getFeedbackSearchTermTypeProvider()->getSearchTermTypeComposeName(SearchTermType::messenger_username),
            'state' => $state = (clone $generalState)
                ->setSearchTerm(
                    $searchTerm = (new SearchTermTransfer('me'))
                        ->setPossibleTypes($searchTermTypes)
                ),
            'expectedState' => (clone $state)
                ->setStep(LookupTelegramBotConversation::STEP_CONFIRM_QUERIED)
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
                'command' => $this->getFeedbackSearchTermTypeProvider()->getSearchTermTypeComposeName($searchTermType),
                'state' => $state = (clone $generalState)
                    ->setSearchTerm(
                        $searchTerm = (new SearchTermTransfer($searchTermText))
                            ->setPossibleTypes($searchTermTypes)
                    ),
                'expectedState' => (clone $state)
                    ->setStep(LookupTelegramBotConversation::STEP_CONFIRM_QUERIED)
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
     * @param TelegramBotConversationState $state
     * @param TelegramBotConversationState $expectedState
     * @param string $expectedReply
     * @param array $expectedButtons
     * @return void
     * @dataProvider gotConfirmChangeSuccessDataProvider
     */
    public function testGotConfirmChangeSuccess(
        string $command,
        TelegramBotConversationState $state,
        TelegramBotConversationState $expectedState,
        string $expectedReply,
        array $expectedButtons
    ): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
        ]);

        $state->setStep(LookupTelegramBotConversation::STEP_CONFIRM_QUERIED);

        $conversation = $this->createConversation(LookupTelegramBotConversation::class, $state);

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
                LookupTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
                'query.search_term',
                [
                    $this->leaveAsButton(),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
            ],
        ];

        foreach ($commands as $commandKey => [$command, $expectedStep, $expectedText, $expectedButtons]) {
            yield sprintf('%s', $commandKey) => [
                'command' => $command,
                'state' => $state = (new LookupTelegramBotConversationState())
                    ->setStep(LookupTelegramBotConversation::STEP_CONFIRM_QUERIED)
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
     * @param LookupTelegramBotConversationState $state
     * @return void
     * @dataProvider gotCancelSuccessDataProvider
     */
    public function testGotCancelSuccess(LookupTelegramBotConversationState $state): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
        ]);

        $expectedState = (clone $state)
            ->setStep(LookupTelegramBotConversation::STEP_CANCEL_PRESSED)
        ;
        $conversation = $this->createConversation(LookupTelegramBotConversation::class, $state);

        $this
            ->type($this->cancelButton())
            ->shouldNotSeeActiveConversation($conversation->getClass(), $expectedState)
            ->shouldSeeReply(
                'reply.canceled'
            )
            ->shouldSeeChooseAction()
        ;
    }

    public function gotCancelSuccessDataProvider(): Generator
    {
        $state = new LookupTelegramBotConversationState();

        yield 'search term' => [
            'state' => (clone $state)
                ->setStep(LookupTelegramBotConversation::STEP_SEARCH_TERM_QUERIED),
        ];

        yield 'change search term' => [
            'state' => (clone $state)
                ->setStep(LookupTelegramBotConversation::STEP_SEARCH_TERM_QUERIED)
                ->setSearchTerm(
                    new SearchTermTransfer('any')
                )
                ->setChange(true),
        ];

        yield 'search term type' => [
            'state' => (clone $state)
                ->setStep(LookupTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
                ->setSearchTerm(
                    new SearchTermTransfer('any')
                ),
        ];

        yield 'change search term type' => [
            'state' => (clone $state)
                ->setStep(LookupTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
                ->setSearchTerm(
                    new SearchTermTransfer('any')
                )
                ->setChange(true),
        ];

        yield 'confirm' => [
            'state' => (clone $state)
                ->setStep(LookupTelegramBotConversation::STEP_CONFIRM_QUERIED)
                ->setSearchTerm(
                    new SearchTermTransfer('any')
                ),
        ];
    }

    /**
     * @param LookupTelegramBotConversationState $state
     * @return void
     * @dataProvider gotConfirmWithEmptyListSuccessDataProvider
     */
    public function testGotConfirmWithEmptyListSuccess(
        LookupTelegramBotConversationState $state
    ): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
        ]);

        $conversation = $this->createConversation(LookupTelegramBotConversation::class, $state);

        $feedbackSearchSearchRepository = $this->getFeedbackSearchSearchRepository();
        $previousFeedbackSearchSearchCount = $feedbackSearchSearchRepository->count([]);

        $this
            ->type($this->confirmButton())
            ->shouldSeeReply(
                'reply.empty_list'
            )
            ->shouldSeeChooseAction(
                'reply.will_notify'
            )
        ;

        $this->assertConversationInactive($conversation);

        $this->assertEquals($previousFeedbackSearchSearchCount + 1, $feedbackSearchSearchRepository->count([]));

        $feedbackSearchSearch = $feedbackSearchSearchRepository->findOneBy([
            'messengerUser' => $this->getUpdateMessengerUser(),
            'searchTermText' => $state->getSearchTerm()->getText(),
            'searchTermType' => $state->getSearchTerm()->getType(),
        ]);

        $this->assertNotNull($feedbackSearchSearch);
    }

    public function gotConfirmWithEmptyListSuccessDataProvider(): Generator
    {
        $generalState = (new LookupTelegramBotConversationState())
            ->setStep(LookupTelegramBotConversation::STEP_CONFIRM_QUERIED)
        ;

        yield 'unknown' => [
            'state' => (clone $generalState)
                ->setStep(LookupTelegramBotConversation::STEP_CONFIRM_QUERIED)
                ->setSearchTerm(
                    (new SearchTermTransfer('any'))
                        ->setType(SearchTermType::unknown)
                ),
        ];
    }

    /**
     * @param LookupTelegramBotConversationState $state
     * @param array $shouldSeeReplyFeedbackSearches
     * @return void
     * @dataProvider gotConfirmWithNonEmptyListSuccessDataProvider
     */
    public function testGotConfirmWithNonEmptyListSuccess(
        LookupTelegramBotConversationState $state,
        array $shouldSeeReplyFeedbackSearches
    ): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
            FeedbackSearch::class,
        ]);

        $conversation = $this->createConversation(LookupTelegramBotConversation::class, $state);

        $feedbackSearchSearchRepository = $this->getFeedbackSearchSearchRepository();
        $previousFeedbackSearchSearchCount = $feedbackSearchSearchRepository->count([]);

        $shouldSeeReply = [];
        $shouldSeeReply[] = 'reply.title';
        $shouldSeeReply = array_merge($shouldSeeReply, array_fill(0, count($shouldSeeReplyFeedbackSearches), 'somebody_from'));

        $this
            ->type($this->confirmButton())
            ->shouldSeeReply(...$shouldSeeReply)
            ->shouldSeeChooseAction()
        ;

        $this->assertConversationInactive($conversation);

        $this->assertEquals($previousFeedbackSearchSearchCount + 1, $feedbackSearchSearchRepository->count([]));

        $feedbackSearchSearch = $feedbackSearchSearchRepository->findOneBy([
            'messengerUser' => $this->getUpdateMessengerUser(),
            'searchTermText' => $state->getSearchTerm()->getText(),
            'searchTermType' => $state->getSearchTerm()->getType(),
        ]);

        $this->assertNotNull($feedbackSearchSearch);
    }

    public function gotConfirmWithNonEmptyListSuccessDataProvider(): Generator
    {
        $generalState = (new LookupTelegramBotConversationState())
            ->setStep(LookupTelegramBotConversation::STEP_CONFIRM_QUERIED)
        ;

        yield 'instagram username' => [
            'state' => (clone $generalState)
                ->setStep(LookupTelegramBotConversation::STEP_CONFIRM_QUERIED)
                ->setSearchTerm(
                    $this->getMessengerUsernameSearchTerm(Fixtures::getInstagramMessengerUserTransferFixture(3))
                        ->setType(SearchTermType::instagram_username)
                ),
            'shouldSeeReplyFeedbackSearches' => [1, 2],
        ];
    }

    private function getShouldSeeReplyOnSearchTermTypeQueried(): array
    {
        return [
            'query.search_term_type',
        ];
    }

    private function getShouldSeeKeyboardOnSearchTermTypeQueried(TelegramBotAwareHelper $tg, LookupTelegramBotConversationState $state): array
    {
        return [
            ...array_map(
                fn (SearchTermType $type) => $tg->button($this->getFeedbackSearchTermTypeProvider()->getSearchTermTypeComposeName($type)),
                $state->getSearchTerm()->getPossibleTypes()
            ),
            $this->backButton(),
            $this->helpButton(),
            $this->cancelButton(),
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
            $this->confirmButton(),
            '📝 keyboard.change_search_term',
            $this->backButton(),
            $this->helpButton(),
            $this->cancelButton(),
        ];
    }
}
