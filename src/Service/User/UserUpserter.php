<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Entity\Messenger\MessengerUser;
use App\Entity\User\User;
use App\Object\Messenger\MessengerUserTransfer;
use App\Repository\Address\AddressRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class UserUpserter
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AddressRepository $addressRepository,
    )
    {
    }

    public function upsertUserByMessengerUser(MessengerUser $messengerUser, MessengerUserTransfer $messengerUserTransfer): User
    {
        $user = $messengerUser->getUser();

        if ($user === null) {
            $user = new User();
            $this->entityManager->persist($user);

            $messengerUser->setUser($user);
        } else {
            $user->setUpdatedAt(new DateTimeImmutable());
        }

        if (empty($user->getUsername()) && !empty($messengerUser->getUsername())) {
            $user->setUsername($messengerUser->getUsername());
        }
        if (empty($user->getName()) && !empty($messengerUser->getName())) {
            $user->setName($messengerUser->getName());
        }
        if (empty($user->getCountryCode()) && !empty($messengerUserTransfer->getCountryCode())) {
            $user->setCountryCode($messengerUserTransfer->getCountryCode());
        }
        if (
            !empty($messengerUserTransfer->getCountryCode())
            && !empty($messengerUserTransfer->getRegion1())
            && !empty($messengerUserTransfer->getRegion2())
            && !empty($messengerUserTransfer->getLocality())
            && empty($user->getAddress())
        ) {
            $address = $this->addressRepository->findOneByAddressComponents(
                $messengerUserTransfer->getCountryCode(),
                $messengerUserTransfer->getRegion1(),
                $messengerUserTransfer->getRegion2(),
                $messengerUserTransfer->getLocality(),
            );

            $user->setAddress($address);
        }
        if ($user->getLocaleCode() === null && $messengerUserTransfer->getLocaleCode() !== null) {
            $user->setLocaleCode($messengerUserTransfer->getLocaleCode());
        }
        if (empty($user->getCurrencyCode()) && !empty($messengerUserTransfer->getCurrencyCode())) {
            $user->setCurrencyCode($messengerUserTransfer->getCurrencyCode());
        }
        if (empty($user->getTimezone()) && !empty($messengerUserTransfer->getTimezone())) {
            $user->setTimezone($messengerUserTransfer->getTimezone());
        }

        return $user;
    }
}