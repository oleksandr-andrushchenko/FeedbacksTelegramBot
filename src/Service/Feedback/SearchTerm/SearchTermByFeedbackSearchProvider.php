<?php

declare(strict_types=1);

namespace App\Service\Feedback\SearchTerm;

use App\Entity\Feedback\FeedbackSearch;
use App\Object\Feedback\SearchTermTransfer;

class SearchTermByFeedbackSearchProvider
{
    public function __construct(
        private readonly SearchTermProvider $searchTermProvider,
    )
    {
    }

    public function getSearchTermByFeedbackSearch(FeedbackSearch $feedbackSearch): SearchTermTransfer
    {
        return $this->searchTermProvider->getSearchTerm(
            $feedbackSearch->getSearchTermText(),
            $feedbackSearch->getSearchTermType(),
            $feedbackSearch->getSearchTermMessenger(),
            $feedbackSearch->getSearchTermMessengerUsername(),
            $feedbackSearch->getSearchTermMessengerUser()
        );
    }
}