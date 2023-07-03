<?php

declare(strict_types=1);

namespace App\Command\Telegram;

use App\Service\Telegram\TelegramRegistry;
use App\Service\Telegram\TelegramWebhookInfoProvider;
use App\Service\Telegram\TelegramWebhookRemover;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class TelegramWebhookDeleteCommand extends Command
{
    public function __construct(
        private readonly TelegramRegistry $telegramRegistry,
        private readonly TelegramWebhookInfoProvider $telegramWebhookInfoProvider,
        private readonly TelegramWebhookRemover $telegramWebhookRemover,
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
            ->setDescription('Delete telegram bot webhook')
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

            $url = $this->telegramWebhookInfoProvider->getTelegramWebhookInfo($telegram)->getUrl();

            if ($url === '') {
                $io->info('No webhook found for delete');

                return Command::SUCCESS;
            }

            $confirmed = $io->askQuestion(
                new ConfirmationQuestion(sprintf('Are you sure you want to delete "%s" webhook?', $url), false)
            );

            if (!$confirmed) {
                $io->info(
                    sprintf('"%s" webhook deletion has been cancelled', $url)
                );

                return Command::SUCCESS;
            }

            $this->telegramWebhookRemover->removeTelegramWebhook($telegram);
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->success('Webhook has been deleted');

        return Command::SUCCESS;
    }
}