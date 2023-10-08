<?php

declare(strict_types=1);

namespace App\Service\Feedback\SearchTerm;

use App\Transfer\Feedback\SearchTermTransfer;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Messenger\Messenger;

class TiktokSearchTermParser implements SearchTermParserInterface
{
    public function supportsSearchTerm(SearchTermTransfer $searchTerm): bool
    {
        if ($searchTerm->getType() === null) {
            return $this->supportsUsername($searchTerm->getText()) || $this->supportsUrl($searchTerm->getText());
        }

        if ($searchTerm->getType() === SearchTermType::tiktok_username) {
            return true;
        }

        return false;
    }

    public function parseWithGuessType(SearchTermTransfer $searchTerm): void
    {
        if ($this->supportsUrl($searchTerm->getText(), $username)) {
            $normalizedUsername = $this->normalizeUsername($username);
            $normalizedProfileUrl = $this->makeProfileUrl($normalizedUsername);

            if ($searchTerm->getText() === $normalizedProfileUrl) {
                $searchTerm
                    ->setNormalizedText($normalizedUsername)
                    ->setType(SearchTermType::tiktok_username)
                    ->setMessengerUsername($normalizedUsername)
                    ->setMessenger(Messenger::tiktok)
                    ->setMessengerProfileUrl($normalizedProfileUrl)
                ;
            } else {
                $searchTerm
                    ->addPossibleType(SearchTermType::tiktok_username)
                ;
            }
        } elseif ($this->supportsUsername($searchTerm->getText())) {
            $searchTerm
                ->addPossibleType(SearchTermType::tiktok_username)
            ;
        }
    }

    public function parseWithKnownType(SearchTermTransfer $searchTerm): void
    {
        if ($searchTerm->getType() === SearchTermType::tiktok_username) {
            $normalizedUsername = $this->normalizeUsername($searchTerm->getText());

            $searchTerm
                ->setNormalizedText($normalizedUsername === $searchTerm->getText() ? null : $normalizedUsername)
                ->setMessenger(Messenger::tiktok)
                ->setMessengerUsername($normalizedUsername)
                ->setMessengerProfileUrl($this->makeProfileUrl($normalizedUsername))
            ;
        }
    }

    public function parseWithNetwork(SearchTermTransfer $searchTerm): void
    {
        // TODO: Implement parseWithNetwork() method.
    }

    private function supportsUsername(string $username): bool
    {
        if (is_numeric($username)) {
            return false;
        }

        return preg_match('/^' . $this->getUsernamePattern(false) . '$/im', $username) === 1;
    }

    private function getUsernamePattern(bool $url): string
    {
        return ($url ? '@' : '@?') . '[A-Za-z0-9-_\.]+';
    }

    private function normalizeUsername(string $username): string
    {
        return ltrim($username, '@');
    }

    private function makeProfileUrl(string $username): string
    {
        return sprintf('https://tiktok.com/@%s', $username);
    }

    private function supportsUrl(string $url, string &$username = null): bool
    {
        $result = preg_match('/^(?:(?:http|https):\/\/)?(?:www\.)?tiktok\.com\/(' . $this->getUsernamePattern(true) . ')[?\/]?/im', $url, $matches);

        if ($result === 1 && $this->supportsUsername($matches[1])) {
            $username = $matches[1];

            return true;
        }

        return false;
    }
}
