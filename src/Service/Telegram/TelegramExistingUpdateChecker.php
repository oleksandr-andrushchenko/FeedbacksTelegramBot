<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Telegram\TelegramUpdate;
use App\Repository\Telegram\TelegramUpdateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class TelegramExistingUpdateChecker
{
    public function __construct(
        private readonly TelegramUpdateRepository $updateRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    )
    {
    }

    /**
     * @param Telegram $telegram
     * @return bool
     */
    public function checkExistingUpdate(Telegram $telegram): bool
    {
        if (!$telegram->getOptions()->checkUpdateDuplicates()) {
            return false;
        }

        $telegramUpdate = $this->updateRepository->find($telegram->getUpdate()?->getUpdateId());

        if ($telegramUpdate !== null) {
            $this->logger->debug('Duplicate telegram update received, processing aborted', [
                'name' => $telegram->getName()->name,
                'update_id' => $telegramUpdate->getId(),
            ]);

            return true;
        }

        $telegramUpdate = new TelegramUpdate($telegram->getUpdate()->getUpdateId());
        $this->entityManager->persist($telegramUpdate);

        return false;
    }
}