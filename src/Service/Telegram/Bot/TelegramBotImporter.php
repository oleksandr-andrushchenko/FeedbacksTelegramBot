<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use App\Entity\ImportResult;
use App\Enum\Telegram\TelegramBotGroupName;
use App\Repository\Telegram\Bot\TelegramBotRepository;
use App\Service\Intl\CountryProvider;
use App\Service\Intl\LocaleProvider;
use App\Service\Telegram\Bot\Api\TelegramBotDescriptionsSyncer;
use App\Service\Telegram\Bot\Api\TelegramBotWebhookSyncer;
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
        private readonly TelegramBotRemover $remover,
        private readonly TelegramBotDescriptionsSyncer $textsUpdater,
        private readonly TelegramBotWebhookSyncer $webhookUpdater,
        private readonly CountryProvider $countryProvider,
        private readonly LocaleProvider $localeProvider,
        private readonly string $stage,
    )
    {
    }

    public function importTelegramBots(string $filename, callable $logger = null): ImportResult
    {
        if (!file_exists($filename)) {
            throw new InvalidArgumentException(sprintf('"%s" file is not exists', $filename));
        }

        $handle = fopen($filename, 'r');

        if ($handle === false) {
            throw new RuntimeException(sprintf('Unable to open "%s" file', $filename));
        }

        $result = new ImportResult();

        try {
            $usernames = [];
            $mandatoryColumns = [
                'skip',
                'group',
                'username',
                'name',
                'token',
                'stage',
                'country',
                'locale',
                'primary',
                'admin_id',
                'admin_only',
            ];

            $logger = $logger ?? fn (string $message) => null;

            $columns = fgetcsv($handle);
            $count = count($columns);

            $index = 1;

            while (($row = fgetcsv($handle)) !== false) {
                $index++;

                if (!isset($row[0]) || [null] === $row) {
                    continue;
                }

                $rowCount = count($row);

                if ($count !== $rowCount) {
                    throw new LogicException(sprintf('Row #%d has wrong number of columns. Should have %d columns, got %d', $index, $count, $rowCount));
                }

                $data = array_combine($columns, $row);

                foreach ($mandatoryColumns as $mandatoryColumn) {
                    if (!array_key_exists($mandatoryColumn, $data)) {
                        throw new LogicException(sprintf('Row #%d has not "%s" column', $index, $mandatoryColumn));
                    }
                }

                if ($data['stage'] !== $this->stage) {
                    continue;
                }

                if ($data['skip'] === '1') {
                    continue;
                }

                $transfer = (new TelegramBotTransfer($data['username']))
                    ->setGroup(TelegramBotGroupName::fromName($data['group']))
                    ->setName($data['name'])
                    ->setToken($data['token'])
                    ->setCountry($this->countryProvider->getCountry($data['country']))
                    ->setLocale($this->localeProvider->getLocale($data['locale']))
                    ->setPrimary($data['primary'] === '1')
                    ->setAdminIds(empty($data['admin_id']) ? null : [$data['admin_id']])
                    ->setAdminOnly($data['admin_only'] === '1')
                ;

                $bot = $this->repository->findAnyOneByUsername($transfer->getUsername());

                $message = $transfer->getUsername();
                $message .= ': [OK] ';

                if ($bot === null) {
                    $bot = $this->creator->createTelegramBot($transfer);
                    $message .= 'created';
                    $result->incCreatedCount();
                } else {
                    $this->updater->updateTelegramBot($bot, $transfer);
                    $message .= 'updated';
                    $result->incUpdatedCount();

                    if ($this->remover->telegramBotRemoved($bot)) {
                        $this->remover->undoTelegramBotRemove($bot);
                        $message .= '; [OK] restored';
                        $result->incRestoredCount();
                    }
                }

                $usernames[] = $bot->getUsername();

                if ($bot !== null && !$bot->descriptionsSynced() && !$this->remover->telegramBotRemoved($bot)) {
                    try {
                        $this->textsUpdater->syncTelegramDescriptions($bot);
                        $message .= '; [OK] descriptions';
                    } catch (Throwable $exception) {
                        $message .= '; [FAIL] descriptions - ' . $exception->getMessage();
                    }
                }
                if ($bot !== null && !$bot->webhookSynced() && !$this->remover->telegramBotRemoved($bot)) {
                    try {
                        $this->webhookUpdater->syncTelegramWebhook($bot);
                        $message .= '; [OK] webhook';
                    } catch (Throwable $exception) {
                        $message .= '; [FAIL] webhook - ' . $exception->getMessage();
                    }
                }

                $logger($message);
            }

            $bots = $this->repository->findAll();
            foreach ($bots as $bot) {
                if (!in_array($bot->getUsername(), $usernames, true) && !$this->remover->telegramBotRemoved($bot)) {
                    $this->remover->removeTelegramBot($bot);
                    $message = $bot->getUsername();
                    $message .= ': [OK] ';
                    $message .= 'deleted';
                    $result->incDeletedCount();
                    $logger($message);
                }
            }
        } finally {
            fclose($handle);
        }

        return $result;
    }
}