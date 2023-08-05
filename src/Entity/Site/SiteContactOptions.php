<?php

declare(strict_types=1);

namespace App\Entity\Site;

readonly class SiteContactOptions
{
    public function __construct(
        private string $botName,
        private string $botLink,
        private string $website,
        private string $phone,
        private string $email,
        private string $telegram,
        private string $instagram,
        private string $github,
        private string $linkedin,
    )
    {
    }

    public function getBotName(): string
    {
        return $this->botName;
    }

    public function getBotLink(): string
    {
        return $this->botLink;
    }

    public function getWebsite(): string
    {
        return $this->website;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getTelegram(): string
    {
        return $this->telegram;
    }

    public function getInstagram(): string
    {
        return $this->instagram;
    }

    public function getGithub(): string
    {
        return $this->github;
    }

    public function getLinkedin(): string
    {
        return $this->linkedin;
    }
}
