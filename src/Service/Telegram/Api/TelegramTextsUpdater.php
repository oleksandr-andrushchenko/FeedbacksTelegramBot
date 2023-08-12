<?php

declare(strict_types=1);

namespace App\Service\Telegram\Api;

use App\Service\Site\SiteUrlGenerator;
use App\Service\Telegram\Telegram;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class TelegramTextsUpdater
{
    public function __construct(
        private string $stage,
        private readonly TranslatorInterface $translator,
        private readonly SiteUrlGenerator $siteUrlGenerator,
        private ?array $myNames = null,
        private ?array $myDescriptions = null,
        private ?array $myShortDescriptions = null,
    )
    {
    }

    /**
     * @param Telegram $telegram
     * @return void
     */
    public function updateTelegramDescriptions(Telegram $telegram): void
    {
        $this->myNames = [];
        $this->myDescriptions = [];
        $this->myShortDescriptions = [];

        $domain = sprintf('tg.%s', $telegram->getBot()->getGroup()->name);

        foreach ($telegram->getOptions()->getLocaleCodes() as $localeCode) {
            $name = $this->translator->trans('name', domain: $domain, locale: $localeCode);
            $this->myNames[$localeCode] = $name;
            $telegram->setMyName([
                'name' => $this->stage === 'prod' ? $name : sprintf('(%s) %s', ucfirst($this->stage), $name),
                'language_code' => $localeCode,
            ]);
            $description = $this->translator->trans(
                'description',
                [
                    'privacy_policy_link' => $this->siteUrlGenerator->generate(
                        'app.site_privacy_policy',
                        [
                            '_locale' => $localeCode,
                        ],
                        referenceType: UrlGeneratorInterface::ABSOLUTE_URL
                    ),
                    'terms_of_use_link' => $this->siteUrlGenerator->generate(
                        'app.site_terms_of_use',
                        [
                            '_locale' => $localeCode,
                        ],
                        referenceType: UrlGeneratorInterface::ABSOLUTE_URL
                    ),
                ],
                domain: $domain,
                locale: $localeCode
            );
            $this->myDescriptions[$localeCode] = $description;
            $telegram->setMyDescription([
                'description' => $description,
                'language_code' => $localeCode,
            ]);
            $shortDescription = $this->translator->trans('short_description', domain: $domain, locale: $localeCode);
            $this->myShortDescriptions[$localeCode] = $shortDescription;
            $telegram->setMyShortDescription([
                'short_description' => $shortDescription,
                'language_code' => $localeCode,
            ]);
        }
    }

    /**
     * @return array|null
     */
    public function getMyNames(): ?array
    {
        return $this->myNames;
    }

    /**
     * @return array|null
     */
    public function getMyDescriptions(): ?array
    {
        return $this->myDescriptions;
    }

    /**
     * @return array|null
     */
    public function getMyShortDescriptions(): ?array
    {
        return $this->myShortDescriptions;
    }
}