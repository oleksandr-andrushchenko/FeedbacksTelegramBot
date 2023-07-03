<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Entity\Feedback\SearchTerm;
use App\Entity\Messenger\MessengerUser;
use App\Entity\Messenger\MessengerUsername;
use App\Entity\User\User;

class UserFinder
{
    public function findUserByMessengerUser(MessengerUser $messengerUser): ?User
    {
        return null;
    }

    public function findUserByMessengerUsername(MessengerUsername $messengerUsername): ?User
    {
        return null;
    }

    public function findUserBySearchTerm(SearchTerm $searchTerm): ?User
    {
        return null;
    }
}