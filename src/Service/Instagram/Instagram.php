<?php

declare(strict_types=1);

namespace App\Service\Instagram;

use App\Entity\Instagram\InstagramOptions;
use App\Entity\Messenger\MessengerUser;
use App\Enum\Instagram\InstagramName;
use App\Exception\Instagram\InstagramException;
use Instagram\SDK\Instagram as InstagramClient;
use Instagram\SDK\Response\DTO\General\User as InstagramUser;
use Instagram\SDK\Response\Responses\ResponseEnvelope;
use Instagram\SDK\Response\Responses\Users\UserInformationResponse;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * @method UserInformationResponse userByName(string $username)
 */
readonly class Instagram
{
    public function __construct(
        private InstagramName $instagramName,
        private InstagramOptions $instagramOptions,
        private InstagramClientRegistry $instagramClientRegistry,
        private LoggerInterface $logger,
    )
    {
    }

    public function getInstagramName(): InstagramName
    {
        return $this->instagramName;
    }

    public function getInstagramOptions(): InstagramOptions
    {
        return $this->instagramOptions;
    }

    public function getInstagramClient(): InstagramClient
    {
        return $this->instagramClientRegistry->getInstagramClient($this->getInstagramName(), $this->getInstagramOptions());
    }

    public function findInstagramUserByUsername(string $username): null|InstagramUser|MessengerUser
    {
        try {
            $response = file_get_contents(sprintf('https://www.instagram.com/site/search/topsearch/?query=%s', $username));
            $data = json_decode($response, true);

            if (!is_array($data) || !isset($data['users']) || !is_array($data['users'])) {
                goto client;
            }

            foreach ($data['users'] as $item) {
                $user = $item['user'];

                if ($user['username'] === $username) {
                    return (new MessengerUser())
                        ->setId($user['pk'] ?? $user['pk_id'] ?? null)
                        ->setName($user['full_name'] ?? null)
                        ->setUsername($user['username'] ?? null)
                        ->setBio($user['biography'] ?? null)
                        ->setPictureUrl($user['profile_pic_url'] ?? null)
                    ;
                }
            }

            return null;
        } catch (Throwable $exception) {
            $this->logger->error($exception);

            client:
            return $this->userByName($username)->getUser();
        }
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     * @throws InstagramException
     */
    public function __call(string $name, array $arguments): mixed
    {
        try {
            return $this->checkResponse(
                $this->getInstagramClient()->{$name}(...$arguments)
            );
        } catch (InstagramException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            $this->logger->error($exception);

            $message = sprintf(
                'Failed to %s for "%s" instagram',
                strtolower(implode(' ', preg_split('/(?=[A-Z])/', $name))),
                $this->getInstagramName()->name,
            );

            throw new InstagramException($message, 0, $exception);
        }
    }

    /**
     * @param $response
     * @return mixed
     * @throws InstagramException
     */
    private function checkResponse($response): mixed
    {
        if ($response instanceof ResponseEnvelope && !$response->isSuccess()) {
            throw new InstagramException(
                sprintf('Error: %s %s', $response->getErrorType(), $response->getMessage())
            );
        }

        return $response;
    }
}
