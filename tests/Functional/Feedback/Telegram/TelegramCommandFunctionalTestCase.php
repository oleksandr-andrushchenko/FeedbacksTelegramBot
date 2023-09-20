<?php

declare(strict_types=1);

namespace App\Tests\Functional\Feedback\Telegram;

use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramConversation;
use App\Entity\Telegram\TelegramConversationState;
use App\Entity\Telegram\TelegramPaymentMethod;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Messenger\Messenger;
use App\Enum\Telegram\TelegramPaymentMethodName;
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
use App\Tests\Traits\Telegram\TelegramStoppedConversationRepositoryProviderTrait;
use App\Tests\Traits\Telegram\TelegramUpdateFixtureProviderTrait;
use App\Tests\Traits\Telegram\TelegramUpdateHandlerTrait;
use App\Tests\Traits\Telegram\TelegramUserProviderTrait;
use App\Tests\Traits\TranslatorProviderTrait;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Entities\Update;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

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
    use TelegramStoppedConversationRepositoryProviderTrait;

    protected ?Telegram $telegram;
    protected ?TelegramAwareHelper $tg;
    protected ?Update $update;
    protected TranslatorInterface $translator;
    protected TelegramKeyboardFactory $keyboardFactory;
    protected TelegramUserProvider $userProvider;
    protected TelegramChatProvider $chatProvider;

    public function setUp(): void
    {
        parent::setUp();

        $this->telegram = null;
        $this->tg = null;
        $this->update = null;
        $this->translator = $this->getTranslator();
        $this->keyboardFactory = $this->getTelegramKeyboardFactory();
        $this->userProvider = $this->getTelegramUserProvider();
        $this->chatProvider = $this->getTelegramChatProvider();
    }

    protected function getTelegram(): Telegram
    {
        if ($this->telegram === null) {
            $this->telegram = $this->getTelegramRegistry()->getTelegram(Fixtures::BOT_USERNAME_1);
        }

        return $this->telegram;
    }

    protected function getTg(): TelegramAwareHelper
    {
        if ($this->tg === null) {
            $this->tg = $this->getTelegramAwareHelper()->withTelegram($this->getTelegram());
        }

        return $this->tg;
    }

    protected function getUpdate(): Update
    {
        if ($this->update === null) {
            $this->update = $this->getTelegramMessageUpdateFixture('any');
        }

        return $this->update;
    }

    public static function getContainer(): ContainerInterface
    {
        return parent::getContainer();
    }

    protected function getUpdateUserId(): ?int
    {
        return $this->userProvider->getTelegramUserByUpdate($this->getTelegram()->getUpdate() ?? $this->getUpdate())?->getId();
    }

    protected function getUpdateChatId(): ?int
    {
        return $this->chatProvider->getTelegramChatByUpdate($this->getTelegram()->getUpdate() ?? $this->getUpdate())?->getId();
    }

    protected function getUpdateMessengerUser(): ?MessengerUser
    {
        return $this->getTelegram()->getMessengerUser() ?? $this->getMessengerUserRepository()->findOneByMessengerAndIdentifier(Messenger::telegram, (string) $this->getUpdateUserId());
    }

    protected function createConversation(string $class, TelegramConversationState $state): TelegramConversation
    {
        $messengerUserId = $this->getUpdateMessengerUser()->getId();
        $chatId = $this->getUpdateChatId();
        $botId = $this->getTelegram()->getBot()->getId();

        $conversation = new TelegramConversation(
            $messengerUserId . '-' . $chatId . '-' . $botId,
            $messengerUserId,
            $chatId,
            $botId,
            $class,
            $this->getSerializer()->normalize($state)
        );
        $this->getEntityManager()->persist($conversation);
//        $this->getEntityManager()->flush();

        return $conversation;
    }

    protected function createPaymentMethod(TelegramPaymentMethodName $name): TelegramPaymentMethod
    {
        $paymentMethod = new TelegramPaymentMethod(
            $this->getTelegram()->getBot(),
            $name,
            'any',
            ['USD', 'EUR', 'UAH'],
        );
        $this->getEntityManager()->persist($paymentMethod);
        $this->getEntityManager()->flush();

        return $paymentMethod;
    }

    protected function type(string $command): static
    {
        $this->telegram = null;
        $this->tg = null;
        $this->update = $this->getTelegramMessageUpdateFixture($command);
        $this->getTelegram()->setUpdate(null);
        $this->getTelegram()->setMessengerUser(null);
        $this->handleTelegramUpdate($this->getTelegram(), $this->getUpdate());

        return $this;
    }

    protected function shouldSeeActiveConversation(string $expectedClass, TelegramConversationState $expectedState): static
    {
        return $this->shouldSeeConversation($expectedClass, $expectedState, true);
    }

    protected function shouldNotSeeActiveConversation(string $expectedClass = null, TelegramConversationState $expectedState = null): static
    {
        return $this->shouldSeeConversation($expectedClass, $expectedState, false);
    }

    protected function shouldSeeConversation(?string $expectedClass, ?TelegramConversationState $expectedState, bool $active): static
    {
        $messengerUserId = $this->getUpdateMessengerUser()->getId();
        $chatId = $this->getUpdateChatId();
        $botId = $this->getTelegram()->getBot()->getId();
        $conversation = $this->getTelegramConversationRepository()->findOneByHash($messengerUserId . '-' . $chatId . '-' . $botId);

        if ($active) {
            $this->assertConversationActive($conversation);
        } else {
            $this->assertConversationInactive($conversation);
        }

        if ($conversation !== null) {
            if ($expectedClass !== null) {
                $this->assertEquals($expectedClass, $conversation->getClass());
            }
            if ($expectedState !== null) {
                $this->assertEquals(
                    $expectedState,
                    $this->getSerializer()->denormalize($conversation->getState(), get_class($expectedState))
                );
            }
        }

        return $this;
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

    protected function shouldSeeButtons(...$expectedButtons): static
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

    protected function shouldSeeChooseAction(
        string $text = null,
        bool $extended = false,
        bool $subscribe = true
    ): static
    {
        $buttons = [
            $this->command('create'),
            $this->command('search'),
            $this->command('lookup'),
        ];
        if ($extended) {
            $buttons = array_merge($buttons, [
                $subscribe ? $this->command('subscribe') : $this->command('subscriptions'),
                $this->command('country'),
                $this->command('locale'),
                $this->command('purge'),
                $this->command('contact'),
                $this->command('commands'),
                $this->command('limits'),
                $this->command('restart'),
                'keyboard.less',
            ]);
        } else {
            $buttons[] = 'keyboard.more';
        }

        return $this
            ->shouldSeeReply($text ?? 'query.action')
            ->shouldSeeButtons(...$buttons)
        ;
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
            ->setMessengerUsername($messengerUser->getUsername())
        ;
    }

    protected function getMessengerUsernameSearchTerm(MessengerUserTransfer $messengerUser): SearchTermTransfer
    {
        return (new SearchTermTransfer($messengerUser->getUsername()))
            ->setMessenger($messengerUser->getMessenger())
            ->setMessengerProfileUrl($messengerUser->getMessenger() === Messenger::unknown ? null : $this->getMessengerUserProfileUrl($messengerUser))
            ->setMessengerUsername($messengerUser->getUsername())
        ;
    }

    protected function confirmButton(): string
    {
        return $this->yesButton();
    }

    protected function backButton(): string
    {
        return '⬅️ keyboard.back';
    }

    protected function helpButton(): string
    {
        return '🚨 keyboard.help';
    }

    protected function cancelButton(): string
    {
        return '❌ keyboard.cancel';
    }

    protected function leaveAsButton(): string
    {
        return 'keyboard.leave_as';
    }

    protected function yesButton(): string
    {
        return '👌 keyboard.yes';
    }

    protected function noButton(): string
    {
        return 'keyboard.no';
    }

    protected function command(string $name): string
    {
        return $name . ' ' . $name;
    }

    protected function assertConversationActive(?TelegramConversation $conversation): void
    {
        $this->assertNotNull($conversation);
        $this->assertNotNull($this->getTelegramConversationRepository()->findOneByHash($conversation->getHash()));
//        $this->assertNull($this->getTelegramStoppedConversationRepository()->find($conversation->getId()));
    }

    protected function assertConversationInactive(?TelegramConversation $conversation): void
    {
        if ($conversation === null) {
            $this->assertNull(null);
        } else {
            $this->assertNull($this->getTelegramConversationRepository()->findOneByHash($conversation->getHash()));
        }
//        $this->assertNotNull($this->getTelegramStoppedConversationRepository()->find($conversation->getId()));
    }
}