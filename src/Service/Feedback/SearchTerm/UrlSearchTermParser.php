<?php

declare(strict_types=1);

namespace App\Service\Feedback\SearchTerm;

use App\Transfer\Feedback\SearchTermTransfer;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Messenger\Messenger;

class UrlSearchTermParser implements SearchTermParserInterface
{
    public function supportsSearchTerm(SearchTermTransfer $searchTerm): bool
    {
        if ($searchTerm->getType() === null) {
            return !empty(parse_url($searchTerm->getText(), PHP_URL_HOST));
        }

        if (in_array($searchTerm->getType(), [SearchTermType::url, SearchTermType::messenger_profile_url], true)) {
            return true;
        }

        return false;
    }

    public function parseWithGuessType(SearchTermTransfer $searchTerm): void
    {
        $searchTerm
            ->addPossibleType(SearchTermType::messenger_profile_url)
            ->addPossibleType(SearchTermType::url)
        ;
    }

    public function parseWithKnownType(SearchTermTransfer $searchTerm): void
    {
        if ($searchTerm->getType() === SearchTermType::messenger_profile_url) {
            $normalizedUsername = $this->normalizeUsername($this->guessUsername($searchTerm));

            if ($normalizedUsername === null) {
                $searchTerm
                    ->setMessenger(Messenger::unknown)
                    ->setMessengerProfileUrl($searchTerm->getText())
                ;
            } else {
                $searchTerm
                    ->setType(SearchTermType::messenger_username)
                    ->setNormalizedText($normalizedUsername)
                    ->setMessenger(Messenger::unknown)
                    ->setMessengerUsername($normalizedUsername)
                    ->setMessengerProfileUrl($searchTerm->getText())
                ;
            }
        }
    }

    public function parseWithNetwork(SearchTermTransfer $searchTerm): void
    {
        // TODO: Implement parseWithNetwork() method.
    }

    private function getUsernamePattern(): string
    {
        return '@?[A-Za-z0-9-_\.]+';
    }

    private function normalizeUsername(?string $username): ?string
    {
        if ($username === null) {
            return null;
        }

        return ltrim($username, '@');
    }

    private function guessUsername(SearchTermTransfer $searchTerm): ?string
    {
        if (preg_match_all('/' . $this->getUsernamePattern() . '/im', parse_url($searchTerm->getText(), PHP_URL_PATH), $matches, PREG_SET_ORDER) > 0) {
            $username = end($matches)[0];

            if (is_numeric($username)) {
                return null;
            }

            return $username;
        }

        return null;
    }
}