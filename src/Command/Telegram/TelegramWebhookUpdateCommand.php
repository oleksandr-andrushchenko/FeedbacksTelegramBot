<?php

declare(strict_types=1);

namespace App\Command\Telegram;

use App\Service\Telegram\Api\TelegramWebhookUpdater;
use App\Service\Telegram\TelegramRegistry;
use App\Service\Telegram\TelegramWebhookUrlGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class TelegramWebhookUpdateCommand extends Command
{
    public function __construct(
        private readonly TelegramRegistry $registry,
        private readonly TelegramWebhookUpdater $updater,
        private readonly TelegramWebhookUrlGenerator $webhookUrlGenerator,
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
            ->setDescription('Update telegram bot webhook')
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

            $this->updater->updateTelegramWebhook($telegram);
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $url = $this->webhookUrlGenerator->generate($telegram->getBot()->getUsername());
        $cert = true ? '' : 'any';

        $io->success(
            sprintf(
                'Webhook url "%s" has been installed%s',
                $url,
                empty($cert) ? '' : sprintf(' with "%s" certificate', $cert)
            )
        );

        return Command::SUCCESS;
    }
}