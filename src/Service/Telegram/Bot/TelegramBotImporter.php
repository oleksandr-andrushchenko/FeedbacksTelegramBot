<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use App\Entity\ImportResult;
use App\Enum\Telegram\TelegramBotGroupName;
use App\Repository\Telegram\Bot\TelegramBotRepository;
use App\Service\CsvFileWalker;
use App\Service\Intl\CountryProvider;
use App\Service\Intl\LocaleProvider;
use App\Service\Telegram\Bot\Api\TelegramBotDescriptionsSyncer;
use App\Service\Telegram\Bot\Api\TelegramBotWebhookSyncer;
use App\Transfer\Telegram\TelegramBotTransfer;
use Doctrine\ORM\EntityManagerInterface;
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
        private readonly CsvFileWalker $walker,
        private readonly EntityManagerInterface $entityManager,
        private readonly string $stage,
    )
    {
    }

    public function importTelegramBots(string $filename, callable $logger = null): ImportResult
    {
        $result = new ImportResult();

        $bots = $this->repository->findAll();
        $usernames = $this->getUsernames($filename);
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

        $this->entityManager->flush();

        $logger = $logger ?? static fn (string $message): null => null;

        $this->walk($filename, function ($data) use ($result, $logger): void {
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
        });

        return $result;
    }

    private function getUsernames(string $filename): array
    {
        $usernames = [];

        $this->walk($filename, static function (array $data) use (&$usernames): void {
            $usernames[] = $data['username'];
        });

        return $usernames;
    }

    private function walk(string $filename, callable $func): void
    {
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

        $this->walker->walk($filename, function (array $data) use ($func): void {
            if ($data['stage'] !== $this->stage) {
                return;
            }

            if ($data['skip'] === '1') {
                return;
            }

            $func($data);
        }, mandatoryColumns: $mandatoryColumns);
    }
}