<?php

declare(strict_types=1);

namespace App\Message\EventHandler;

use App\Entity\Feedback\Feedback;
use App\Entity\Feedback\FeedbackLookup;
use App\Entity\Feedback\FeedbackNotification;
use App\Entity\Feedback\FeedbackSearch;
use App\Entity\Telegram\TelegramBotPayment;
use App\Entity\User\UserContactMessage;
use App\Message\Event\ActivityEvent;
use App\Repository\Feedback\FeedbackLookupRepository;
use App\Repository\Feedback\FeedbackNotificationRepository;
use App\Repository\Feedback\FeedbackRepository;
use App\Repository\Feedback\FeedbackSearchRepository;
use App\Repository\Telegram\Bot\TelegramBotPaymentRepository;
use App\Repository\User\UserContactMessageRepository;
use Psr\Log\LoggerInterface;
use Throwable;

class ActivityEventHandler
{
    public function __construct(
        private readonly FeedbackRepository $feedbackRepository,
        private readonly FeedbackSearchRepository $feedbackSearchRepository,
        private readonly FeedbackLookupRepository $feedbackLookupRepository,
        private readonly FeedbackNotificationRepository $feedbackNotificationRepository,
        private readonly TelegramBotPaymentRepository $telegramBotPaymentRepository,
        private readonly UserContactMessageRepository $userContactMessageRepository,
        private readonly LoggerInterface $activityLogger,
        private readonly LoggerInterface $logger,
    )
    {
    }

    public function __invoke(ActivityEvent $event): void
    {
        if ($event->getEntity() === null) {
            $repository = match ($event->getEntityClass()) {
                Feedback::class => $this->feedbackRepository,
                FeedbackSearch::class => $this->feedbackSearchRepository,
                FeedbackLookup::class => $this->feedbackLookupRepository,
                FeedbackNotification::class => $this->feedbackNotificationRepository,
                TelegramBotPayment::class => $this->telegramBotPaymentRepository,
                UserContactMessage::class => $this->userContactMessageRepository,
                default => null,
            };

            if ($repository === null) {
                $this->logger->warning(
                    sprintf(
                        'No repository was found for %s entity class',
                        $event->getEntityClass()
                    )
                );
                return;
            }

            $entity = $repository->find($event->getEntityId());
        } else {
            $entity = $event->getEntity();
        }

        if ($entity === null) {
            $this->logger->warning(
                sprintf(
                    'No entity was found in %s for %s & %s id',
                    __CLASS__,
                    $event->getEntityClass(),
                    $event->getEntityId()
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