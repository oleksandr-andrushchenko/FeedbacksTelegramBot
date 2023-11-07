<?php

declare(strict_types=1);

namespace App\Message\CommandHandler;

use App\Entity\Feedback\Feedback;
use App\Entity\Feedback\FeedbackLookup;
use App\Entity\Feedback\FeedbackLookupUserTelegramNotification;
use App\Entity\Feedback\FeedbackSearch;
use App\Entity\Feedback\FeedbackSearchTermUserTelegramNotification;
use App\Entity\Feedback\FeedbackSearchUserTelegramNotification;
use App\Entity\Telegram\TelegramBotPayment;
use App\Entity\User\UserContactMessage;
use App\Message\Command\NotifyActivityReceiversCommand;
use App\Repository\Feedback\FeedbackLookupRepository;
use App\Repository\Feedback\FeedbackLookupUserTelegramNotificationRepository;
use App\Repository\Feedback\FeedbackRepository;
use App\Repository\Feedback\FeedbackSearchRepository;
use App\Repository\Feedback\FeedbackSearchTermUserTelegramNotificationRepository;
use App\Repository\Feedback\FeedbackSearchUserTelegramNotificationRepository;
use App\Repository\Telegram\Bot\TelegramBotPaymentRepository;
use App\Repository\User\UserContactMessageRepository;
use Psr\Log\LoggerInterface;
use Throwable;

class NotifyActivityReceiversCommandHandler
{
    public function __construct(
        private readonly FeedbackRepository $feedbackRepository,
        private readonly FeedbackSearchRepository $feedbackSearchRepository,
        private readonly FeedbackSearchTermUserTelegramNotificationRepository $feedbackSearchTermUserTelegramNotificationRepository,
        private readonly FeedbackSearchUserTelegramNotificationRepository $feedbackSearchUserTelegramNotificationRepository,
        private readonly FeedbackLookupUserTelegramNotificationRepository $feedbackLookupUserTelegramNotificationRepository,
        private readonly FeedbackLookupRepository $feedbackLookupRepository,
        private readonly TelegramBotPaymentRepository $telegramBotPaymentRepository,
        private readonly UserContactMessageRepository $userContactMessageRepository,
        private readonly LoggerInterface $activityLogger,
        private readonly LoggerInterface $logger,
    )
    {
    }

    public function __invoke(NotifyActivityReceiversCommand $command): void
    {
        if ($command->getEntity() === null) {
            $repository = match ($command->getEntityClass()) {
                Feedback::class => $this->feedbackRepository,
                FeedbackSearch::class => $this->feedbackSearchRepository,
                FeedbackSearchTermUserTelegramNotification::class => $this->feedbackSearchTermUserTelegramNotificationRepository,
                FeedbackSearchUserTelegramNotification::class => $this->feedbackSearchUserTelegramNotificationRepository,
                FeedbackLookupUserTelegramNotification::class => $this->feedbackLookupUserTelegramNotificationRepository,
                FeedbackLookup::class => $this->feedbackLookupRepository,
                TelegramBotPayment::class => $this->telegramBotPaymentRepository,
                UserContactMessage::class => $this->userContactMessageRepository,
                default => null,
            };

            if ($repository === null) {
                $this->logger->warning(
                    sprintf(
                        'No repository was found for %s entity class',
                        $command->getEntityClass()
                    )
                );
                return;
            }

            $entity = $repository->find($command->getEntityId());
        } else {
            $entity = $command->getEntity();
        }

        if ($entity === null) {
            $this->logger->warning(
                sprintf(
                    'No entity was found in %s for %s & %s id',
                    __CLASS__,
                    $command->getEntityClass(),
                    $command->getEntityId()
                )
            );
            return;
        }

        try {
            $this->activityLogger->info($entity);
        } catch (Throwable $exception) {
            $this->logger->error($exception);
        }
    }
}