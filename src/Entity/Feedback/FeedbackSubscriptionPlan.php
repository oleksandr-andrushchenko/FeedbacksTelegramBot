<?php

declare(strict_types=1);

namespace App\Entity\Feedback;

use App\Enum\Feedback\FeedbackSubscriptionPlanName;

readonly class FeedbackSubscriptionPlan
{
    public function __construct(
        private FeedbackSubscriptionPlanName $name,
        private string $datetimeModifier,
        private float $defaultPrice,
        private array $prices,
        private array $countries,
    )
    {
    }

    public function getName(): FeedbackSubscriptionPlanName
    {
        return $this->name;
    }

    public function getDatetimeModifier(): string
    {
        return $this->datetimeModifier;
    }

    public function getDefaultPrice(): float
    {
        return $this->defaultPrice;
    }

    /**
     * @return float[]
     */
    public function getPrices(): array
    {
        return $this->prices;
    }

    public function getCountries(): array
    {
        return $this->countries;
    }

    public function getPrice(string $countryFilter = null): float
    {
        if ($countryFilter !== null) {
            foreach ($this->getPrices() as $country => $price) {
                if ($country === $countryFilter) {
                    return $price;
                }
            }
        }

        return $this->getDefaultPrice();
    }
}
