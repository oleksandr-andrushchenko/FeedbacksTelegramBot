<?php

declare(strict_types=1);

namespace App\Service\Feedback\SearchTerm;

use App\Entity\Feedback\Feedback;
use App\Transfer\Feedback\SearchTermTransfer;

class SearchTermByFeedbackProvider
{
    public function __construct(
        private readonly SearchTermProvider $searchTermProvider,
    )
    {
    }

    public function getSearchTermByFeedback(Feedback $feedback): SearchTermTransfer
    {
        return $this->searchTermProvider->getSearchTerm(
            $feedback->getSearchTermText(),
            $feedback->getSearchTermType(),
            $feedback->getSearchTermMessenger(),
            $feedback->getSearchTermMessengerUsername(),
            $feedback->getSearchTermMessengerUser()
        );
    }
}