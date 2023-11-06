<?php

declare(strict_types=1);

namespace App\EventListener\Doctrine;

use App\Entity\Messenger\MessengerUser;
use Doctrine\ORM\Event\PreUpdateEventArgs;

class MessengerUserUsernameChangedEventListener
{
    public function preUpdate(MessengerUser $messengerUser, PreUpdateEventArgs $args): void
    {
        if ($args->hasChangedField('username')) {
            $username = $args->getOldValue('username');

            $messengerUser->addUsernameHistory($username);
        }
    }
}