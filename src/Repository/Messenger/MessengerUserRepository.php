<?php

declare(strict_types=1);

namespace App\Repository\Messenger;

use App\Entity\Messenger\MessengerUser;
use App\Entity\User\User;
use App\Enum\Messenger\Messenger;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method MessengerUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method MessengerUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method MessengerUser[]    findAll()
 * @method MessengerUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MessengerUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MessengerUser::class);
    }

    public function findOneByMessengerAndIdentifier(
        Messenger $messenger,
        string $identifier,
        bool $withUser = false
    ): ?MessengerUser
    {
        if ($withUser) {
            return $this->createQueryBuilder('mu')
                ->select('mu', 'u')
                ->innerJoin('mu.user', 'u')
                ->andWhere('mu.messenger = :messenger')
                ->setParameter('messenger', $messenger)
                ->andWhere('mu.identifier = :identifier')
                ->setParameter('identifier', $identifier)
                ->setMaxResults(1)
                ->getQuery()
                ->getSingleResult()
            ;
        }

        return $this->findOneBy([
            'messenger' => $messenger,
            'identifier' => $identifier,
        ]);
    }

    public function findByUser(User $user): array
    {
        return $this->findBy([
            'user' => $user,
        ]);
    }
}
