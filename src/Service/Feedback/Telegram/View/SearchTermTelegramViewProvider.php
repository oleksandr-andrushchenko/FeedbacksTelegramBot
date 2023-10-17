<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\View;

use App\Enum\Feedback\SearchTermType;
use App\Service\Feedback\SearchTerm\SearchTermTypeProvider;
use App\Transfer\Feedback\SearchTermTransfer;

class SearchTermTelegramViewProvider
{
    public function __construct(
        private readonly SearchTermTypeProvider $termTypeProvider,
    )
    {
    }

    public function getSearchTermTelegramMainView(SearchTermTransfer $searchTerm): string
    {
        $message = '<u><b>';

        if ($searchTerm->getMessengerProfileUrl() !== null) {
            $message .= sprintf(
                '<a href="%s">%s</a>',
                $searchTerm->getMessengerProfileUrl(),
                $searchTerm->getMessengerUsername() ?? $searchTerm->getMessengerProfileUrl()
            );
        } elseif ($searchTerm->getMessengerUsername() !== null) {
            $message .= $searchTerm->getMessengerUsername();
        } elseif ($searchTerm->getType() === SearchTermType::url) {
            $message .= sprintf(
                '<a href="%s">%s</a>',
                $searchTerm->getText(),
                $searchTerm->getNormalizedText() ?? $searchTerm->getText()
            );
        } elseif ($searchTerm->getType() === SearchTermType::phone_number) {
            $message .= $searchTerm->getNormalizedText() ?? $searchTerm->getText();
        } elseif ($searchTerm->getType() === SearchTermType::email) {
            $message .= $searchTerm->getNormalizedText() ?? $searchTerm->getText();
        } else {
            $message .= $searchTerm->getText();
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