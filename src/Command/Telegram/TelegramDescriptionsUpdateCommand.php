<?php

declare(strict_types=1);

namespace App\Command\Telegram;

use App\Service\Telegram\Api\TelegramDescriptionsUpdater;
use App\Service\Telegram\TelegramRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class TelegramDescriptionsUpdateCommand extends Command
{
    public function __construct(
        private readonly TelegramRegistry $registry,
        private readonly TelegramDescriptionsUpdater $updater,
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
            ->setDescription('Update telegram bot name, short and long descriptions')
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

            $this->updater->updateTelegramDescriptions($telegram);
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $table = [
            'name' => implode("; ", $this->updater->getMyNames()),
            'description' => implode("; ", $this->updater->getMyDescriptions()),
            'short_description' => implode("; ", $this->updater->getMyShortDescriptions()),
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
        $io->success('Descriptions have been updated');

        return Command::SUCCESS;
    }
}