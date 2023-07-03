<?php

declare(strict_types=1);

namespace App\Service\Instagram;

use App\Entity\Messenger\MessengerUser;
use Instagram\SDK\Response\DTO\General\User as InstagramUser;

class InstagramMessengerUserMaker
{
    public function makeInstagramMessengerUser(InstagramUser|MessengerUser $instagramUser): MessengerUser
    {
        if ($instagramUser instanceof InstagramUser) {
            $id = (int) $instagramUser->getId();

            return (new MessengerUser())
                ->setId(empty($id) ? null : $id)
                ->setName(empty($instagramUser->getFullName()) ? null : $instagramUser->getFullName())
                ->setUsername(empty($instagramUser->getUsername()) ? null : $instagramUser->getUsername())
                ->setBio(empty($instagramUser->getBiography()) ? null : $instagramUser->getBiography())
                ->setPictureUrl(empty($instagramUser->getProfilePictureUrl()) ? null : $instagramUser->getProfilePictureUrl())
            ;
        }

        return $instagramUser;
    }
}