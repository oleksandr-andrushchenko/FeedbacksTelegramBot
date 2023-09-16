<?php

declare(strict_types=1);

namespace App\Service\Telegram\Channel;

use App\Entity\Telegram\TelegramPayment;
use App\Service\Telegram\Command\TelegramCommandInterface;
use App\Service\Telegram\Conversation\TelegramConversationFactory;
use App\Service\Telegram\Telegram;
use App\Service\Telegram\TelegramAwareHelper;

abstract class TelegramChannel implements TelegramChannelInterface
{
    public function __construct(
        private readonly TelegramAwareHelper $awareHelper,
        protected readonly TelegramConversationFactory $conversationFactory,
    )
    {
    }

    /**
     * @param TelegramAwareHelper $tg
     * @return TelegramCommandInterface[]
     */
    abstract protected function getCommands(TelegramAwareHelper $tg): iterable;

    /**
     * @param Telegram $telegram
     * @return array|TelegramCommandInterface[]
     */
    final public function getTelegramCommands(Telegram $telegram): array
    {
        $tg = $this->awareHelper->withTelegram($telegram);

        return iterator_to_array($this->getCommands($tg));
    }

    final public function getTelegramConversationFactory(): TelegramConversationFactory
    {
        return $this->conversationFactory;
    }

    abstract protected function acceptPayment(TelegramPayment $payment, TelegramAwareHelper $tg): void;

    final public function acceptTelegramPayment(Telegram $telegram, TelegramPayment $payment): void
    {
        $tg = $this->awareHelper->withTelegram($telegram);
        $this->acceptPayment($payment, $tg);
    }

    abstract protected function supportsUpdate(TelegramAwareHelper $tg): bool;

    final public function supportsTelegramUpdate(Telegram $telegram): bool
    {
        $tg = $this->awareHelper->withTelegram($telegram);

        return $this->supportsUpdate($tg);
    }
}