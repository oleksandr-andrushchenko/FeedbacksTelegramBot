<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramOptions;
use App\Enum\Telegram\TelegramName;
use App\Exception\Telegram\TelegramException;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Exception\InvalidBotTokenException;
use Longman\TelegramBot\Exception\TelegramException as InnerTelegramException;
use Longman\TelegramBot\Request as TelegramRequest;
use Longman\TelegramBot\Telegram as TelegramClient;
use Longman\TelegramBot\TelegramLog;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * @method ServerResponse setWebhook(string $url, array $data = [])
 * @method ServerResponse setMyCommands(array $data)
 * @method ServerResponse deleteMyCommands(array $data)
 * @method ServerResponse getWebhookInfo()
 * @method ServerResponse deleteWebhook(array $data = [])
 * @method ServerResponse sendMessage(array $data)
 * @method ServerResponse deleteMessage(array $data)
 * @method ServerResponse emptyResponse()
 * @method ServerResponse getMe()
 * @method ServerResponse getUserProfilePhotos(array $data)
 * @method ServerResponse setMyName(array $data)
 * @method ServerResponse setMyDescription(array $data)
 * @method ServerResponse setMyShortDescription(array $data)
 * @method ServerResponse setChatMenuButton(array $data)
 * @method ServerResponse sendChatAction(array $data)
 * @method ServerResponse sendInvoice(array $data)
 * @method ServerResponse createInvoiceLink(array $data)
 * @method ServerResponse answerPreCheckoutQuery(array $data)
 * @method ServerResponse leaveChat(array $data)
 */
class Telegram
{
    private ?Update $update;
    private ?MessengerUser $messengerUser;

    public function __construct(
        private readonly TelegramName $name,
        private readonly TelegramOptions $options,
        private readonly TelegramClientRegistry $clientRegistry,
        private readonly TelegramRequestChecker $requestChecker,
        private readonly LoggerInterface $logger,
    )
    {
        $this->update = null;
        $this->messengerUser = null;
    }

    public function getName(): TelegramName
    {
        return $this->name;
    }

    public function getOptions(): TelegramOptions
    {
        return $this->options;
    }

    public function getUpdate(): ?Update
    {
        return $this->update;
    }

    public function setUpdate(?Update $update): static
    {
        $this->update = $update;

        return $this;
    }

    public function getMessengerUser(): ?MessengerUser
    {
        return $this->messengerUser;
    }

    public function setMessengerUser(?MessengerUser $messengerUser): static
    {
        $this->messengerUser = $messengerUser;

        return $this;
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     * @throws TelegramException
     */
    public function __call(string $name, array $arguments): mixed
    {
        $request = $this->requestChecker->checkTelegramRequest($this, $name, $arguments[0] ?? []);
        $response = $this->request($name, $arguments);

        if ($response instanceof ServerResponse) {
            $request?->setResponse($response->getRawData());

            if (!$response->isOk()) {
                throw new TelegramException(
                    sprintf('Error: %d %s', $response->getErrorCode(), $response->getDescription())
                );
            }
        } else {
            $this->logger->info(sprintf('unknown "%s" response object', get_class($response)));
        }

        return $response;
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     * @throws TelegramException
     */
    private function request(string $name, array $arguments): mixed
    {
        try {
            if (method_exists($this->getClient(), $name)) {
                return $this->getClient()->{$name}(...$arguments);
            }

            try {
                return TelegramRequest::{$name}(...$arguments);
            } catch (InnerTelegramException $exception) {
                if (str_contains($exception->getMessage(), 'action') && str_contains($exception->getMessage(), 'doesn\'t exist')) {
                    // copied from TelegramRequest::send
                    $raw_response = TelegramRequest::execute($name, $arguments[0]);
                    $response = json_decode($raw_response, true);

                    if (null === $response) {
                        TelegramLog::debug($raw_response);
                        throw new InnerTelegramException('Telegram returned an invalid response!');
                    }

                    $response = new ServerResponse($response, $this->getOptions()->getUsername());

                    if (!$response->isOk() && $response->getErrorCode() === 401 && $response->getDescription() === 'Unauthorized') {
                        throw new InvalidBotTokenException();
                    }

                    return $response;
                }

                throw $exception;
            }
        } catch (TelegramException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            $this->logger->error($exception);

            $message = sprintf(
                'Failed to %s for "%s" telegram',
                strtolower(implode(' ', preg_split('/(?=[A-Z])/', $name))),
                $this->getName()->name,
            );

            throw new TelegramException($message, 0, $exception);
        }
    }

    private function getClient(): TelegramClient
    {
        return $this->clientRegistry->getTelegramClient($this->getName(), $this->getOptions());
    }
}