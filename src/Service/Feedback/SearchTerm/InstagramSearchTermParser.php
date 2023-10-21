<?php

declare(strict_types=1);

namespace App\Service\Feedback\SearchTerm;

use App\Enum\Feedback\SearchTermType;
use App\Enum\Messenger\Messenger;
use App\Transfer\Feedback\SearchTermTransfer;

class InstagramSearchTermParser implements SearchTermParserInterface
{
    public function supportsSearchTerm(SearchTermTransfer $searchTerm): bool
    {
        if ($searchTerm->getType() === null) {
            return $this->supportsUsername($searchTerm->getText()) || $this->supportsUrl($searchTerm->getText());
        }

        if ($searchTerm->getType() === SearchTermType::instagram_username) {
            return true;
        }

        return false;
    }

    public function parseWithGuessType(SearchTermTransfer $searchTerm): void
    {
        if ($this->supportsUrl($searchTerm->getText(), $username)) {
            $normalizedUsername = $this->normalizeUsername($username);
            $normalizedProfileUrl = $this->makeProfileUrl($normalizedUsername);

            $searchTerm
                ->setNormalizedText($normalizedUsername)
                ->setType(SearchTermType::instagram_username)
                ->setMessengerUsername($normalizedUsername)
                ->setMessenger(Messenger::instagram)
                ->setMessengerProfileUrl($normalizedProfileUrl)
            ;
        } elseif ($this->supportsUsername($searchTerm->getText())) {
            $searchTerm
                ->addType(SearchTermType::instagram_username)
            ;
        }
    }

    public function parseWithKnownType(SearchTermTransfer $searchTerm): void
    {
        if ($searchTerm->getType() === SearchTermType::instagram_username) {
            $normalizedUsername = $this->normalizeUsername($searchTerm->getText());

            $searchTerm
                ->setNormalizedText($normalizedUsername === $searchTerm->getText() ? null : $normalizedUsername)
                ->setMessenger(Messenger::instagram)
                ->setMessengerUsername($normalizedUsername)
                ->setMessengerProfileUrl($this->makeProfileUrl($normalizedUsername))
            ;
        }
    }

    public function parseWithNetwork(SearchTermTransfer $searchTerm): void
    {
//        $messengerUser = $this->userProvider->getInstagramMessengerUser($searchTerm->getMessengerUsername());
//
//        $searchTerm
//            ->setMessengerUser($messengerUser)
//        ;
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
        return ($url ? '' : '@?') . '\w(?!.*?\.{2})[\w.]{1,28}\w';
    }

    private function normalizeUsername(string $username): string
    {
        return ltrim($username, '@');
    }

    private function makeProfileUrl(string $username): string
    {
        return sprintf('https://instagram.com/%s', $username);
    }

    private function supportsUrl(string $url, string &$username = null): bool
    {
        $result = preg_match('/^(?:(?:http|https):\/\/)?(?:www\.)?(?:instagram\.com|instagr\.am)\/(' . $this->getUsernamePattern(true) . ')[?\/]?/im', $url, $matches);

        if ($result === 1 && $this->supportsUsername($matches[1])) {
            $username = $matches[1];

            return true;
        }

        return false;
    }
}
