<?php

declare(strict_types=1);

namespace App\Service\Site;

use App\Entity\Site\SiteContactOptions;
use App\Enum\Telegram\TelegramGroup;
use App\Repository\Telegram\TelegramBotRepository;

class SiteContactOptionsFactory
{
    public function __construct(
        private readonly TelegramBotRepository $telegramBotRepository,
    )
    {
    }

    public function createSiteContactOptions(array $options, string $groupName): SiteContactOptions
    {
        $group = TelegramGroup::fromName($groupName);
        $primary = $this->telegramBotRepository->findPrimaryByGroup($group);

        return new SiteContactOptions(
            sprintf('@%s', $primary->getUsername()),
            sprintf('https://t.me/%s', $primary->getUsername()),
            $options['website'],
            $options['phone'],
            $options['email'],
            $options['telegram'],
            $options['instagram'],
            $options['github'],
            $options['linkedin'],
        );
    }
}