<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Telegram\TelegramConversation;
use App\Entity\Telegram\TelegramConversationState;
use App\Enum\Telegram\TelegramConversationStatus;
use App\Repository\Telegram\TelegramConversationRepository;
use App\Service\Telegram\Conversation\TelegramConversationInterface;
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

        $conversation = $this->conversationRepository->findOneByMessengerUserAndChatId($messengerUser, $chatId);

        if ($conversation === null) {
            return null;
        }

        if ($conversation->getStatus() === TelegramConversationStatus::ACTIVE) {
            return $conversation;
        }

        return null;
    }

    public function startTelegramConversation(Telegram $telegram, string $conversationClass): void
    {
        $conversation = $this->createTelegramConversation($telegram, $conversationClass);

        $dbConversation = new TelegramConversation(
            $telegram->getMessengerUser(),
            $telegram->getUpdate()->getMessage()->getChat()->getId(),
            // conversations from container have container class
            get_parent_class($conversation),
            TelegramConversationStatus::ACTIVE
        );

        $this->entityManager->persist($dbConversation);

        $conversation->invokeConversation($telegram, $dbConversation);

        $dbConversation->setState($this->normalizeTelegramConversationState($conversation->getState()));
    }

    public function continueTelegramConversation(Telegram $telegram, TelegramConversation $dbConversation): void
    {
        $conversation = $this->createTelegramConversation($telegram, $dbConversation->getClass());

        $conversation
            ->setState($this->denormalizeTelegramConversationState($dbConversation->getState(), get_class($conversation->getState())))
            ->invokeConversation($telegram, $dbConversation)
        ;

        $dbConversation
            ->setState($this->normalizeTelegramConversationState($conversation->getState()))
            ->setUpdatedAt(new DateTimeImmutable())
        ;
    }

    public function denormalizeTelegramConversationState(array $state, string $class): TelegramConversationState
    {
        return $this->conversationStateDenormalizer->denormalize($state, $class);
    }

    public function normalizeTelegramConversationState(TelegramConversationState $state): array
    {
        $normalizedState = $this->conversationStateNormalizer->normalize($state);
        return $this->arrayNullFilter->filterNulls($normalizedState);
    }

    public function stopTelegramConversations(Telegram $telegram): void
    {
        $conversations = $this->conversationRepository->getActiveByMessengerUser($telegram->getMessengerUser());

        foreach ($conversations as $conversation) {
            $this->stopTelegramConversation($conversation);
        }
    }

    public function stopTelegramConversation(TelegramConversation $conversation): void
    {
        $conversation
            ->setStatus(TelegramConversationStatus::STOPPED)
            ->setUpdatedAt(new DateTimeImmutable())
        ;

        $this->entityManager->remove($conversation);
    }

    public function cancelTelegramConversation(TelegramConversation $conversation): void
    {
        $conversation
            ->setStatus(TelegramConversationStatus::CANCELLED)
            ->setUpdatedAt(new DateTimeImmutable())
        ;

        $this->entityManager->remove($conversation);
    }

    public function finishTelegramConversation(TelegramConversation $conversation): void
    {
        $conversation
            ->setStatus(TelegramConversationStatus::FINISHED)
            ->setUpdatedAt(new DateTimeImmutable())
        ;

        $this->entityManager->remove($conversation);
    }

    public function createTelegramConversation(Telegram $telegram, string $conversationClass): TelegramConversationInterface
    {
        $channel = $this->channelRegistry->getTelegramChannel($telegram->getName());
        // todo: throw not found exception
        return $channel->getTelegramConversationFactory()->createTelegramConversation($conversationClass);
    }
}