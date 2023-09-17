<?php

declare(strict_types=1);

namespace App\Repository\Telegram;

use App\Entity\Telegram\TelegramBot;
use App\Entity\Telegram\TelegramPaymentMethod;
use App\Enum\Telegram\TelegramPaymentMethodName;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TelegramPaymentMethod>
 *
 * @method TelegramPaymentMethod|null find($id, $lockMode = null, $lockVersion = null)
 * @method TelegramPaymentMethod|null findOneBy(array $criteria, array $orderBy = null)
 * @method TelegramPaymentMethod[]    findAll()
 * @method TelegramPaymentMethod[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TelegramPaymentMethodRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TelegramPaymentMethod::class);
    }

    /**
     * @param TelegramBot $bot
     * @return TelegramPaymentMethod[]
     */
    public function findActiveByBot(TelegramBot $bot): array
    {
        return $this->findBy([
            'bot' => $bot,
            'deletedAt' => null,
        ]);
    }

    public function findOneActiveByBotAndName(TelegramBot $bot, TelegramPaymentMethodName $name): ?TelegramPaymentMethod
    {
        return $this->findOneBy([
            'bot' => $bot,
            'name' => $name,
            'deletedAt' => null,
        ]);
    }
}
