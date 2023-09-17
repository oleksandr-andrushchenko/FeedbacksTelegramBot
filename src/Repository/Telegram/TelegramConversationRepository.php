<?php

declare(strict_types=1);

namespace App\Repository\Telegram;

use App\Entity\Telegram\TelegramConversation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TelegramConversation>
 *
 * @method TelegramConversation|null find($id, $lockMode = null, $lockVersion = null)
 * @method TelegramConversation|null findOneBy(array $criteria, array $orderBy = null)
 * @method TelegramConversation[] findAll()
 * @method TelegramConversation[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TelegramConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TelegramConversation::class);
    }

    public function findOneByHash(string $hash): ?TelegramConversation
    {
        $records = $this->findByHash($hash);

        if (count($records) === 0) {
            return null;
        }

        $return = $records[0];

        foreach ($records as $record) {
            if ($record->getCreatedAt() > $return) {
                $return = $record;
            }
        }

        return $return;
    }

    public function findByHash(string $hash): array
    {
        return $this->findBy([
            'hash' => $hash,
        ]);
    }
}
