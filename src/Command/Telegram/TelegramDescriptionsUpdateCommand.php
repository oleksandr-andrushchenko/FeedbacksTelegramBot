<?php

declare(strict_types=1);

namespace App\Command\Telegram;

use App\Service\Telegram\TelegramDescriptionsUpdater;
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
        private readonly TelegramRegistry $telegramRegistry,
        private readonly TelegramDescriptionsUpdater $telegramDescriptionUpdater,
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
            $telegram = $this->telegramRegistry->getTelegram($input->getArgument('name'));

            $this->telegramDescriptionUpdater->updateTelegramDescriptions($telegram);
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $table = [
            'name' => implode("; ", $this->telegramDescriptionUpdater->getMyNames()),
            'description' => implode("; ", $this->telegramDescriptionUpdater->getMyDescriptions()),
            'short_description' => implode("; ", $this->telegramDescriptionUpdater->getMyShortDescriptions()),
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