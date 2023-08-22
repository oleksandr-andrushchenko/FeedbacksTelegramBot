<?php

declare(strict_types=1);

namespace App\Entity\Feedback;

use App\Entity\Messenger\MessengerUser;
use App\Entity\User\User;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Messenger\Messenger;
use DateTimeImmutable;
use DateTimeInterface;

class FeedbackSearchSearch
{
    public function __construct(
        private readonly User $user,
        private readonly MessengerUser $messengerUser,
        private readonly string $searchTermText,
        private readonly string $searchTermNormalizedText,
        private readonly SearchTermType $searchTermType,
        private readonly ?MessengerUser $searchTermMessengerUser,
        private readonly ?Messenger $searchTermMessenger,
        private readonly ?string $searchTermMessengerUsername,
        private readonly ?string $countryCode = null,
        private readonly ?string $localeCode = null,
        private readonly DateTimeInterface $createdAt = new DateTimeImmutable(),
        private ?int $id = null,
    )
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getMessengerUser(): MessengerUser
    {
        return $this->messengerUser;
    }

    public function getSearchTermText(): string
    {
        return $this->searchTermText;
    }

    public function getSearchTermNormalizedText(): string
    {
        return $this->searchTermNormalizedText;
    }

    public function getSearchTermType(): SearchTermType
    {
        return $this->searchTermType;
    }

    public function getSearchTermMessengerUser(): ?MessengerUser
    {
        return $this->searchTermMessengerUser;
    }

    public function getSearchTermMessenger(): ?Messenger
    {
        return $this->searchTermMessenger;
    }

    public function getSearchTermMessengerUsername(): ?string
    {
        return $this->searchTermMessengerUsername;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function getLocaleCode(): ?string
    {
        return $this->localeCode;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }
}
