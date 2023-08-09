<?php

declare(strict_types=1);

namespace App\Service\Site;

use App\Entity\Site\SiteContactOptions;
use App\Enum\Telegram\TelegramGroup;
use App\Repository\Telegram\TelegramBotRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

class SiteContactOptionsFactory
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly TelegramBotRepository $telegramBotRepository,
    )
    {
    }

    public function createSiteContactOptions(array $options, string $groupName): SiteContactOptions
    {
        $group = TelegramGroup::fromName($groupName);
        $bots = $this->telegramBotRepository->findByGroup($group);

        return new SiteContactOptions(
            $this->translator->trans('bot', [], sprintf('tg.%s', $group->name)),
            isset($bots[0]) ? sprintf('https://t.me/%s', $bots[0]->getUsername()) : null,
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