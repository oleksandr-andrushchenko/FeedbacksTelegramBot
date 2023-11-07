<?php

declare(strict_types=1);

namespace App\Repository\Feedback;

use App\Entity\Feedback\FeedbackSearchSearchTermUserTelegramNotification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FeedbackSearchSearchTermUserTelegramNotification>
 *
 * @method FeedbackSearchSearchTermUserTelegramNotification|null find($id, $lockMode = null, $lockVersion = null)
 * @method FeedbackSearchSearchTermUserTelegramNotification|null findOneBy(array $criteria, array $orderBy = null)
 * @method FeedbackSearchSearchTermUserTelegramNotification[]    findAll()
 * @method FeedbackSearchSearchTermUserTelegramNotification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeedbackSearchSearchTermUserTelegramNotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeedbackSearchSearchTermUserTelegramNotification::class);
    }
}
