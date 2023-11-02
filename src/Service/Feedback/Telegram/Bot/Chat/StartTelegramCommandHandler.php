<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Bot\Chat;

use App\Repository\Telegram\Channel\TelegramChannelRepository;
use App\Service\Intl\CountryProvider;
use App\Service\Intl\LocaleProvider;
use App\Service\Telegram\Bot\TelegramBotAwareHelper;
use App\Service\Telegram\Channel\View\TelegramChannelLinkViewProvider;

class StartTelegramCommandHandler
{
    public function __construct(
        private readonly ChooseActionTelegramChatSender $chooseActionTelegramChatSender,
        private readonly CountryProvider $countryProvider,
        private readonly LocaleProvider $localeProvider,
        private readonly TelegramChannelRepository $telegramChannelRepository,
        private readonly TelegramChannelLinkViewProvider $telegramChannelLinkViewProvider,
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
        $message = '👋 ' . $tg->queryText($tg->trans('greetings', domain: $domain));
        $message .= "\n\n";
        $message .= $tg->infoText($tg->trans('title', domain: $domain));

        $channel = $this->telegramChannelRepository->findOnePrimaryByBot($tg->getBot()->getEntity());

        if ($channel !== null) {
            $message .= "\n\n";
            $message .= $tg->queryText($tg->trans('channel', domain: $domain)) . ':';
            $message .= "\n";
            $message .= $this->telegramChannelLinkViewProvider->getTelegramChannelLinkView($channel);
        }

        $message .= "\n\n";
        $message .= $tg->queryText($tg->trans('main_commands', domain: $domain)) . ':';
        $message .= "\n";
        $message .= $tg->command('create', html: true);
        $message .= "\n";
        $message .= $tg->command('search', html: true);
        $message .= "\n";
        $message .= $tg->command('lookup', html: true);
        $message .= "\n\n";
        $message .= $tg->queryText($tg->trans('setting_commands', domain: $domain)) . ':';
        $message .= "\n";
        $message .= $tg->command('country', icon: $this->countryProvider->getCountryIconByCode($tg->getCountryCode()), html: true);
        $message .= "\n";
        $locale = $this->localeProvider->getLocale($tg->getLocaleCode());
        $message .= $tg->command('locale', icon: $this->localeProvider->getLocaleIcon($locale), html: true);

        $tg->reply($message);
    }
}
