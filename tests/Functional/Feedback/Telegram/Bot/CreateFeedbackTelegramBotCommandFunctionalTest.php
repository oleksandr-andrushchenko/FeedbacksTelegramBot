<?php

declare(strict_types=1);

namespace App\Tests\Functional\Feedback\Telegram\Bot;

use App\Entity\Feedback\Feedback;
use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Feedback\Telegram\Bot\CreateFeedbackTelegramBotConversationState;
use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Entity\Telegram\TelegramBotConversation;
use App\Entity\User\User;
use App\Enum\Feedback\Rating;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Messenger\Messenger;
use App\Service\Feedback\Telegram\Bot\Conversation\CreateFeedbackTelegramBotConversation;
use App\Service\Feedback\Telegram\Bot\FeedbackTelegramBotGroup;
use App\Tests\Fixtures;
use App\Tests\Functional\Telegram\Bot\TelegramBotCommandFunctionalTestCase;
use App\Tests\Traits\Feedback\FeedbackRatingProviderTrait;
use App\Tests\Traits\Feedback\FeedbackRepositoryProviderTrait;
use App\Tests\Traits\Feedback\FeedbackSearchTermTypeProviderTrait;
use App\Tests\Traits\User\UserRepositoryProviderTrait;
use App\Transfer\Feedback\SearchTermTransfer;
use App\Transfer\Messenger\MessengerUserTransfer;
use Generator;

class CreateFeedbackTelegramBotCommandFunctionalTest extends TelegramBotCommandFunctionalTestCase
{
    use FeedbackRepositoryProviderTrait;
    use UserRepositoryProviderTrait;
    use FeedbackRatingProviderTrait;
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
            ->shouldSeeStateStep(
                $this->getConversation(),
                CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED
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
            'command' => $this->command('create'),
        ];

        yield 'command' => [
            'command' => FeedbackTelegramBotGroup::CREATE,
        ];
    }

    /**
     * @param SearchTermTransfer[]|null $searchTerms
     * @param string $command
     * @param array $shouldSeeReplies
     * @param array $shouldSeeButtons
     * @param int|null $shouldSeeStep
     * @param string|null $shouldSeeFirstSearchTermText
     * @return void
     * @dataProvider searchTermStepDataProvider
     */
    public function testSearchTermStep(
        ?array $searchTerms,
        string $command,
        array $shouldSeeReplies,
        array $shouldSeeButtons,
        ?int $shouldSeeStep,
        ?string $shouldSeeFirstSearchTermText
    ): void
    {
        $this->test(
            $searchTerms,
            null,
            null,
            CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
            $command,
            $shouldSeeReplies,
            $shouldSeeButtons,
            $shouldSeeStep,
            $shouldSeeFirstSearchTermText
        );
    }

    public function searchTermStepDataProvider(): Generator
    {
        $validators = [
            ['empty', '', 'text.not_blank'],
            ['multiple lines', "first_line\nsecond_line", 'text.single_line'],
            ['forbidden chars', 'qweqwe, sdf', 'text.allowed_chars'],
            ['too short', 'q', 'text.min_length'],
            ['too long', str_repeat('q', 256 + 1), 'text.max_length'],
        ];

        foreach ($validators as [$key, $command, $reply]) {
            yield 'type ' . $key . ' & empty search terms' => [
                'searchTerms' => null,
                'command' => $command,
                'shouldSeeReplies' => [
                    $reply,
                    'query.search_term',
                ],
                'shouldSeeButtons' => [
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
                'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
                'shouldSeeFirstSearchTermText' => null,
            ];

            yield 'type ' . $key . ' & non-empty search terms' => [
                'searchTerms' => [
                    $searchTerm = new SearchTermTransfer('any_search_term', SearchTermType::person_name),
                ],
                'command' => $command,
                'shouldSeeReplies' => [
                    $reply,
                    'query.search_term',
                ],
                'shouldSeeButtons' => [
                    $this->removeButton($searchTerm->getText()),
                    $this->nextButton(),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
                'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
                'shouldSeeFirstSearchTermText' => null,
            ];
        }

        yield 'type & empty search term & unknown type' => [
            'searchTerms' => null,
            'command' => $text = 'any_search_term',
            'shouldSeeReplies' => [
                'query.search_term_type',
            ],
            'shouldSeeButtons' => [
                $this->searchTermTypeButton(SearchTermType::unknown),
                $this->removeButton($text),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED,
            'shouldSeeFirstSearchTermText' => null,
        ];

        yield 'type & empty search term & known type' => [
            'searchTerms' => null,
            'command' => Fixtures::getMessengerUserProfileUrl(new MessengerUserTransfer(Messenger::telegram, 'id', 'any_search_term')),
            'shouldSeeReplies' => [
                'query.extra_search_term',
            ],
            'shouldSeeButtons' => [
                $this->prevButton(),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_EXTRA_SEARCH_TERM_QUERIED,
            'shouldSeeFirstSearchTermText' => null,
        ];

        yield 'type & single search term & known type' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::onlyfans_username),
            ],
            'command' => $searchTermText = Fixtures::getMessengerUserProfileUrl(new MessengerUserTransfer(Messenger::telegram, 'id', 'any_search_term')),
            'shouldSeeReplies' => [
                'query.extra_search_term',
            ],
            'shouldSeeButtons' => [
                $this->prevButton(),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_EXTRA_SEARCH_TERM_QUERIED,
            'shouldSeeFirstSearchTermText' => $searchTermText,
        ];

        yield 'type & multiple search terms & known type' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::onlyfans_username),
                new SearchTermTransfer('any_search_term2', SearchTermType::place_name),
            ],
            'command' => $searchTermText = Fixtures::getMessengerUserProfileUrl(new MessengerUserTransfer(Messenger::telegram, 'id', 'any_search_term')),
            'shouldSeeReplies' => [
                'query.extra_search_term',
            ],
            'shouldSeeButtons' => [
                $this->prevButton(),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_EXTRA_SEARCH_TERM_QUERIED,
            'shouldSeeFirstSearchTermText' => $searchTermText,
        ];

        yield 'remove & single search term' => [
            'searchTerms' => [
                $searchTerm = new SearchTermTransfer('any_search_term', SearchTermType::onlyfans_username),
            ],
            'command' => $this->removeButton($searchTerm->getText()),
            'shouldSeeReplies' => [
                'query.search_term',
            ],
            'shouldSeeButtons' => [
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
            'shouldSeeFirstSearchTermText' => null,
        ];

        yield 'remove & multiple search terms' => [
            'searchTerms' => [
                $searchTerm1 = new SearchTermTransfer('any_search_term', SearchTermType::onlyfans_username),
                $searchTerm2 = new SearchTermTransfer('any_search_term2', SearchTermType::place_name),
            ],
            'command' => $this->removeButton($searchTerm1->getText()),
            'shouldSeeReplies' => [
                'query.search_term',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($searchTerm2->getText()),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
            'shouldSeeFirstSearchTermText' => null,
        ];

        yield 'next & single search term' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::person_name),
            ],
            'command' => $this->nextButton(),
            'shouldSeeReplies' => [
                'query.extra_search_term',
            ],
            'shouldSeeButtons' => [
                $this->prevButton(),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_EXTRA_SEARCH_TERM_QUERIED,
            'shouldSeeFirstSearchTermText' => null,
        ];

        yield 'next & multiple single search terms' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::person_name),
                $searchTerm2 = new SearchTermTransfer('any_search_term2', SearchTermType::telegram_username),
            ],
            'command' => $this->nextButton(),
            'shouldSeeReplies' => [
                'query.extra_search_term',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($searchTerm2->getText()),
                $this->prevButton(),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_EXTRA_SEARCH_TERM_QUERIED,
            'shouldSeeFirstSearchTermText' => null,
        ];

        yield 'help & empty search terms' => [
            'searchTerms' => null,
            'command' => $this->helpButton(),
            'shouldSeeReplies' => [
                'title',
                'query.search_term',
                'search_term',
                'help.use_input',
            ],
            'shouldSeeButtons' => [
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
            'shouldSeeFirstSearchTermText' => null,
        ];

        yield 'help & non-empty search terms' => [
            'searchTerms' => [
                $searchTerm = new SearchTermTransfer('any_search_term', SearchTermType::person_name),
            ],
            'command' => $this->helpButton(),
            'shouldSeeReplies' => [
                'title',
                'query.search_term',
                'search_term',
                'help.use_input',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($searchTerm->getText()),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
            'shouldSeeFirstSearchTermText' => null,
        ];

        yield 'cancel' => [
            'searchTerms' => [
                new SearchTermTransfer('15613145672', SearchTermType::phone_number),
            ],
            'command' => $this->cancelButton(),
            'shouldSeeReplies' => [
                'reply.canceled',
                'query.action',
            ],
            'shouldSeeButtons' => [
                $this->commandButton('create'),
                $this->commandButton('search'),
                $this->commandButton('lookup'),
            ],
            'shouldSeeStep' => null,
            'shouldSeeFirstSearchTermText' => null,
        ];
    }

    /**
     * @param SearchTermTransfer[]|null $searchTerms
     * @param string $command
     * @param array $shouldSeeReplies
     * @param array $shouldSeeButtons
     * @param int|null $shouldSeeStep
     * @return void
     * @dataProvider searchTermTypeStepDataProvider
     */
    public function testSearchTermTypeStep(
        ?array $searchTerms,
        string $command,
        array $shouldSeeReplies,
        array $shouldSeeButtons,
        ?int $shouldSeeStep
    ): void
    {
        $this->test(
            $searchTerms,
            null,
            null,
            CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED,
            $command,
            $shouldSeeReplies,
            $shouldSeeButtons,
            $shouldSeeStep,
            null
        );
    }

    public function searchTermTypeStepDataProvider(): Generator
    {
        yield 'type unknown & single search term' => [
            'searchTerms' => [
                $searchTerm = (new SearchTermTransfer('any_search_term'))
                    ->setTypes([
                        SearchTermType::instagram_username,
                        SearchTermType::telegram_username,
                        SearchTermType::organization_name,
                    ]),
            ],
            'command' => 'kjlk',
            'shouldSeeReplies' => [
                'reply.wrong',
                'query.search_term_type',
            ],
            'shouldSeeButtons' => [
                ...array_map(fn (SearchTermType $type): string => $this->searchTermTypeButton($type), $searchTerm->getTypes()),
                ...[
                    $this->searchTermTypeButton(SearchTermType::unknown),
                    $this->removeButton($searchTerm->getText()),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED,
        ];

        yield 'type not in the list & single search term' => [
            'searchTerms' => [
                $searchTerm = (new SearchTermTransfer('any_search_term'))
                    ->setTypes([
                        SearchTermType::instagram_username,
                        SearchTermType::telegram_username,
                        SearchTermType::organization_name,
                    ]),
            ],
            'command' => $this->searchTermTypeButton(SearchTermType::tiktok_username),
            'shouldSeeReplies' => [
                'reply.wrong',
                'query.search_term_type',
            ],
            'shouldSeeButtons' => [
                ...array_map(fn (SearchTermType $type): string => $this->searchTermTypeButton($type), $searchTerm->getTypes()),
                ...[
                    $this->searchTermTypeButton(SearchTermType::unknown),
                    $this->removeButton($searchTerm->getText()),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED,
        ];

        yield 'select type & single search term' => [
            'searchTerms' => [
                $searchTerm = (new SearchTermTransfer('any_search_term'))
                    ->setTypes([
                        SearchTermType::instagram_username,
                    ]),
            ],
            'command' => $this->searchTermTypeButton($searchTerm->getTypes()[0]),
            'shouldSeeReplies' => [
                'query.extra_search_term',
            ],
            'shouldSeeButtons' => [
                $this->prevButton(),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_EXTRA_SEARCH_TERM_QUERIED,
        ];

        yield 'select type & multiple search terms' => [
            'searchTerms' => [
                $searchTerm1 = (new SearchTermTransfer('any_search_term'))
                    ->setTypes([
                        SearchTermType::instagram_username,
                    ]),
                $searchTerm2 = (new SearchTermTransfer('any_search_term2'))
                    ->setType(SearchTermType::telegram_username),
            ],
            'command' => $this->searchTermTypeButton($searchTerm1->getTypes()[0]),
            'shouldSeeReplies' => [
                'query.extra_search_term',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($searchTerm2->getText()),
                $this->prevButton(),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_EXTRA_SEARCH_TERM_QUERIED,
        ];

        yield 'remove & single search term' => [
            'searchTerms' => [
                $searchTerm = (new SearchTermTransfer('any_search_term'))
                    ->setTypes([
                        SearchTermType::instagram_username,
                    ]),
            ],
            'command' => $this->removeButton($searchTerm->getText()),
            'shouldSeeReplies' => [
                'query.search_term',
            ],
            'shouldSeeButtons' => [
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
        ];

        yield 'remove & multiple search terms' => [
            'searchTerms' => [
                $searchTerm1 = (new SearchTermTransfer('any_search_term'))
                    ->setTypes([
                        SearchTermType::instagram_username,
                    ]),
                $searchTerm2 = (new SearchTermTransfer('any_search_term2'))
                    ->setType(SearchTermType::telegram_username),
                (new SearchTermTransfer('any_search_term3'))
                    ->setType(SearchTermType::tiktok_username),
            ],
            'command' => $this->removeButton($searchTerm1->getText()),
            'shouldSeeReplies' => [
                'query.search_term',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($searchTerm2->getText()),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
        ];

        yield 'help & single search term' => [
            'searchTerms' => [
                $searchTerm = (new SearchTermTransfer('any_search_term'))
                    ->setTypes([
                        SearchTermType::instagram_username,
                        SearchTermType::telegram_username,
                        SearchTermType::organization_name,
                    ]),
            ],
            'command' => $this->helpButton(),
            'shouldSeeReplies' => [
                'title',
                'query.search_term_type',
                'search_term_type',
                'help.use_keyboard',
            ],
            'shouldSeeButtons' => [
                ...array_map(fn (SearchTermType $type): string => $this->searchTermTypeButton($type), $searchTerm->getTypes()),
                ...[
                    $this->searchTermTypeButton(SearchTermType::unknown),
                    $this->removeButton($searchTerm->getText()),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED,
        ];

        yield 'help & multiple search terms' => [
            'searchTerms' => [
                $searchTerm1 = (new SearchTermTransfer('any_search_term'))
                    ->setTypes([
                        SearchTermType::instagram_username,
                        SearchTermType::telegram_username,
                        SearchTermType::organization_name,
                    ]),
                (new SearchTermTransfer('any_search_term2'))
                    ->setType(SearchTermType::tiktok_username),
            ],
            'command' => $this->helpButton(),
            'shouldSeeReplies' => [
                'title',
                'query.search_term_type',
                'search_term_type',
                'help.use_keyboard',
            ],
            'shouldSeeButtons' => [
                ...array_map(fn (SearchTermType $type): string => $this->searchTermTypeButton($type), $searchTerm1->getTypes()),
                ...[
                    $this->searchTermTypeButton(SearchTermType::unknown),
                    $this->removeButton($searchTerm1->getText()),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED,
        ];

        yield 'cancel' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term'),
            ],
            'command' => $this->cancelButton(),
            'shouldSeeReplies' => [
                'reply.canceled',
                'query.action',
            ],
            'shouldSeeButtons' => [
                $this->commandButton('create'),
                $this->commandButton('search'),
                $this->commandButton('lookup'),
            ],
            'shouldSeeStep' => null,
        ];
    }


    /**
     * @param SearchTermTransfer[]|null $searchTerms
     * @param Rating|null $rating
     * @param string $command
     * @param array $shouldSeeReplies
     * @param array $shouldSeeButtons
     * @param int|null $shouldSeeStep
     * @return void
     * @dataProvider extraSearchTermStepDataProvider
     */
    public function testExtraSearchTermStep(
        ?array $searchTerms,
        ?Rating $rating,
        string $command,
        array $shouldSeeReplies,
        array $shouldSeeButtons,
        ?int $shouldSeeStep
    ): void
    {
        $this->test(
            $searchTerms,
            $rating,
            null,
            CreateFeedbackTelegramBotConversation::STEP_EXTRA_SEARCH_TERM_QUERIED,
            $command,
            $shouldSeeReplies,
            $shouldSeeButtons,
            $shouldSeeStep,
            null
        );
    }

    public function extraSearchTermStepDataProvider(): Generator
    {
        $validators = [
            ['empty', '', 'text.not_blank'],
            ['multiple lines', "first_line\nsecond_line", 'text.single_line'],
            ['forbidden chars', 'qweqwe, sdf', 'text.allowed_chars'],
            ['too short', 'q', 'text.min_length'],
            ['too long', str_repeat('q', 256 + 1), 'text.max_length'],
        ];

        foreach ($validators as [$key, $command, $reply]) {
            yield 'type ' . $key . ' & empty extra search terms' => [
                'searchTerms' => [
                    new SearchTermTransfer('any_search_term', SearchTermType::person_name),
                ],
                'rating' => null,
                'command' => $command,
                'shouldSeeReplies' => [
                    $reply,
                    'query.extra_search_term',
                ],
                'shouldSeeButtons' => [
                    $this->prevButton(),
                    $this->nextButton(),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
                'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_EXTRA_SEARCH_TERM_QUERIED,
            ];

            yield 'type ' . $key . ' & non-empty extra search terms' => [
                'searchTerms' => [
                    new SearchTermTransfer('any_search_term', SearchTermType::organization_name),
                    $searchTerm2 = new SearchTermTransfer('any_search_term2', SearchTermType::person_name),
                ],
                'rating' => null,
                'command' => $command,
                'shouldSeeReplies' => [
                    $reply,
                    'query.extra_search_term',
                ],
                'shouldSeeButtons' => [
                    $this->removeButton($searchTerm2->getText()),
                    $this->prevButton(),
                    $this->nextButton(),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
                'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_EXTRA_SEARCH_TERM_QUERIED,
            ];
        }

        yield 'type & empty extra search terms & unknown type' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::onlyfans_username),
            ],
            'rating' => null,
            'command' => $text = 'any_search_term',
            'shouldSeeReplies' => [
                'query.search_term_type',
            ],
            'shouldSeeButtons' => [
                $this->searchTermTypeButton(SearchTermType::unknown),
                $this->removeButton($text),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_EXTRA_SEARCH_TERM_TYPE_QUERIED,
        ];

        yield 'type & empty extra search terms & known type' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::onlyfans_username),
            ],
            'rating' => null,
            'command' => Fixtures::getMessengerUserProfileUrl($user = new MessengerUserTransfer(Messenger::telegram, 'id', 'any_search_term')),
            'shouldSeeReplies' => [
                'query.extra_search_term',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($user->getUsername()),
                $this->prevButton(),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_EXTRA_SEARCH_TERM_QUERIED,
        ];

        yield 'type & single extra search terms & known type' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::onlyfans_username),
                $searchTerm2 = new SearchTermTransfer('any_search_term2', SearchTermType::instagram_username),
            ],
            'rating' => null,
            'command' => Fixtures::getMessengerUserProfileUrl($user = new MessengerUserTransfer(Messenger::telegram, 'id', 'any_search_term')),
            'shouldSeeReplies' => [
                'query.extra_search_term',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($searchTerm2->getText()),
                $this->removeButton($user->getUsername()),
                $this->prevButton(),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_EXTRA_SEARCH_TERM_QUERIED,
        ];

        yield 'prev' => [
            'searchTerms' => [
                $searchTerm = new SearchTermTransfer('any_search_term', SearchTermType::onlyfans_username),
            ],
            'rating' => null,
            'command' => $this->prevButton(),
            'shouldSeeReplies' => [
                'query.search_term',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($searchTerm->getText()),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
        ];

        yield 'remove & single extra search term' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::onlyfans_username),
                $searchTerm2 = new SearchTermTransfer('any_search_term2', SearchTermType::telegram_username),
            ],
            'rating' => null,
            'command' => $this->removeButton($searchTerm2->getText()),
            'shouldSeeReplies' => [
                'query.extra_search_term',
            ],
            'shouldSeeButtons' => [
                $this->prevButton(),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_EXTRA_SEARCH_TERM_QUERIED,
        ];

        yield 'remove & multiple extra search terms' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::onlyfans_username),
                $searchTerm2 = new SearchTermTransfer('any_search_term2', SearchTermType::place_name),
                $searchTerm3 = new SearchTermTransfer('any_search_term3', SearchTermType::organization_name),
            ],
            'rating' => null,
            'command' => $this->removeButton($searchTerm2->getText()),
            'shouldSeeReplies' => [
                'query.extra_search_term',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($searchTerm3->getText()),
                $this->prevButton(),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_EXTRA_SEARCH_TERM_QUERIED,
        ];

        yield 'next & empty rating' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::person_name),
            ],
            'rating' => null,
            'command' => $this->nextButton(),
            'shouldSeeReplies' => [
                'query.rating',
            ],
            'shouldSeeButtons' => [
                ...array_map(
                    fn (Rating $rating): string => $this->ratingButton($rating),
                    array_values(array_filter(iterator_to_array(Fixtures::ratings())))
                ),
                ...[
                    $this->prevButton(),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_RATING_QUERIED,
        ];

        yield 'next & non-empty rating' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::person_name),
            ],
            'rating' => $rating = Rating::random(),
            'command' => $this->nextButton(),
            'shouldSeeReplies' => [
                'query.rating',
            ],
            'shouldSeeButtons' => [
                ...array_map(
                    fn (Rating $r): string => $rating === $r ? $this->selectedText($this->ratingButton($r)) : $this->ratingButton($r),
                    array_values(array_filter(iterator_to_array(Fixtures::ratings())))
                ),
                ...[
                    $this->prevButton(),
                    $this->nextButton(),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_RATING_QUERIED,
        ];

        yield 'help & empty extra search terms' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::person_name),
            ],
            'rating' => null,
            'command' => $this->helpButton(),
            'shouldSeeReplies' => [
                'title',
                'query.extra_search_term',
                'extra_search_term',
                'help.use_input',
            ],
            'shouldSeeButtons' => [
                $this->prevButton(),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_EXTRA_SEARCH_TERM_QUERIED,
        ];

        yield 'help & non-empty extra search terms' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::organization_name),
                $searchTerm2 = new SearchTermTransfer('any_search_term2', SearchTermType::person_name),
            ],
            'rating' => null,
            'command' => $this->helpButton(),
            'shouldSeeReplies' => [
                'title',
                'query.extra_search_term',
                'extra_search_term',
                'help.use_input',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($searchTerm2->getText()),
                $this->prevButton(),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_EXTRA_SEARCH_TERM_QUERIED,
        ];

        yield 'cancel' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::organization_name),
            ],
            'rating' => null,
            'command' => $this->cancelButton(),
            'shouldSeeReplies' => [
                'reply.canceled',
                'query.action',
            ],
            'shouldSeeButtons' => [
                $this->commandButton('create'),
                $this->commandButton('search'),
                $this->commandButton('lookup'),
            ],
            'shouldSeeStep' => null,
        ];
    }

    /**
     * @param SearchTermTransfer[]|null $searchTerms
     * @param string $command
     * @param array $shouldSeeReplies
     * @param array $shouldSeeButtons
     * @param int|null $shouldSeeStep
     * @return void
     * @dataProvider extraSearchTermTypeStepSuccessDataProvider
     */
    public function testExtraSearchTermTypeStepSuccess(
        ?array $searchTerms,
        string $command,
        array $shouldSeeReplies,
        array $shouldSeeButtons,
        ?int $shouldSeeStep
    ): void
    {
        $this->test(
            $searchTerms,
            null,
            null,
            CreateFeedbackTelegramBotConversation::STEP_EXTRA_SEARCH_TERM_TYPE_QUERIED,
            $command,
            $shouldSeeReplies,
            $shouldSeeButtons,
            $shouldSeeStep,
            null
        );
    }

    public function extraSearchTermTypeStepSuccessDataProvider(): Generator
    {
        yield 'type unknown & multiple extra search terms' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::instagram_username),
                new SearchTermTransfer('any_search_term2', SearchTermType::telegram_username),
                $searchTerm3 = (new SearchTermTransfer('any_search_term3'))
                    ->setTypes([
                        SearchTermType::tiktok_username,
                        SearchTermType::youtube_username,
                    ]),
            ],
            'command' => 'unknown',
            'shouldSeeReplies' => [
                'reply.wrong',
                'query.search_term_type',
            ],
            'shouldSeeButtons' => [
                ...array_map(fn (SearchTermType $type): string => $this->searchTermTypeButton($type), $searchTerm3->getTypes()),
                ...[
                    $this->searchTermTypeButton(SearchTermType::unknown),
                    $this->removeButton($searchTerm3->getText()),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_EXTRA_SEARCH_TERM_TYPE_QUERIED,
        ];

        yield 'type not in the list & multiple extra search terms' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::instagram_username),
                new SearchTermTransfer('any_search_term2', SearchTermType::telegram_username),
                $searchTerm3 = (new SearchTermTransfer('any_search_term3'))
                    ->setTypes([
                        SearchTermType::tiktok_username,
                        SearchTermType::youtube_username,
                    ]),
            ],
            'command' => $this->searchTermTypeButton(SearchTermType::telegram_username),
            'shouldSeeReplies' => [
                'reply.wrong',
                'query.search_term_type',
            ],
            'shouldSeeButtons' => [
                ...array_map(fn (SearchTermType $type): string => $this->searchTermTypeButton($type), $searchTerm3->getTypes()),
                ...[
                    $this->searchTermTypeButton(SearchTermType::unknown),
                    $this->removeButton($searchTerm3->getText()),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_EXTRA_SEARCH_TERM_TYPE_QUERIED,
        ];

        yield 'select type & single extra search term' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::telegram_username),
                $searchTerm2 = (new SearchTermTransfer('any_search_term2'))
                    ->setTypes([
                        SearchTermType::instagram_username,
                    ]),
            ],
            'command' => $this->searchTermTypeButton($searchTerm2->getTypes()[0]),
            'shouldSeeReplies' => [
                'query.extra_search_term',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($searchTerm2->getText()),
                $this->prevButton(),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_EXTRA_SEARCH_TERM_QUERIED,
        ];

        yield 'select type & multiple extra search terms' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::instagram_username),
                $searchTerm2 = new SearchTermTransfer('any_search_term2', SearchTermType::telegram_username),
                $searchTerm3 = (new SearchTermTransfer('any_search_term3'))
                    ->setTypes([
                        SearchTermType::youtube_username,
                        SearchTermType::tiktok_username,
                    ]),
            ],
            'command' => $this->searchTermTypeButton($searchTerm3->getTypes()[count($searchTerm3->getTypes()) - 1]),
            'shouldSeeReplies' => [
                'query.extra_search_term',
            ],
            'shouldSeeButtons' => [
                ...array_map(fn (SearchTermTransfer $searchTerm): string => $this->removeButton($searchTerm->getText()), [$searchTerm2, $searchTerm3]),
                ...[
                    $this->prevButton(),
                    $this->nextButton(),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_EXTRA_SEARCH_TERM_QUERIED,
        ];

        yield 'remove & single extra search term' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::instagram_username),
                $searchTerm2 = (new SearchTermTransfer('any_search_term2'))
                    ->setTypes([
                        SearchTermType::telegram_username,
                        SearchTermType::youtube_username,
                    ]),
            ],
            'command' => $this->removeButton($searchTerm2->getText()),
            'shouldSeeReplies' => [
                'query.extra_search_term',
            ],
            'shouldSeeButtons' => [
                $this->prevButton(),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_EXTRA_SEARCH_TERM_QUERIED,
        ];

        yield 'remove & multiple extra search term' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::instagram_username),
                $searchTerm2 = new SearchTermTransfer('any_search_term2', SearchTermType::telegram_username),
                $searchTerm3 = (new SearchTermTransfer('any_search_term3'))
                    ->setTypes([
                        SearchTermType::tiktok_username,
                        SearchTermType::youtube_username,
                    ]),
            ],
            'command' => $this->removeButton($searchTerm3->getText()),
            'shouldSeeReplies' => [
                'query.extra_search_term',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($searchTerm2->getText()),
                $this->prevButton(),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_EXTRA_SEARCH_TERM_QUERIED,
        ];

        yield 'help & single extra search term' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::instagram_username),
                $searchTerm2 = (new SearchTermTransfer('any_search_term2'))
                    ->setTypes([
                        SearchTermType::instagram_username,
                        SearchTermType::telegram_username,
                        SearchTermType::organization_name,
                    ]),
            ],
            'command' => $this->helpButton(),
            'shouldSeeReplies' => [
                'title',
                'query.search_term_type',
                'search_term_type',
                'help.use_keyboard',
            ],
            'shouldSeeButtons' => [
                ...array_map(fn (SearchTermType $type): string => $this->searchTermTypeButton($type), $searchTerm2->getTypes()),
                ...[
                    $this->searchTermTypeButton(SearchTermType::unknown),
                    $this->removeButton($searchTerm2->getText()),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_EXTRA_SEARCH_TERM_TYPE_QUERIED,
        ];

        yield 'help & multiple extra search terms' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::instagram_username),
                new SearchTermTransfer('any_search_term2', SearchTermType::telegram_username),
                $searchTerm3 = (new SearchTermTransfer('any_search_term3'))
                    ->setTypes([
                        SearchTermType::tiktok_username,
                        SearchTermType::youtube_username,
                    ]),
            ],
            'command' => $this->helpButton(),
            'shouldSeeReplies' => [
                'title',
                'query.search_term_type',
                'search_term_type',
                'help.use_keyboard',
            ],
            'shouldSeeButtons' => [
                ...array_map(fn (SearchTermType $type): string => $this->searchTermTypeButton($type), $searchTerm3->getTypes()),
                ...[
                    $this->searchTermTypeButton(SearchTermType::unknown),
                    $this->removeButton($searchTerm3->getText()),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_EXTRA_SEARCH_TERM_TYPE_QUERIED,
        ];

        yield 'cancel & empty extra search terms' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::car_number),
            ],
            'command' => $this->cancelButton(),
            'shouldSeeReplies' => [
                'reply.canceled',
                'query.action',
            ],
            'shouldSeeButtons' => [
                $this->commandButton('create'),
                $this->commandButton('search'),
                $this->commandButton('lookup'),
            ],
            'shouldSeeStep' => null,
        ];

        yield 'cancel & non-empty extra search terms' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::car_number),
                new SearchTermTransfer('1563145672', SearchTermType::phone_number),
                new SearchTermTransfer('any_search_term3', SearchTermType::telegram_username),
            ],
            'command' => $this->cancelButton(),
            'shouldSeeReplies' => [
                'reply.canceled',
                'query.action',
            ],
            'shouldSeeButtons' => [
                $this->commandButton('create'),
                $this->commandButton('search'),
                $this->commandButton('lookup'),
            ],
            'shouldSeeStep' => null,
        ];
    }

    /**
     * @param SearchTermTransfer[]|null $searchTerms
     * @param Rating|null $rating
     * @param string|null $description
     * @param string $command
     * @param array $shouldSeeReplies
     * @param array $shouldSeeButtons
     * @param int|null $shouldSeeStep
     * @return void
     * @dataProvider ratingStepDataProvider
     */
    public function testRatingStep(
        ?array $searchTerms,
        ?Rating $rating,
        ?string $description,
        string $command,
        array $shouldSeeReplies,
        array $shouldSeeButtons,
        ?int $shouldSeeStep
    ): void
    {
        $this->test(
            $searchTerms,
            $rating,
            $description,
            CreateFeedbackTelegramBotConversation::STEP_RATING_QUERIED,
            $command,
            $shouldSeeReplies,
            $shouldSeeButtons,
            $shouldSeeStep,
            null
        );
    }

    public function ratingStepDataProvider(): Generator
    {
        yield 'type unknown & empty rating' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::unknown),
            ],
            'rating' => null,
            'description' => null,
            'command' => 'unknown',
            'shouldSeeReplies' => [
                'reply.wrong',
                'query.rating',
            ],
            'shouldSeeButtons' => [
                ...array_map(
                    fn (Rating $rating): string => $this->ratingButton($rating),
                    array_values(array_filter(iterator_to_array(Fixtures::ratings())))
                ),
                ...[
                    $this->prevButton(),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_RATING_QUERIED,
        ];

        yield 'type unknown & non-empty rating' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::unknown),
            ],
            'rating' => $rating = Rating::random(),
            'description' => null,
            'command' => 'unknown',
            'shouldSeeReplies' => [
                'reply.wrong',
                'query.rating',
            ],
            'shouldSeeButtons' => [
                ...array_map(
                    fn (Rating $r): string => $rating === $r ? $this->selectedText($this->ratingButton($r)) : $this->ratingButton($r),
                    array_values(array_filter(iterator_to_array(Fixtures::ratings())))
                ),
                ...[
                    $this->prevButton(),
                    $this->nextButton(),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_RATING_QUERIED,
        ];

        yield 'prev & empty extra search terms' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => 'any_description',
            'command' => $this->prevButton(),
            'shouldSeeReplies' => [
                'query.extra_search_term',
            ],
            'shouldSeeButtons' => [
                $this->prevButton(),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_EXTRA_SEARCH_TERM_QUERIED,
        ];

        yield 'prev & non-empty extra search terms' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::unknown),
                $searchTerm2 = new SearchTermTransfer('any_search_term2', SearchTermType::telegram_username),
                $searchTerm3 = new SearchTermTransfer('any_search_term3', SearchTermType::vkontakte_username),
            ],
            'rating' => Rating::random(),
            'description' => 'any_description',
            'command' => $this->prevButton(),
            'shouldSeeReplies' => [
                'query.extra_search_term',
            ],
            'shouldSeeButtons' => [
                ...array_map(
                    fn (SearchTermTransfer $searchTerm): string => $this->removeButton($searchTerm->getText()),
                    [$searchTerm2, $searchTerm3]
                ),
                ...[
                    $this->prevButton(),
                    $this->nextButton(),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_EXTRA_SEARCH_TERM_QUERIED,
        ];

        yield 'next & empty description' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => null,
            'command' => $this->nextButton(),
            'shouldSeeReplies' => [
                'query.description',
            ],
            'shouldSeeButtons' => [
                $this->prevButton(),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_DESCRIPTION_QUERIED,
        ];

        yield 'next & non-empty description' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => $description = 'any description',
            'command' => $this->nextButton(),
            'shouldSeeReplies' => [
                'query.description',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($description),
                $this->prevButton(),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_DESCRIPTION_QUERIED,
        ];

        yield 'help & empty rating' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => 'any_description',
            'command' => $this->helpButton(),
            'shouldSeeReplies' => [
                'title',
                'query.rating',
                'rating',
                'help.use_keyboard',
            ],
            'shouldSeeButtons' => [
                ...array_map(
                    fn (Rating $rating): string => $this->ratingButton($rating),
                    array_values(array_filter(iterator_to_array(Fixtures::ratings())))
                ),
                ...[
                    $this->prevButton(),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_RATING_QUERIED,
        ];

        yield 'help & non-empty rating' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::unknown),
            ],
            'rating' => $rating = Rating::random(),
            'description' => 'any_description',
            'command' => $this->helpButton(),
            'shouldSeeReplies' => [
                'title',
                'query.rating',
                'rating',
                'help.use_keyboard',
            ],
            'shouldSeeButtons' => [
                ...array_map(
                    fn (Rating $r): string => $rating === $r ? $this->selectedText($this->ratingButton($r)) : $this->ratingButton($r),
                    array_values(array_filter(iterator_to_array(Fixtures::ratings())))
                ),
                ...[
                    $this->prevButton(),
                    $this->nextButton(),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_RATING_QUERIED,
        ];

        yield 'cancel' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => 'any_description',
            'command' => $this->cancelButton(),
            'shouldSeeReplies' => [
                'reply.canceled',
                'query.action',
            ],
            'shouldSeeButtons' => [
                $this->commandButton('create'),
                $this->commandButton('search'),
                $this->commandButton('lookup'),
            ],
            'shouldSeeStep' => null,
        ];

        yield 'type & empty rating' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::unknown),
            ],
            'rating' => null,
            'description' => null,
            'command' => $this->ratingButton(Rating::neutral),
            'shouldSeeReplies' => [
                'query.description',
            ],
            'shouldSeeButtons' => [
                $this->prevButton(),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_DESCRIPTION_QUERIED,
        ];

        yield 'type same & non-empty rating & empty description' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::unknown),
            ],
            'rating' => $rating = Rating::neutral,
            'description' => null,
            'command' => $this->selectedText($this->ratingButton($rating)),
            'shouldSeeReplies' => [
                'query.description',
            ],
            'shouldSeeButtons' => [
                $this->prevButton(),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_DESCRIPTION_QUERIED,
        ];

        yield 'type same & non-empty rating & non-empty description' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::unknown),
            ],
            'rating' => $rating = Rating::neutral,
            'description' => $description = 'any description',
            'command' => $this->selectedText($this->ratingButton($rating)),
            'shouldSeeReplies' => [
                'query.description',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($description),
                $this->prevButton(),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_DESCRIPTION_QUERIED,
        ];

        yield 'type different & non-empty rating & empty description' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::unknown),
            ],
            'rating' => Rating::satisfied,
            'description' => null,
            'command' => $this->ratingButton(Rating::neutral),
            'shouldSeeReplies' => [
                'query.description',
            ],
            'shouldSeeButtons' => [
                $this->prevButton(),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_DESCRIPTION_QUERIED,
        ];

        yield 'type different & non-empty rating & non-empty description' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::unknown),
            ],
            'rating' => Rating::satisfied,
            'description' => $description = 'any description',
            'command' => $this->ratingButton(Rating::neutral),
            'shouldSeeReplies' => [
                'query.description',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($description),
                $this->prevButton(),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_DESCRIPTION_QUERIED,
        ];
    }

    /**
     * @param SearchTermTransfer[]|null $searchTerms
     * @param Rating|null $rating
     * @param string|null $description
     * @param string $command
     * @param array $shouldSeeReplies
     * @param array $shouldSeeButtons
     * @param int|null $shouldSeeStep
     * @return void
     * @dataProvider descriptionStepDataProvider
     */
    public function testDescriptionStep(
        ?array $searchTerms,
        ?Rating $rating,
        ?string $description,
        string $command,
        array $shouldSeeReplies,
        array $shouldSeeButtons,
        ?int $shouldSeeStep
    ): void
    {
        $this->test(
            $searchTerms,
            $rating,
            $description,
            CreateFeedbackTelegramBotConversation::STEP_DESCRIPTION_QUERIED,
            $command,
            $shouldSeeReplies,
            $shouldSeeButtons,
            $shouldSeeStep,
            null
        );
    }

    public function descriptionStepDataProvider(): Generator
    {
        $validators = [
            ['empty', '', 'description.not_blank'],
            ['too short', 'q', 'description.min_length'],
            ['too long', str_repeat('q', 2048 + 1), 'description.max_length'],
        ];

        foreach ($validators as [$key, $command, $reply]) {
            yield 'type ' . $key . ' & empty description' => [
                'searchTerms' => [
                    new SearchTermTransfer('any_search_term', SearchTermType::unknown),
                ],
                'rating' => Rating::random(),
                'description' => null,
                'command' => $command,
                'shouldSeeReplies' => [
                    $reply,
                    'query.description',
                ],
                'shouldSeeButtons' => [
                    $this->prevButton(),
                    $this->nextButton(),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
                'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_DESCRIPTION_QUERIED,
            ];

            yield 'type ' . $key . ' & non-empty description' => [
                'searchTerms' => [
                    new SearchTermTransfer('any_search_term', SearchTermType::unknown),
                ],
                'rating' => Rating::random(),
                'description' => $description = 'any description',
                'command' => $command,
                'shouldSeeReplies' => [
                    $reply,
                    'query.description',
                ],
                'shouldSeeButtons' => [
                    $this->removeButton($description),
                    $this->prevButton(),
                    $this->nextButton(),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
                'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_DESCRIPTION_QUERIED,
            ];
        }

        yield 'prev' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::unknown),
            ],
            'rating' => $rating = Rating::random(),
            'description' => 'any_description',
            'command' => $this->prevButton(),
            'shouldSeeReplies' => [
                'query.rating',
            ],
            'shouldSeeButtons' => [
                ...array_map(
                    fn (Rating $r): string => $rating === $r ? $this->selectedText($this->ratingButton($r)) : $this->ratingButton($r),
                    array_values(array_filter(iterator_to_array(Fixtures::ratings())))
                ),
                ...[
                    $this->prevButton(),
                    $this->nextButton(),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_RATING_QUERIED,
        ];

        yield 'next' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => 'any_description',
            'command' => $this->nextButton(),
            'shouldSeeReplies' => [
                'query.confirm',
            ],
            'shouldSeeButtons' => [
                $this->yesButton(),
                $this->prevButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_CONFIRM_QUERIED,
        ];

        yield 'help & empty description' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => null,
            'command' => $this->helpButton(),
            'shouldSeeReplies' => [
                'title',
                'query.description',
                'description',
                'help.use_input',
            ],
            'shouldSeeButtons' => [
                $this->prevButton(),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_DESCRIPTION_QUERIED,
        ];

        yield 'help & non-empty description' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => $description = 'any description',
            'command' => $this->helpButton(),
            'shouldSeeReplies' => [
                'title',
                'query.description',
                'description',
                'help.use_input',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($description),
                $this->prevButton(),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_DESCRIPTION_QUERIED,
        ];

        yield 'cancel' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => 'any_description',
            'command' => $this->cancelButton(),
            'shouldSeeReplies' => [
                'reply.canceled',
                'query.action',
            ],
            'shouldSeeButtons' => [
                $this->commandButton('create'),
                $this->commandButton('search'),
                $this->commandButton('lookup'),
            ],
            'shouldSeeStep' => null,
        ];

        yield 'type' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => 'any_description',
            'command' => 'any_description2',
            'shouldSeeReplies' => [
                'query.confirm',
            ],
            'shouldSeeButtons' => [
                $this->yesButton(),
                $this->prevButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_CONFIRM_QUERIED,
        ];

        yield 'remove' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => $description = 'any description',
            'command' => $this->removeButton($description),
            'shouldSeeReplies' => [
                'query.description',
            ],
            'shouldSeeButtons' => [
                $this->prevButton(),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_DESCRIPTION_QUERIED,
        ];
    }

    /**
     * @param SearchTermTransfer[] $searchTerms
     * @param Rating $rating
     * @param string|null $description
     * @param string $command
     * @param array $shouldSeeReplies
     * @param array $shouldSeeButtons
     * @param int|null $shouldSeeStep
     * @return void
     * @dataProvider confirmStepDataProvider
     */
    public function testConfirmStep(
        array $searchTerms,
        Rating $rating,
        ?string $description,
        string $command,
        array $shouldSeeReplies,
        array $shouldSeeButtons,
        ?int $shouldSeeStep
    ): void
    {
        $this->test(
            $searchTerms,
            $rating,
            $description,
            CreateFeedbackTelegramBotConversation::STEP_CONFIRM_QUERIED,
            $command,
            $shouldSeeReplies,
            $shouldSeeButtons,
            $shouldSeeStep,
            null
        );
    }

    public function confirmStepDataProvider(): Generator
    {
        yield 'type unknown' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => 'any_description',
            'command' => 'unknown',
            'shouldSeeReplies' => [
                'reply.wrong',
                'query.confirm',
            ],
            'shouldSeeButtons' => [
                $this->yesButton(),
                $this->prevButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_CONFIRM_QUERIED,
        ];

        yield 'yes' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => 'any_description',
            'command' => $this->yesButton(),
            'shouldSeeReplies' => [
                'reply.created',
                'query.send_to_channel_confirm',
            ],
            'shouldSeeButtons' => [
                $this->yesButton(),
                $this->noButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEND_TO_CHANNEL_CONFIRM_QUERIED,
        ];

        yield 'prev & empty description' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => null,
            'command' => $this->prevButton(),
            'shouldSeeReplies' => [
                'query.description',
            ],
            'shouldSeeButtons' => [
                $this->prevButton(),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_DESCRIPTION_QUERIED,
        ];

        yield 'prev & non-empty description' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => $description = 'any description',
            'command' => $this->prevButton(),
            'shouldSeeReplies' => [
                'query.description',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($description),
                $this->prevButton(),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_DESCRIPTION_QUERIED,
        ];

        yield 'help' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => 'any_description',
            'command' => $this->helpButton(),
            'shouldSeeReplies' => [
                'title',
                'query.confirm',
                'confirm',
                'help.use_keyboard',
            ],
            'shouldSeeButtons' => [
                $this->yesButton(),
                $this->prevButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_CONFIRM_QUERIED,
        ];

        yield 'cancel' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => 'any_description',
            'command' => $this->cancelButton(),
            'shouldSeeReplies' => [
                'reply.canceled',
                'query.action',
            ],
            'shouldSeeButtons' => [
                $this->commandButton('create'),
                $this->commandButton('search'),
                $this->commandButton('lookup'),
            ],
            'shouldSeeStep' => null,
        ];
    }

    /**
     * @param SearchTermTransfer[] $searchTerms
     * @param Rating $rating
     * @param string|null $description
     * @param string $command
     * @param array $shouldSeeReplies
     * @param array $shouldSeeButtons
     * @param int|null $shouldSeeStep
     * @return void
     * @dataProvider sendToChannelConfirmStepDataProvider
     */
    public function testSendToChannelConfirmStep(
        array $searchTerms,
        Rating $rating,
        ?string $description,
        string $command,
        array $shouldSeeReplies,
        array $shouldSeeButtons,
        ?int $shouldSeeStep
    ): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
            FeedbackSearchTerm::class,
            Feedback::class,
        ]);

        $state = (new CreateFeedbackTelegramBotConversationState())
            ->setSearchTerms($searchTerms)
            ->setRating($rating)
            ->setDescription($description)
            ->setCreatedId(1)
            ->setStep(CreateFeedbackTelegramBotConversation::STEP_SEND_TO_CHANNEL_CONFIRM_QUERIED)
        ;

        $conversation = $this->createConversation(CreateFeedbackTelegramBotConversation::class, $state);

        $this
            ->type($command)
            ->shouldSeeStateStep($conversation, $shouldSeeStep)
            ->shouldSeeReply(...$shouldSeeReplies)
            ->shouldSeeButtons(...$shouldSeeButtons)
        ;
    }

    public function sendToChannelConfirmStepDataProvider(): Generator
    {
        yield 'type unknown' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => 'any_description',
            'command' => 'unknown',
            'shouldSeeReplies' => [
                'reply.wrong',
                'query.send_to_channel_confirm',
            ],
            'shouldSeeButtons' => [
                $this->yesButton(),
                $this->noButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEND_TO_CHANNEL_CONFIRM_QUERIED,
        ];

        yield 'yes' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => 'any_description',
            'command' => $this->yesButton(),
            'shouldSeeReplies' => [
                'reply.sent_to_channel',
            ],
            'shouldSeeButtons' => [
                $this->commandButton('create'),
                $this->commandButton('search'),
                $this->commandButton('lookup'),
            ],
            'shouldSeeStep' => null,
        ];

        yield 'no' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => 'any_description',
            'command' => $this->noButton(),
            'shouldSeeReplies' => [
                'query.action',
            ],
            'shouldSeeButtons' => [
                $this->commandButton('create'),
                $this->commandButton('search'),
                $this->commandButton('lookup'),
            ],
            'shouldSeeStep' => null,
        ];

        yield 'help' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => 'any_description',
            'command' => $this->helpButton(),
            'shouldSeeReplies' => [
                'title',
                'query.send_to_channel_confirm',
                'send_to_channel_confirm',
                'help.use_keyboard',
            ],
            'shouldSeeButtons' => [
                $this->yesButton(),
                $this->noButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEND_TO_CHANNEL_CONFIRM_QUERIED,
        ];

        yield 'cancel' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => 'any_description',
            'command' => $this->cancelButton(),
            'shouldSeeReplies' => [
                'reply.canceled',
                'query.action',
            ],
            'shouldSeeButtons' => [
                $this->commandButton('create'),
                $this->commandButton('search'),
                $this->commandButton('lookup'),
            ],
            'shouldSeeStep' => null,
        ];
    }

    /**
     * @param SearchTermTransfer[]|null $searchTerms
     * @param Rating|null $rating
     * @param string|null $description
     * @param int|null $stateStep
     * @param string $command
     * @param array $shouldSeeReplies
     * @param array $shouldSeeButtons
     * @param int|null $shouldSeeStep
     * @param string|null $shouldSeeFirstSearchTermText
     * @return void
     */
    protected function test(
        ?array $searchTerms,
        ?Rating $rating,
        ?string $description,
        ?int $stateStep,
        string $command,
        array $shouldSeeReplies,
        array $shouldSeeButtons,
        ?int $shouldSeeStep,
        ?string $shouldSeeFirstSearchTermText
    ): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
        ]);

        $state = (new CreateFeedbackTelegramBotConversationState())
            ->setSearchTerms($searchTerms)
            ->setRating($rating)
            ->setDescription($description)
            ->setStep($stateStep)
        ;

        $conversation = $this->createConversation(CreateFeedbackTelegramBotConversation::class, $state);

        $this
            ->type($command)
            ->shouldSeeStateStep($conversation, $shouldSeeStep)
            ->shouldSeeFirstSearchTermText($conversation, $shouldSeeFirstSearchTermText)
            ->shouldSeeReply(...$shouldSeeReplies)
            ->shouldSeeButtons(...$shouldSeeButtons)
        ;
    }

    protected function shouldSeeFirstSearchTermText(TelegramBotConversation $conversation, ?string $shouldSeeFirstSearchTermText): static
    {
        if ($shouldSeeFirstSearchTermText !== null) {
            $this->assertEquals($shouldSeeFirstSearchTermText, $conversation->getState()['search_terms'][0]['text']);
        }

        return $this;
    }
}
