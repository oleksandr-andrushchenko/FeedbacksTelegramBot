<?php

declare(strict_types=1);

namespace App\Repository\Telegram;

use App\Entity\Telegram\TelegramPayment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TelegramPayment>
 *
 * @method TelegramPayment|null find($id, $lockMode = null, $lockVersion = null)
 * @method TelegramPayment|null findOneBy(array $criteria, array $orderBy = null)
 * @method TelegramPayment[]    findAll()
 * @method TelegramPayment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TelegramPaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TelegramPayment::class);
    }
}
