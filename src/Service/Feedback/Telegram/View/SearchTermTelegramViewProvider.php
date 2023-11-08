<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\View;

use App\Enum\Feedback\SearchTermType;
use App\Enum\Messenger\Messenger;
use App\Service\Feedback\SearchTerm\SearchTermMessengerProvider;
use App\Service\Feedback\SearchTerm\SearchTermTypeProvider;
use App\Service\Messenger\MessengerUserProfileUrlProvider;
use App\Service\Util\String\SecretsAdder;
use App\Transfer\Feedback\SearchTermTransfer;

class SearchTermTelegramViewProvider
{
    public function __construct(
        private readonly SearchTermTypeProvider $searchTermTypeProvider,
        private readonly MessengerUserProfileUrlProvider $messengerUserProfileUrlProvider,
        private readonly SearchTermMessengerProvider $searchTermMessengerProvider,
        private readonly SecretsAdder $secretsAdder,
    )
    {
    }

    public function getSearchTermTelegramMainView(SearchTermTransfer $searchTerm, bool $addSecrets = false): string
    {
        $message = '<u><b>';

        $messenger = $this->searchTermMessengerProvider->getSearchTermMessenger($searchTerm->getType());
        // todo: add search term formatter & implement it here and everywhere
        $text = $searchTerm->getNormalizedText() ?? $searchTerm->getText();

        if (!in_array($messenger, [null, Messenger::unknown])) {
            $url = $this->messengerUserProfileUrlProvider->getMessengerUserProfileUrl($messenger, $text);
            $message .= sprintf('<a href="%s">%s</a>', $url, $text);
        } elseif (in_array($searchTerm->getType(), [SearchTermType::url, SearchTermType::messenger_profile_url], true)) {
            $message .= sprintf('<a href="%s">%s</a>', $searchTerm->getText(), $text);
        } else {
            $secretTypes = [
                SearchTermType::phone_number,
                SearchTermType::email,
                SearchTermType::car_number,
            ];

            if ($addSecrets && in_array($searchTerm->getType(), $secretTypes, true)) {
                if ($searchTerm->getType() === SearchTermType::phone_number) {
                    $position = 4;
                } else {
                    $position = 2;
                }

                $message .= $this->secretsAdder->addSecrets($text, position: $position);
            } else {
                $message .= $text;
            }
        }

        $message .= '</b></u>';

        return $message;
    }

    public function getSearchTermTelegramView(
        SearchTermTransfer $searchTerm,
        bool $addSecrets = false,
        bool $forceType = true,
        string $localeCode = null
    ): string
    {
        $message = $this->getSearchTermTelegramMainView($searchTerm, addSecrets: $addSecrets);

        $skipTypes = [
            SearchTermType::person_name,
            SearchTermType::email,
            SearchTermType::url,
            ...SearchTermType::known_messengers,
        ];

        if ($searchTerm->getType() !== null && ($forceType || !in_array($searchTerm->getType(), $skipTypes, true))) {
            $message .= ' [ ';
            $message .= $this->searchTermTypeProvider->getSearchTermTypeName($searchTerm->getType(), $localeCode);
            $message .= ' ]';
        }

        return $message;
    }

    public function getSearchTermTelegramReverseView(
        SearchTermTransfer $searchTerm,
        bool $addSecrets = false,
        string $localeCode = null
    ): string
    {
        $message = $this->searchTermTypeProvider->getSearchTermTypeComposeName($searchTerm->getType(), localeCode: $localeCode);
        $message .= ' [ ';
        $message .= $this->getSearchTermTelegramMainView($searchTerm, addSecrets: $addSecrets);
        $message .= ' ] ';

        return $message;
    }
}