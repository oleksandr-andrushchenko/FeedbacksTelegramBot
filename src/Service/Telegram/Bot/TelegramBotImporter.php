<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use App\Enum\Telegram\TelegramBotGroupName;
use App\Repository\Telegram\Bot\TelegramBotRepository;
use App\Service\Intl\CountryProvider;
use App\Service\Intl\LocaleProvider;
use App\Service\Telegram\Bot\Api\TelegramBotCommandsUpdater;
use App\Service\Telegram\Bot\Api\TelegramBotTextsUpdater;
use App\Service\Telegram\Bot\Api\TelegramBotWebhookUpdater;
use App\Transfer\Telegram\TelegramBotTransfer;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Throwable;

class TelegramBotImporter
{
    public function __construct(
        private readonly TelegramBotRepository $repository,
        private readonly TelegramBotCreator $creator,
        private readonly TelegramBotUpdater $updater,
        private readonly TelegramBotTextsUpdater $textsUpdater,
        private readonly TelegramBotWebhookUpdater $webhookUpdater,
        private readonly TelegramBotCommandsUpdater $commandsUpdater,
        private readonly CountryProvider $countryProvider,
        private readonly LocaleProvider $localeProvider,
    )
    {
    }

    public function importTelegramBots(
        string $filename,
        callable $logger = null,
        int &$countCreated = 0,
        int &$countUpdated = 0,
    ): void
    {
        $this->validateTelegramBots($filename);

        $logger = $logger ?? fn (string $message) => null;

        $handle = fopen($filename, 'r');
        $columns = fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($columns, $row);

            if (isset($data['skip']) && $data['skip'] === '1') {
                continue;
            }

            $transfer = (new TelegramBotTransfer($data['username']))
                ->setGroup(empty($data['group']) ? null : TelegramBotGroupName::fromName($data['group']))
                ->setName(empty($data['name']) ? null : $data['name'])
                ->setToken(empty($data['token']) ? null : $data['token'])
                ->setCountry(empty($data['country']) ? null : $this->countryProvider->getCountry($data['country']))
                ->setLocale(empty($data['locale']) ? null : $this->localeProvider->getLocale($data['locale']))
                ->setAdminIds(empty($data['admin_id']) ? null : [$data['admin_id']])
                ->setSyncTexts(isset($data['sync_texts']) && $data['sync_texts'] === '1')
                ->setSyncWebhook(isset($data['sync_webhook']) && $data['sync_webhook'] === '1')
                ->setSyncCommands(isset($data['sync_commands']) && $data['sync_commands'] === '1')
                ->setPrimary(isset($data['primary']) && $data['primary'] === '1')
                ->setAdminOnly(isset($data['admin_only']) && $data['admin_only'] === '1')
            ;

            $bot = $this->repository->findOneByUsername($transfer->getUsername());

            if ($bot === null) {
                $bot = $this->creator->createTelegramBot($transfer);
                $countCreated++;
            } else {
                $this->updater->updateTelegramBot($bot, $transfer);
                $countUpdated++;
            }

            $message = $bot->getUsername();
            $message .= ': [OK] ';
            $message .= $bot->getId() === null ? 'created' : 'updated';

            if ($transfer->syncTexts()) {
                try {
                    $this->textsUpdater->updateTelegramDescriptions($bot);
                    $message .= '; [OK] texts';
                } catch (Throwable $exception) {
                    $message .= '; [FAIL] texts - ' . $exception->getMessage();
                }
            }
            if ($transfer->syncWebhook()) {
                try {
                    $this->webhookUpdater->updateTelegramWebhook($bot);
                    $message .= '; [OK] webhook';
                } catch (Throwable $exception) {
                    $message .= '; [FAIL] webhook - ' . $exception->getMessage();
                }
            }
            if ($transfer->syncCommands()) {
                try {
                    $this->commandsUpdater->updateTelegramCommands($bot);
                    $message .= '; [OK] commands';
                } catch (Throwable $exception) {
                    $message .= '; [FAIL] commands - ' . $exception->getMessage();
                }
            }

            $logger($message);
        }

        fclose($handle);
    }

    public function validateTelegramBots(string $filename): void
    {
        if (!file_exists($filename)) {
            throw new InvalidArgumentException(sprintf('"%s" file is not exists', $filename));
        }

        $handle = fopen($filename, 'r');

        if ($handle === false) {
            throw new RuntimeException(sprintf('Unable to open "%s" file', $filename));
        }

        try {
            $columns = fgetcsv($handle);
            $count = count($columns);

            $index = 2;

            while (($row = fgetcsv($handle)) !== false) {
                $rowCount = count($row);

                if ($count !== $rowCount) {
                    throw new LogicException(sprintf('Row #%d has wrong number of columns. Should have %d columns, got %d', $index, $count, $rowCount));
                }

                $data = array_combine($columns, $row);

                if (!isset($data['username'])) {
                    throw new LogicException(sprintf('Row #%d has not "username" column', $index));
                }

                $index++;
            }
        } finally {
            fclose($handle);
        }
    }
}