<?php

declare(strict_types=1);

namespace App\Repository\Feedback;

use App\Entity\Feedback\FeedbackSearchTermTelegramNotification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FeedbackSearchTermTelegramNotification>
 *
 * @method FeedbackSearchTermTelegramNotification|null find($id, $lockMode = null, $lockVersion = null)
 * @method FeedbackSearchTermTelegramNotification|null findOneBy(array $criteria, array $orderBy = null)
 * @method FeedbackSearchTermTelegramNotification[]    findAll()
 * @method FeedbackSearchTermTelegramNotification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeedbackSearchTermTelegramNotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeedbackSearchTermTelegramNotification::class);
    }
}
