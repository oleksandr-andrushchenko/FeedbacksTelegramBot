<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\View;

use App\Enum\Feedback\SearchTermType;
use App\Object\Feedback\SearchTermTransfer;
use Symfony\Contracts\Translation\TranslatorInterface;

class SearchTermTelegramViewProvider
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    )
    {
    }

    public function getSearchTermTelegramView(SearchTermTransfer $searchTermTransfer, string $localeCode = null): string
    {
        $message = '<u><b>';

        if ($searchTermTransfer->getMessengerProfileUrl() !== null) {
            $message .= sprintf(
                '<a href="%s">%s</a>',
                $searchTermTransfer->getMessengerProfileUrl(),
                $searchTermTransfer->getMessengerUsername() ?? $searchTermTransfer->getMessengerProfileUrl()
            );
        } elseif ($searchTermTransfer->getMessengerUsername() !== null) {
            $message .= $searchTermTransfer->getMessengerUsername();
        } elseif ($searchTermTransfer->getType() === SearchTermType::url) {
            $message .= sprintf(
                '<a href="%s">%s</a>',
                $searchTermTransfer->getText(),
                $searchTermTransfer->getNormalizedText() ?? $searchTermTransfer->getText()
            );
        } elseif ($searchTermTransfer->getType() === SearchTermType::phone_number) {
            $message .= $searchTermTransfer->getNormalizedText() ?? $searchTermTransfer->getText();
        } elseif ($searchTermTransfer->getType() === SearchTermType::email) {
            $message .= $searchTermTransfer->getNormalizedText() ?? $searchTermTransfer->getText();
        } else {
            $message .= $searchTermTransfer->getText();
        }

        $message .= '</b></u>';

        if ($searchTermTransfer->getType() !== null && $searchTermTransfer->getType() !== SearchTermType::unknown) {
            $message .= ' ';
            $searchTermTypeTrans = $this->translator->trans($searchTermTransfer->getType()->name, domain: 'feedbacks.search_term_type', locale: $localeCode);
            $message .= '(' . $searchTermTypeTrans . ')';
        }

        return $message;
    }
}