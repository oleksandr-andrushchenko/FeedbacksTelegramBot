<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use App\Entity\Telegram\TelegramBot;
use App\Transfer\Telegram\TelegramBotTransfer;
use DateTimeImmutable;

class TelegramBotUpdater
{
    public function __construct(
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
            $syncTexts = $bot->getName() !== $botTransfer->getName();
            $bot->setName($botTransfer->getName());
            $bot->setDescriptionsSynced($syncTexts);
        }

        if ($botTransfer->tokenPassed()) {
            $bot->setToken($botTransfer->getToken());
        }

        if ($botTransfer->countryPassed()) {
            $bot->setCountryCode($botTransfer->getCountry()->getCode());
        }
        if ($botTransfer->localePassed()) {
            $bot->setLocaleCode($botTransfer->getLocale()->getCode());
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
        if ($botTransfer->primaryPassed()) {
            $bot->setPrimary($botTransfer->primary());
        }

        $bot->setUpdatedAt(new DateTimeImmutable());

        $this->validator->validateTelegramBot($bot);
    }
}