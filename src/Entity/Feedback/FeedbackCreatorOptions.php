<?php

declare(strict_types=1);

namespace App\Entity\Feedback;

/**
 * todo: params though constructor & use factory method (static method & call in services)
 */
readonly class FeedbackCreatorOptions
{
    private bool $userTargetMessengerRequired;

    public function __construct(
        array $options,
    )
    {
        $this->userTargetMessengerRequired = $options['user_target_messenger_required'];
    }

    public function userTargetMessengerRequired(): bool
    {
        return $this->userTargetMessengerRequired;
    }
}
