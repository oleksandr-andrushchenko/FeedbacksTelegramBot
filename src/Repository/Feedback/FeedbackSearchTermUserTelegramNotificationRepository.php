<?php

declare(strict_types=1);

namespace App\Repository\Feedback;

use App\Entity\Feedback\FeedbackSearchTermUserTelegramNotification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FeedbackSearchTermUserTelegramNotification>
 *
 * @method FeedbackSearchTermUserTelegramNotification|null find($id, $lockMode = null, $lockVersion = null)
 * @method FeedbackSearchTermUserTelegramNotification|null findOneBy(array $criteria, array $orderBy = null)
 * @method FeedbackSearchTermUserTelegramNotification[]    findAll()
 * @method FeedbackSearchTermUserTelegramNotification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeedbackSearchTermUserTelegramNotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeedbackSearchTermUserTelegramNotification::class);
    }
}
