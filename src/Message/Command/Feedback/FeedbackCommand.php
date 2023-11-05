<?php

declare(strict_types=1);

namespace App\Message\Command\Feedback;

use App\Message\Event\Feedback\FeedbackEvent;

abstract readonly class FeedbackCommand extends FeedbackEvent
{
}
