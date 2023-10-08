<?php

declare(strict_types=1);

namespace App\Repository\Telegram\Bot;

use App\Entity\Telegram\TelegramBotConversation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TelegramBotConversation>
 *
 * @method TelegramBotConversation|null find($id, $lockMode = null, $lockVersion = null)
 * @method TelegramBotConversation|null findOneBy(array $criteria, array $orderBy = null)
 * @method TelegramBotConversation[] findAll()
 * @method TelegramBotConversation[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TelegramBotConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TelegramBotConversation::class);
    }

    public function findOneByHash(string $hash): ?TelegramBotConversation
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
