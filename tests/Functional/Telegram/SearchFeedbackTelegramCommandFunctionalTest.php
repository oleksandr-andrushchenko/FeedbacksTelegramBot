<?php

declare(strict_types=1);

namespace App\Tests\Functional\Telegram;

use App\Entity\Telegram\SearchFeedbackTelegramConversationState;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Messenger\Messenger;
use App\Object\Feedback\SearchTermTransfer;
use App\Object\Messenger\MessengerUserTransfer;
use App\Service\Telegram\Channel\FeedbackTelegramChannel;
use App\Service\Telegram\Chat\ChooseActionTelegramChatSender;
use App\Service\Telegram\Conversation\SearchFeedbackTelegramConversation;
use App\Service\Telegram\TelegramAwareHelper;
use App\Tests\Fixtures;
use App\Tests\Traits\Feedback\FeedbackRepositoryProviderTrait;
use App\Tests\Traits\Feedback\FeedbackSearchRepositoryProviderTrait;
use Generator;

class SearchFeedbackTelegramCommandFunctionalTest extends TelegramCommandFunctionalTestCase
{
    use FeedbackSearchRepositoryProviderTrait;
    use FeedbackRepositoryProviderTrait;

    /**
     * @param callable $fn
     * @return void
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
                new SearchFeedbackTelegramConversationState(SearchFeedbackTelegramConversation::STEP_SEARCH_TERM_QUERIED)
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
                'command' => ChooseActionTelegramChatSender::getSearchButton($tg)->getText(),
            ],
        ];

        yield sprintf('start_step_as_%s', 'command') => [
            fn ($tg) => [
                'command' => FeedbackTelegramChannel::SEARCH,
            ],
        ];
    }

    /**
     * @param callable $fn
     * @return void
     * @dataProvider onSearchTermStepDataProvider
     */
    public function testOnSearchTermStepSuccess(callable $fn): void
    {
        extract($fn($this->tg));
        $state->setStep(SearchFeedbackTelegramConversation::STEP_SEARCH_TERM_QUERIED);

        $mocks && $mocks();

        $this->type($command, $state, $expectedState, conversationClass: SearchFeedbackTelegramConversation::class)
            ->shouldSeeReply(...$shouldSeeReply)
            ->shouldSeeKeyboard(...$shouldSeeKeyboard)
        ;
    }

    public function onSearchTermStepDataProvider(): Generator
    {
        $state = (new SearchFeedbackTelegramConversationState())
            ->setStep(SearchFeedbackTelegramConversation::STEP_SEARCH_TERM_QUERIED)
        ;

        // messenger profile urls
        foreach (Fixtures::getMessengerUserProfileUrls() as $commandKey => [$messengerUser, $expectedSearchTermType]) {
            yield sprintf('search_term_step_as_%s_profile_url', $commandKey) => [
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
                    'shouldSeeKeyboard' => $this->getShouldSeeKeyboardOnConfirmQueried($tg),
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
                    ->setStep(SearchFeedbackTelegramConversation::STEP_CONFIRM_QUERIED)
                    ->setSearchTerm(
                        $this->addSearchTermPossibleTypes(new SearchTermTransfer($command))
                            ->setType(SearchTermType::phone_number)
                    ),
                'shouldSeeReply' => $this->getShouldSeeReplyOnConfirmQueried(),
                'shouldSeeKeyboard' => $this->getShouldSeeKeyboardOnConfirmQueried($tg),
            ],
        ];

        // normalized email
        yield sprintf('search_term_step_as_normalized_%s', SearchTermType::email->name) => [
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
                'shouldSeeKeyboard' => $this->getShouldSeeKeyboardOnConfirmQueried($tg),
            ],
        ];

        yield from $this->onSearchTermStepToTypeQueriedDataProvider('search_term_step', $state);
    }

    public function onSearchTermStepToTypeQueriedDataProvider(string $key, SearchFeedbackTelegramConversationState $state): Generator
    {
        /** @var MessengerUserTransfer $messengerUser */

        // unknown messenger profile url
        yield sprintf('%s_as_messenger_profile_url', $key) => [
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
                        ->setStep(SearchFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
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
                    ->setStep(SearchFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
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
                        ->setStep(SearchFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
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
                    ->setStep(SearchFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
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
                    ->setStep(SearchFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
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
            ->setStep(SearchFeedbackTelegramConversation::STEP_SEARCH_TERM_QUERIED)
            ->setChange(true)
        ;

        $mocks && $mocks($this);

        $this->type($command, $state, $expectedState, conversationClass: SearchFeedbackTelegramConversation::class)
            ->shouldSeeReply(...$shouldSeeReply)
            ->shouldSeeKeyboard(...$shouldSeeKeyboard)
        ;
    }

    public function onChangeSearchTermStepDataProvider(): Generator
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
                    'shouldSeeKeyboard' => $this->getShouldSeeKeyboardOnConfirmQueried($tg),
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
                    'shouldSeeKeyboard' => $this->getShouldSeeKeyboardOnConfirmQueried($tg),
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
        $state->setStep(SearchFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED);

        $this->type($command, $state, $expectedState, conversationClass: SearchFeedbackTelegramConversation::class)
            ->shouldSeeReply(...$this->getShouldSeeReplyOnConfirmQueried())
            ->shouldSeeKeyboard(...$this->getShouldSeeKeyboardOnConfirmQueried($this->tg))
        ;
    }

    public function onSearchTermTypeStepDataProvider(): Generator
    {
        $searchTermTypes = SearchTermType::cases();

        /** @var MessengerUserTransfer $messengerUser */

        // unknown messenger profile url
        yield 'search_term_type_step_as_messenger_profile_url' => [
            fn ($tg) => [
                'command' => SearchFeedbackTelegramConversation::getSearchTermTypeButton(SearchTermType::messenger_profile_url, $tg)->getText(),
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
            ],
        ];

        // messenger usernames
        foreach (Fixtures::getMessengerUserUsernames() as $commandKey => [$messengerUser, $searchTermType]) {
            yield sprintf('search_term_type_step_as_%s_username', $commandKey) => [
                fn ($tg) => [
                    'command' => SearchFeedbackTelegramConversation::getSearchTermTypeButton($searchTermType, $tg)->getText(),
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
                ],
            ];
        }

        // unknown messenger username
        yield 'search_term_type_step_as_messenger_username' => [
            fn ($tg) => [
                'command' => SearchFeedbackTelegramConversation::getSearchTermTypeButton(SearchTermType::messenger_username, $tg)->getText(),
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
            ],
        ];

        // non-messengers
        foreach (Fixtures::NON_MESSENGER_SEARCH_TYPES as $typeKey => [$searchTermType, $searchTermText, $searchTermNormalizedText]) {
            yield sprintf('search_term_type_step_as_%s', $typeKey) => [
                fn ($tg) => [
                    'command' => SearchFeedbackTelegramConversation::getSearchTermTypeButton($searchTermType, $tg)->getText(),
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
            ->setStep(SearchFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
            ->setChange(true)
        ;

        $this->type($command, $state, $expectedState, conversationClass: SearchFeedbackTelegramConversation::class)
            ->shouldSeeReply(...$this->getShouldSeeReplyOnConfirmQueried())
            ->shouldSeeKeyboard(...$this->getShouldSeeKeyboardOnConfirmQueried($this->tg))
        ;
    }

    public function onChangeSearchTermTypeStepDataProvider(): Generator
    {
        $generalState = (new SearchFeedbackTelegramConversationState())
            ->setStep(SearchFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED)
            ->setChange(true)
        ;
        $searchTermTypes = SearchTermType::cases();

        /** @var MessengerUserTransfer $messengerUser */

        // unknown messenger profile url
        yield 'change_search_term_type_step_as_messenger_profile_url' => [
            fn ($tg) => [
                'command' => SearchFeedbackTelegramConversation::getSearchTermTypeButton(SearchTermType::messenger_profile_url, $tg)->getText(),
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
            ],
        ];

        // messenger usernames
        foreach (Fixtures::getMessengerUserUsernames() as $commandKey => [$messengerUser, $searchTermType]) {
            yield sprintf('change_search_term_type_step_as_%s_username', $commandKey) => [
                fn ($tg) => [
                    'command' => SearchFeedbackTelegramConversation::getSearchTermTypeButton($searchTermType, $tg)->getText(),
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
                ],
            ];
        }

        // unknown messenger username
        yield 'change_search_term_type_step_as_messenger_username' => [
            fn ($tg) => [
                'command' => SearchFeedbackTelegramConversation::getSearchTermTypeButton(SearchTermType::messenger_username, $tg)->getText(),
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
            ],
        ];

        // non-messengers
        foreach (Fixtures::NON_MESSENGER_SEARCH_TYPES as $typeKey => [$searchTermType, $searchTermText, $searchTermNormalizedText]) {
            yield sprintf('change_search_term_type_step_as_%s', $typeKey) => [
                fn ($tg) => [
                    'command' => SearchFeedbackTelegramConversation::getSearchTermTypeButton($searchTermType, $tg)->getText(),
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
                ],
            ];
        }
    }

    /**
     * @param callable $fn
     * @return void
     * @dataProvider onConfirmStepSuccessDataProvider
     */
    public function testOnConfirmStepSuccess(callable $fn): void
    {
        extract($fn($this->tg));
        $state->setStep(SearchFeedbackTelegramConversation::STEP_CONFIRM_QUERIED);

        $this->type($command, $state, $expectedState, conversationClass: SearchFeedbackTelegramConversation::class)
            ->shouldSeeReply($expectedReply)
            ->shouldSeeKeyboard(...$expectedKeyboard)
        ;
    }

    public function onConfirmStepSuccessDataProvider(): Generator
    {
        $searchTerm = new SearchTermTransfer('any');

        $commands = [
            'search_term_change' => [
                fn ($tg) => SearchFeedbackTelegramConversation::getChangeSearchTermButton($tg)->getText(),
                SearchFeedbackTelegramConversation::STEP_SEARCH_TERM_QUERIED,
                'query.search_term',
                fn ($tg) => [
                    SearchFeedbackTelegramConversation::getLeaveAsButton($searchTerm->getText(), $tg),
                    SearchFeedbackTelegramConversation::getCancelButton($tg),
                ],
            ],
        ];

        foreach ($commands as $commandKey => [$command, $expectedStep, $expectedText, $expectedKeyboard]) {
            yield sprintf('confirm_step_as_%s', $commandKey) => [
                fn ($tg) => [
                    'command' => $command($tg),
                    'state' => $state = (new SearchFeedbackTelegramConversationState())
                        ->setStep(SearchFeedbackTelegramConversation::STEP_CONFIRM_QUERIED)
                        ->setSearchTerm($searchTerm),
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
            state: $state = (new SearchFeedbackTelegramConversationState())
                ->setStep($step)
                ->setChange($change),
            expectedState: (clone $state)
                ->setStep(SearchFeedbackTelegramConversation::STEP_CANCEL_PRESSED),
            shouldSeeReply: [
                'reply.canceled',
                ChooseActionTelegramChatSender::getActionQuery($this->tg),
            ],
            shouldSeeKeyboard: [
                ChooseActionTelegramChatSender::getCreateButton($this->tg),
                ChooseActionTelegramChatSender::getSearchButton($this->tg),
            ],
            conversationClass: SearchFeedbackTelegramConversation::class
        );
    }

    public function cancelSuccessDataProvider(): Generator
    {
        yield 'search_term' => [
            SearchFeedbackTelegramConversation::STEP_SEARCH_TERM_QUERIED,
        ];

        yield 'change_search_term' => [
            SearchFeedbackTelegramConversation::STEP_SEARCH_TERM_QUERIED,
            true,
        ];

        yield 'search_term_type' => [
            SearchFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_QUERIED,
        ];

        yield 'change_search_term_type' => [
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
     * @dataProvider confirmSuccessDataProvider
     */
    public function testConfirmSuccess(
        SearchFeedbackTelegramConversationState $state,
        array $shouldSeeReplyFeedbacks
    ): void
    {
        $shouldSeeReply = [];

        $count = count($shouldSeeReplyFeedbacks);
        if ($count === 0) {
            $shouldSeeReply[] = 'reply.empty_list';
        } else {
            $shouldSeeReply[] = 'reply.title';

            foreach ($shouldSeeReplyFeedbacks as $shouldSeeReplyFeedback) {
                $shouldSeeReply[] = $this->getFeedbackReply($shouldSeeReplyFeedback);
            }
        }

        $feedbackSearchRepository = $this->getFeedbackSearchRepository();
        $previousFeedbackSearchCount = $feedbackSearchRepository->count([]);

        $this->typeConfirm(
            state: $state,
            shouldSeeReply: [
                ...$shouldSeeReply,
                ChooseActionTelegramChatSender::getActionQuery($this->tg),
            ],
            shouldSeeKeyboard: [
                ChooseActionTelegramChatSender::getCreateButton($this->tg),
                ChooseActionTelegramChatSender::getSearchButton($this->tg),
            ],
            conversationClass: SearchFeedbackTelegramConversation::class
        );

        $this->assertEquals($previousFeedbackSearchCount + 1, $feedbackSearchRepository->count([]));

        $feedbackSearch = $feedbackSearchRepository->findOneBy([
            'messengerUser' => $this->getUpdateMessengerUser(),
            'searchTermText' => $state->getSearchTerm()->getText(),
            'searchTermType' => $state->getSearchTerm()->getType(),
        ]);

        $this->assertNotNull($feedbackSearch);

        $this->assertEquals($this->getUpdateMessengerUser()->getId(), $feedbackSearch->getMessengerUser()->getId());
    }

    public function confirmSuccessDataProvider(): Generator
    {
        $generalState = (new SearchFeedbackTelegramConversationState())
            ->setStep(SearchFeedbackTelegramConversation::STEP_CONFIRM_QUERIED)
        ;

        yield 'empty_list' => [
            'state' => (clone $generalState)
                ->setStep(SearchFeedbackTelegramConversation::STEP_CONFIRM_QUERIED)
                ->setSearchTerm(
                    (new SearchTermTransfer('any'))
                        ->setType(SearchTermType::unknown)
                ),
            'shouldSeeReplyFeedbacks' => [],
        ];

        yield 'instagram_username' => [
            'state' => (clone $generalState)
                ->setStep(SearchFeedbackTelegramConversation::STEP_CONFIRM_QUERIED)
                ->setSearchTerm(
                    $this->getMessengerUsernameSearchTerm(Fixtures::getInstagramMessengerUserTransferFixture(3))
                        ->setType(SearchTermType::instagram_username)
                ),
            'shouldSeeReplyFeedbacks' => [
                1,
                2,
            ],
        ];
    }

    private function getFeedbackReply(int $feedbackId): string
    {
        return 'somebody_from';
        $feedback = $this->getFeedbackRepository()->find($feedbackId);

        return $feedback->getSearchTermText();
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
            SearchFeedbackTelegramConversation::getCancelButton($tg),
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
            SearchFeedbackTelegramConversation::getCancelButton($tg),
        ];
    }

    private function getShouldSeeReplyOnConfirmQueried(): array
    {
        return [
            'query.confirm',
        ];
    }

    private function getShouldSeeKeyboardOnConfirmQueried(TelegramAwareHelper $tg): array
    {
        return [
            SearchFeedbackTelegramConversation::getConfirmButton($tg),
            SearchFeedbackTelegramConversation::getChangeSearchTermButton($tg),
            SearchFeedbackTelegramConversation::getCancelButton($tg),
        ];
    }
}
