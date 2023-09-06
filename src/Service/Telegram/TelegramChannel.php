<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Telegram\TelegramPayment;

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
    public function getTelegramCommands(Telegram $telegram): array
    {
        $tg = $this->awareHelper->withTelegram($telegram);

        return iterator_to_array($this->getCommands($tg));
    }

    public function getTelegramConversationFactory(): TelegramConversationFactory
    {
        return $this->conversationFactory;
    }

    abstract protected function acceptPayment(TelegramPayment $payment, TelegramAwareHelper $tg): void;

    public function acceptTelegramPayment(Telegram $telegram, TelegramPayment $payment): void
    {
        $tg = $this->awareHelper->withTelegram($telegram);
        $this->acceptPayment($payment, $tg);
    }
}