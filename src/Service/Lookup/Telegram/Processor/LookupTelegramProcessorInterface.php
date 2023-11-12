<?php

declare(strict_types=1);

namespace App\Service\Lookup\Telegram\Processor;

use App\Entity\Feedback\FeedbackSearch;
use App\Entity\Lookup\LookupProcessorResult;
use App\Service\Telegram\Bot\TelegramBotAwareHelper;

interface LookupTelegramProcessorInterface
{
    public function lookupByFeedbackSearch(FeedbackSearch $feedbackSearch, TelegramBotAwareHelper $tg): ?LookupProcessorResult;
}
