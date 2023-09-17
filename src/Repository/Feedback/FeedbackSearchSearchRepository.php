<?php

declare(strict_types=1);

namespace App\Repository\Feedback;

use App\Entity\Feedback\FeedbackSearchSearch;
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
}
