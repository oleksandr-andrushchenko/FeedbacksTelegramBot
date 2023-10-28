<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\View;

use App\Enum\Feedback\SearchTermType;
use App\Enum\Messenger\Messenger;
use App\Service\Feedback\SearchTerm\SearchTermMessengerProvider;
use App\Service\Feedback\SearchTerm\SearchTermTypeProvider;
use App\Service\Messenger\MessengerUserProfileUrlProvider;
use App\Transfer\Feedback\SearchTermTransfer;

class SearchTermTelegramViewProvider
{
    public function __construct(
        private readonly SearchTermTypeProvider $termTypeProvider,
        private readonly MessengerUserProfileUrlProvider $messengerUserProfileUrlProvider,
        private readonly SearchTermMessengerProvider $searchTermMessengerProvider,
    )
    {
    }

    public function getSearchTermTelegramMainView(SearchTermTransfer $searchTerm): string
    {
        $message = '<u><b>';

        $messenger = $this->searchTermMessengerProvider->getSearchTermMessenger($searchTerm->getType());
        $text = $searchTerm->getNormalizedText() ?? $searchTerm->getText();

        if (!in_array($messenger, [null, Messenger::unknown])) {
            $url = $this->messengerUserProfileUrlProvider->getMessengerUserProfileUrl($messenger, $text);
            $message .= sprintf('<a href="%s">%s</a>', $url, $text);
        } elseif (in_array($searchTerm->getType(), [SearchTermType::url, SearchTermType::messenger_profile_url], true)) {
            $message .= sprintf('<a href="%s">%s</a>', $searchTerm->getText(), $text);
        } else {
            $message .= $text;
        }

        $message .= '</b></u>';

        return $message;
    }

    public function getSearchTermTelegramView(SearchTermTransfer $searchTerm, string $localeCode = null): string
    {
        $message = $this->getSearchTermTelegramMainView($searchTerm);

        if ($searchTerm->getType() !== null && $searchTerm->getType() !== SearchTermType::unknown) {
            $searchTermTypeView = $this->termTypeProvider->getSearchTermTypeName($searchTerm->getType(), $localeCode);
            $message .= ' (' . $searchTermTypeView . ')';
        }

        return $message;
    }
}