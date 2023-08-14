<?php

declare(strict_types=1);

namespace App\Tests\Functional\Telegram;

use App\Entity\Telegram\CreateFeedbackTelegramConversationState;
use App\Enum\Feedback\Rating;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Messenger\Messenger;
use App\Enum\Telegram\TelegramView;
use App\Object\Feedback\SearchTermTransfer;
use App\Object\Messenger\MessengerUserTransfer;
use App\Service\Telegram\Channel\FeedbackTelegramChannel;
use App\Service\Telegram\Chat\ChooseActionTelegramChatSender;
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
     * @param callable $fn
     * @dataProvider onStartStepDataProvider
     */
    public function testOnStartStepSuccess(callable $fn): void
    {
        extract($fn($this->tg));

        $this->getEntityManager()->remove($this->getTelegramConversation());
        $this->getEntityManager()->flush();

        $this
            ->command($command)
            ->conversation($conversationClass, $conversationState)
            ->invoke()
            ->expectsState(
                new CreateFeedbackTelegramConversationState(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_QUERIED)
            )
            ->shouldSeeReply(...$this->getShouldSeeReplyOnSearchTermQueried())
            ->shouldSeeKeyboard(...$this->getShouldSeeKeyboardOnSearchTermQueried($this->tg))
        ;

        $messengerUser = $this->getMessengerUserRepository()->findOneByMessengerAndIdentifier(
            Messenger::telegram,
            (string) $this->getUpdateUserId()
        );

        $this->assertNotNull($messengerUser);
    }

    public function onStartStepDataProvider(): Generator
    {
        yield sprintf('start_step_as_%s', 'text') => [
            fn ($tg) => [
                'command' => ChooseActionTelegramChatSender::getCreateButton($tg)->getText(),
                'conversationClass' => null,
                'conversationState' => null,
            ],
        ];

        yield sprintf('start_step_as_%s', 'command') => [
            fn ($tg) => [
                'command' => FeedbackTelegramChannel::CREATE,
            ],
        ];
    }

    /**
     * @param callable $fn
     * @dataProvider onSearchTermStepDataProvider
     */
    public function testOnSearchTermStepSuccess(callable $fn): void
    {
        extract($fn($this->tg));
        $state->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_QUERIED);

        $mocks && $mocks();

        $this->type($command, $state, $expectedState, conversationClass: CreateFeedbackTelegramConversation::class)
            ->shouldSeeReply(...$shouldSeeReply)
            ->shouldSeeKeyboard(...$shouldSeeKeyboard)
        ;
    }

    public function onSearchTermStepDataProvider(): Generator
    {
        $state = (new CreateFeedbackTelegramConversationState())
            ->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_QUERIED)
        ;

        // messenger profile urls
        foreach (Fixtures::getMessengerUserProfileUrls() as $commandKey => [$messengerUser, $expectedSearchTermType]) {
            yield sprintf('search_term_step_as_%s_profile_url', $commandKey) => [
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
                    'shouldSeeKeyboard' => $this->getShouldSeeKeyboardOnRatingQueried($tg),
                ],
            ];
        }

        // normalized phone number
        yield sprintf('search_term_step_as_normalized_%s', SearchTermType::phone_number->name) => [
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
                'shouldSeeKeyboard' => $this->getShouldSeeKeyboardOnRatingQueried($tg),
            ],
        ];

        // normalized email
        yield sprintf('search_term_step_as_normalized_%s', SearchTermType::email->name) => [
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
                'shouldSeeKeyboard' => $this->getShouldSeeKeyboardOnRatingQueried($tg),
            ],
        ];

        yield from $this->onSearchTermStepToTypeQueriedDataProvider('search_term_step', $state);
    }

    public function onSearchTermStepToTypeQueriedDataProvider(string $key, CreateFeedbackTelegramConversationState $state): Generator
    {
        /** @var MessengerUserTransfer $messengerUser */

        // unknown messenger profile url
        yield sprintf('%s_as_messenger_profile_url', $key) => [
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
                'shouldSeeKeyboard' => $this->getShouldSeeKeyboardOnSearchTermTypeQueried($tg, $expectedState),
            ],
        ];

        // messenger usernames
        foreach (Fixtures::getMessengerUserUsernames() as $commandKey => [$messengerUser, $expectedSearchTermPossibleType]) {
            yield sprintf('%s_as_%s_username', $key, $commandKey) => [
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
                    'shouldSeeKeyboard' => $this->getShouldSeeKeyboardOnSearchTermTypeQueried($tg, $expectedState),
                ],
            ];
        }

        // unknown messenger username
        yield sprintf('%s_as_messenger_username', $key) => [
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
                'shouldSeeKeyboard' => $this->getShouldSeeKeyboardOnSearchTermTypeQueried($tg, $expectedState),
            ],
        ];

        // person names
        foreach (Fixtures::PERSONS as $personKey => $personName) {
            yield sprintf('%s_as_person_name_%s', $key, $personKey) => [
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
                    'shouldSeeKeyboard' => $this->getShouldSeeKeyboardOnSearchTermTypeQueried($tg, $expectedState),
                ],
            ];
        }

        // place names
        foreach (Fixtures::PLACES as $placeKey => $placeName) {
            yield sprintf('%s_as_place_name_%s', $key, $placeKey) => [
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
                    'shouldSeeKeyboard' => $this->getShouldSeeKeyboardOnSearchTermTypeQueried($tg, $expectedState),
                ],
            ];
        }

        // organizations names
        foreach (Fixtures::ORGANIZATIONS as $orgKey => $orgName) {
            yield sprintf('%s_as_organization_name_%s', $key, $orgKey) => [
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
                    'shouldSeeKeyboard' => $this->getShouldSeeKeyboardOnSearchTermTypeQueried($tg, $expectedState),
                ],
            ];
        }

        // phone number
        yield sprintf('search_term_step_as_%s', SearchTermType::phone_number->name) => [
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
                'shouldSeeKeyboard' => $this->getShouldSeeKeyboardOnSearchTermTypeQueried($tg, $expectedState),
            ],
        ];

        // email
        yield sprintf('%s_as_%s', $key, SearchTermType::email->name) => [
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
                'shouldSeeKeyboard' => $this->getShouldSeeKeyboardOnSearchTermTypeQueried($tg, $expectedState),
            ],
        ];
    }

    /**
     * @param callable $fn
     * @return void
     * @dataProvider onChangeSearchTermStepDataProvider
     */
    public function testOnChangeSearchTermStepSuccess(callable $fn): void
    {
        extract($fn($this->tg));
        $state
            ->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_QUERIED)
            ->setChange(true)
        ;

        $mocks && $mocks($this);

        $this->type($command, $state, $expectedState, conversationClass: CreateFeedbackTelegramConversation::class)
            ->shouldSeeReply(...$shouldSeeReply)
            ->shouldSeeKeyboard(...$shouldSeeKeyboard)
        ;
    }

    public function onChangeSearchTermStepDataProvider(): Generator
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
            yield sprintf('change_search_term_step_as_%s', $commandKey) => [
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
                    'shouldSeeReply' => $this->getShouldSeeReplyOnConfirmQueried($tg, $expectedState),
                    'shouldSeeKeyboard' => $this->getShouldSeeKeyboardOnConfirmQueried($tg, $expectedState),
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
                    'expectedState' => $expectedState = (clone $state)
                        ->setStep(CreateFeedbackTelegramConversation::STEP_CONFIRM_QUERIED)
                        ->setChange(false)
                        ->setSearchTerm(
                            $this->addSearchTermPossibleTypes(
                                $this->getMessengerProfileUrlSearchTerm($messengerUser)
                            )->setType($expectedSearchTermType)
                                ->setMessengerUser(null)
                        ),
                    'shouldSeeReply' => $this->getShouldSeeReplyOnConfirmQueried($tg, $expectedState),
                    'shouldSeeKeyboard' => $this->getShouldSeeKeyboardOnConfirmQueried($tg, $expectedState),
                ],
            ];
        }

        yield from $this->onSearchTermStepToTypeQueriedDataProvider('change_search_term_step', $state);
    }

    /**
     * @param callable $fn
     * @return void
     * @dataProvider onSearchTermTypeStepDataProvider
     */
    public function testOnSearchTermTypeStepSuccess(callable $fn): void
    {
        extract($fn($this->tg));
        $state->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED);

        $this->type($command, $state, $expectedState, conversationClass: CreateFeedbackTelegramConversation::class)
            ->shouldSeeReply(...$this->getShouldSeeReplyOnRatingQueried())
            ->shouldSeeKeyboard(...$this->getShouldSeeKeyboardOnRatingQueried($this->tg))
        ;
    }

    public function onSearchTermTypeStepDataProvider(): Generator
    {
        $searchTermTypes = SearchTermType::cases();

        /** @var MessengerUserTransfer $messengerUser */

        // unknown messenger profile url
        yield 'search_term_type_step_as_messenger_profile_url' => [
            fn ($tg) => [
                'command' => CreateFeedbackTelegramConversation::getSearchTermTypeButton(SearchTermType::messenger_profile_url, $tg)->getText(),
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
            ],
        ];

        // messenger usernames
        foreach (Fixtures::getMessengerUserUsernames() as $commandKey => [$messengerUser, $searchTermType]) {
            yield sprintf('search_term_type_step_as_%s_username', $commandKey) => [
                fn ($tg) => [
                    'command' => CreateFeedbackTelegramConversation::getSearchTermTypeButton($searchTermType, $tg)->getText(),
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
                ],
            ];
        }

        // unknown messenger username
        yield 'search_term_type_step_as_messenger_username' => [
            fn ($tg) => [
                'command' => CreateFeedbackTelegramConversation::getSearchTermTypeButton(SearchTermType::messenger_username, $tg)->getText(),
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
            ],
        ];

        // non-messengers
        foreach (Fixtures::NON_MESSENGER_SEARCH_TYPES as $typeKey => [$searchTermType, $searchTermText, $searchTermNormalizedText]) {
            yield sprintf('search_term_type_step_as_%s', $typeKey) => [
                fn ($tg) => [
                    'command' => CreateFeedbackTelegramConversation::getSearchTermTypeButton($searchTermType, $tg)->getText(),
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
                ],
            ];
        }
    }

    /**
     * @param callable $fn
     * @return void
     * @dataProvider onChangeSearchTermTypeStepDataProvider
     */
    public function testOnChangeSearchTermTypeStepSuccess(callable $fn): void
    {
        extract($fn($this->tg));
        $state
            ->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
            ->setChange(true)
        ;

//        $mocks && $mocks($this);

        $this->type($command, $state, $expectedState, conversationClass: CreateFeedbackTelegramConversation::class)
            ->shouldSeeReply(...$this->getShouldSeeReplyOnConfirmQueried($this->tg, $expectedState))
            ->shouldSeeKeyboard(...$this->getShouldSeeKeyboardOnConfirmQueried($this->tg, $expectedState))
        ;
    }

    public function onChangeSearchTermTypeStepDataProvider(): Generator
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
        yield 'change_search_term_type_step_as_messenger_profile_url' => [
            fn ($tg) => [
                'command' => CreateFeedbackTelegramConversation::getSearchTermTypeButton(SearchTermType::messenger_profile_url, $tg)->getText(),
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
            ],
        ];

        // messenger usernames
        foreach (Fixtures::getMessengerUserUsernames() as $commandKey => [$messengerUser, $searchTermType, $mocks]) {
            yield sprintf('change_search_term_type_step_as_%s_username', $commandKey) => [
                fn ($tg) => [
                    'command' => CreateFeedbackTelegramConversation::getSearchTermTypeButton($searchTermType, $tg)->getText(),
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
                ],
            ];
        }

        // unknown messenger username
        yield 'change_search_term_type_step_as_messenger_username' => [
            fn ($tg) => [
                'command' => CreateFeedbackTelegramConversation::getSearchTermTypeButton(SearchTermType::messenger_username, $tg)->getText(),
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
            ],
        ];

        // non-messengers
        foreach (Fixtures::NON_MESSENGER_SEARCH_TYPES as $typeKey => [$searchTermType, $searchTermText, $searchTermNormalizedText]) {
            yield sprintf('change_search_term_type_step_as_%s', $typeKey) => [
                fn ($tg) => [
                    'command' => CreateFeedbackTelegramConversation::getSearchTermTypeButton($searchTermType, $tg)->getText(),
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
                ],
            ];
        }
    }

    /**
     * @param Rating $rating
     * @return void
     * @dataProvider onRatingStepDataProvider
     */
    public function testOnRatingStepSuccess(Rating $rating): void
    {
        $command = CreateFeedbackTelegramConversation::getRatingButton($rating, $this->tg)->getText();
        $state = (new CreateFeedbackTelegramConversationState())
            ->setStep(CreateFeedbackTelegramConversation::STEP_RATING_QUERIED)
            ->setSearchTerm(new SearchTermTransfer('any'))
        ;
        $expectedState = (clone $state)
            ->setStep(CreateFeedbackTelegramConversation::STEP_DESCRIPTION_QUERIED)
            ->setRating($rating)
        ;

        $this->type($command, $state, $expectedState, conversationClass: CreateFeedbackTelegramConversation::class)
            ->shouldSeeReply(...$this->getShouldSeeReplyOnDescriptionQueried())
            ->shouldSeeKeyboard(...$this->getShouldSeeKeyboardOnDescriptionQueried($this->tg))
        ;
    }

    public function onRatingStepDataProvider(): Generator
    {
        foreach (Rating::cases() as $rating) {
            yield sprintf('rating_step_as_%s', $rating->name) => ['rating' => $rating];
        }
    }

    /**
     * @param Rating $rating
     * @return void
     * @dataProvider onChangeRatingStepDataProvider
     */
    public function testOnChangeRatingStepSuccess(Rating $rating): void
    {
        $command = CreateFeedbackTelegramConversation::getRatingButton($rating, $this->tg)->getText();
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
        $expectedState = (clone $state)
            ->setStep(CreateFeedbackTelegramConversation::STEP_CONFIRM_QUERIED)
            ->setRating($rating)
            ->setChange(false)
        ;

        $this->type($command, $state, $expectedState, conversationClass: CreateFeedbackTelegramConversation::class)
            ->shouldSeeReply(...$this->getShouldSeeReplyOnConfirmQueried($this->tg, $expectedState))
            ->shouldSeeKeyboard(...$this->getShouldSeeKeyboardOnConfirmQueried($this->tg, $expectedState))
        ;
    }

    public function onChangeRatingStepDataProvider(): Generator
    {
        foreach (Rating::cases() as $rating) {
            yield sprintf('change_rating_step_as_%s', $rating->name) => ['rating' => $rating];
        }
    }

    /**
     * @param callable $fn
     * @return void
     * @dataProvider onDescriptionStepSuccessDataProvider
     */
    public function testOnDescriptionStepSuccess(callable $fn): void
    {
        extract($fn($this->tg));
        $state->setStep(CreateFeedbackTelegramConversation::STEP_DESCRIPTION_QUERIED);

        $expectedState = (clone $state)
            ->setStep(CreateFeedbackTelegramConversation::STEP_CONFIRM_QUERIED)
            ->setDescription($expectedDescription)
        ;

        $mocks && $mocks($this);

        $this->type($command, $state, $expectedState, conversationClass: CreateFeedbackTelegramConversation::class)
            ->shouldSeeReply(...$this->getShouldSeeReplyOnConfirmQueried($this->tg, $expectedState))
            ->shouldSeeKeyboard(...$this->getShouldSeeKeyboardOnConfirmQueried($this->tg, $expectedState))
        ;
    }

    public function onDescriptionStepSuccessDataProvider(): Generator
    {
        $generalState = (new CreateFeedbackTelegramConversationState())
            ->setStep(CreateFeedbackTelegramConversation::STEP_DESCRIPTION_QUERIED)
        ;

        $description = 'any';

        /** @var MessengerUserTransfer $messengerUser */

        $commands = [
            'leave_empty' => [
                fn ($tg) => CreateFeedbackTelegramConversation::getLeaveEmptyButton($tg)->getText(),
                null,
            ],
            'type_something' => [
                fn ($tg) => $description,
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
                    yield sprintf('description_step_as_%s_as_%s_profile_url_as_%s', $commandKey, $messengerProfileUrlKey, $rating->name) => [
                        fn ($tg) => [
                            'command' => $command($tg),
                            'mocks' => $mocks,
                            'state' => (clone $generalState)
                                ->setSearchTerm($this->getMessengerProfileUrlSearchTerm($messengerUser)
                                    ->setType($searchTermType))
                                ->setRating($rating),
                            'expectedDescription' => $expectedDescription,
                        ],
                    ];
                }

                // unknown messenger profile url
                yield sprintf('description_step_as_%s_as_unknown_profile_url_as_%s', $commandKey, $rating->name) => [
                    fn ($tg) => [
                        'command' => $command($tg),
                        'mocks' => null,
                        'state' => (clone $generalState)
                            ->setSearchTerm(
                                (new SearchTermTransfer('https://unknown.com/me'))
                                    ->setType(SearchTermType::messenger_profile_url)
                                    ->setMessenger(Messenger::unknown)
                            )
                            ->setRating($rating),
                        'expectedDescription' => $expectedDescription,
                    ],
                ];

                // messenger usernames
                foreach (Fixtures::getMessengerUserUsernames() as $messengerUsernameKey => [$messengerUser, $searchTermType, $mocks]) {
                    yield sprintf('description_step_as_%s_as_%s_username_as_%s', $commandKey, $messengerUsernameKey, $rating->name) => [
                        fn ($tg) => [
                            'command' => $command($tg),
                            'mocks' => $mocks,
                            'state' => (clone $generalState)
                                ->setSearchTerm(
                                    $this->getMessengerUsernameSearchTerm($messengerUser)
                                        ->setType($searchTermType)
                                )
                                ->setRating($rating),
                            'expectedDescription' => $expectedDescription,
                        ],
                    ];
                }

                // unknown messenger username
                yield sprintf('description_step_as_%s_as_unknown_username_as_%s', $commandKey, $rating->name) => [
                    fn ($tg) => [
                        'command' => $command($tg),
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
                    ],
                ];

                // non-messengers
                foreach (Fixtures::NON_MESSENGER_SEARCH_TYPES as $simpleKey => [$searchTermType, $searchTermText, $searchTermNormalizedText]) {
                    yield sprintf('description_step_as_%s_as_%s_as_%s', $commandKey, $simpleKey, $rating->name) => [
                        fn ($tg) => [
                            'command' => $command($tg),
                            'mocks' => null,
                            'state' => (clone $generalState)
                                ->setSearchTerm(
                                    (new SearchTermTransfer($searchTermText))
                                        ->setType($searchTermType)
                                        ->setNormalizedText($searchTermNormalizedText)
                                )
                                ->setRating($rating),
                            'expectedDescription' => $expectedDescription,
                        ],
                    ];
                }
            }
        }
    }

    /**
     * @param callable $fn
     * @return void
     * @dataProvider onChangeDescriptionStepSuccessDataProvider
     */
    public function testOnChangeDescriptionStepSuccess(callable $fn): void
    {
        extract($fn($this->tg));
        $state
            ->setStep(CreateFeedbackTelegramConversation::STEP_DESCRIPTION_QUERIED)
            ->setChange(true)
        ;

        $expectedState = (clone $state)
            ->setStep(CreateFeedbackTelegramConversation::STEP_CONFIRM_QUERIED)
            ->setChange(false)
            ->setDescription($expectedDescription)
        ;

        $mocks && $mocks($this);

        $this->type($command, $state, $expectedState, conversationClass: CreateFeedbackTelegramConversation::class)
            ->shouldSeeReply(...$this->getShouldSeeReplyOnConfirmQueried($this->tg, $expectedState))
            ->shouldSeeKeyboard(...$this->getShouldSeeKeyboardOnConfirmQueried($this->tg, $expectedState))
        ;
    }

    public function onChangeDescriptionStepSuccessDataProvider(): Generator
    {
        $generalState = (new CreateFeedbackTelegramConversationState())
            ->setStep(CreateFeedbackTelegramConversation::STEP_DESCRIPTION_QUERIED)
        ;

        /** @var MessengerUserTransfer $messengerUser */

        $commands = [
            'empty_leave_empty' => [
                null,
                fn ($tg) => CreateFeedbackTelegramConversation::getLeaveEmptyButton($tg)->getText(),
                null,
            ],
            'empty_type_something' => [
                null,
                fn ($tg) => 'any',
                'any',
            ],
            'make_empty' => [
                'any',
                fn ($tg) => CreateFeedbackTelegramConversation::getMakeEmptyButton($tg)->getText(),
                null,
            ],
            'change' => [
                'any1',
                fn ($tg) => 'any2',
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
                    yield sprintf('change_description_step_as_%s_as_%s_profile_url_as_%s', $commandKey, $messengerProfileUrlKey, $rating->name) => [
                        fn ($tg) => [
                            'command' => $command($tg),
                            'mocks' => $mocks,
                            'state' => (clone $generalState)
                                ->setSearchTerm($this->getMessengerProfileUrlSearchTerm($messengerUser)
                                    ->setType($searchTermType))
                                ->setRating($rating)
                                ->setDescription($description),
                            'expectedDescription' => $expectedDescription,
                        ],
                    ];
                }

                // unknown messenger profile url
                yield sprintf('change_description_step_as_%s_as_unknown_profile_url_as_%s', $commandKey, $rating->name) => [
                    fn ($tg) => [
                        'command' => $command($tg),
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
                    ],
                ];

                // messenger usernames
                foreach (Fixtures::getMessengerUserUsernames() as $messengerUsernameKey => [$messengerUser, $searchTermType, $mocks]) {
                    yield sprintf('change_description_step_as_%s_as_%s_username_as_%s', $commandKey, $messengerUsernameKey, $rating->name) => [
                        fn ($tg) => [
                            'command' => $command($tg),
                            'mocks' => $mocks,
                            'state' => (clone $generalState)
                                ->setSearchTerm(
                                    $this->getMessengerUsernameSearchTerm($messengerUser)
                                        ->setType($searchTermType)
                                )
                                ->setRating($rating)
                                ->setDescription($description),
                            'expectedDescription' => $expectedDescription,
                        ],
                    ];
                }

                // unknown messenger username
                yield sprintf('change_description_step_as_%s_as_unknown_username_as_%s', $commandKey, $rating->name) => [
                    fn ($tg) => [
                        'command' => $command($tg),
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
                    ],
                ];

                // non-messengers
                foreach (Fixtures::NON_MESSENGER_SEARCH_TYPES as $simpleKey => [$searchTermType, $searchTermText, $searchTermNormalizedText]) {
                    yield sprintf('change_description_step_as_%s_as_%s_as_%s', $commandKey, $simpleKey, $rating->name) => [
                        fn ($tg) => [
                            'command' => $command($tg),
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
                        ],
                    ];
                }
            }
        }
    }

    /**
     * @param callable $fn
     * @return void
     * @dataProvider onConfirmStepSuccessDataProvider
     */
    public function testOnConfirmStepSuccess1(callable $fn): void
    {
        extract($fn($this->tg));
        $state->setStep(CreateFeedbackTelegramConversation::STEP_CONFIRM_QUERIED);

        $this->type($command, $state, $expectedState, conversationClass: CreateFeedbackTelegramConversation::class)
            ->shouldSeeReply($expectedReply)
            ->shouldSeeKeyboard(...$expectedKeyboard)
        ;
    }

    public function onConfirmStepSuccessDataProvider(): Generator
    {
        $searchTerm = new SearchTermTransfer('any');
        $description = 'whatever';

        $commands = [
            'search_term_change' => [
                fn ($tg) => CreateFeedbackTelegramConversation::getChangeSearchTermButton($tg)->getText(),
                $description,
                CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_QUERIED,
                'query.search_term',
                fn ($tg) => [
                    CreateFeedbackTelegramConversation::getLeaveAsButton($searchTerm->getText(), $tg),
                    CreateFeedbackTelegramConversation::getCancelButton($tg),
                ],
            ],
            'rating_change' => [
                fn ($tg) => CreateFeedbackTelegramConversation::getChangeRatingButton($tg)->getText(),
                $description,
                CreateFeedbackTelegramConversation::STEP_RATING_QUERIED,
                'query.rating',
                fn ($tg) => [
                    ...CreateFeedbackTelegramConversation::getRatingButtons($tg),
                    CreateFeedbackTelegramConversation::getCancelButton($tg),
                ],
            ],
            'empty_description_change' => [
                fn ($tg) => CreateFeedbackTelegramConversation::getAddDescriptionButton($tg)->getText(),
                null,
                CreateFeedbackTelegramConversation::STEP_DESCRIPTION_QUERIED,
                'query.description',
                fn ($tg) => [
                    CreateFeedbackTelegramConversation::getLeaveEmptyButton($tg),
                    CreateFeedbackTelegramConversation::getCancelButton($tg),
                ],
            ],
            'description_change' => [
                fn ($tg) => CreateFeedbackTelegramConversation::getChangeDescriptionButton($tg)->getText(),
                $description,
                CreateFeedbackTelegramConversation::STEP_DESCRIPTION_QUERIED,
                'query.description',
                fn ($tg) => [
                    CreateFeedbackTelegramConversation::getLeaveAsButton($description, $tg),
                    CreateFeedbackTelegramConversation::getMakeEmptyButton($tg),
                    CreateFeedbackTelegramConversation::getCancelButton($tg),
                ],
            ],
        ];

        foreach ($commands as $commandKey => [$command, $description, $expectedStep, $expectedText, $expectedKeyboard]) {
            yield sprintf('confirm_step_as_%s', $commandKey) => [
                fn ($tg) => [
                    'command' => $command($tg),
                    'state' => $state = (new CreateFeedbackTelegramConversationState())
                        ->setStep(CreateFeedbackTelegramConversation::STEP_CONFIRM_QUERIED)
                        ->setSearchTerm($searchTerm)
                        ->setRating(Rating::random())
                        ->setDescription($description),
                    'expectedState' => (clone $state)
                        ->setStep($expectedStep)
                        ->setChange(true),
                    'expectedReply' => $expectedText,
                    'expectedKeyboard' => $expectedKeyboard($tg),
                ],
            ];
        }
    }

    /**
     * @param int $step
     * @param bool|null $change
     * @return void
     * @dataProvider cancelSuccessDataProvider
     */
    public function testCancelSuccess(int $step, bool $change = null): void
    {
        $this->typeCancel(
            state: $state = (new CreateFeedbackTelegramConversationState())
                ->setStep($step)
                ->setChange($change),
            expectedState: (clone $state)
                ->setStep(CreateFeedbackTelegramConversation::STEP_CANCEL_PRESSED),
            shouldSeeReply: [
                'reply.canceled',
                ChooseActionTelegramChatSender::getActionQuery($this->tg),
            ],
            shouldSeeKeyboard: [
                ChooseActionTelegramChatSender::getCreateButton($this->tg),
                ChooseActionTelegramChatSender::getSearchButton($this->tg),
            ],
            conversationClass: CreateFeedbackTelegramConversation::class
        );
    }

    public function cancelSuccessDataProvider(): Generator
    {
        yield 'search_term' => [
            CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_QUERIED,
        ];

        yield 'change_search_term' => [
            CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_QUERIED,
            true,
        ];

        yield 'search_term_type' => [
            CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED,
        ];

        yield 'change_search_term_type' => [
            CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED,
            true,
        ];

        yield 'rating' => [
            CreateFeedbackTelegramConversation::STEP_RATING_QUERIED,
        ];

        yield 'change_rating' => [
            CreateFeedbackTelegramConversation::STEP_RATING_QUERIED,
            true,
        ];

        yield 'description' => [
            CreateFeedbackTelegramConversation::STEP_DESCRIPTION_QUERIED,
        ];

        yield 'change_description' => [
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
     * @dataProvider confirmSuccessDataProvider
     */
    public function testConfirmSuccess(CreateFeedbackTelegramConversationState $state): void
    {
        $feedbackRepository = $this->getFeedbackRepository();
        $previousFeedbackCount = $feedbackRepository->count([]);

        $this->typeConfirm(
            state: $state,
            shouldSeeReply: [
                'reply.ok',
                ChooseActionTelegramChatSender::getActionQuery($this->tg),
            ],
            shouldSeeKeyboard: [
                ChooseActionTelegramChatSender::getCreateButton($this->tg),
                ChooseActionTelegramChatSender::getSearchButton($this->tg),
            ],
            conversationClass: CreateFeedbackTelegramConversation::class
        );

        $this->assertEquals($previousFeedbackCount + 1, $feedbackRepository->count([]));

        $feedback = $feedbackRepository->findOneBy([
            'messengerUser' => $this->getUpdateMessengerUser(),
            'searchTermText' => $state->getSearchTerm()->getText(),
            'searchTermType' => $state->getSearchTerm()->getType(),
        ]);
        $this->assertNotNull($feedback);

        $this->assertEquals($this->getUpdateMessengerUser()->getId(), $feedback->getMessengerUser()->getId());
//        $this->assertEquals($state->getSearchTerm()->getMessengerUser()->getId(), $feedback->getSearchTermMessengerUser()->getIdentifier());
    }

    public function confirmSuccessDataProvider(): Generator
    {
        $generalState = (new CreateFeedbackTelegramConversationState())
            ->setStep(CreateFeedbackTelegramConversation::STEP_CONFIRM_QUERIED)
            ->setRating(Rating::random())
            ->setDescription(mt_rand(0, 1) === 0 ? null : 'any')
        ;

        // network messenger profile urls
        foreach (Fixtures::getNetworkMessengerUserProfileUrls(2) as $commandKey => [$messengerUser, $expectedSearchTermType, $mocks]) {
            yield sprintf('confirm_as_%s', $commandKey) => [
                'state' => (clone $generalState)
                    ->setStep(CreateFeedbackTelegramConversation::STEP_CONFIRM_QUERIED)
                    ->setSearchTerm(
                        $this->addSearchTermPossibleTypes(
                            $this->getMessengerProfileUrlSearchTerm($messengerUser)
                        )->setType($expectedSearchTermType)
                    ),
            ];
        }

        // non-network messenger profile urls
//        foreach (Fixtures::getNonNetworkMessengerUserProfileUrls() as $commandKey => [$messengerUser, $expectedSearchTermType]) {
//            yield sprintf('change_search_term_step_as_%s', $commandKey) => [
//                'state' => $expectedState = (clone $state)
//                    ->setStep(CreateFeedbackTelegramConversation::STEP_CONFIRM_QUERIED)
//                    ->setSearchTerm(
//                        $this->addSearchTermPossibleTypes(
//                            $this->getMessengerProfileUrlSearchTerm($messengerUser)
//                        )->setType($expectedSearchTermType)
//                             ->setMessengerUser(null)
//                    ),
//            ];
//        }
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
            CreateFeedbackTelegramConversation::getCancelButton($tg),
        ];
    }

    private function getShouldSeeReplyOnSearchTermQueried(): array
    {
        return [
            'query.search_term',
        ];
    }

    private function getShouldSeeKeyboardOnSearchTermQueried(TelegramAwareHelper $tg): array
    {
        return [
            CreateFeedbackTelegramConversation::getCancelButton($tg),
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
            CreateFeedbackTelegramConversation::getCancelButton($tg),
        ];
    }

    private function getShouldSeeReplyOnDescriptionQueried(): array
    {
        return [
            'query.description',
        ];
    }

    private function getShouldSeeKeyboardOnDescriptionQueried(TelegramAwareHelper $tg): array
    {
        return [
            CreateFeedbackTelegramConversation::getLeaveEmptyButton($tg),
            CreateFeedbackTelegramConversation::getCancelButton($tg),
        ];
    }

    private function getShouldSeeReplyOnConfirmQueried(TelegramAwareHelper $tg, CreateFeedbackTelegramConversationState $state): array
    {
        return [
            'query.confirm',
            $tg->view(TelegramView::FEEDBACK, [
                'search_term' => $state->getSearchTerm(),
                'rating' => $state->getRating(),
                'description' => $state->getDescription(),
            ]),
        ];
    }

    private function getShouldSeeKeyboardOnConfirmQueried(TelegramAwareHelper $tg, CreateFeedbackTelegramConversationState $state): array
    {
        return [
            CreateFeedbackTelegramConversation::getConfirmButton($tg),
            CreateFeedbackTelegramConversation::getChangeSearchTermButton($tg),
            CreateFeedbackTelegramConversation::getChangeRatingButton($tg),
            $state->getDescription() === null
                ? CreateFeedbackTelegramConversation::getAddDescriptionButton($tg)
                : CreateFeedbackTelegramConversation::getChangeDescriptionButton($tg),
            CreateFeedbackTelegramConversation::getCancelButton($tg),
        ];
    }
}
