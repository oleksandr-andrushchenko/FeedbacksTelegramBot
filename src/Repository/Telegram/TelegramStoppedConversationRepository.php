<?php

declare(strict_types=1);

namespace App\Repository\Telegram;

use App\Entity\Telegram\TelegramStoppedConversation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TelegramStoppedConversation>
 *
 * @method TelegramStoppedConversation|null find($id, $lockMode = null, $lockVersion = null)
 * @method TelegramStoppedConversation|null findOneBy(array $criteria, array $orderBy = null)
 * @method TelegramStoppedConversation[] findAll()
 * @method TelegramStoppedConversation[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TelegramStoppedConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TelegramStoppedConversation::class);
    }
}
