<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot\Conversation;

use App\Entity\Telegram\TelegramBotConversation;
use App\Entity\Telegram\TelegramBotConversationState;
use App\Entity\Telegram\TelegramBotStoppedConversation;
use App\Repository\Telegram\Bot\TelegramBotConversationRepository;
use App\Service\Telegram\Bot\Group\TelegramBotGroupRegistry;
use App\Service\Telegram\Bot\TelegramBot;
use App\Service\Telegram\Bot\TelegramBotAwareHelper;
use App\Service\Telegram\Bot\TelegramBotChatProvider;
use App\Service\Util\Array\ArrayNullFilter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @todo: use cache to store conversations (one conversation per chat)
 */
class TelegramBotConversationManager
{
    public function __construct(
        private readonly TelegramBotAwareHelper $awareHelper,
        private readonly TelegramBotConversationRepository $repository,
        private readonly EntityManagerInterface $entityManager,
        private readonly NormalizerInterface $conversationStateNormalizer,
        private readonly DenormalizerInterface $conversationStateDenormalizer,
        private readonly ArrayNullFilter $arrayNullFilter,
        private readonly TelegramBotGroupRegistry $groupRegistry,
        private readonly TelegramBotChatProvider $chatProvider,
    )
    {
    }

    public function getCurrentTelegramConversation(TelegramBot $bot): ?TelegramBotConversation
    {
        $messengerUser = $bot->getMessengerUser();
        $chatId = $this->chatProvider->getTelegramChatByUpdate($bot->getUpdate())?->getId();

        if ($messengerUser === null || $chatId === null) {
            return null;
        }

        $hash = $this->createTelegramConversationHash($messengerUser->getId(), $chatId, $bot->getEntity()->getId());

        return $this->repository->findOneByHash($hash);
    }

    public function startTelegramConversation(TelegramBot $bot, string $class): void
    {
        $entity = $this->createTelegramConversation($bot, $class);

        $this->executeConversation($bot, $entity, 'invoke');
    }

    public function createTelegramConversationHash(string $messengerUserId, int $chatId, int $botId): string
    {
        return $messengerUserId . '-' . $chatId . '-' . $botId;
    }

    public function createTelegramConversation(
        TelegramBot $bot,
        string $class,
        TelegramBotConversationState $state = null
    ): TelegramBotConversation
    {
        $messengerUserId = $bot->getMessengerUser()->getId();
        $chatId = $this->chatProvider->getTelegramChatByUpdate($bot->getUpdate())?->getId();
        $botId = $bot->getEntity()->getId();
        $hash = $this->createTelegramConversationHash($messengerUserId, $chatId, $botId);

        $entity = new TelegramBotConversation(
            $hash,
            $messengerUserId,
            $chatId,
            $botId,
            $class,
            $state === null ? null : $this->normalizeState($state)
        );
        $this->entityManager->persist($entity);

        return $entity;
    }

    public function executeTelegramConversation(
        TelegramBot $bot,
        string $class,
        TelegramBotConversationState $state,
        string $method
    ): void
    {
        $entity = $this->createTelegramConversation($bot, $class, $state);

        $this->executeConversation($bot, $entity, $method);
    }

    public function continueTelegramConversation(TelegramBot $bot, TelegramBotConversation $entity): void
    {
        $this->executeConversation($bot, $entity, 'invoke');
    }

    public function denormalizeState(?array $state, string $class): TelegramBotConversationState
    {
        if ($state === null) {
            return new $class();
        }

        return $this->conversationStateDenormalizer->denormalize($state, $class);
    }

    public function normalizeState(TelegramBotConversationState $state): array
    {
        $normalized = $this->conversationStateNormalizer->normalize($state);

        return $this->arrayNullFilter->filterNulls($normalized);
    }

    public function stopCurrentTelegramConversation(TelegramBot $bot): void
    {
        $conversation = $this->getCurrentTelegramConversation($bot);

        if ($conversation === null) {
            return;
        }

        $this->stopTelegramConversation($conversation);
    }

    public function stopTelegramConversation(TelegramBotConversation $entity): void
    {
        $stopped = new TelegramBotStoppedConversation(
            $entity->getMessengerUserId(),
            $entity->getChatId(),
            $entity->getBotId(),
            $entity->getClass(),
            $entity->getState(),
            $entity->getCreatedAt()
        );
        $this->entityManager->persist($stopped);

        $this->entityManager->remove($entity);
    }

    public function executeConversation(
        TelegramBot $bot,
        TelegramBotConversation $entity,
        string $method
    ): TelegramBotConversationInterface
    {
        $group = $this->groupRegistry->getTelegramGroup($bot->getEntity()->getGroup());
        // todo: throw not found exception
        $conversation = $group->getTelegramConversationFactory()->createTelegramConversation($entity->getClass());

        $state = $this->denormalizeState($entity->getState(), get_class($conversation->getState()));
        $conversation->setState($state);

        $tg = $this->awareHelper->withTelegramBot($bot);

        $conversation->$method($tg, $entity);

        $state = $this->normalizeState($conversation->getState());

        $entity->setState($state);

        return $conversation;
    }
}