<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Telegram\TelegramBot;
use App\Object\Telegram\TelegramBotTransfer;
use App\Service\Intl\CountryProvider;

class TelegramBotUpdater
{
    public function __construct(
        private readonly CountryProvider $countryProvider,
        private readonly TelegramBotValidator $validator,
    )
    {
    }

    /**
     * @param TelegramBot $bot
     * @param TelegramBotTransfer $botTransfer
     * @return void
     */
    public function updateTelegramBot(TelegramBot $bot, TelegramBotTransfer $botTransfer): void
    {
        if ($botTransfer->groupPassed()) {
            $bot->setGroup($botTransfer->getGroup());
        }

        if ($botTransfer->namePassed()) {
            $bot->setName($botTransfer->getName());
            $bot->setTextsSet(false);
        }

        if ($botTransfer->tokenPassed()) {
            $bot->setToken($botTransfer->getToken());
        }

        if ($botTransfer->countryPassed()) {
            $bot->setCountryCode($botTransfer->getCountry()->getCode());
        }

        if ($botTransfer->localePassed()) {
            if ($botTransfer->getLocale() === null) {
                $country = $this->countryProvider->getCountry($bot->getCountryCode());
                $bot->setLocaleCode($country->getLocaleCodes()[0]);
            } else {
                $bot->setLocaleCode($botTransfer->getLocale()->getCode());
            }
        }

        if ($botTransfer->channelUsernamePassed()) {
            $bot->setChannelUsername($botTransfer->getChannelUsername());
        }
        if ($botTransfer->groupUsernamePassed()) {
            $bot->setGroupUsername($botTransfer->getGroupUsername());
        }

        if ($botTransfer->checkUpdatesPassed()) {
            $bot->setCheckUpdates($botTransfer->checkUpdates());
        }
        if ($botTransfer->checkRequestsPassed()) {
            $bot->setCheckRequests($botTransfer->checkRequests());
        }
        if ($botTransfer->acceptPaymentsPassed()) {
            $bot->setAcceptPayments($botTransfer->acceptPayments());
        }
        if ($botTransfer->adminOnlyPassed()) {
            $bot->setAdminOnly($botTransfer->adminOnly());
        }

        if ($botTransfer->adminIdsPassed()) {
            $bot->setAdminIds($botTransfer->getAdminIds());
        }
        if ($botTransfer->singleChannelPassed()) {
            $bot->setSingleChannel($botTransfer->singleChannel());
        }

        $this->validator->validateTelegramBot($bot);
    }
}