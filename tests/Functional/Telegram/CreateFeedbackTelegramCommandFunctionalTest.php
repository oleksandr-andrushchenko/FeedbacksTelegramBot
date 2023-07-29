<?php

declare(strict_types=1);

namespace App\Tests\Functional\Telegram;

use App\Entity\Telegram\CreateFeedbackTelegramConversationState;
use App\Entity\Telegram\TelegramConversationState;
use App\Enum\Feedback\Rating;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Messenger\Messenger;
use App\Enum\Telegram\TelegramView;
use App\Object\Feedback\SearchTermTransfer;
use App\Object\Messenger\MessengerUserTransfer;
use App\Service\Telegram\Channel\FeedbackTelegramChannel;
use App\Service\Telegram\Conversation\ChooseFeedbackActionTelegramConversation;
use App\Service\Telegram\Conversation\CreateFeedbackTelegramConversation;
use App\Tests\Fixtures;
use App\Tests\Traits\Feedback\FeedbackRepositoryProviderTrait;
use App\Tests\Traits\Telegram\TelegramCommandFunctionalTrait;
use App\Tests\Traits\User\UserRepositoryProviderTrait;
use Generator;

class CreateFeedbackTelegramCommandFunctionalTest extends TelegramCommandFunctionalTestCase
{
    use TelegramCommandFunctionalTrait;
    use FeedbackRepositoryProviderTrait;
    use UserRepositoryProviderTrait;

    public function testTest(): void
    {
        $this->assertTrue(true);
    }

    /**
     * @param string $command
     * @param string|null $conversationClass
     * @param TelegramConversationState|null $conversationState
     * @return void
     * @dataProvider onStartStepDataProvider
     */
    public function testOnStartStepSuccess(
        string $command,
        string $conversationClass = null,
        TelegramConversationState $conversationState = null,
    ): void
    {
        $this
            ->command($command)
            ->conversation($conversationClass, $conversationState)
            ->invoke()
            ->expectsState(
                new CreateFeedbackTelegramConversationState(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_ASKED)
            )
            ->shouldSeeReply(...$this->getShouldSeeReplyOnSearchTermAsked())
            ->shouldSeeKeyboard(...$this->getShouldSeeKeyboardOnSearchTermAsked())
        ;

        $messengerUser = $this->getMessengerUserRepository()->findOneByMessengerAndIdentifier(
            Messenger::telegram,
            (string) $this->getUpdateUserId()
        );

        $this->assertNotNull($messengerUser);
    }

    public function onStartStepDataProvider(): Generator
    {
        $this->telegramCommandUp();

        yield sprintf('start_step_as_%s', 'text') => [
            'command' => ChooseFeedbackActionTelegramConversation::getCreateButton($this->tg)->getText(),
            'conversationClass' => ChooseFeedbackActionTelegramConversation::class,
            'conversationState' => new TelegramConversationState(ChooseFeedbackActionTelegramConversation::STEP_ACTION_ASKED),
        ];

        yield sprintf('start_step_as_%s', 'command') => [
            'command' => FeedbackTelegramChannel::CREATE_FEEDBACK,
        ];
    }

    /**
     * @param string $command
     * @param callable|null $mocks
     * @param CreateFeedbackTelegramConversationState $state
     * @param CreateFeedbackTelegramConversationState $expectedState
     * @param array $shouldSeeReply
     * @param array $shouldSeeKeyboard
     * @return void
     * @dataProvider onSearchTermStepDataProvider
     */
    public function testOnSearchTermStepSuccess(
        string $command,
        ?callable $mocks,
        CreateFeedbackTelegramConversationState $state,
        CreateFeedbackTelegramConversationState $expectedState,
        array $shouldSeeReply,
        array $shouldSeeKeyboard,
    ): void
    {
        $state->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_ASKED);

        $mocks && $mocks();

        $this->type($command, $state, $expectedState, conversationClass: CreateFeedbackTelegramConversation::class)
            ->shouldSeeReply(...$shouldSeeReply)
            ->shouldSeeKeyboard(...$shouldSeeKeyboard)
        ;
    }

    public function onSearchTermStepDataProvider(): Generator
    {
        $this->telegramCommandUp();

        $state = (new CreateFeedbackTelegramConversationState())
            ->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_ASKED)
        ;

        // messenger profile urls
        foreach (Fixtures::getMessengerUserProfileUrls() as $commandKey => [$messengerUser, $expectedSearchTermType]) {
            yield sprintf('search_term_step_as_%s_profile_url', $commandKey) => [
                'command' => $this->getMessengerUserProfileUrl($messengerUser),
                'mocks' => null,
                'state' => clone $state,
                'expectedState' => (clone $state)
                    ->setStep(CreateFeedbackTelegramConversation::STEP_RATING_ASKED)
                    ->setSearchTerm(
                        $this->addSearchTermPossibleTypes($this->getMessengerProfileUrlSearchTerm($messengerUser))
                            ->setType($expectedSearchTermType)
                            ->setMessengerUser(null)
                    ),
                'shouldSeeReply' => $this->getShouldSeeReplyOnRatingAsked(),
                'shouldSeeKeyboard' => $this->getShouldSeeKeyboardOnRatingAsked(),
            ];
        }

        // normalized phone number
        yield sprintf('search_term_step_as_normalized_%s', SearchTermType::phone_number->name) => [
            'command' => $command = '15613145672',
            'mocks' => null,
            'state' => clone $state,
            'expectedState' => (clone $state)
                ->setStep(CreateFeedbackTelegramConversation::STEP_RATING_ASKED)
                ->setSearchTerm(
                    $this->addSearchTermPossibleTypes(new SearchTermTransfer($command))
                        ->setType(SearchTermType::phone_number)
                ),
            'shouldSeeReply' => $this->getShouldSeeReplyOnRatingAsked(),
            'shouldSeeKeyboard' => $this->getShouldSeeKeyboardOnRatingAsked(),
        ];

        // normalized email
        yield sprintf('search_term_step_as_normalized_%s', SearchTermType::email->name) => [
            'command' => $command = 'example@gmail.com',
            'mocks' => null,
            'state' => clone $state,
            'expectedState' => (clone $state)
                ->setStep(CreateFeedbackTelegramConversation::STEP_RATING_ASKED)
                ->setSearchTerm(
                    $this->addSearchTermPossibleTypes(new SearchTermTransfer($command))
                        ->setType(SearchTermType::email),
                ),
            'shouldSeeReply' => $this->getShouldSeeReplyOnRatingAsked(),
            'shouldSeeKeyboard' => $this->getShouldSeeKeyboardOnRatingAsked(),
        ];

        yield from $this->onSearchTermStepToTypeAskedDataProvider('search_term_step', $state);
    }

    public function onSearchTermStepToTypeAskedDataProvider(string $key, CreateFeedbackTelegramConversationState $state): Generator
    {
        /** @var MessengerUserTransfer $messengerUser */

        // unknown messenger profile url
        yield sprintf('%s_as_messenger_profile_url', $key) => [
            'command' => $command = 'https://unknown.com/me',
            'mocks' => null,
            'state' => clone $state,
            'expectedState' => $expectedState = (clone $state)
                ->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_ASKED)
                ->setSearchTerm(
                    $this->addSearchTermPossibleTypes(new SearchTermTransfer($command), SearchTermType::messenger_profile_url)
                ),
            'shouldSeeReply' => $this->getShouldSeeReplyOnSearchTermTypeAsked(),
            'shouldSeeKeyboard' => $this->getShouldSeeKeyboardOnSearchTermTypeAsked($expectedState),
        ];

        // messenger usernames
        foreach (Fixtures::getMessengerUserUsernames() as $commandKey => [$messengerUser, $expectedSearchTermPossibleType]) {
            yield sprintf('%s_as_%s_username', $key, $commandKey) => [
                'command' => $command = $messengerUser->getUsername(),
                'mocks' => null,
                'state' => clone $state,
                'expectedState' => $expectedState = (clone $state)
                    ->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_ASKED)
                    ->setSearchTerm(
                        $this->addSearchTermPossibleTypes(new SearchTermTransfer($command), $expectedSearchTermPossibleType)
                    ),
                'shouldSeeReply' => $this->getShouldSeeReplyOnSearchTermTypeAsked(),
                'shouldSeeKeyboard' => $this->getShouldSeeKeyboardOnSearchTermTypeAsked($expectedState),
            ];
        }

        // unknown messenger username
        yield sprintf('%s_as_messenger_username', $key) => [
            'command' => $command = 'me',
            'mocks' => null,
            'state' => clone $state,
            'expectedState' => $expectedState = (clone $state)
                ->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_ASKED)
                ->setSearchTerm(
                    $this->addSearchTermPossibleTypes(new SearchTermTransfer($command), SearchTermType::messenger_username)
                ),
            'shouldSeeReply' => $this->getShouldSeeReplyOnSearchTermTypeAsked(),
            'shouldSeeKeyboard' => $this->getShouldSeeKeyboardOnSearchTermTypeAsked($expectedState),
        ];

        // person names
        foreach (Fixtures::PERSONS as $personKey => $personName) {
            yield sprintf('%s_as_person_name_%s', $key, $personKey) => [
                'command' => $personName,
                'mocks' => null,
                'state' => clone $state,
                'expectedState' => $expectedState = (clone $state)
                    ->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_ASKED)
                    ->setSearchTerm(
                        (new SearchTermTransfer($personName))
                            ->setPossibleTypes([
                                SearchTermType::person_name,
                                SearchTermType::organization_name,
                                SearchTermType::place_name,
                                SearchTermType::unknown,
                            ])
                    ),
                'shouldSeeReply' => $this->getShouldSeeReplyOnSearchTermTypeAsked(),
                'shouldSeeKeyboard' => $this->getShouldSeeKeyboardOnSearchTermTypeAsked($expectedState),
            ];
        }

        // place names
        foreach (Fixtures::PLACES as $placeKey => $placeName) {
            yield sprintf('%s_as_place_name_%s', $key, $placeKey) => [
                'command' => $placeName,
                'mocks' => null,
                'state' => clone $state,
                'expectedState' => $expectedState = (clone $state)
                    ->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_ASKED)
                    ->setSearchTerm(
                        (new SearchTermTransfer($placeName))
                            ->setPossibleTypes([
                                SearchTermType::organization_name,
                                SearchTermType::place_name,
                                SearchTermType::unknown,
                            ])
                    ),
                'shouldSeeReply' => $this->getShouldSeeReplyOnSearchTermTypeAsked(),
                'shouldSeeKeyboard' => $this->getShouldSeeKeyboardOnSearchTermTypeAsked($expectedState),
            ];
        }

        // organizations names
        foreach (Fixtures::ORGANIZATIONS as $orgKey => $orgName) {
            yield sprintf('%s_as_organization_name_%s', $key, $orgKey) => [
                'command' => $orgName,
                'mocks' => null,
                'state' => clone $state,
                'expectedState' => $expectedState = (clone $state)
                    ->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_ASKED)
                    ->setSearchTerm(
                        (new SearchTermTransfer($orgName))
                            ->setPossibleTypes([
                                SearchTermType::organization_name,
                                SearchTermType::unknown,
                            ])
                    ),
                'shouldSeeReply' => $this->getShouldSeeReplyOnSearchTermTypeAsked(),
                'shouldSeeKeyboard' => $this->getShouldSeeKeyboardOnSearchTermTypeAsked($expectedState),
            ];
        }

        // phone number
        yield sprintf('search_term_step_as_%s', SearchTermType::phone_number->name) => [
            'command' => $command = '+1 (561) 314-5672',
            'mocks' => null,
            'state' => clone $state,
            'expectedState' => $expectedState = (clone $state)
                ->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_ASKED)
                ->setSearchTerm(
                    (new SearchTermTransfer($command))
                        ->setPossibleTypes([
                            SearchTermType::phone_number,
                            SearchTermType::unknown,
                        ])
                ),
            'shouldSeeReply' => $this->getShouldSeeReplyOnSearchTermTypeAsked(),
            'shouldSeeKeyboard' => $this->getShouldSeeKeyboardOnSearchTermTypeAsked($expectedState),
        ];

        // email
        yield sprintf('%s_as_%s', $key, SearchTermType::email->name) => [
            'command' => $command = 'example+123@gma//il.com',
            'mocks' => null,
            'state' => clone $state,
            'expectedState' => $expectedState = (clone $state)
                ->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_ASKED)
                ->setSearchTerm(
                    (new SearchTermTransfer($command))
                        ->setPossibleTypes([
                            SearchTermType::email,
                            SearchTermType::unknown,
                        ])
                ),
            'shouldSeeReply' => $this->getShouldSeeReplyOnSearchTermTypeAsked(),
            'shouldSeeKeyboard' => $this->getShouldSeeKeyboardOnSearchTermTypeAsked($expectedState),
        ];
    }

    /**
     * @param string $command
     * @param callable|null $mocks
     * @param CreateFeedbackTelegramConversationState $state
     * @param CreateFeedbackTelegramConversationState $expectedState
     * @param array $shouldSeeReply
     * @param array $shouldSeeKeyboard
     * @return void
     * @dataProvider onChangeSearchTermStepDataProvider
     */
    public function testOnChangeSearchTermStepSuccess(
        string $command,
        ?callable $mocks,
        CreateFeedbackTelegramConversationState $state,
        CreateFeedbackTelegramConversationState $expectedState,
        array $shouldSeeReply,
        array $shouldSeeKeyboard,
    ): void
    {
        $state
            ->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_ASKED)
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
        $this->telegramCommandUp();

        $state = (new CreateFeedbackTelegramConversationState())
            ->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_ASKED)
            ->setChange(true)
            ->setSearchTerm(new SearchTermTransfer('any'))
            ->setRating(Rating::random())
            ->setDescription(mt_rand(0, 1) === 0 ? null : 'any')
        ;

        // network messenger profile urls
        foreach (Fixtures::getNetworkMessengerUserProfileUrls() as $commandKey => [$messengerUser, $expectedSearchTermType, $mocks]) {
            yield sprintf('change_search_term_step_as_%s', $commandKey) => [
                'command' => $this->getMessengerUserProfileUrl($messengerUser),
                'mocks' => $mocks,
                'state' => clone $state,
                'expectedState' => $expectedState = (clone $state)
                    ->setStep(CreateFeedbackTelegramConversation::STEP_CONFIRM_ASKED)
                    ->setChange(false)
                    ->setSearchTerm(
                        $this->addSearchTermPossibleTypes(
                            $this->getMessengerProfileUrlSearchTerm($messengerUser)
                        )->setType($expectedSearchTermType)
                    ),
                'shouldSeeReply' => $this->getShouldSeeReplyOnConfirmAsked($expectedState),
                'shouldSeeKeyboard' => $this->getShouldSeeKeyboardOnConfirmAsked($expectedState),
            ];
        }

        // non-network messenger profile urls
        foreach (Fixtures::getNonNetworkMessengerUserProfileUrls() as $commandKey => [$messengerUser, $expectedSearchTermType]) {
            yield sprintf('change_search_term_step_as_%s', $commandKey) => [
                'command' => $this->getMessengerUserProfileUrl($messengerUser),
                'mocks' => null,
                'state' => clone $state,
                'expectedState' => $expectedState = (clone $state)
                    ->setStep(CreateFeedbackTelegramConversation::STEP_CONFIRM_ASKED)
                    ->setChange(false)
                    ->setSearchTerm(
                        $this->addSearchTermPossibleTypes(
                            $this->getMessengerProfileUrlSearchTerm($messengerUser)
                        )->setType($expectedSearchTermType)
                            ->setMessengerUser(null)
                    ),
                'shouldSeeReply' => $this->getShouldSeeReplyOnConfirmAsked($expectedState),
                'shouldSeeKeyboard' => $this->getShouldSeeKeyboardOnConfirmAsked($expectedState),
            ];
        }

        yield from $this->onSearchTermStepToTypeAskedDataProvider('change_search_term_step', $state);
    }

    /**
     * @param string $command
     * @param CreateFeedbackTelegramConversationState $state
     * @param CreateFeedbackTelegramConversationState $expectedState
     * @return void
     * @dataProvider onSearchTermTypeStepDataProvider
     */
    public function testOnSearchTermTypeStepSuccess(
        string $command,
        CreateFeedbackTelegramConversationState $state,
        CreateFeedbackTelegramConversationState $expectedState,
    ): void
    {
        $state->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_ASKED);

        $this->type($command, $state, $expectedState, conversationClass: CreateFeedbackTelegramConversation::class)
            ->shouldSeeReply(...$this->getShouldSeeReplyOnRatingAsked())
            ->shouldSeeKeyboard(...$this->getShouldSeeKeyboardOnRatingAsked())
        ;
    }

    public function onSearchTermTypeStepDataProvider(): Generator
    {
        $this->telegramCommandUp();

        $searchTermTypes = SearchTermType::cases();

        /** @var MessengerUserTransfer $messengerUser */

        // unknown messenger profile url
        yield 'search_term_type_step_as_messenger_profile_url' => [
            'command' => CreateFeedbackTelegramConversation::getSearchTermTypeButton(SearchTermType::messenger_profile_url, $this->tg)->getText(),
            'state' => $state = (new CreateFeedbackTelegramConversationState())
                ->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_ASKED)
                ->setSearchTerm(
                    $searchTerm = (new SearchTermTransfer('https://unknown.com/me'))
                        ->setPossibleTypes($searchTermTypes)
                ),
            'expectedState' => (clone $state)
                ->setStep(CreateFeedbackTelegramConversation::STEP_RATING_ASKED)
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
            yield sprintf('search_term_type_step_as_%s_username', $commandKey) => [
                'command' => CreateFeedbackTelegramConversation::getSearchTermTypeButton($searchTermType, $this->tg)->getText(),
                'state' => $state = (new CreateFeedbackTelegramConversationState())
                    ->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_ASKED)
                    ->setSearchTerm(
                        $searchTerm = (new SearchTermTransfer($messengerUser->getUsername()))
                            ->setPossibleTypes($searchTermTypes)
                    ),
                'expectedState' => (clone $state)
                    ->setStep(CreateFeedbackTelegramConversation::STEP_RATING_ASKED)
                    ->setSearchTerm(
                        $this->getMessengerUsernameSearchTerm($messengerUser)
                            ->setType($searchTermType)
                            ->setMessengerUser(null)
                            ->setPossibleTypes($searchTerm->getPossibleTypes())
                    ),
            ];
        }

        // unknown messenger username
        yield 'search_term_type_step_as_messenger_username' => [
            'command' => CreateFeedbackTelegramConversation::getSearchTermTypeButton(SearchTermType::messenger_username, $this->tg)->getText(),
            'state' => $state = (new CreateFeedbackTelegramConversationState())
                ->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_ASKED)
                ->setSearchTerm(
                    $searchTerm = (new SearchTermTransfer('me'))
                        ->setPossibleTypes($searchTermTypes)
                ),
            'expectedState' => (clone $state)
                ->setStep(CreateFeedbackTelegramConversation::STEP_RATING_ASKED)
                ->setSearchTerm(
                    (clone $searchTerm)
                        ->setType(SearchTermType::messenger_username)
                        ->setMessenger(Messenger::unknown)
                        ->setMessengerUsername('me')
                ),
        ];

        // non-messengers
        foreach (Fixtures::NON_MESSENGER_SEARCH_TYPES as $typeKey => [$searchTermType, $searchTermText, $searchTermNormalizedText]) {
            yield sprintf('search_term_type_step_as_%s', $typeKey) => [
                'command' => CreateFeedbackTelegramConversation::getSearchTermTypeButton($searchTermType, $this->tg)->getText(),
                'state' => $state = (new CreateFeedbackTelegramConversationState())
                    ->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_ASKED)
                    ->setSearchTerm(
                        $searchTerm = (new SearchTermTransfer($searchTermText))
                            ->setPossibleTypes($searchTermTypes)
                    ),
                'expectedState' => (clone $state)
                    ->setStep(CreateFeedbackTelegramConversation::STEP_RATING_ASKED)
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
     * @dataProvider onChangeSearchTermTypeStepDataProvider
     */
    public function testOnChangeSearchTermTypeStepSuccess(
        string $command,
        ?callable $mocks,
        CreateFeedbackTelegramConversationState $state,
        CreateFeedbackTelegramConversationState $expectedState,
    ): void
    {
        $state
            ->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_ASKED)
            ->setChange(true)
        ;

//        $mocks && $mocks($this);

        $this->type($command, $state, $expectedState, conversationClass: CreateFeedbackTelegramConversation::class)
            ->shouldSeeReply(...$this->getShouldSeeReplyOnConfirmAsked($expectedState))
            ->shouldSeeKeyboard(...$this->getShouldSeeKeyboardOnConfirmAsked($expectedState))
        ;
    }

    public function onChangeSearchTermTypeStepDataProvider(): Generator
    {
        $this->telegramCommandUp();

        $generalState = (new CreateFeedbackTelegramConversationState())
            ->setStep(CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_ASKED)
            ->setChange(true)
            ->setRating(Rating::random())
            ->setDescription('any')
        ;
        $searchTermTypes = SearchTermType::cases();

        /** @var MessengerUserTransfer $messengerUser */

        // unknown messenger profile url
        yield 'change_search_term_type_step_as_messenger_profile_url' => [
            'command' => CreateFeedbackTelegramConversation::getSearchTermTypeButton(SearchTermType::messenger_profile_url, $this->tg)->getText(),
            'mocks' => null,
            'state' => $state = (clone $generalState)
                ->setSearchTerm(
                    $searchTerm = (new SearchTermTransfer('https://unknown.com/me'))
                        ->setPossibleTypes($searchTermTypes)
                ),
            'expectedState' => (clone $state)
                ->setStep(CreateFeedbackTelegramConversation::STEP_CONFIRM_ASKED)
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
            yield sprintf('change_search_term_type_step_as_%s_username', $commandKey) => [
                'command' => CreateFeedbackTelegramConversation::getSearchTermTypeButton($searchTermType, $this->tg)->getText(),
                'mocks' => $mocks,
                'state' => $state = (clone $generalState)
                    ->setSearchTerm(
                        $searchTerm = (new SearchTermTransfer($messengerUser->getUsername()))
                            ->setPossibleTypes($searchTermTypes)
                    ),
                'expectedState' => (clone $state)
                    ->setStep(CreateFeedbackTelegramConversation::STEP_CONFIRM_ASKED)
                    ->setChange(false)
                    ->setSearchTerm(
                        $this->getMessengerUsernameSearchTerm($messengerUser)
                            ->setType($searchTermType)
                            ->setPossibleTypes($searchTerm->getPossibleTypes())
                    ),
            ];
        }

        // unknown messenger username
        yield 'change_search_term_type_step_as_messenger_username' => [
            'command' => CreateFeedbackTelegramConversation::getSearchTermTypeButton(SearchTermType::messenger_username, $this->tg)->getText(),
            'mocks' => null,
            'state' => $state = (clone $generalState)
                ->setSearchTerm(
                    $searchTerm = (new SearchTermTransfer('me'))
                        ->setPossibleTypes($searchTermTypes)
                ),
            'expectedState' => (clone $state)
                ->setStep(CreateFeedbackTelegramConversation::STEP_CONFIRM_ASKED)
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
            yield sprintf('change_search_term_type_step_as_%s', $typeKey) => [
                'command' => CreateFeedbackTelegramConversation::getSearchTermTypeButton($searchTermType, $this->tg)->getText(),
                'mocks' => null,
                'state' => $state = (clone $generalState)
                    ->setSearchTerm(
                        $searchTerm = (new SearchTermTransfer($searchTermText))
                            ->setPossibleTypes($searchTermTypes)
                    ),
                'expectedState' => (clone $state)
                    ->setStep(CreateFeedbackTelegramConversation::STEP_CONFIRM_ASKED)
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
     * @dataProvider onRatingStepDataProvider
     */
    public function testOnRatingStepSuccess(Rating $rating): void
    {
        $command = CreateFeedbackTelegramConversation::getRatingButton($rating, $this->tg)->getText();
        $state = (new CreateFeedbackTelegramConversationState())
            ->setStep(CreateFeedbackTelegramConversation::STEP_RATING_ASKED)
        ;
        $expectedState = (clone $state)
            ->setStep(CreateFeedbackTelegramConversation::STEP_DESCRIPTION_ASKED)
            ->setRating($rating)
        ;

        $this->type($command, $state, $expectedState, conversationClass: CreateFeedbackTelegramConversation::class)
            ->shouldSeeReply(...$this->getShouldSeeReplyOnDescriptionAsked())
            ->shouldSeeKeyboard(...$this->getShouldSeeKeyboardOnDescriptionAsked())
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
            ->setStep(CreateFeedbackTelegramConversation::STEP_RATING_ASKED)
            ->setChange(true)
            ->setSearchTerm(
                (new SearchTermTransfer('any'))
                    ->setType(SearchTermType::unknown)
            )
            ->setRating(Rating::random())
            ->setDescription('any')
        ;
        $expectedState = (clone $state)
            ->setStep(CreateFeedbackTelegramConversation::STEP_CONFIRM_ASKED)
            ->setRating($rating)
            ->setChange(false)
        ;

        $this->type($command, $state, $expectedState, conversationClass: CreateFeedbackTelegramConversation::class)
            ->shouldSeeReply(...$this->getShouldSeeReplyOnConfirmAsked($expectedState))
            ->shouldSeeKeyboard(...$this->getShouldSeeKeyboardOnConfirmAsked($expectedState))
        ;
    }

    public function onChangeRatingStepDataProvider(): Generator
    {
        foreach (Rating::cases() as $rating) {
            yield sprintf('change_rating_step_as_%s', $rating->name) => ['rating' => $rating];
        }
    }

    /**
     * @param string $command
     * @param callable|null $mocks
     * @param CreateFeedbackTelegramConversationState $state
     * @param string|null $expectedDescription
     * @return void
     * @dataProvider onDescriptionStepSuccessDataProvider
     */
    public function testOnDescriptionStepSuccess(
        string $command,
        ?callable $mocks,
        CreateFeedbackTelegramConversationState $state,
        ?string $expectedDescription
    ): void
    {
        $state->setStep(CreateFeedbackTelegramConversation::STEP_DESCRIPTION_ASKED);

        $expectedState = (clone $state)
            ->setStep(CreateFeedbackTelegramConversation::STEP_CONFIRM_ASKED)
            ->setDescription($expectedDescription)
        ;

        $mocks && $mocks($this);

        $this->type($command, $state, $expectedState, conversationClass: CreateFeedbackTelegramConversation::class)
            ->shouldSeeReply(...$this->getShouldSeeReplyOnConfirmAsked($expectedState))
            ->shouldSeeKeyboard(...$this->getShouldSeeKeyboardOnConfirmAsked($expectedState))
        ;
    }

    public function onDescriptionStepSuccessDataProvider(): Generator
    {
        $this->telegramCommandUp();

        $generalState = (new CreateFeedbackTelegramConversationState())
            ->setStep(CreateFeedbackTelegramConversation::STEP_DESCRIPTION_ASKED)
        ;

        /** @var MessengerUserTransfer $messengerUser */

        $commands = [
            'leave_empty' => [
                CreateFeedbackTelegramConversation::getLeaveEmptyButton($this->tg)->getText(),
                null,
            ],
            'type_something' => [
                $description = 'any',
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
                yield sprintf('description_step_as_%s_as_unknown_profile_url_as_%s', $commandKey, $rating->name) => [
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
                    yield sprintf('description_step_as_%s_as_%s_username_as_%s', $commandKey, $messengerUsernameKey, $rating->name) => [
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
                yield sprintf('description_step_as_%s_as_unknown_username_as_%s', $commandKey, $rating->name) => [
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
                    yield sprintf('description_step_as_%s_as_%s_as_%s', $commandKey, $simpleKey, $rating->name) => [
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
     * @dataProvider onChangeDescriptionStepSuccessDataProvider
     */
    public function testOnChangeDescriptionStepSuccess(
        string $command,
        ?callable $mocks,
        CreateFeedbackTelegramConversationState $state,
        ?string $expectedDescription
    ): void
    {
        $state
            ->setStep(CreateFeedbackTelegramConversation::STEP_DESCRIPTION_ASKED)
            ->setChange(true)
        ;

        $expectedState = (clone $state)
            ->setStep(CreateFeedbackTelegramConversation::STEP_CONFIRM_ASKED)
            ->setChange(false)
            ->setDescription($expectedDescription)
        ;

        $mocks && $mocks($this);

        $this->type($command, $state, $expectedState, conversationClass: CreateFeedbackTelegramConversation::class)
            ->shouldSeeReply(...$this->getShouldSeeReplyOnConfirmAsked($expectedState))
            ->shouldSeeKeyboard(...$this->getShouldSeeKeyboardOnConfirmAsked($expectedState))
        ;
    }

    public function onChangeDescriptionStepSuccessDataProvider(): Generator
    {
        $this->telegramCommandUp();

        $generalState = (new CreateFeedbackTelegramConversationState())
            ->setStep(CreateFeedbackTelegramConversation::STEP_DESCRIPTION_ASKED)
        ;

        /** @var MessengerUserTransfer $messengerUser */

        $commands = [
            'empty_leave_empty' => [
                null,
                CreateFeedbackTelegramConversation::getLeaveEmptyButton($this->tg)->getText(),
                null,
            ],
            'empty_type_something' => [
                null,
                $description = 'any',
                $description,
            ],
            'make_empty' => [
                $description,
                CreateFeedbackTelegramConversation::getMakeEmptyButton($this->tg)->getText(),
                null,
            ],
            'change' => [
                'any1',
                $description = 'any2',
                $description,
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
                yield sprintf('change_description_step_as_%s_as_unknown_profile_url_as_%s', $commandKey, $rating->name) => [
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
                    yield sprintf('change_description_step_as_%s_as_%s_username_as_%s', $commandKey, $messengerUsernameKey, $rating->name) => [
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
                yield sprintf('change_description_step_as_%s_as_unknown_username_as_%s', $commandKey, $rating->name) => [
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
                    yield sprintf('change_description_step_as_%s_as_%s_as_%s', $commandKey, $simpleKey, $rating->name) => [
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
     * @param string $command
     * @param CreateFeedbackTelegramConversationState $state
     * @param CreateFeedbackTelegramConversationState $expectedState
     * @param string $expectedReply
     * @param array $expectedKeyboard
     * @return void
     * @dataProvider onConfirmStepSuccessDataProvider
     */
    public function testOnConfirmStepSuccess(
        string $command,
        CreateFeedbackTelegramConversationState $state,
        CreateFeedbackTelegramConversationState $expectedState,
        string $expectedReply,
        array $expectedKeyboard,
    ): void
    {
        $state->setStep(CreateFeedbackTelegramConversation::STEP_CONFIRM_ASKED);

        $this->type($command, $state, $expectedState, conversationClass: CreateFeedbackTelegramConversation::class)
            ->shouldSeeReply($expectedReply)
            ->shouldSeeKeyboard(...$expectedKeyboard)
        ;
    }

    public function onConfirmStepSuccessDataProvider(): Generator
    {
        $this->telegramCommandUp();

        $searchTerm = new SearchTermTransfer('any');
        $description = 'whatever';

        $commands = [
            'search_term_change' => [
                CreateFeedbackTelegramConversation::getChangeSearchTermButton($this->tg)->getText(),
                $description,
                CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_ASKED,
                $this->trans('ask.create.search_term'),
                [
                    CreateFeedbackTelegramConversation::getLeaveAsButton($searchTerm->getText(), $this->tg),
                    CreateFeedbackTelegramConversation::getCancelButton($this->tg),
                ],
            ],
            'rating_change' => [
                CreateFeedbackTelegramConversation::getChangeRatingButton($this->tg)->getText(),
                $description,
                CreateFeedbackTelegramConversation::STEP_RATING_ASKED,
                $this->trans('ask.create.rating'),
                [
                    ...CreateFeedbackTelegramConversation::getRatingButtons($this->tg),
                    CreateFeedbackTelegramConversation::getCancelButton($this->tg),
                ],
            ],
            'empty_description_change' => [
                CreateFeedbackTelegramConversation::getAddDescriptionButton($this->tg)->getText(),
                null,
                CreateFeedbackTelegramConversation::STEP_DESCRIPTION_ASKED,
                $this->trans('ask.create.description'),
                [
                    CreateFeedbackTelegramConversation::getLeaveEmptyButton($this->tg),
                    CreateFeedbackTelegramConversation::getCancelButton($this->tg),
                ],
            ],
            'description_change' => [
                CreateFeedbackTelegramConversation::getChangeDescriptionButton($this->tg)->getText(),
                $description,
                CreateFeedbackTelegramConversation::STEP_DESCRIPTION_ASKED,
                $this->trans('ask.create.description'),
                [
                    CreateFeedbackTelegramConversation::getLeaveAsButton($description, $this->tg),
                    CreateFeedbackTelegramConversation::getMakeEmptyButton($this->tg),
                    CreateFeedbackTelegramConversation::getCancelButton($this->tg),
                ],
            ],
        ];

        foreach ($commands as $commandKey => [$command, $description, $expectedStep, $expectedText, $expectedKeyboard]) {
            yield sprintf('confirm_step_as_%s', $commandKey) => [
                'command' => $command,
                'state' => $state = (new CreateFeedbackTelegramConversationState())
                    ->setStep(CreateFeedbackTelegramConversation::STEP_CONFIRM_ASKED)
                    ->setSearchTerm($searchTerm)
                    ->setRating(Rating::random())
                    ->setDescription($description),
                'expectedState' => (clone $state)
                    ->setStep($expectedStep)
                    ->setChange(true),
                'expectedText' => $expectedText,
                'expectedKeyboard' => $expectedKeyboard,
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
                $this->trans('reply.icon.upset') . ' ' . $this->trans('reply.create.canceled'),
                ChooseFeedbackActionTelegramConversation::getActionAsk($this->tg),
            ],
            shouldSeeKeyboard: [
                ChooseFeedbackActionTelegramConversation::getCreateButton($this->tg),
                ChooseFeedbackActionTelegramConversation::getSearchButton($this->tg),
            ],
            conversationClass: CreateFeedbackTelegramConversation::class
        );
    }

    public function cancelSuccessDataProvider(): Generator
    {
        yield 'search_term' => [
            CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_ASKED,
        ];

        yield 'change_search_term' => [
            CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_ASKED,
            true,
        ];

        yield 'search_term_type' => [
            CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_ASKED,
        ];

        yield 'change_search_term_type' => [
            CreateFeedbackTelegramConversation::STEP_SEARCH_TERM_TYPE_ASKED,
            true,
        ];

        yield 'rating' => [
            CreateFeedbackTelegramConversation::STEP_RATING_ASKED,
        ];

        yield 'change_rating' => [
            CreateFeedbackTelegramConversation::STEP_RATING_ASKED,
            true,
        ];

        yield 'description' => [
            CreateFeedbackTelegramConversation::STEP_DESCRIPTION_ASKED,
        ];

        yield 'change_description' => [
            CreateFeedbackTelegramConversation::STEP_DESCRIPTION_ASKED,
            true,
        ];

        yield 'confirm' => [
            CreateFeedbackTelegramConversation::STEP_CONFIRM_ASKED,
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
                $this->trans('reply.icon.ok') . ' ' . $this->trans('reply.create.ok'),
                ChooseFeedbackActionTelegramConversation::getActionAsk($this->tg),
            ],
            shouldSeeKeyboard: [
                ChooseFeedbackActionTelegramConversation::getCreateButton($this->tg),
                ChooseFeedbackActionTelegramConversation::getSearchButton($this->tg),
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
        $this->telegramCommandUp();

        $generalState = (new CreateFeedbackTelegramConversationState())
            ->setStep(CreateFeedbackTelegramConversation::STEP_CONFIRM_ASKED)
            ->setRating(Rating::random())
            ->setDescription(mt_rand(0, 1) === 0 ? null : 'any')
        ;

        // network messenger profile urls
        foreach (Fixtures::getNetworkMessengerUserProfileUrls(2) as $commandKey => [$messengerUser, $expectedSearchTermType, $mocks]) {
            yield sprintf('confirm_as_%s', $commandKey) => [
                'state' => (clone $generalState)
                    ->setStep(CreateFeedbackTelegramConversation::STEP_CONFIRM_ASKED)
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
//                    ->setStep(CreateFeedbackTelegramConversation::STEP_CONFIRM_ASKED)
//                    ->setSearchTerm(
//                        $this->addSearchTermPossibleTypes(
//                            $this->getMessengerProfileUrlSearchTerm($messengerUser)
//                        )->setType($expectedSearchTermType)
//                             ->setMessengerUser(null)
//                    ),
//            ];
//        }
    }

    private function getShouldSeeReplyOnSearchTermTypeAsked(): array
    {
        return [
            $this->trans('ask.create.search_term_type'),
        ];
    }

    private function getShouldSeeKeyboardOnSearchTermTypeAsked(CreateFeedbackTelegramConversationState $state): array
    {
        return [
            ...CreateFeedbackTelegramConversation::getSearchTermTypeButtons(SearchTermType::sort($state->getSearchTerm()->getPossibleTypes()), $this->tg),
            CreateFeedbackTelegramConversation::getCancelButton($this->tg),
        ];
    }

    private function getShouldSeeReplyOnSearchTermAsked(): array
    {
        return [
            sprintf('[1/3] %s', $this->trans('ask.create.search_term')),
        ];
    }

    private function getShouldSeeKeyboardOnSearchTermAsked(): array
    {
        return [
            CreateFeedbackTelegramConversation::getCancelButton($this->tg),
        ];
    }

    private function getShouldSeeReplyOnRatingAsked(): array
    {
        return [
            sprintf('[2/3] %s', $this->trans('ask.create.rating')),
        ];
    }

    private function getShouldSeeKeyboardOnRatingAsked(): array
    {
        return [
            ...CreateFeedbackTelegramConversation::getRatingButtons($this->tg),
            CreateFeedbackTelegramConversation::getCancelButton($this->tg),
        ];
    }

    private function getShouldSeeReplyOnDescriptionAsked(): array
    {
        return [
            sprintf('[3/3] %s', $this->trans('ask.create.description')),
        ];
    }

    private function getShouldSeeKeyboardOnDescriptionAsked(): array
    {
        return [
            CreateFeedbackTelegramConversation::getLeaveEmptyButton($this->tg),
            CreateFeedbackTelegramConversation::getCancelButton($this->tg),
        ];
    }

    private function getShouldSeeReplyOnConfirmAsked(CreateFeedbackTelegramConversationState $state): array
    {
        return [
            $this->trans('ask.create.confirm'),
            $this->renderView(TelegramView::FEEDBACK, [
                'search_term' => $state->getSearchTerm(),
                'rating' => $state->getRating(),
                'description' => $state->getDescription(),
            ]),
        ];
    }

    private function getShouldSeeKeyboardOnConfirmAsked(CreateFeedbackTelegramConversationState $state): array
    {
        return [
            CreateFeedbackTelegramConversation::getConfirmButton($this->tg),
            CreateFeedbackTelegramConversation::getChangeSearchTermButton($this->tg),
            CreateFeedbackTelegramConversation::getChangeRatingButton($this->tg),
            $state->getDescription() === null
                ? CreateFeedbackTelegramConversation::getAddDescriptionButton($this->tg)
                : CreateFeedbackTelegramConversation::getChangeDescriptionButton($this->tg),
            CreateFeedbackTelegramConversation::getCancelButton($this->tg),
        ];
    }
}
