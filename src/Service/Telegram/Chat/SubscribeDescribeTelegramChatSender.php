<?php

declare(strict_types=1);

namespace App\Service\Telegram\Chat;

use App\Entity\CommandOptions;
use App\Service\Telegram\TelegramAwareHelper;

class SubscribeDescribeTelegramChatSender
{
    public function __construct(
        private readonly CommandOptions $createOptions,
        private readonly CommandOptions $searchOptions,
        private readonly CommandOptions $lookupOptions,
    )
    {
    }

    public function sendSubscribeDescribe(TelegramAwareHelper $tg): null
    {
        return $tg->reply($tg->view('describe_subscribe', [
            'commands' => [
                'create' => $this->createOptions->getLimits(),
                'search' => $this->searchOptions->getLimits(),
                'lookup' => $this->lookupOptions->getLimits(),
            ],
        ]))->null();
    }
}
