<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Telegram\TelegramUpdate;
use App\Repository\Telegram\TelegramUpdateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class TelegramUpdateChecker
{
    public function __construct(
        private readonly TelegramUpdateRepository $updateRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly bool $saveOnly = false,
    )
    {
    }

    /**
     * @param Telegram $telegram
     * @return bool
     */
    public function checkTelegramUpdate(Telegram $telegram): bool
    {
        if (!$telegram->getBot()->checkUpdates()) {
            return false;
        }

        if (!$this->saveOnly) {
            $telegramUpdate = $this->updateRepository->find($telegram->getUpdate()?->getUpdateId());

            if ($telegramUpdate !== null) {
                $this->logger->debug('Duplicate telegram update received, processing aborted', [
                    'name' => $telegram->getBot()->getGroup()->name,
                    'update_id' => $telegramUpdate->getId(),
                ]);

                return true;
            }
        }

        $telegramUpdate = new TelegramUpdate(
            $telegram->getUpdate()->getUpdateId(),
            $telegram->getUpdate()->getRawData(),
            $telegram->getBot()
        );
        $this->entityManager->persist($telegramUpdate);

        return false;
    }
}