<?php

declare(strict_types=1);

namespace App\Repository\Feedback;

use App\Entity\Feedback\FeedbackSearchUserTelegramNotification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FeedbackSearchUserTelegramNotification>
 *
 * @method FeedbackSearchUserTelegramNotification|null find($id, $lockMode = null, $lockVersion = null)
 * @method FeedbackSearchUserTelegramNotification|null findOneBy(array $criteria, array $orderBy = null)
 * @method FeedbackSearchUserTelegramNotification[]    findAll()
 * @method FeedbackSearchUserTelegramNotification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeedbackLookupUserTelegramNotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeedbackSearchUserTelegramNotification::class);
    }
}
