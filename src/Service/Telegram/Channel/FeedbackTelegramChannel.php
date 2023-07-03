<?php

declare(strict_types=1);

namespace App\Service\Telegram\Channel;

use App\Service\Telegram\Conversation\ChooseFeedbackActionTelegramConversation;
use App\Service\Telegram\Conversation\CreateFeedbackTelegramConversation;
use App\Service\Telegram\Conversation\SearchFeedbackTelegramConversation;
use App\Service\Telegram\FallbackTelegramCommand;
use App\Service\Telegram\TelegramCommand;
use App\Service\Telegram\TelegramAwareHelper;

class FeedbackTelegramChannel extends TelegramChannel implements TelegramChannelInterface
{
    public const START = '/start';
    public const CREATE_FEEDBACK = '/add';
    public const SEARCH_FEEDBACK = '/find';
    public const RESTART = '/restart';

    protected function getCommands(TelegramAwareHelper $tg): iterable
    {
        yield new TelegramCommand(self::START, fn () => $tg->startConversation(ChooseFeedbackActionTelegramConversation::class));
        yield new TelegramCommand(self::CREATE_FEEDBACK, fn () => $tg->startConversation(CreateFeedbackTelegramConversation::class), key: 'create_feedback');
        yield new TelegramCommand(self::SEARCH_FEEDBACK, fn () => $tg->startConversation(SearchFeedbackTelegramConversation::class), key: 'search_feedback');
        yield new TelegramCommand(self::RESTART, fn () => $tg->stopConversations()->replyOk('feedbacks.reply.restart.ok')->startConversation(ChooseFeedbackActionTelegramConversation::class), key: 'restart_feedbacks', beforeConversations: true);
        // todo: "who've been looking for me" command
        // todo: "list my feedbacks" command
        // todo: "list feedbacks on me" command
        // todo: "subscribe on mine/somebodies feedbacks" command

        yield new FallbackTelegramCommand(fn () => $tg->replyWrong()->startConversation(ChooseFeedbackActionTelegramConversation::class));
    }
}