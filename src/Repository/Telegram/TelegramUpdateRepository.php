<?php

declare(strict_types=1);

namespace App\Repository\Telegram;

use App\Entity\Telegram\TelegramUpdate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TelegramUpdate>
 *
 * @method TelegramUpdate|null find($id, $lockMode = null, $lockVersion = null)
 * @method TelegramUpdate|null findOneBy(array $criteria, array $orderBy = null)
 * @method TelegramUpdate[]    findAll()
 * @method TelegramUpdate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TelegramUpdateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TelegramUpdate::class);
    }
}
