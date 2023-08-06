<?php

declare(strict_types=1);

namespace App\Service\Site;

use App\Entity\Site\SiteContactOptions;
use App\Entity\Telegram\TelegramOptions;
use Symfony\Contracts\Translation\TranslatorInterface;

class SiteContactOptionsFactory
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    )
    {
    }

    public function createSiteContactOptions(array $options, TelegramOptions $telegramOptions): SiteContactOptions
    {
        return new SiteContactOptions(
            $this->translator->trans('bot', [], sprintf('tg.%s', $telegramOptions->getGroupKey())),
            sprintf('https://t.me/%s', $telegramOptions->getUsername()),
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