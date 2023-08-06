<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Entity\Feedback\FeedbackSubscriptionPlan;
use App\Enum\Feedback\FeedbackSubscriptionPlanName;

class FeedbackSubscriptionPlanProvider
{
    public function __construct(
        private readonly array $sourceSubscriptionPlans,
        private ?array $subscriptionPlans = null,
    )
    {
    }

    /**
     * @param string|null $country
     * @return FeedbackSubscriptionPlan[]
     */
    public function getSubscriptionPlans(string $country = null): array
    {
        if ($this->subscriptionPlans === null) {
            $subscriptionPlans = [];

            foreach ($this->sourceSubscriptionPlans as $subscriptionPlanName => $subscriptionPlan) {
                $subscriptionPlans[] = new FeedbackSubscriptionPlan(
                    FeedbackSubscriptionPlanName::fromName($subscriptionPlanName),
                    $subscriptionPlan['duration'],
                    $subscriptionPlan['default_price'],
                    $subscriptionPlan['prices'],
                    $subscriptionPlan['countries'] ?? [],
                );
            }

            $this->subscriptionPlans = $subscriptionPlans;
        }

        $subscriptionPlans = $this->subscriptionPlans;

        if ($country !== null) {
            $subscriptionPlans = array_filter(
                $subscriptionPlans,
                fn (FeedbackSubscriptionPlan $subscriptionPlan) => $subscriptionPlan->isGlobal() || in_array($country, $subscriptionPlan->getCountries(), true)
            );
        }

        return $subscriptionPlans;
    }

    public function getSubscriptionPlan(FeedbackSubscriptionPlanName $subscriptionPlanName): ?FeedbackSubscriptionPlan
    {
        foreach ($this->getSubscriptionPlans() as $subscriptionPlan) {
            if ($subscriptionPlan->getName() === $subscriptionPlanName) {
                return $subscriptionPlan;
            }
        }

        return null;
    }
}