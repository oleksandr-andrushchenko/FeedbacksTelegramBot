<?php

declare(strict_types=1);

namespace App\Repository\Telegram;

use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
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

    public function save(TelegramConversation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TelegramConversation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneByMessengerUserAndChatId(MessengerUser $messengerUser, int $chatId, TelegramBot $bot): ?TelegramConversation
    {
        return $this->findOneBy(
            [
                'messengerUser' => $messengerUser,
                'chatId' => $chatId,
                'bot' => $bot,
            ],
            [
                'id' => 'DESC',
            ]
        );
    }

    /**
     * @param MessengerUser $messengerUser
     * @param TelegramBot $bot
     * @return TelegramConversation[]
     */
    public function getActiveByMessengerUser(MessengerUser $messengerUser, TelegramBot $bot): array
    {
        return $this->findBy([
            'messengerUser' => $messengerUser,
            'active' => true,
            'bot' => $bot,
        ]);
    }
}
