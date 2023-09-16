<?php

declare(strict_types=1);

namespace App\Service\Telegram\Conversation;

use App\Entity\Telegram\TelegramConversation;
use App\Entity\Telegram\TelegramConversationState;
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
        private readonly TelegramConversationRepository $conversationRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly NormalizerInterface $conversationStateNormalizer,
        private readonly DenormalizerInterface $conversationStateDenormalizer,
        private readonly ArrayNullFilter $arrayNullFilter,
        private readonly TelegramChannelRegistry $channelRegistry,
        private readonly TelegramChatProvider $chatProvider,
    )
    {
    }

    public function getLastTelegramConversation(Telegram $telegram): ?TelegramConversation
    {
        $messengerUser = $telegram->getMessengerUser();
        $chatId = $this->chatProvider->getTelegramChatByUpdate($telegram->getUpdate())?->getId();

        if ($messengerUser === null || $chatId === null) {
            return null;
        }

        $conversation = $this->conversationRepository->findOneByMessengerUserAndChatId($messengerUser, $chatId, $telegram->getBot());

        if ($conversation === null) {
            return null;
        }

        if ($conversation->active()) {
            return $conversation;
        }

        return null;
    }

    public function startTelegramConversation(Telegram $telegram, string $class): void
    {
        $entity = $this->createTelegramConversation($telegram, $class);

        $this->executeConversation($telegram, $entity, 'invoke');
    }

    public function createTelegramConversation(
        Telegram $telegram,
        string $class,
        TelegramConversationState $state = null
    ): TelegramConversation
    {
        $chatId = $this->chatProvider->getTelegramChatByUpdate($telegram->getUpdate())?->getId();

        $entity = new TelegramConversation(
            $telegram->getMessengerUser(),
            $chatId,
            $class,
            $telegram->getBot(),
            true,
            $state === null ? null : $this->normalizeState($state)
        );
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

    public function stopTelegramConversations(Telegram $telegram): void
    {
        $conversations = $this->conversationRepository->getActiveByMessengerUser($telegram->getMessengerUser(), $telegram->getBot());

        foreach ($conversations as $conversation) {
            $this->stopTelegramConversation($conversation);
        }
    }

    public function stopTelegramConversation(TelegramConversation $entity): void
    {
        $entity->setActive(false);
        $entity->setUpdatedAt(new DateTimeImmutable());

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