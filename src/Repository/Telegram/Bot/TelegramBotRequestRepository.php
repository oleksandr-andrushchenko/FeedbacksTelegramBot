<?php

declare(strict_types=1);

namespace App\Repository\Telegram\Bot;

use App\Entity\Telegram\TelegramBotRequest;
use App\Entity\Telegram\TelegramBotRequestLimits;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TelegramBotRequest>
 *
 * @method TelegramBotRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method TelegramBotRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method TelegramBotRequest[]    findAll()
 * @method TelegramBotRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TelegramBotRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TelegramBotRequest::class);
    }

    public function getLimits(null|int|string $chatId, ?int $inlineMessageId): ?TelegramBotRequestLimits
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

        return new TelegramBotRequestLimits(
            (int) $perSecondAll,
            (int) $perSecond,
            (int) $perMinute
        );
    }
}
