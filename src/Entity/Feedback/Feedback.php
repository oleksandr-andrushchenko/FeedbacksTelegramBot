<?php

declare(strict_types=1);

namespace App\Entity\Feedback;

use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Entity\User\User;
use App\Enum\Feedback\Rating;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Stringable;

class Feedback implements Stringable
{
    private Collection $searchTerms;

    public function __construct(
        private readonly string $id,
        private readonly User $user,
        private readonly MessengerUser $messengerUser,
        array $searchTerms,
        private readonly Rating $rating,
        private readonly ?string $description,
        private readonly bool $hasActiveSubscription,
        private readonly ?string $countryCode = null,
        private readonly ?string $localeCode = null,
        private ?array $channelMessageIds = null,
        private readonly ?TelegramBot $telegramBot = null,
        private ?DateTimeInterface $createdAt = null,
    )
    {
        $this->searchTerms = new ArrayCollection();

        foreach ($searchTerms as $searchTerm) {
            $this->addSearchTerm($searchTerm);
        }
    }

    public function getId(): string
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

    /**
     * @return ArrayCollection|FeedbackSearchTerm[]
     */
    public function getSearchTerms(): iterable
    {
        return $this->searchTerms;
    }

    public function addSearchTerm(FeedbackSearchTerm $searchTerm): self
    {
        if (!$this->searchTerms->contains($searchTerm)) {
            $this->searchTerms->add($searchTerm);
        }

        return $this;
    }

    public function getRating(): Rating
    {
        return $this->rating;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function hasActiveSubscription(): bool
    {
        return $this->hasActiveSubscription;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function getLocaleCode(): ?string
    {
        return $this->localeCode;
    }

    public function addChannelMessageId(string|int $channelMessageId): self
    {
        if ($this->channelMessageIds === null) {
            $this->channelMessageIds = [];
        }

        $this->channelMessageIds[] = (int) $channelMessageId;
        $this->channelMessageIds = array_filter(array_unique($this->channelMessageIds));

        return $this;
    }

    public function getTelegramBot(): ?TelegramBot
    {
        return $this->telegramBot;
    }

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function __toString(): string
    {
        return $this->getId();
    }
}
