<?php

declare(strict_types=1);

namespace App\Message\EventHandler\User;

use App\Message\Event\User\UserContactMessageCreatedEvent;
use App\Repository\User\UserContactMessageRepository;
use Psr\Log\LoggerInterface;
use Throwable;

class UserContactMessageCreatedActivityLogger
{
    public function __construct(
        private readonly UserContactMessageRepository $messageRepository,
        private readonly LoggerInterface $activityLogger,
        private readonly LoggerInterface $logger,
    )
    {
    }

    public function __invoke(UserContactMessageCreatedEvent $event): void
    {
        $message = $event->getMessage() ?? $this->messageRepository->find($event->getMessageId());

        if ($message === null) {
            $this->logger->warning(sprintf('No user contact message was found in %s for %s id', __CLASS__, $event->getMessageId()));
            return;
        }

        try {
            $this->activityLogger->info($message);
        } catch (Throwable $exception) {
            $this->logger->error($exception);
        }
    }
}