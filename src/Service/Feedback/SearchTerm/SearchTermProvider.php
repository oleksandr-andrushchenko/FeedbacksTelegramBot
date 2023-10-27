<?php

declare(strict_types=1);

namespace App\Service\Feedback\SearchTerm;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Messenger\MessengerUser;
use App\Enum\Feedback\SearchTermType;
use App\Transfer\Feedback\SearchTermTransfer;
use App\Transfer\Messenger\MessengerUserTransfer;

class SearchTermProvider
{
    public function getSearchTerm(
        string $text,
        ?SearchTermType $type,
        ?string $normalizedText,
        ?MessengerUser $messengerUser
    ): SearchTermTransfer
    {
        $searchTerm = new SearchTermTransfer($text, type: $type, normalizedText: $normalizedText);

        if ($messengerUser !== null) {
            $searchTerm->setMessengerUser(new MessengerUserTransfer(
                $messengerUser->getMessenger(),
                $messengerUser->getIdentifier(),
                username: $messengerUser->getUsername(),
                name: $messengerUser->getName(),
                countryCode: $messengerUser->getUser()->getCountryCode(),
                localeCode: $messengerUser->getUser()->getLocaleCode(),
                currencyCode: $messengerUser->getUser()->getCurrencyCode()
            ));
        }

        return $searchTerm;
    }

    public function getSearchTermByFeedbackSearchTerm(FeedbackSearchTerm $searchTerm): SearchTermTransfer
    {
        return $this->getSearchTerm(
            $searchTerm->getText(),
            $searchTerm->getType(),
            $searchTerm->getNormalizedText(),
            $searchTerm->getMessengerUser()
        );
    }
}