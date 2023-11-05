<?php

declare(strict_types=1);

namespace App\Repository\Feedback;

use App\Entity\Feedback\FeedbackSearch;
use App\Entity\User\User;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FeedbackSearch>
 *
 * @method FeedbackSearch|null find($id, $lockMode = null, $lockVersion = null)
 * @method FeedbackSearch|null findOneBy(array $criteria, array $orderBy = null)
 * @method FeedbackSearch[]    findAll()
 * @method FeedbackSearch[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeedbackSearchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeedbackSearch::class);
    }

    public function findOneLast(): ?FeedbackSearch
    {
        return $this->findOneBy([], ['createdAt' => 'DESC']);
    }

    public function countByUserAndFromWithoutActiveSubscription(User $user, DateTimeInterface $from): int
    {
        return (int) $this->createQueryBuilder('fs')
            ->select('COUNT(fs.id)')
            ->andWhere('fs.createdAt >= :createdAtFrom')
            ->setParameter('createdAtFrom', $from)
            ->andWhere('fs.user = :user')
            ->setParameter('user', $user)
            ->andWhere('fs.hasActiveSubscription = :hasActiveSubscription')
            ->setParameter('hasActiveSubscription', false)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
