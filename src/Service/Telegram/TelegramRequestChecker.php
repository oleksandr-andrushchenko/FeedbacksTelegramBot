<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Telegram\TelegramRequest;
use App\Exception\Telegram\TelegramException;
use App\Repository\Telegram\TelegramRequestRepository;
use Doctrine\ORM\EntityManagerInterface;

class TelegramRequestChecker
{
    public function __construct(
        private readonly TelegramRequestRepository $requestRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly bool $saveOnly = false,
        private readonly int $waitingTimeout = 60,
        private readonly int $intervalBetweenChecks = 1,
        private readonly array $methodWhitelist = [
            'sendMessage',
            'forwardMessage',
            'copyMessage',
            'sendPhoto',
            'sendAudio',
            'sendDocument',
            'sendSticker',
            'sendVideo',
            'sendAnimation',
            'sendVoice',
            'sendVideoNote',
            'sendMediaGroup',
            'sendLocation',
            'editMessageLiveLocation',
            'stopMessageLiveLocation',
            'sendVenue',
            'sendContact',
            'sendPoll',
            'sendDice',
            'sendInvoice',
            'sendGame',
            'setGameScore',
            'setMyCommands',
            'deleteMyCommands',
            'editMessageText',
            'editMessageCaption',
            'editMessageMedia',
            'editMessageReplyMarkup',
            'stopPoll',
            'setChatTitle',
            'setChatDescription',
            'setChatStickerSet',
            'deleteChatStickerSet',
            'setPassportDataErrors',
        ],
    )
    {
    }

    /**
     * @param Telegram $telegram
     * @param string $method
     * @param mixed $data
     * @return TelegramRequest|null
     * @throws TelegramException
     */
    public function checkTelegramRequest(Telegram $telegram, string $method, mixed $data): ?TelegramRequest
    {
        if (!$telegram->getBot()->checkRequests()) {
            return null;
        }

        if (!is_array($data)) {
            return null;
        }

        $chatId = $data['chat_id'] ?? null;
        $inlineMessageId = $data['inline_message_id'] ?? null;

        if ($chatId === null && $inlineMessageId === null) {
            return null;
        }

        if (!in_array($method, $this->methodWhitelist, true)) {
            return null;
        }

        if (!$this->saveOnly) {
            $timeout = $this->waitingTimeout;

            while (true) {
                if ($timeout <= 0) {
                    // todo: use specific
                    throw new TelegramException('Timed out while waiting for a request spot!');
                }

                $limits = $this->requestRepository->getLimits($chatId, $inlineMessageId);

                if ($limits === null) {
                    break;
                }

                // No more than one message per second inside a particular chat
                $chatPerSecond = $limits->getPerSecond() === 0;

                // No more than 30 messages per second to different chats
                $globalPerSecond = $limits->getPerSecondAll() < 30;

                // No more than 20 messages per minute in groups and channels
                $groupsPerMinute = ((is_numeric($chatId) && $chatId > 0) || $inlineMessageId !== null) || ((!is_numeric($chatId) || $chatId < 0) && $limits->getPerMinute() < 20);

                if ($chatPerSecond && $globalPerSecond && $groupsPerMinute) {
                    break;
                }

                $timeout--;

                usleep($this->intervalBetweenChecks * 1000000);
            }
        }

        $request = new TelegramRequest(
            $method,
            $chatId,
            $inlineMessageId,
            $data,
            $telegram->getBot()
        );
        $this->entityManager->persist($request);

        return $request;
    }
}