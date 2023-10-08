<?php

declare(strict_types=1);

namespace App\Repository\Telegram\Bot;

use App\Entity\Telegram\TelegramBotUpdate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TelegramBotUpdate>
 *
 * @method TelegramBotUpdate|null find($id, $lockMode = null, $lockVersion = null)
 * @method TelegramBotUpdate|null findOneBy(array $criteria, array $orderBy = null)
 * @method TelegramBotUpdate[]    findAll()
 * @method TelegramBotUpdate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TelegramBotUpdateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TelegramBotUpdate::class);
    }
}
