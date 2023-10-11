<?php

declare(strict_types=1);

namespace App\Tests\Functional\Telegram\Bot;

use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBotConversation;
use App\Entity\Telegram\TelegramBotConversationState;
use App\Entity\Telegram\TelegramBotPaymentMethod;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Messenger\Messenger;
use App\Enum\Telegram\TelegramBotPaymentMethodName;
use App\Service\Telegram\Bot\TelegramBot;
use App\Service\Telegram\Bot\TelegramBotAwareHelper;
use App\Service\Telegram\Bot\TelegramBotChatProvider;
use App\Service\Telegram\Bot\TelegramBotKeyboardFactory;
use App\Service\Telegram\Bot\TelegramBotUserProvider;
use App\Tests\DatabaseTestCase;
use App\Tests\Fixtures;
use App\Tests\Traits\EntityManagerProviderTrait;
use App\Tests\Traits\Feedback\SearchTermParserProviderTrait;
use App\Tests\Traits\Messenger\MessengerUserProfileUrlProviderTrait;
use App\Tests\Traits\Messenger\MessengerUserRepositoryProviderTrait;
use App\Tests\Traits\SerializerProviderTrait;
use App\Tests\Traits\Telegram\Bot\TelegramBotAwareHelperProviderTrait;
use App\Tests\Traits\Telegram\Bot\TelegramBotChatProviderTrait;
use App\Tests\Traits\Telegram\Bot\TelegramBotConversationRepositoryProviderTrait;
use App\Tests\Traits\Telegram\Bot\TelegramBotKeyboardFactoryProviderTrait;
use App\Tests\Traits\Telegram\Bot\TelegramBotMessageSenderProviderTrait;
use App\Tests\Traits\Telegram\Bot\TelegramBotRegistryProviderTrait;
use App\Tests\Traits\Telegram\Bot\TelegramBotRepositoryProviderTrait;
use App\Tests\Traits\Telegram\Bot\TelegramBotUpdateFixtureProviderTrait;
use App\Tests\Traits\Telegram\Bot\TelegramBotUpdateHandlerTrait;
use App\Tests\Traits\Telegram\Bot\TelegramBotUserProviderTrait;
use App\Tests\Traits\TranslatorProviderTrait;
use App\Transfer\Feedback\SearchTermTransfer;
use App\Transfer\Messenger\MessengerUserTransfer;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Entities\Update;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class TelegramBotCommandFunctionalTestCase extends DatabaseTestCase
{
    use TelegramBotUpdateHandlerTrait;
    use TelegramBotUpdateFixtureProviderTrait;
    use TelegramBotRegistryProviderTrait;
    use TelegramBotMessageSenderProviderTrait;
    use TranslatorProviderTrait;
    use TelegramBotKeyboardFactoryProviderTrait;
    use TelegramBotConversationRepositoryProviderTrait;
    use EntityManagerProviderTrait;
    use MessengerUserRepositoryProviderTrait;
    use MessengerUserProfileUrlProviderTrait;
    use ArraySubsetAsserts;
    use SearchTermParserProviderTrait;
    use SerializerProviderTrait;
    use TelegramBotAwareHelperProviderTrait;
    use TelegramBotUserProviderTrait;
    use TelegramBotChatProviderTrait;
    use TelegramBotRepositoryProviderTrait;

    protected ?TelegramBot $bot;
    protected ?TelegramBotAwareHelper $tg;
    protected ?Update $update;
    protected TranslatorInterface $translator;
    protected TelegramBotKeyboardFactory $keyboardFactory;
    protected TelegramBotUserProvider $userProvider;
    protected TelegramBotChatProvider $chatProvider;

    public function setUp(): void
    {
        parent::setUp();

        $this->bot = null;
        $this->tg = null;
        $this->update = null;
        $this->translator = $this->getTranslator();
        $this->keyboardFactory = $this->getTelegramBotKeyboardFactory();
        $this->userProvider = $this->getTelegramBotUserProvider();
        $this->chatProvider = $this->getTelegramBotChatProvider();
    }

    protected function getBot(): TelegramBot
    {
        if ($this->bot === null) {
            $bot = $this->getTelegramBotRepository()->findAnyOneByUsername(Fixtures::BOT_USERNAME_1);
            $this->bot = $this->getTelegramBotRegistry()->getTelegramBot($bot);
        }

        return $this->bot;
    }

    protected function getTg(): TelegramBotAwareHelper
    {
        if ($this->tg === null) {
            $this->tg = $this->getTelegramBotAwareHelper()->withTelegramBot($this->getBot());
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
        return $this->userProvider->getTelegramUserByUpdate($this->getBot()->getUpdate() ?? $this->getUpdate())?->getId();
    }

    protected function getUpdateChatId(): ?int
    {
        return $this->chatProvider->getTelegramChatByUpdate($this->getBot()->getUpdate() ?? $this->getUpdate())?->getId();
    }

    protected function getUpdateMessengerUser(): ?MessengerUser
    {
        return $this->getBot()->getMessengerUser() ?? $this->getMessengerUserRepository()->findOneByMessengerAndIdentifier(Messenger::telegram, (string) $this->getUpdateUserId());
    }

    protected function createConversation(string $class, TelegramBotConversationState $state): TelegramBotConversation
    {
        $messengerUserId = $this->getUpdateMessengerUser()->getId();
        $chatId = $this->getUpdateChatId();
        $botId = $this->getBot()->getEntity()->getId();

        $conversation = new TelegramBotConversation(
            $messengerUserId . '-' . $chatId . '-' . $botId,
            $messengerUserId,
            $chatId,
            $botId,
            $class,
            $this->getSerializer()->normalize($state)
        );
        $this->getEntityManager()->persist($conversation);

        return $conversation;
    }

    protected function createPaymentMethod(TelegramBotPaymentMethodName $name): TelegramBotPaymentMethod
    {
        $paymentMethod = new TelegramBotPaymentMethod(
            $this->getBot()->getEntity(),
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
        $this->bot = null;
        $this->tg = null;
        $this->update = $this->getTelegramMessageUpdateFixture($command);
        $this->getBot()->setUpdate(null);
        $this->getBot()->setMessengerUser(null);
        $this->handleTelegramBotUpdate($this->getBot()->getEntity(), $this->getUpdate());

        return $this;
    }

    protected function shouldSeeActiveConversation(string $expectedClass, TelegramBotConversationState $expectedState): static
    {
        return $this->shouldSeeConversation($expectedClass, $expectedState, true);
    }

    protected function shouldNotSeeActiveConversation(string $expectedClass = null, TelegramBotConversationState $expectedState = null): static
    {
        return $this->shouldSeeConversation($expectedClass, $expectedState, false);
    }

    protected function shouldSeeConversation(?string $expectedClass, ?TelegramBotConversationState $expectedState, bool $active): static
    {
        $messengerUserId = $this->getUpdateMessengerUser()->getId();
        $chatId = $this->getUpdateChatId();
        $botId = $this->getBot()->getEntity()->getId();
        $conversation = $this->getTelegramBotConversationRepository()->findOneByHash($messengerUserId . '-' . $chatId . '-' . $botId);

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
            $this->getTelegramBotMessageSender()->getCalls()
        );

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
            $this->getTelegramBotMessageSender()->getCalls()
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

    protected function assertConversationActive(?TelegramBotConversation $conversation): void
    {
        $this->assertNotNull($conversation);
        $this->assertNotNull($this->getTelegramBotConversationRepository()->findOneByHash($conversation->getHash()));
    }

    protected function assertConversationInactive(?TelegramBotConversation $conversation): void
    {
        if ($conversation === null) {
            $this->assertNull(null);
        } else {
            $this->assertNull($this->getTelegramBotConversationRepository()->findOneByHash($conversation->getHash()));
        }
    }
}