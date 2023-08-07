<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Entity\Feedback\FeedbackUserSubscription;
use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramPayment;
use App\Enum\Feedback\FeedbackSubscriptionPlanName;
use App\Repository\Feedback\FeedbackUserSubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use DateTime;

class FeedbackUserSubscriptionManager
{
    public function __construct(
        private readonly FeedbackSubscriptionPlanProvider $subscriptionPlanProvider,
        private readonly FeedbackUserSubscriptionRepository $subscriptionRepository,
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }

    public function createByTelegramPayment(TelegramPayment $telegramPayment): FeedbackUserSubscription
    {
        $subscriptionPlanName = FeedbackSubscriptionPlanName::fromName($telegramPayment->getPurpose());
        $subscriptionPlan = $this->subscriptionPlanProvider->getSubscriptionPlan($subscriptionPlanName);

        $subscription = new FeedbackUserSubscription(
            $telegramPayment->getMessengerUser(),
            $subscriptionPlan->getName(),
            (new DateTime())->modify($subscriptionPlan->getDatetimeModifier()),
            $telegramPayment
        );
        $this->entityManager->persist($subscription);

        $telegramPayment->getMessengerUser()->getUser()?->setSubscriptionExpireAt($subscription->getExpireAt());

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
        return new DateTime() < $subscription->getExpireAt();
    }

    public function hasActiveSubscription(MessengerUser $messengerUser): bool
    {
        if ($messengerUser->getUser()?->getSubscriptionExpireAt() === null) {
            return false;
        }

        return new DateTime() < $messengerUser->getUser()->getSubscriptionExpireAt();
    }

    public function hasSubscription(MessengerUser $messengerUser): bool
    {
        return $messengerUser->getUser()?->getSubscriptionExpireAt() !== null;
    }
}