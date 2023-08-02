<?php


declare(strict_types=1);

namespace App\Tests\Traits\Telegram;

use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramConversation;
use App\Entity\Telegram\TelegramConversationState;
use App\Entity\User\User;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Messenger\Messenger;
use App\Enum\Telegram\TelegramName;
use App\Enum\Telegram\TelegramView;
use App\Object\Feedback\SearchTermTransfer;
use App\Object\Messenger\MessengerUserTransfer;
use App\Service\Telegram\Telegram;
use App\Service\Telegram\TelegramAwareHelper;
use App\Service\Telegram\TelegramChatProvider;
use App\Service\Telegram\TelegramKeyboardFactory;
use App\Service\Telegram\TelegramUserProvider;
use App\Tests\Traits\EntityManagerProviderTrait;
use App\Tests\Traits\Feedback\SearchTermParserProviderTrait;
use App\Tests\Traits\Instagram\InstagramMessengerUserFinderMockProviderTrait;
use App\Tests\Traits\Instagram\InstagramMessengerUserProviderTrait;
use App\Tests\Traits\Messenger\MessengerUserProfileUrlProviderTrait;
use App\Tests\Traits\Messenger\MessengerUserRepositoryProviderTrait;
use App\Tests\Traits\SerializerProviderTrait;
use App\Tests\Traits\TranslatorProviderTrait;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

trait TelegramCommandFunctionalTrait
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
    use TelegramTemplateRendererProviderTrait;
    use MessengerUserProfileUrlProviderTrait;
    use ArraySubsetAsserts;
    use SearchTermParserProviderTrait;
    use SerializerProviderTrait;
    use TelegramAwareHelperProviderTrait;
    use InstagramMessengerUserProviderTrait;
    use TelegramUserProviderTrait;
    use TelegramChatProviderTrait;

    protected Telegram $telegram;
    protected TelegramAwareHelper $tg;
    protected ?Update $update;
    protected ?TelegramConversation $conversation;
    protected TranslatorInterface $translator;
    protected TelegramKeyboardFactory $keyboardFactory;
    protected TelegramUserProvider $userProvider;
    protected TelegramChatProvider $chatProvider;

    protected function telegramCommandUp(): void
    {
        $this->telegram = $this->getTelegramRegistry()->getTelegram(TelegramName::feedbacks);
        $this->tg = $this->getTelegramAwareHelper()->withTelegram($this->telegram);
        $this->update = $this->getTelegramMessageUpdateFixture('any');
        $this->conversation = null;
        $this->translator = $this->getTranslator();
        $this->keyboardFactory = $this->getTelegramKeyboardFactory();
        $this->userProvider = $this->getTelegramUserProvider();
        $this->chatProvider = $this->getTelegramChatProvider();
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
            $this->bootFixtures([
                User::class,
                MessengerUser::class,
                TelegramConversation::class,
            ]);

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

    protected function expectsReplyCalls(array $expectedReplyCalls = []): static
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
            ->expectsReplyCalls($expectedReplyCalls)
            ->invoke()
            ->expectsState($expectedState)
        ;
    }

    protected function typeCancel(
        TelegramConversationState $state,
        TelegramConversationState $expectedState,
        array $shouldSeeReply,
        array $shouldSeeKeyboard,
        string $conversationClass = null
    ): void
    {
        $command = $this->trans('keyboard.cancel');

        $this->type($command, $state, conversationClass: $conversationClass)
            ->shouldSeeReply(...$shouldSeeReply)
            ->shouldSeeKeyboard(...$shouldSeeKeyboard)
        ;

        $this->assertTelegramCommandState($expectedState);
        $this->assertEquals(false, $this->getConversation()->isActive());
    }

    protected function typeConfirm(
        TelegramConversationState $state,
        array $shouldSeeReply,
        array $shouldSeeKeyboard,
        string $conversationClass = null
    ): void
    {
        $command = $this->trans('keyboard.confirm');

        $this->type($command, $state, conversationClass: $conversationClass)
            ->shouldSeeReply(...$shouldSeeReply)
            ->shouldSeeKeyboard(...$shouldSeeKeyboard)
        ;

        $this->assertEquals(false, $this->getConversation()->isActive());
    }

    protected function shouldSeeReply(...$expectedReplies): static
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
        /** @var KeyboardButton[] $expectedButtons */
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
            fn (KeyboardButton $button) => $button->getText(),
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

    protected function getTelegramConversation(): TelegramConversation
    {
        return $this->getTelegramConversationRepository()->findOneByMessengerUserAndChatId(
            $this->getUpdateMessengerUser(),
            $this->getUpdateChatId()
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

    protected function trans(string $id, array $parameters = []): string
    {
        return $this->translator->trans($id, $parameters, locale: $this->getUpdateLanguageCode());
    }

    protected function renderView(string|TelegramView $template, array $context): string
    {
        return $this->getTelegramTemplateRenderer()->renderTelegramTemplate($template, $context, $this->getUpdateLanguageCode());
    }
}
