<?php

declare(strict_types=1);

namespace App\Controller\Telegram;

use App\Exception\Telegram\TelegramNotFoundException;
use App\Exception\Telegram\TelegramOptionsNotFoundException;
use App\Service\Telegram\TelegramRegistry;
use App\Service\Telegram\TelegramUpdateHandler;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TelegramController
{
    public function __construct(
        private readonly TelegramRegistry $telegramRegistry,
        private readonly TelegramUpdateHandler $telegramUpdateHandler,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    )
    {
    }

    public function webhook(string $username, Request $request): Response
    {
        try {
            $telegram = $this->telegramRegistry->getTelegram($username);

            // todo: push to ordered queue (amqp)
            $this->telegramUpdateHandler->handleTelegramUpdate($telegram, $request);
            $this->entityManager->flush();

            return new Response('ok');
        } catch (TelegramNotFoundException|TelegramOptionsNotFoundException $exception) {
            $this->logger->error($exception);

            return new Response('failed', Response::HTTP_NOT_FOUND);
        } catch (Throwable $exception) {
            $this->logger->error($exception);

            return new Response('failed');
        }
    }
}
