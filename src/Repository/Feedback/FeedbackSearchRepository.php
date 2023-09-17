<?php

declare(strict_types=1);

namespace App\Repository\Feedback;

use App\Entity\Feedback\FeedbackSearch;
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
}
