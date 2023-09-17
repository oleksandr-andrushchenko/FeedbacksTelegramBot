<?php

declare(strict_types=1);

namespace App\Repository\Feedback;

use App\Entity\Feedback\FeedbackUserSubscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FeedbackUserSubscription>
 *
 * @method FeedbackUserSubscription|null find($id, $lockMode = null, $lockVersion = null)
 * @method FeedbackUserSubscription|null findOneBy(array $criteria, array $orderBy = null)
 * @method FeedbackUserSubscription[]    findAll()
 * @method FeedbackUserSubscription[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeedbackUserSubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeedbackUserSubscription::class);
    }
}
