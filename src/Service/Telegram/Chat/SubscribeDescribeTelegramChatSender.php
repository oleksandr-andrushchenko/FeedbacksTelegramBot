<?php

declare(strict_types=1);

namespace App\Service\Telegram\Chat;

use App\Entity\Feedback\FeedbackCreatorOptions;
use App\Entity\Feedback\FeedbackSearchCreatorOptions;
use App\Enum\Telegram\TelegramView;
use App\Service\Telegram\Channel\FeedbackTelegramChannel;
use App\Service\Telegram\TelegramAwareHelper;

class SubscribeDescribeTelegramChatSender
{
    public function __construct(
        private readonly FeedbackCreatorOptions $creatorOptions,
        private readonly FeedbackSearchCreatorOptions $searchCreatorOptions,
    )
    {
    }

    public function sendSubscribeDescribe(TelegramAwareHelper $tg): null
    {
        return $tg->reply($tg->view(TelegramView::DESCRIBE_SUBSCRIBE, [
            'commands' => [
                'create' => [
                    'command' => FeedbackTelegramChannel::CREATE,
                    'limits' => [
                        'day' => $this->creatorOptions->userPerDayLimit(),
                        'month' => $this->creatorOptions->userPerMonthLimit(),
                        'year' => $this->creatorOptions->userPerYearLimit(),
                    ],
                ],
                'search' => [
                    'command' => FeedbackTelegramChannel::SEARCH,
                    'limits' => [
                        'day' => $this->searchCreatorOptions->userPerDayLimit(),
                        'month' => $this->searchCreatorOptions->userPerMonthLimit(),
                        'year' => $this->searchCreatorOptions->userPerYearLimit(),
                    ],
                ],
            ],
        ]), parseMode: 'HTML')->null();
    }
}
