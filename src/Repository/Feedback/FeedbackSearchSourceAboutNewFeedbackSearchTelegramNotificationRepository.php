<?php

declare(strict_types=1);

namespace App\Repository\Feedback;

use App\Entity\Feedback\FeedbackSearchSourceAboutNewFeedbackSearchTelegramNotification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FeedbackSearchSourceAboutNewFeedbackSearchTelegramNotification>
 *
 * @method FeedbackSearchSourceAboutNewFeedbackSearchTelegramNotification|null find($id, $lockMode = null, $lockVersion = null)
 * @method FeedbackSearchSourceAboutNewFeedbackSearchTelegramNotification|null findOneBy(array $criteria, array $orderBy = null)
 * @method FeedbackSearchSourceAboutNewFeedbackSearchTelegramNotification[]    findAll()
 * @method FeedbackSearchSourceAboutNewFeedbackSearchTelegramNotification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeedbackSearchSourceAboutNewFeedbackSearchTelegramNotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeedbackSearchSourceAboutNewFeedbackSearchTelegramNotification::class);
    }
}
