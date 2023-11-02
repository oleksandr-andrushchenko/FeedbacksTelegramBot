<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Bot\Chat;

use App\Service\Intl\CountryProvider;
use App\Service\Intl\LocaleProvider;
use App\Service\Telegram\Bot\TelegramBotAwareHelper;

class StartTelegramCommandHandler
{
    public function __construct(
        private readonly ChooseActionTelegramChatSender $chooseActionTelegramChatSender,
        private readonly CountryProvider $countryProvider,
        private readonly LocaleProvider $localeProvider,
    )
    {
    }

    public function handleStart(TelegramBotAwareHelper $tg): null
    {
        $this->reply($tg);

        return $this->chooseActionTelegramChatSender->sendActions($tg);
    }

    public function reply(TelegramBotAwareHelper $tg): void
    {
        $domain = 'start';
        $locale = $this->localeProvider->getLocale($tg->getLocaleCode());
        $message = $tg->infoText($tg->trans('title', domain: $domain));
        $message .= "\n\n";
        $parameters = [];
        $parameters['country_command'] = $tg->command('country', icon: $this->countryProvider->getCountryIconByCode($tg->getCountryCode()), html: true);
        $message .= $tg->infoText($tg->trans('country', parameters: $parameters, domain: $domain));
        $message .= "\n";
        $parameters = [];
        $parameters['locale_command'] = $tg->command('locale', icon: $this->localeProvider->getLocaleIcon($locale), html: true);
        $message .= $tg->trans('locale', parameters: $parameters, domain: $domain);
        $message .= "\n";
        $parameters = [];
        $parameters['commands_command'] = $tg->command('commands', html: true);
        $message .= $tg->trans('commands', parameters: $parameters, domain: $domain);

        $tg->reply($message);
    }
}
