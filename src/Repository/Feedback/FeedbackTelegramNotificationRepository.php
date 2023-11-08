<?php

declare(strict_types=1);

namespace App\Repository\Feedback;

use App\Entity\Feedback\FeedbackTelegramNotification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FeedbackTelegramNotification>
 *
 * @method FeedbackTelegramNotification|null find($id, $lockMode = null, $lockVersion = null)
 * @method FeedbackTelegramNotification|null findOneBy(array $criteria, array $orderBy = null)
 * @method FeedbackTelegramNotification[]    findAll()
 * @method FeedbackTelegramNotification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeedbackTelegramNotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeedbackTelegramNotification::class);
    }
}
