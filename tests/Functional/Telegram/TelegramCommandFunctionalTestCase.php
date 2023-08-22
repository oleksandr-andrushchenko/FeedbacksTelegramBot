<?php

declare(strict_types=1);

namespace App\Tests\Functional\Telegram;

use App\Entity\Feedback\Feedback;
use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Entity\Telegram\TelegramConversation;
use App\Entity\Telegram\TelegramConversationState;
use App\Entity\User\User;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Messenger\Messenger;
use App\Object\Feedback\SearchTermTransfer;
use App\Object\Messenger\MessengerUserTransfer;
use App\Service\Telegram\Telegram;
use App\Service\Telegram\TelegramAwareHelper;
use App\Service\Telegram\TelegramChatProvider;
use App\Service\Telegram\TelegramKeyboardFactory;
use App\Service\Telegram\TelegramUserProvider;
use App\Tests\DatabaseTestCase;
use App\Tests\Fixtures;
use App\Tests\Traits\EntityManagerProviderTrait;
use App\Tests\Traits\Feedback\SearchTermParserProviderTrait;
use App\Tests\Traits\Instagram\InstagramMessengerUserFinderMockProviderTrait;
use App\Tests\Traits\Instagram\InstagramMessengerUserProviderTrait;
use App\Tests\Traits\Messenger\MessengerUserProfileUrlProviderTrait;
use App\Tests\Traits\Messenger\MessengerUserRepositoryProviderTrait;
use App\Tests\Traits\SerializerProviderTrait;
use App\Tests\Traits\Telegram\TelegramAwareHelperProviderTrait;
use App\Tests\Traits\Telegram\TelegramBotRepositoryMockProviderTrait;
use App\Tests\Traits\Telegram\TelegramChatProviderTrait;
use App\Tests\Traits\Telegram\TelegramConversationManagerProviderTrait;
use App\Tests\Traits\Telegram\TelegramConversationRepositoryProviderTrait;
use App\Tests\Traits\Telegram\TelegramKeyboardFactoryProviderTrait;
use App\Tests\Traits\Telegram\TelegramMessageSenderMockProviderTrait;
use App\Tests\Traits\Telegram\TelegramMessageSenderProviderTrait;
use App\Tests\Traits\Telegram\TelegramRegistryProviderTrait;
use App\Tests\Traits\Telegram\TelegramUpdateFixtureProviderTrait;
use App\Tests\Traits\Telegram\TelegramUpdateHandlerTrait;
use App\Tests\Traits\Telegram\TelegramUserProviderTrait;
use App\Tests\Traits\TranslatorProviderTrait;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;

abstract class TelegramCommandFunctionalTestCase extends DatabaseTestCase
{
    use TelegramUpdateHandlerTrait;
    use TelegramUpdateFixtureProviderTrait;
    use TelegramRegistryProviderTrait;
    use TelegramMessageSenderMockProviderTrait;
    use TelegramMessageSenderProviderTrait;
    use TranslatorProviderTrait;
    use TelegramKeyboardFactoryProviderTrait;
    use TelegramConversationRepositoryProviderTrait;
    use EntityManagerProviderTrait;
    use InstagramMessengerUserFinderMockProviderTrait;
    use MessengerUserRepositoryProviderTrait;
    use TelegramConversationManagerProviderTrait;
    use MessengerUserProfileUrlProviderTrait;
    use ArraySubsetAsserts;
    use SearchTermParserProviderTrait;
    use SerializerProviderTrait;
    use TelegramAwareHelperProviderTrait;
    use InstagramMessengerUserProviderTrait;
    use TelegramUserProviderTrait;
    use TelegramChatProviderTrait;
    use TelegramBotRepositoryMockProviderTrait;

    protected Telegram $telegram;
    protected TelegramAwareHelper $tg;
    protected ?Update $update;
    protected ?TelegramConversation $conversation;
    protected TranslatorInterface $translator;
    protected TelegramKeyboardFactory $keyboardFactory;
    protected TelegramUserProvider $userProvider;
    protected TelegramChatProvider $chatProvider;

    public function setUp(): void
    {
        parent::setUp();

        $this->tgUp();
    }

    public function tgUp(): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
            TelegramConversation::class,
            Feedback::class,
        ]);

        $this->telegram = $this->getTelegramRegistry()->getTelegram(Fixtures::BOT_USERNAME_1);
        $this->tg = $this->getTelegramAwareHelper()->withTelegram($this->telegram);
        $this->update = $this->getTelegramMessageUpdateFixture('any');
        $this->conversation = null;
        $this->translator = $this->getTranslator();
        $this->keyboardFactory = $this->getTelegramKeyboardFactory();
        $this->userProvider = $this->getTelegramUserProvider();
        $this->chatProvider = $this->getTelegramChatProvider();
    }

    protected function fnd()
    {
        $this->databaseDown();
        $this->databaseUp();
        static::$fixtures = [];

        $this->bootFixtures([
            TelegramBot::class,
        ]);
    }

    protected function tg(): TelegramAwareHelper
    {
        return $this->tg;
    }

    public static function getContainer(): ContainerInterface
    {
        return parent::getContainer();
    }

    protected function getUpdateUserId(): ?int
    {
        return $this->userProvider->getTelegramUserByUpdate($this->telegram->getUpdate() ?? $this->update)?->getId();
    }

    protected function getUpdateChatId(): ?int
    {
        return $this->chatProvider->getTelegramChatByUpdate($this->telegram->getUpdate() ?? $this->update)?->getId();
    }

    protected function getUpdateLanguageCode(): ?string
    {
        return $this->userProvider->getTelegramUserByUpdate($this->telegram->getUpdate() ?? $this->update)?->getLanguageCode();
    }

    protected function getUpdateMessengerUser(): ?MessengerUser
    {
        return $this->telegram->getMessengerUser() ?? $this->getMessengerUserRepository()->findOneByMessengerAndIdentifier(Messenger::telegram, (string) $this->getUpdateUserId());
    }

    protected function getConversation(): ?TelegramConversation
    {
        return $this->conversation ?? $this->getTelegramConversation();
    }

    protected function command(string $command): static
    {
        $this->update = $this->getTelegramMessageUpdateFixture($command);
        $this->telegram->setUpdate(null);
        $this->telegram->setMessengerUser(null);
        $this->conversation = null;

        return $this;
    }

    protected function conversation(string $class = null, TelegramConversationState $state = null): static
    {
        if ($class !== null || $state !== null) {
            $this->conversation = $this->getTelegramConversation();

            if ($class !== null) {
                $this->conversation->setClass($class);
            }

            if ($state !== null) {
                $this->conversation->setState($this->getSerializer()->normalize($state));
            }
        }

        return $this;
    }

    protected function expectsReplyCalls(string ...$expectedReplyCalls): static
    {
        if ($count = count($expectedReplyCalls)) {
            $this
                ->getTelegramMessageSenderMock()
                ->expects($this->exactly($count))
                ->method('sendTelegramMessage')
                ->withConsecutive(...array_map(
                    fn ($expectedReplyCall) => [
                        $this->telegram,
                        $this->getUpdateChatId(),
                        ...is_array($expectedReplyCall) ? $expectedReplyCall : [$expectedReplyCall],
                    ],
                    $expectedReplyCalls
                ))
                ->willReturn(...array_fill(0, $count, Request::emptyResponse()))
            ;
        }

        return $this;
    }

    protected function invoke(): static
    {
        $this->handleTelegramUpdate($this->telegram, $this->update);

        return $this;
    }

    protected function expectsState(TelegramConversationState $expectedState = null): static
    {
        if ($expectedState !== null) {
            $this->assertTelegramCommandState($expectedState);
        }

        return $this;
    }

    protected function type(
        string $command,
        TelegramConversationState $state = null,
        TelegramConversationState $expectedState = null,
        array $expectedReplyCalls = [],
        string $conversationClass = null
    ): static
    {
        return $this
            ->command($command)
            ->conversation($conversationClass, $state)
            ->expectsReplyCalls(...$expectedReplyCalls)
            ->invoke()
            ->expectsState($expectedState)
        ;
    }

    protected function typeCancel(
        TelegramConversationState $state,
        TelegramConversationState $expectedState,
        array $shouldSeeReply = [],
        array $shouldSeeKeyboard = [],
        string $conversationClass = null
    ): void
    {
        $command = $this->tg->trans('keyboard.cancel');

        $this
            ->type($command, $state, conversationClass: $conversationClass)
            ->shouldSeeReply(...$shouldSeeReply)
            ->shouldSeeKeyboard(...$shouldSeeKeyboard)
            ->shouldSeeChooseAction()
        ;

        $this->assertTelegramCommandState($expectedState);
        $this->assertEquals(false, $this->getConversation()->active());
    }

    protected function typeConfirm(
        TelegramConversationState $state,
        array $shouldSeeReply = [],
        array $shouldSeeKeyboard = [],
        string $conversationClass = null
    ): void
    {
        $command = $this->tg->trans('keyboard.confirm');

        $this
            ->type($command, $state, conversationClass: $conversationClass)
            ->shouldSeeReply(...$shouldSeeReply)
            ->shouldSeeKeyboard(...$shouldSeeKeyboard)
            ->shouldSeeChooseAction()
        ;

        $this->assertEquals(false, $this->getConversation()->active());
    }

    protected function shouldSeeReply(string ...$expectedReplies): static
    {
        /** @var string[] $actualReplies */
        /** @var string[] $expectedReplies */
        $actualReplies = array_map(
            fn (array $call) => $call[2],
            $this->getTelegramMessageSender()->getCalls()
        );

        // #1
//        $this->assertArraySubset($expectedReplies, $actualReplies);

        // #2
//        $this->assertCount(count($expectedReplies), $actualReplies);
//
//        foreach ($expectedReplies as $index => $expectedReply) {
//            $this->assertStringContainsString($expectedReply, $actualReplies[$index]);
//        }

        // #3
        // todo: check order
        foreach ($expectedReplies as $expectedReply) {
            $contains = false;
            foreach ($actualReplies as $actualReply) {
                if (str_contains($actualReply, $expectedReply)) {
                    $contains = true;
                    break;
                }
            }
            if ($contains) {
                $this->assertTrue(true);
            } else {
                $this->assertTrue(false, sprintf('"%s" was not found in [%s]', $expectedReply, '"' . join('", "', $actualReplies) . '"'));
            }
        }

        return $this;
    }

    protected function shouldSeeKeyboard(...$expectedButtons): static
    {
        /** @var Keyboard[] $actualKeyboards */
        /** @var KeyboardButton[]|string[] $expectedButtons */
        $actualKeyboards = array_map(
            fn (array $call) => $call[3],
            $this->getTelegramMessageSender()->getCalls()
        );
        $actualKeyboards = array_values(array_filter($actualKeyboards));

        $actualButtons = [];

        foreach ($actualKeyboards as $actualKeyboard) {
            foreach ($actualKeyboard->getRawData()['keyboard'] as $row) {
                foreach ($row as $button) {
                    /** @var KeyboardButton $button */
                    $actualButtons[] = $button->getText();
                }
            }
        }

        $expectedButtons = array_map(
            fn ($button) => is_string($button) ? $button : $button->getText(),
            $expectedButtons
        );

//        if (count($expectedButtons) > 0) {
//            $this->assertNotEmpty($actualButtons);
//        }
//
//        $this->assertArraySubset($expectedButtons, $actualButtons);
//
//        foreach ($expectedButtons as $expectedButton) {
//            $this->assertContains($expectedButton, $actualButtons);
//        }

        // todo: check order
        foreach ($expectedButtons as $expectedButton) {
            $contains = false;
            foreach ($actualButtons as $actualButton) {
                if (str_contains($actualButton, $expectedButton)) {
                    $contains = true;
                    break;
                }
            }
            if ($contains) {
                $this->assertTrue(true);
            } else {
                $this->assertTrue(false, sprintf('"%s" was not found in [%s]', $expectedButton, '"' . join('", "', $actualButtons) . '"'));
            }
        }
        return $this;
    }

    protected function shouldSeeChooseAction(): static
    {
        return $this
            ->shouldSeeReply(
                'query.action',
            )
            ->shouldSeeKeyboard(
                'command.create',
                'command.search',
                'command.lookup',
                'keyboard.more',
            )
        ;
    }

    protected function shouldSeeExtendedChooseAction(): static
    {
        return $this
            ->shouldSeeChooseAction()
            ->shouldSeeKeyboard(
                'keyboard.less',
            )
        ;
    }

    protected function getTelegramConversation(): TelegramConversation
    {
        return $this->getTelegramConversationRepository()->findOneByMessengerUserAndChatId(
            $this->getUpdateMessengerUser(),
            $this->getUpdateChatId(),
            $this->telegram->getBot()
        );
    }

    protected function assertTelegramCommandState(TelegramConversationState $expectedState): static
    {
        $state = $this->getSerializer()->denormalize($this->getConversation()->getState(), get_class($expectedState));

        $this->assertEquals($expectedState, $state);

        return $this;
    }

    /**
     * @param SearchTermTransfer $searchTerm
     * @param SearchTermType|null $expectedType
     * @return SearchTermTransfer
     */
    protected function addSearchTermPossibleTypes(SearchTermTransfer $searchTerm, SearchTermType $expectedType = null): SearchTermTransfer
    {
        $this->getSearchTermParser()->parseWithGuessType($searchTerm);

        if ($expectedType === null) {
            return $searchTerm;
        }

        $possibleTypes = $searchTerm->getPossibleTypes() ?? [];

        if (!in_array($expectedType, $possibleTypes, true)) {
            $searchTerm->addPossibleType($expectedType);
        }

        return $searchTerm;
    }

    protected function getMessengerProfileUrlSearchTerm(MessengerUserTransfer $messengerUser): SearchTermTransfer
    {
        return (new SearchTermTransfer($url = $this->getMessengerUserProfileUrl($messengerUser)))
            ->setMessenger($messengerUser->getMessenger())
            ->setMessengerProfileUrl($url)
            ->setMessengerUsername($messengerUser->getUsername())//            ->setMessengerUser($messengerUser->getMessenger() === Messenger::unknown ? null : $messengerUser)
            ;
    }

    protected function getMessengerUsernameSearchTerm(MessengerUserTransfer $messengerUser): SearchTermTransfer
    {
        return (new SearchTermTransfer($messengerUser->getUsername()))
            ->setMessenger($messengerUser->getMessenger())
            ->setMessengerProfileUrl($messengerUser->getMessenger() === Messenger::unknown ? null : $this->getMessengerUserProfileUrl($messengerUser))
            ->setMessengerUsername($messengerUser->getUsername())//            ->setMessengerUser($messengerUser->getMessenger() === Messenger::unknown ? null : $messengerUser)
            ;
    }
}