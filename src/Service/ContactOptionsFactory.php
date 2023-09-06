<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ContactOptions;
use App\Entity\Intl\Locale;
use App\Enum\Telegram\TelegramGroup;
use App\Exception\ContactOptionsNotFoundException;
use App\Repository\Telegram\TelegramBotRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContactOptionsFactory
{
    public function __construct(
        private readonly array $options,
        private readonly TelegramBotRepository $telegramBotRepository,
        private readonly TranslatorInterface $translator,
    )
    {
    }

    public function createContactOptions(TelegramGroup $group, string $localeCode): ContactOptions
    {
        if (!array_key_exists($group->name, $this->options)) {
            throw new ContactOptionsNotFoundException($group->name);
        }

        $options = $this->options[$group->name];
        $primary = $this->telegramBotRepository->findPrimaryByGroup($group);
        $domain = sprintf('%s.contact', $group->name);

        return new ContactOptions(
            $this->translator->trans('company', domain: $domain, locale: $localeCode),
            $this->translator->trans('address', domain: $domain, locale: $localeCode),
            $this->translator->trans('tax', domain: $domain, locale: $localeCode),
            $primary->getUsername(),
            // todo: fix
            $this->translator->trans(sprintf('%s.name', $group->name), domain: 'feedbacks.tg.texts', locale: $localeCode),
            sprintf('https://t.me/%s', $primary->getUsername()),
            $options['website'],
            $this->translator->trans('phone', domain: $domain, locale: $localeCode),
            $this->translator->trans('email', domain: $domain, locale: $localeCode),
            $options['telegram'],
            $options['instagram'],
            $options['github'],
            $options['linkedin'],
        );
    }
}