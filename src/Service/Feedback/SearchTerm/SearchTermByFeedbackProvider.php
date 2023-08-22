<?php

declare(strict_types=1);

namespace App\Service\Feedback\SearchTerm;

use App\Entity\Feedback\Feedback;
use App\Object\Feedback\SearchTermTransfer;
use App\Object\Messenger\MessengerUserTransfer;

class SearchTermByFeedbackProvider
{
    public function getSearchTermByFeedback(Feedback $feedback): SearchTermTransfer
    {
        return (new SearchTermTransfer($feedback->getSearchTermText()))
            ->setType($feedback->getSearchTermType())
            ->setMessenger($feedback->getSearchTermMessenger())
            // todo:
            ->setMessengerProfileUrl(null)
            ->setMessengerUsername($feedback->getSearchTermMessengerUsername())
            ->setMessengerUser(
                $feedback->getSearchTermMessengerUser() === null ? null : new MessengerUserTransfer(
                    $feedback->getSearchTermMessengerUser()->getMessenger(),
                    $feedback->getSearchTermMessengerUser()->getIdentifier(),
                    $feedback->getSearchTermMessengerUser()->getUsername(),
                    $feedback->getSearchTermMessengerUser()->getName(),
                    $feedback->getSearchTermMessengerUser()->getUser()->getCountryCode(),
                    $feedback->getSearchTermMessengerUser()->getLocaleCode(),
                    $feedback->getSearchTermMessengerUser()->getUser()->getCurrencyCode()
                )
            )
        ;
    }
}