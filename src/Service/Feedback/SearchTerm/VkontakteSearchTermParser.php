<?php

declare(strict_types=1);

namespace App\Service\Feedback\SearchTerm;

use App\Transfer\Feedback\SearchTermTransfer;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Messenger\Messenger;
use App\Transfer\Messenger\MessengerUserTransfer;

class VkontakteSearchTermParser implements SearchTermParserInterface
{
    public function supportsSearchTerm(SearchTermTransfer $searchTerm): bool
    {
        if ($searchTerm->getType() === null) {
            return $this->supportsUsername($searchTerm->getText()) || $this->supportsUrl($searchTerm->getText());
        }

        if ($searchTerm->getType() === SearchTermType::vkontakte_username) {
            return true;
        }

        return false;
    }

    public function setupSearchTerm(SearchTermTransfer $searchTerm, string $username): void
    {
        $normalizedUsername = $this->normalizeUsername($username);
        $normalizedProfileUrl = $this->makeProfileUrl($normalizedUsername);

        $searchTerm
            ->setType(SearchTermType::vkontakte_username)
            ->setMessenger(Messenger::vkontakte)
            ->setNormalizedText($searchTerm->getText() === $normalizedUsername ? null : $normalizedUsername)
            ->setMessengerUsername($normalizedUsername)
            ->setMessengerProfileUrl($normalizedProfileUrl)
        ;

        if (str_starts_with($username, 'id')) {
            $id = substr($username, 2);

            if (is_numeric($id)) {
                $searchTerm
                    ->setMessengerUser(
                        new MessengerUserTransfer(
                            Messenger::vkontakte,
                            $id,
                            username: $normalizedUsername
                        )
                    )
                ;
            }
        }
    }

    public function parseWithGuessType(SearchTermTransfer $searchTerm): void
    {
        if ($this->supportsUrl($searchTerm->getText(), $username)) {
            $this->setupSearchTerm($searchTerm, $username);
        } elseif ($this->supportsUsername($searchTerm->getText())) {
            $searchTerm
                ->addPossibleType(SearchTermType::vkontakte_username)
            ;
        }
    }

    public function parseWithKnownType(SearchTermTransfer $searchTerm): void
    {
        if ($searchTerm->getType() === SearchTermType::vkontakte_username) {
            $username = $searchTerm->getText();

            $this->supportsUsername($username);
            $this->setupSearchTerm($searchTerm, $username);
        }
    }

    public function parseWithNetwork(SearchTermTransfer $searchTerm): void
    {
        // TODO: Implement parseWithNetwork() method.
    }

    private function supportsUsername(string $username): bool
    {
        return preg_match('/^' . $this->getUsernamePattern(false) . '$/im', $username) === 1;
    }

    private function getUsernamePattern(bool $url): string
    {
        return ($url ? '' : '@?') . '[A-Za-z0-9]{1}[A-Za-z0-9_\.]+';
    }

    private function normalizeUsername(string $username): string
    {
        return ltrim($username, '@');
    }

    private function makeProfileUrl(string $username): string
    {
        return sprintf('https://vk.com/%s', $username);
    }

    private function supportsUrl(string $url, string &$username = null): bool
    {
        $result = preg_match('/^(?:(?:http|https):\/\/)?(?:www\.)?(?:m\.)?(?:vkontakte\.ru|vk\.(?:com|ru))\/(' . $this->getUsernamePattern(true) . ')[?\/]?/im', $url, $matches);

        if ($result === 1 && $this->supportsUsername($matches[1])) {
            $username = $matches[1];

            return true;
        }

        return false;
    }
}
