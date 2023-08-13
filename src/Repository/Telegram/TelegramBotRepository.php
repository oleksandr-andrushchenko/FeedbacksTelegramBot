<?php

declare(strict_types=1);

namespace App\Repository\Telegram;

use App\Entity\Telegram\TelegramBot;
use App\Enum\Telegram\TelegramGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TelegramBot>
 *
 * @method TelegramBot|null find($id, $lockMode = null, $lockVersion = null)
 * @method TelegramBot|null findOneBy(array $criteria, array $orderBy = null)
 * @method TelegramBot[]    findAll()
 * @method TelegramBot[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TelegramBotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TelegramBot::class);
    }

    public function save(TelegramBot $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TelegramBot $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneByUsername(string $username): ?TelegramBot
    {
        return $this->findOneBy([
            'username' => $username,
        ]);
    }

    /**
     * @param TelegramGroup $group
     * @return TelegramBot[]
     */
    public function findByGroup(TelegramGroup $group): array
    {
        return $this->findBy([
            'group' => $group,
        ]);
    }

    public function findPrimaryByGroup(TelegramGroup $group): ?TelegramBot
    {
        return $this->findOneBy([
            'group' => $group,
            'primaryBot' => null,
        ]);
    }
}
