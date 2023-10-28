<?php

declare(strict_types=1);

namespace App\Repository\Feedback;

use App\Entity\Feedback\FeedbackLookup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FeedbackLookup>
 *
 * @method FeedbackLookup|null find($id, $lockMode = null, $lockVersion = null)
 * @method FeedbackLookup|null findOneBy(array $criteria, array $orderBy = null)
 * @method FeedbackLookup[]    findAll()
 * @method FeedbackLookup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeedbackLookupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeedbackLookup::class);
    }

    public function findOneLast(): ?FeedbackLookup
    {
        return $this->findOneBy([], ['createdAt' => 'DESC']);
    }
}
