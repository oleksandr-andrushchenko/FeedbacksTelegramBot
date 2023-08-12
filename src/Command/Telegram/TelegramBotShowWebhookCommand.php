<?php

declare(strict_types=1);

namespace App\Command\Telegram;

use App\Service\Telegram\Api\TelegramWebhookInfoProvider;
use App\Service\Telegram\TelegramRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class TelegramBotShowWebhookCommand extends Command
{
    public function __construct(
        private readonly TelegramRegistry $registry,
        private readonly TelegramWebhookInfoProvider $provider,
    )
    {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Telegram bot username')
            ->setDescription('Show telegram bot webhook info')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $telegram = $this->registry->getTelegram($input->getArgument('name'));

            $webhookInfo = $this->provider->getTelegramWebhookInfo($telegram);
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $row = [
            'url' => $webhookInfo->getUrl(),
            'custom_certificate' => $webhookInfo->getHasCustomCertificate(),
            'pending_update_count' => $webhookInfo->getPendingUpdateCount(),
            'ip_address' => $webhookInfo->getIpAddress(),
            'max_connections' => $webhookInfo->getMaxConnections(),
            'allowed_updates' => $webhookInfo->getAllowedUpdates(),
            'last_error_date' => $webhookInfo->getLastErrorDate(),
            'last_error_message' => $webhookInfo->getLastErrorMessage(),
            'last_synchronization_error_date' => $webhookInfo->getLastSynchronizationErrorDate(),
        ];

        $io->createTable()
            ->setHeaders(array_keys($row))
            ->setRows([$row])
            ->setVertical()
            ->render()
        ;

        $io->newLine();
        $io->success('Webhook info has been retrieved');

        return Command::SUCCESS;
    }
}