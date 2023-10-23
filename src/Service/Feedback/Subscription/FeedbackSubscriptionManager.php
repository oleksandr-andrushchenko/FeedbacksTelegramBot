<?php

declare(strict_types=1);

namespace App\Service\Feedback\Subscription;

use App\Entity\Feedback\FeedbackUserSubscription;
use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBotPayment;
use App\Enum\Feedback\FeedbackSubscriptionPlanName;
use App\Message\Event\Feedback\FeedbackUserSubscriptionCreatedEvent;
use App\Repository\Feedback\FeedbackUserSubscriptionRepository;
use App\Service\IdGenerator;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class FeedbackSubscriptionManager
{
    public function __construct(
        private readonly FeedbackSubscriptionPlanProvider $subscriptionPlanProvider,
        private readonly FeedbackUserSubscriptionRepository $subscriptionRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly IdGenerator $idGenerator,
        private readonly MessageBusInterface $eventBus,
    )
    {
    }

    public function createByTelegramPayment(TelegramBotPayment $payment): FeedbackUserSubscription
    {
        $subscriptionPlanName = FeedbackSubscriptionPlanName::fromName($payment->getPurpose());
        $subscriptionPlan = $this->subscriptionPlanProvider->getSubscriptionPlan($subscriptionPlanName);

        $subscription = new FeedbackUserSubscription(
            $this->idGenerator->generateId(),
            $payment->getMessengerUser(),
            $subscriptionPlan->getName(),
            (new DateTimeImmutable())->modify($subscriptionPlan->getDatetimeModifier()),
            $payment
        );
        $this->entityManager->persist($subscription);

        $this->eventBus->dispatch(new FeedbackUserSubscriptionCreatedEvent(subscription: $subscription));

        $payment->getMessengerUser()?->getUser()?->setSubscriptionExpireAt($subscription->getExpireAt());

        return $subscription;
    }

    /**
     * @param MessengerUser $messengerUser
     * @return FeedbackUserSubscription[]
     */
    public function getSubscriptions(MessengerUser $messengerUser): array
    {
        return $this->subscriptionRepository->findBy([
            'messengerUser' => $messengerUser,
        ]);
    }

    public function getActiveSubscription(MessengerUser $messengerUser): ?FeedbackUserSubscription
    {
        $subscriptions = $this->getSubscriptions($messengerUser);

        if (count($subscriptions) === 0) {
            return null;
        }

        foreach ($subscriptions as $subscription) {
            if ($this->isSubscriptionActive($subscription)) {
                return $subscription;
            }
        }

        return null;
    }

    public function isSubscriptionActive(FeedbackUserSubscription $subscription): bool
    {
        return new DateTimeImmutable() < $subscription->getExpireAt();
    }

    public function hasActiveSubscription(MessengerUser $messengerUser): bool
    {
        if ($messengerUser->getUser()?->getSubscriptionExpireAt() === null) {
            return false;
        }

        return new DateTimeImmutable() < $messengerUser->getUser()->getSubscriptionExpireAt();
    }

    public function hasSubscription(MessengerUser $messengerUser): bool
    {
        return $messengerUser->getUser()?->getSubscriptionExpireAt() !== null;
    }
}