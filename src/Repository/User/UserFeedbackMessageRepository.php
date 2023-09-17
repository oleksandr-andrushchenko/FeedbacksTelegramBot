<?php

declare(strict_types=1);

namespace App\Repository\User;

use App\Entity\User\UserFeedbackMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserFeedbackMessage>
 *
 * @method UserFeedbackMessage|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserFeedbackMessage|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserFeedbackMessage[]    findAll()
 * @method UserFeedbackMessage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserFeedbackMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserFeedbackMessage::class);
    }
}
