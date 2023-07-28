<?php

declare(strict_types=1);

namespace App\Service\Instagram\UserFinder;

use App\Enum\Messenger\Messenger;
use App\Object\Messenger\MessengerUserTransfer;
use App\Service\HttpClientFactory;
use Psr\Log\LoggerInterface;
use Throwable;

class OpenSearchInstagramMessengerUserFinder implements InstagramMessengerUserFinderInterface
{
    public function __construct(
        private readonly HttpClientFactory $httpClientFactory,
        private readonly LoggerInterface $logger,
    )
    {
    }

    public function findInstagramMessengerUser(string $username, $_ = null): ?MessengerUserTransfer
    {
        static $client = null;

        if ($client === null) {
            $client = $this->httpClientFactory->createHttpClient();
        }

        try {
            $response = $client->get(sprintf('https://www.instagram.com/site/search/topsearch/?query=%s', $username));
            $data = json_decode($response->getBody()->getContents(), true);

            if (!isset($data['users']) || !is_array($data['users'])) {
                return null;
            }

            foreach ($data['users'] as $item) {
                if (!is_array($item)) {
                    continue;
                }

                if (!isset($item['user']) || !is_array($item['user'])) {
                    continue;
                }

                $user = $item['user'];

                if (!isset($user['pk_id'], $user['username'], $user['full_name'])) {
                    continue;
                }

                if ($user['username'] === $username) {
                    return new MessengerUserTransfer(
                        Messenger::instagram,
                        $user['pk_id'],
                        $user['username'],
                        $user['full_name'],
                        null
                    );
                }
            }
        } catch (Throwable $exception) {
            $this->logger->error($exception);
        }

        return null;
    }
}
