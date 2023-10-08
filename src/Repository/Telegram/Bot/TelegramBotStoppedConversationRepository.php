<?php

declare(strict_types=1);

namespace App\Repository\Telegram\Bot;

use App\Entity\Telegram\TelegramBotStoppedConversation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TelegramBotStoppedConversation>
 *
 * @method TelegramBotStoppedConversation|null find($id, $lockMode = null, $lockVersion = null)
 * @method TelegramBotStoppedConversation|null findOneBy(array $criteria, array $orderBy = null)
 * @method TelegramBotStoppedConversation[] findAll()
 * @method TelegramBotStoppedConversation[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TelegramBotStoppedConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TelegramBotStoppedConversation::class);
    }
}
