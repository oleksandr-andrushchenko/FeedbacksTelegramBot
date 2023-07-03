<?php

declare(strict_types=1);

namespace App\Command\Telegram;

use App\Service\Telegram\TelegramRegistry;
use App\Service\Telegram\TelegramWebhookUpdater;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class TelegramWebhookUpdateCommand extends Command
{
    public function __construct(
        private readonly TelegramRegistry $telegramRegistry,
        private readonly TelegramWebhookUpdater $telegramWebhookUpdater,
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
            $telegram = $this->telegramRegistry->getTelegram($input->getArgument('name'));

            $this->telegramWebhookUpdater->updateTelegramWebhook($telegram);
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $url = $telegram->getOptions()->getWebhookUrl();
        $cert = $telegram->getOptions()->getWebhookCertificatePath();

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