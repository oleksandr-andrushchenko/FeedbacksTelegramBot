<?php

declare(strict_types=1);

namespace App\Repository\Feedback;

use App\Entity\Feedback\FeedbackSearchTerm;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FeedbackSearchTerm>
 *
 * @method FeedbackSearchTerm|null find($id, $lockMode = null, $lockVersion = null)
 * @method FeedbackSearchTerm|null findOneBy(array $criteria, array $orderBy = null)
 * @method FeedbackSearchTerm[]    findAll()
 * @method FeedbackSearchTerm[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeedbackSearchTermRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeedbackSearchTerm::class);
    }

    public function findByNormalizedText(string $normalizedText): array
    {
        return $this->findBy([
            'normalizedText' => $normalizedText,
        ]);
    }
}
