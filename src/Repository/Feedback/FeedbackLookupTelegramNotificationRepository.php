<?php

declare(strict_types=1);

namespace App\Repository\Feedback;

use App\Entity\Feedback\FeedbackSearchTelegramNotification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FeedbackSearchTelegramNotification>
 *
 * @method FeedbackSearchTelegramNotification|null find($id, $lockMode = null, $lockVersion = null)
 * @method FeedbackSearchTelegramNotification|null findOneBy(array $criteria, array $orderBy = null)
 * @method FeedbackSearchTelegramNotification[]    findAll()
 * @method FeedbackSearchTelegramNotification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeedbackLookupTelegramNotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeedbackSearchTelegramNotification::class);
    }
}
