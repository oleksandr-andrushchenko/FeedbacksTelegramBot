<?php

declare(strict_types=1);

namespace App\Service\Feedback\SearchTerm;

use App\Entity\Feedback\Feedback;
use App\Entity\Feedback\FeedbackSearch;
use App\Object\Feedback\SearchTermTransfer;
use App\Object\Messenger\MessengerUserTransfer;

class SearchTermByFeedbackSearchProvider
{
    public function getSearchTermByFeedbackSearch(FeedbackSearch $feedbackSearch): SearchTermTransfer
    {
        return (new SearchTermTransfer($feedbackSearch->getSearchTermText()))
            ->setType($feedbackSearch->getSearchTermType())
            ->setMessenger($feedbackSearch->getSearchTermMessenger())
            // todo:
            ->setMessengerProfileUrl(null)
            ->setMessengerUsername($feedbackSearch->getSearchTermMessengerUsername())
            ->setMessengerUser(
                $feedbackSearch->getSearchTermMessengerUser() === null ? null : new MessengerUserTransfer(
                    $feedbackSearch->getSearchTermMessengerUser()->getMessenger(),
                    $feedbackSearch->getSearchTermMessengerUser()->getIdentifier(),
                    $feedbackSearch->getSearchTermMessengerUser()->getUsername(),
                    $feedbackSearch->getSearchTermMessengerUser()->getName(),
                    $feedbackSearch->getSearchTermMessengerUser()->getUser()->getCountryCode(),
                    $feedbackSearch->getSearchTermMessengerUser()->getLocaleCode(),
                    $feedbackSearch->getSearchTermMessengerUser()->getUser()->getCurrencyCode()
                )
            )
        ;
    }
}