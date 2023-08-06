<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Exception\Telegram\InvalidTelegramUpdateException;
use Longman\TelegramBot\Entities\Update;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class TelegramUpdateFactory
{
    public function __construct(
        private readonly LoggerInterface $logger,
    )
    {
    }

    /**
     * @param Telegram $telegram
     * @param Request $request
     * @return Update
     * @throws InvalidTelegramUpdateException
     */
    public function createUpdate(Telegram $telegram, Request $request): Update
    {
        $input = $request->getContent();

        $data = json_decode($input, true);

        if (!is_array($data)) {
            $this->logger->debug('Invalid telegram update received, processing aborted', [
                'name' => $telegram->getGroup()->name,
                'input' => $input,
            ]);

            throw new InvalidTelegramUpdateException($input);
        }

        $this->logger->info('Telegram update received', [
            'name' => $telegram->getGroup()->name,
            'json' => $input,
        ]);

        return new Update($data, $telegram->getOptions()->getUsername());
    }
}