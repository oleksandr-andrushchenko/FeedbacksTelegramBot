<?php

declare(strict_types=1);

namespace App\Repository\Telegram;

use App\Entity\Telegram\TelegramRequest;
use App\Entity\Telegram\TelegramRequestLimits;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TelegramRequest>
 *
 * @method TelegramRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method TelegramRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method TelegramRequest[]    findAll()
 * @method TelegramRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TelegramRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TelegramRequest::class);
    }

    public function save(TelegramRequest $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TelegramRequest $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getLimits(null|int|string $chatId, ?int $inlineMessageId): ?TelegramRequestLimits
    {
        $perSecondAll = $this
            ->createQueryBuilder('tr')
            ->select('COUNT(DISTINCT tr.chatId)')
            ->andWhere('tr.createdAt >= :createdAtFrom')
            ->setParameter('createdAtFrom', new DateTime())
            ->getQuery()
            ->getSingleScalarResult()
        ;

        if ($perSecondAll === null) {
            return null;
        }

        $perSecond = $this
            ->createQueryBuilder('tr')
            ->select('COUNT(tr.id)')
            ->andWhere('tr.createdAt >= :createdAtFrom')
            ->setParameter('createdAtFrom', new DateTime())
            ->andWhere('(tr.chatId = :chatId AND tr.inlineMessageId IS NULL) OR (tr.inlineMessageId = :inlineMessageId AND tr.chatId IS NULL)')
            ->setParameter('chatId', $chatId)
            ->setParameter('inlineMessageId', $inlineMessageId)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $perMinute = $this
            ->createQueryBuilder('tr')
            ->select('COUNT(tr.id)')
            ->andWhere('tr.createdAt >= :createdAtFrom')
            ->setParameter('createdAtFrom', (new DateTime())->modify('-1 minute'))
            ->andWhere('tr.chatId = :chatId')
            ->setParameter('chatId', $chatId)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return new TelegramRequestLimits(
            (int) $perSecondAll,
            (int) $perSecond,
            (int) $perMinute
        );
    }
}
