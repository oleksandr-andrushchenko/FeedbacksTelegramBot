<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use Symfony\Component\DependencyInjection\ServiceLocator;

class TelegramConversationFactory
{
    public function __construct(
        private readonly ServiceLocator $conversationServiceLocator,
    )
    {
    }

    public function createTelegramConversation(string $conversationClass): TelegramConversationInterface
    {
        return $this->conversationServiceLocator->get(array_search($conversationClass, $this->getTelegramConversations()));
    }

    /**
     * @return array|string[]|TelegramConversationInterface[]
     */
    public function getTelegramConversations(): array
    {
        return $this->conversationServiceLocator->getProvidedServices();
    }
}
