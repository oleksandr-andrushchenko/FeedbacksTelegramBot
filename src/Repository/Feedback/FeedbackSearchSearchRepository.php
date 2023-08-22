<?php

declare(strict_types=1);

namespace App\Repository\Feedback;

use App\Entity\Feedback\FeedbackSearchSearch;
use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FeedbackSearchSearch>
 *
 * @method FeedbackSearchSearch|null find($id, $lockMode = null, $lockVersion = null)
 * @method FeedbackSearchSearch|null findOneBy(array $criteria, array $orderBy = null)
 * @method FeedbackSearchSearch[]    findAll()
 * @method FeedbackSearchSearch[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeedbackSearchSearchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeedbackSearchSearch::class);
    }

    public function save(FeedbackSearchSearch $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(FeedbackSearchSearch $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByTargetUser(User $targetUser): array
    {
        return $this->findBy(['targetUser' => $targetUser]);
    }
}
