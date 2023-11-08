<?php

declare(strict_types=1);

namespace App\Message\CommandHandler;

use App\Entity\Feedback\Feedback;
use App\Entity\Feedback\FeedbackLookup;
use App\Entity\Feedback\FeedbackLookupTelegramNotification;
use App\Entity\Feedback\FeedbackSearch;
use App\Entity\Feedback\FeedbackSearchSearchTermTelegramNotification;
use App\Entity\Feedback\FeedbackSearchTermTelegramNotification;
use App\Entity\Feedback\FeedbackSearchTelegramNotification;
use App\Entity\Feedback\FeedbackTelegramNotification;
use App\Entity\Telegram\TelegramBotPayment;
use App\Entity\User\UserContactMessage;
use App\Message\Command\NotifyActivityAdminsCommand;
use App\Repository\Feedback\FeedbackLookupRepository;
use App\Repository\Feedback\FeedbackLookupTelegramNotificationRepository;
use App\Repository\Feedback\FeedbackRepository;
use App\Repository\Feedback\FeedbackSearchRepository;
use App\Repository\Feedback\FeedbackSearchSearchTermTelegramNotificationRepository;
use App\Repository\Feedback\FeedbackSearchTermTelegramNotificationRepository;
use App\Repository\Feedback\FeedbackSearchTelegramNotificationRepository;
use App\Repository\Feedback\FeedbackTelegramNotificationRepository;
use App\Repository\Telegram\Bot\TelegramBotPaymentRepository;
use App\Repository\User\UserContactMessageRepository;
use Psr\Log\LoggerInterface;
use Throwable;

class NotifyActivityAdminsCommandHandler
{
    public function __construct(
        private readonly FeedbackRepository $feedbackRepository,
        private readonly FeedbackSearchRepository $feedbackSearchRepository,
        private readonly FeedbackSearchTermTelegramNotificationRepository $feedbackSearchTermUserTelegramNotificationRepository,
        private readonly FeedbackSearchSearchTermTelegramNotificationRepository $feedbackSearchSearchTermUserTelegramNotificationRepository,
        private readonly FeedbackSearchTelegramNotificationRepository $feedbackSearchUserTelegramNotificationRepository,
        private readonly FeedbackLookupTelegramNotificationRepository $feedbackLookupUserTelegramNotificationRepository,
        private readonly FeedbackLookupRepository $feedbackLookupRepository,
        private readonly FeedbackTelegramNotificationRepository $feedbackTelegramNotificationRepository,
        private readonly TelegramBotPaymentRepository $telegramBotPaymentRepository,
        private readonly UserContactMessageRepository $userContactMessageRepository,
        private readonly LoggerInterface $activityLogger,
        private readonly LoggerInterface $logger,
    )
    {
    }

    public function __invoke(NotifyActivityAdminsCommand $command): void
    {
        if ($command->getEntity() === null) {
            $repository = match ($command->getEntityClass()) {
                Feedback::class => $this->feedbackRepository,
                FeedbackSearch::class => $this->feedbackSearchRepository,
                FeedbackSearchTermTelegramNotification::class => $this->feedbackSearchTermUserTelegramNotificationRepository,
                FeedbackSearchSearchTermTelegramNotification::class => $this->feedbackSearchSearchTermUserTelegramNotificationRepository,
                FeedbackSearchTelegramNotification::class => $this->feedbackSearchUserTelegramNotificationRepository,
                FeedbackLookupTelegramNotification::class => $this->feedbackLookupUserTelegramNotificationRepository,
                FeedbackTelegramNotification::class => $this->feedbackTelegramNotificationRepository,
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