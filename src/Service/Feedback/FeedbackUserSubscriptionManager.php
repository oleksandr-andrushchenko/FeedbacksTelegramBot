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
        private readonly FeedbackUserSubscriptionRepository $userSubscriptionRepository,
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }

    public function createByTelegramPayment(TelegramPayment $telegramPayment): FeedbackUserSubscription
    {
        $subscriptionPlanName = FeedbackSubscriptionPlanName::fromName($telegramPayment->getPurpose());
        $subscriptionPlan = $this->subscriptionPlanProvider->getSubscriptionPlan($subscriptionPlanName);

        $userSubscription = new FeedbackUserSubscription(
            $telegramPayment->getMessengerUser(),
            $subscriptionPlan->getName(),
            (new DateTime())->modify($subscriptionPlan->getDatetimeModifier()),
            $telegramPayment
        );
        $this->entityManager->persist($userSubscription);

        return $userSubscription;
    }

    /**
     * @param MessengerUser $messengerUser
     * @return FeedbackUserSubscription[]
     */
    public function getSubscriptions(MessengerUser $messengerUser): array
    {
        return $this->userSubscriptionRepository->findBy([
            'messengerUser' => $messengerUser,
        ]);
    }

    public function getActiveSubscription(MessengerUser $messengerUser): ?FeedbackUserSubscription
    {
        $userSubscriptions = $this->getSubscriptions($messengerUser);

        if (count($userSubscriptions) === 0) {
            return null;
        }

        foreach ($userSubscriptions as $userSubscription) {
            if ($this->isSubscriptionActive($userSubscription)) {
                return $userSubscription;
            }
        }

        return null;
    }

    public function isSubscriptionActive(FeedbackUserSubscription $userSubscription): bool
    {
        return new DateTime() < $userSubscription->getExpireAt();
    }

    public function hasActiveSubscription(MessengerUser $messengerUser): bool
    {
        // todo: optimize (create separate user.premium_expire_at column)
        return $this->getActiveSubscription($messengerUser) !== null;
    }
}