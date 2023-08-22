<?php

declare(strict_types=1);

namespace App\Object\Feedback;

use App\Entity\Messenger\MessengerUser;

readonly class FeedbackSearchSearchTransfer
{
    public function __construct(
        private ?MessengerUser $messengerUser,
        private ?SearchTermTransfer $searchTerm,
    )
    {
    }

    public function getMessengerUser(): ?MessengerUser
    {
        return $this->messengerUser;
    }

    public function getSearchTerm(): ?SearchTermTransfer
    {
        return $this->searchTerm;
    }
}