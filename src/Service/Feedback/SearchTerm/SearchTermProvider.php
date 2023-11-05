<?php

declare(strict_types=1);

namespace App\Service\Feedback\SearchTerm;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Transfer\Feedback\SearchTermTransfer;
use App\Transfer\Messenger\MessengerUserTransfer;

class SearchTermProvider
{
    public function getFeedbackSearchTermTransfer(FeedbackSearchTerm $searchTerm): SearchTermTransfer
    {
        $transfer = new SearchTermTransfer(
            $searchTerm->getText(),
            type: $searchTerm->getType(),
            normalizedText: $searchTerm->getNormalizedText()
        );

        $messengerUser = $searchTerm->getMessengerUser();

        if ($messengerUser !== null) {
            $transfer->setMessengerUser(new MessengerUserTransfer(
                $messengerUser->getMessenger(),
                $messengerUser->getIdentifier(),
                username: $messengerUser->getUsername(),
                name: $messengerUser->getName(),
                countryCode: $messengerUser->getUser()->getCountryCode(),
                localeCode: $messengerUser->getUser()->getLocaleCode(),
                currencyCode: $messengerUser->getUser()->getCurrencyCode()
            ));
        }

        return $transfer;
    }
}