<?php

declare(strict_types=1);

namespace App\Service\Telegram\Conversation;

use App\Entity\Telegram\TelegramConversation;
use App\Entity\Telegram\TelegramConversationState;
use App\Entity\Telegram\TelegramStoppedConversation;
use App\Repository\Telegram\TelegramConversationRepository;
use App\Service\Telegram\Channel\TelegramChannelRegistry;
use App\Service\Telegram\Telegram;
use App\Service\Telegram\TelegramAwareHelper;
use App\Service\Telegram\TelegramChatProvider;
use App\Service\Util\Array\ArrayNullFilter;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @todo: use cache to store conversations (one conversation per chat)
 */
class TelegramConversationManager
{
    public function __construct(
        private readonly TelegramAwareHelper $awareHelper,
        private readonly TelegramConversationRepository $repository,
        private readonly EntityManagerInterface $entityManager,
        private readonly NormalizerInterface $conversationStateNormalizer,
        private readonly DenormalizerInterface $conversationStateDenormalizer,
        private readonly ArrayNullFilter $arrayNullFilter,
        private readonly TelegramChannelRegistry $channelRegistry,
        private readonly TelegramChatProvider $chatProvider,
    )
    {
    }

    public function getCurrentTelegramConversation(Telegram $telegram): ?TelegramConversation
    {
        $messengerUser = $telegram->getMessengerUser();
        $chatId = $this->chatProvider->getTelegramChatByUpdate($telegram->getUpdate())?->getId();

        if ($messengerUser === null || $chatId === null) {
            return null;
        }

        $hash = $this->createTelegramConversationHash($messengerUser->getId(), $chatId, $telegram->getBot()->getId());

        return $this->repository->findOneByHash($hash);
    }

    public function startTelegramConversation(Telegram $telegram, string $class): void
    {
        $entity = $this->createTelegramConversation($telegram, $class);

        $this->executeConversation($telegram, $entity, 'invoke');
    }

    public function createTelegramConversationHash(int $messengerUserId, int $chatId, int $botId): string
    {
        return $messengerUserId . '-' . $chatId . '-' . $botId;
    }

    public function createTelegramConversation(
        Telegram $telegram,
        string $class,
        TelegramConversationState $state = null
    ): TelegramConversation
    {
        $messengerUserId = $telegram->getMessengerUser()->getId();
        $chatId = $this->chatProvider->getTelegramChatByUpdate($telegram->getUpdate())?->getId();
        $botId = $telegram->getBot()->getId();
        $hash = $this->createTelegramConversationHash($messengerUserId, $chatId, $botId);

        $entity = new TelegramConversation($hash, $messengerUserId, $chatId, $botId, $class, $state === null ? null : $this->normalizeState($state));
        $this->entityManager->persist($entity);

        return $entity;
    }

    public function executeTelegramConversation(
        Telegram $telegram,
        string $class,
        TelegramConversationState $state,
        string $method
    ): void
    {
        $entity = $this->createTelegramConversation($telegram, $class, $state);

        $this->executeConversation($telegram, $entity, $method);
    }

    public function continueTelegramConversation(Telegram $telegram, TelegramConversation $entity): void
    {
        $this->executeConversation($telegram, $entity, 'invoke');
    }

    public function denormalizeState(?array $state, string $class): TelegramConversationState
    {
        if ($state === null) {
            return new $class();
        }

        return $this->conversationStateDenormalizer->denormalize($state, $class);
    }

    public function normalizeState(TelegramConversationState $state): array
    {
        $normalized = $this->conversationStateNormalizer->normalize($state);

        return $this->arrayNullFilter->filterNulls($normalized);
    }

    public function stopCurrentTelegramConversation(Telegram $telegram): void
    {
        $conversation = $this->getCurrentTelegramConversation($telegram);

        if ($conversation === null) {
            return;
        }

        $this->stopTelegramConversation($conversation);
    }

    public function stopTelegramConversation(TelegramConversation $entity): void
    {
        $stopped = new TelegramStoppedConversation(
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
        Telegram $telegram,
        TelegramConversation $entity,
        string $method
    ): TelegramConversationInterface
    {
        $channel = $this->channelRegistry->getTelegramChannel($telegram->getBot()->getGroup());
        // todo: throw not found exception
        $conversation = $channel->getTelegramConversationFactory()->createTelegramConversation($entity->getClass());

        $state = $this->denormalizeState($entity->getState(), get_class($conversation->getState()));
        $conversation->setState($state);

        $tg = $this->awareHelper->withTelegram($telegram);

        $conversation->$method($tg, $entity);

        $state = $this->normalizeState($conversation->getState());

        $entity->setState($state);
        $entity->setUpdatedAt(new DateTimeImmutable());

        return $conversation;
    }
}