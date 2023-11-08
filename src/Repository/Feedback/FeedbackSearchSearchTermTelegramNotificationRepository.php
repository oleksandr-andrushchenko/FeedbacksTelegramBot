<?php

declare(strict_types=1);

namespace App\Repository\Feedback;

use App\Entity\Feedback\FeedbackSearchSearchTermTelegramNotification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FeedbackSearchSearchTermTelegramNotification>
 *
 * @method FeedbackSearchSearchTermTelegramNotification|null find($id, $lockMode = null, $lockVersion = null)
 * @method FeedbackSearchSearchTermTelegramNotification|null findOneBy(array $criteria, array $orderBy = null)
 * @method FeedbackSearchSearchTermTelegramNotification[]    findAll()
 * @method FeedbackSearchSearchTermTelegramNotification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeedbackSearchSearchTermTelegramNotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeedbackSearchSearchTermTelegramNotification::class);
    }
}
