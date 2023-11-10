<?php

declare(strict_types=1);

namespace App\Repository\Feedback;

use App\Entity\Feedback\FeedbackNotification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FeedbackNotification>
 *
 * @method FeedbackNotification|null find($id, $lockMode = null, $lockVersion = null)
 * @method FeedbackNotification|null findOneBy(array $criteria, array $orderBy = null)
 * @method FeedbackNotification[]    findAll()
 * @method FeedbackNotification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeedbackNotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeedbackNotification::class);
    }
}
