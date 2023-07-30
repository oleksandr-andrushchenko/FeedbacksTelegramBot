<?php

declare(strict_types=1);

namespace App\Service\Telegram\Chat;

use App\Entity\Feedback\FeedbackCreatorOptions;
use App\Entity\Feedback\FeedbackSearchCreatorOptions;
use App\Enum\Telegram\TelegramView;
use App\Service\Telegram\Channel\FeedbackTelegramChannel;
use App\Service\Telegram\TelegramAwareHelper;

class PremiumDescribeTelegramChatSender
{
    public function __construct(
        private readonly FeedbackCreatorOptions $creatorOptions,
        private readonly FeedbackSearchCreatorOptions $searchCreatorOptions,
    )
    {
    }

    public function sendPremiumDescribe(TelegramAwareHelper $tg): null
    {
        return $tg->replyView(TelegramView::PREMIUM, [
            'commands' => [
                'create' => [
                    'command' => FeedbackTelegramChannel::CREATE_FEEDBACK,
                    'limits' => [
                        'day' => $this->creatorOptions->userPerDayLimit(),
                        'month' => $this->creatorOptions->userPerMonthLimit(),
                        'year' => $this->creatorOptions->userPerYearLimit(),
                    ],
                ],
                'search' => [
                    'command' => FeedbackTelegramChannel::SEARCH_FEEDBACK,
                    'limits' => [
                        'day' => $this->searchCreatorOptions->userPerDayLimit(),
                        'month' => $this->searchCreatorOptions->userPerMonthLimit(),
                        'year' => $this->searchCreatorOptions->userPerYearLimit(),
                    ],
                ],
            ],
        ])->null();
    }
}
