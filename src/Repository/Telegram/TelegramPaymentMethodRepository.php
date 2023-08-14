<?php

declare(strict_types=1);

namespace App\Repository\Telegram;

use App\Entity\Telegram\TelegramBot;
use App\Entity\Telegram\TelegramPaymentMethod;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TelegramPaymentMethod>
 *
 * @method TelegramPaymentMethod|null find($id, $lockMode = null, $lockVersion = null)
 * @method TelegramPaymentMethod|null findOneBy(array $criteria, array $orderBy = null)
 * @method TelegramPaymentMethod[]    findAll()
 * @method TelegramPaymentMethod[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TelegramPaymentMethodRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TelegramPaymentMethod::class);
    }

    public function save(TelegramPaymentMethod $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TelegramPaymentMethod $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param TelegramBot $bot
     * @return TelegramPaymentMethod[]
     */
    public function findByBot(TelegramBot $bot): array
    {
        return $this->findBy([
            'bot' => $bot,
        ]);
    }
}
