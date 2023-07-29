<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Entity\Messenger\MessengerUser;
use App\Entity\User\User;
use App\Repository\User\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class UserUpserter
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }

    public function upsertUserByMessengerUser(MessengerUser $messengerUser): User
    {
        $user = $messengerUser->getUser();

        if ($user === null) {
            $user = new User(
                $messengerUser->getUsername(),
                $messengerUser->getName(),
                $messengerUser->getLanguageCode()
            );

            $this->entityManager->persist($user);

            $messengerUser->setUser($user);

            return $user;
        }

        if (empty($user->getUsername()) && !empty($messengerUser->getUsername())) {
            $user->setUsername($messengerUser->getUsername());
        }

        if (empty($user->getName()) && !empty($messengerUser->getName())) {
            $user->setName($messengerUser->getName());
        }

        if (empty($user->getLanguageCode()) && !empty($messengerUser->getLanguageCode())) {
            $user->setLanguageCode($messengerUser->getLanguageCode());
        }

        $user->setUpdatedAt(new DateTimeImmutable());

        return $user;
    }

    public function upsertUserByName(string $name): User
    {
        $user = $this->userRepository->findOneByName($name);

        if ($user === null) {
            $user = (new User())
                ->setName($name)
            ;
            $this->entityManager->persist($user);

            return $user;
        }

        $user->setUpdatedAt(new DateTimeImmutable());

        return $user;
    }
}