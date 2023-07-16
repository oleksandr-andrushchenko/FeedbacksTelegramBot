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

class TelegramWebhookInfoCommand extends Command
{
    public function __construct(
        private readonly TelegramRegistry $telegramRegistry,
        private readonly TelegramWebhookInfoProvider $telegramWebhookInfoProvider,
    )
    {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Telegram bot name')
            ->setDescription('Get telegram bot webhook Info')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $telegram = $this->telegramRegistry->getTelegram($input->getArgument('name'));

            $webhookInfo = $this->telegramWebhookInfoProvider->getTelegramWebhookInfo($telegram);
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $table = [
            'Url' => $webhookInfo->getUrl(),
            'Custom certificate' => $webhookInfo->getHasCustomCertificate(),
            'Pending update count' => $webhookInfo->getPendingUpdateCount(),
            'Ip address' => $webhookInfo->getIpAddress(),
            'Max connections' => $webhookInfo->getMaxConnections(),
            'Allowed updates' => $webhookInfo->getAllowedUpdates(),
            'Last error date' => $webhookInfo->getLastErrorDate(),
            'Last error message' => $webhookInfo->getLastErrorMessage(),
            'Last synchronization error date' => $webhookInfo->getLastSynchronizationErrorDate(),
        ];

        $io->createTable()
            ->setHeaders(array_keys($table))
            ->setRows([
                $table,
            ])
            ->setVertical()
            ->render()
        ;

        $io->newLine();
        $io->success('Webhook info has been retrieved');

        return Command::SUCCESS;
    }
}