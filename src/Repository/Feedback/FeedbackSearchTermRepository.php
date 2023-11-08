<?php

declare(strict_types=1);

namespace App\Repository\Feedback;

use App\Entity\Feedback\FeedbackSearchTerm;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Tools\Pagination\Paginator;
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

    /**
     * @param DateTimeInterface $from
     * @param DateTimeInterface $to
     * @return Paginator|FeedbackSearchTerm[]
     */
    public function findByPeriod(DateTimeInterface $from, DateTimeInterface $to): Paginator
    {
        $queryBuilder = $this->createQueryBuilder('fst');

        $query = $queryBuilder
            ->andWhere(
                $queryBuilder->expr()->gte('fst.createdAt', ':createdAtFrom'),
                $queryBuilder->expr()->lt('fst.createdAt', ':createdAtTo'),
            )
            ->setParameter('createdAtFrom', $from, Types::DATE_IMMUTABLE)
            ->setParameter('createdAtTo', $to, Types::DATE_IMMUTABLE)
        ;

        return new Paginator($query);
    }
}
