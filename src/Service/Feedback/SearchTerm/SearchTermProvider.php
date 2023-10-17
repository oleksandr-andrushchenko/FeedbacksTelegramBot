<?php

declare(strict_types=1);

namespace App\Service\Feedback\SearchTerm;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Messenger\MessengerUser;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Messenger\Messenger;
use App\Transfer\Feedback\SearchTermTransfer;
use App\Transfer\Messenger\MessengerUserTransfer;
use App\Service\Messenger\MessengerUserProfileUrlProvider;

class SearchTermProvider
{
    public function __construct(
        private readonly MessengerUserProfileUrlProvider $messengerUserProfileUrlProvider,
    )
    {
    }

    public function getSearchTerm(
        string $text,
        ?SearchTermType $type,
        ?Messenger $messenger,
        ?string $messengerUsername,
        ?MessengerUser $messengerUser
    ): SearchTermTransfer
    {
        $messengerProfileUrl = null;

        if ($messengerUser === null) {
            $messengerUserTransfer = null;
        } else {
            $messengerUserTransfer = new MessengerUserTransfer(
                $messengerUser->getMessenger(),
                $messengerUser->getIdentifier(),
                username: $messengerUser->getUsername(),
                name: $messengerUser->getName(),
                countryCode: $messengerUser->getUser()->getCountryCode(),
                localeCode: $messengerUser->getUser()->getLocaleCode(),
                currencyCode: $messengerUser->getUser()->getCurrencyCode()
            );

            $messengerProfileUrl = $this->messengerUserProfileUrlProvider->getMessengerUserProfileUrlByUser($messengerUserTransfer);
        }

        if ($messengerProfileUrl === null && $messenger !== Messenger::unknown && $messengerUsername !== null) {
            $messengerProfileUrl = $this->messengerUserProfileUrlProvider->getMessengerUserProfileUrl($messenger, $messengerUsername);
        }

        return (new SearchTermTransfer($text))
            ->setType($type)
            ->setMessenger($messenger)
            ->setMessengerProfileUrl($messengerProfileUrl)
            ->setMessengerUsername($messengerUsername)
            ->setMessengerUser($messengerUserTransfer)
        ;
    }

    public function getSearchTermByFeedbackSearchTerm(FeedbackSearchTerm $searchTerm): SearchTermTransfer
    {
        return $this->getSearchTerm(
            $searchTerm->getText(),
            $searchTerm->getType(),
            $searchTerm->getMessenger(),
            $searchTerm->getMessengerUsername(),
            $searchTerm->getMessengerUser()
        );
    }
}