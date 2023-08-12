<?php

declare(strict_types=1);

namespace App\Command\Telegram;

use App\Service\Telegram\Api\TelegramTextsUpdater;
use App\Service\Telegram\TelegramRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class TelegramBotUpdateTextsCommand extends Command
{
    public function __construct(
        private readonly TelegramRegistry $registry,
        private readonly TelegramTextsUpdater $updater,
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

        $table = [];
        $row = [];
        foreach ($this->updater->getMyNames() as $localeCode => $name) {
            $row['name_' . $localeCode] = $name;
            $row['short_description_' . $localeCode] = $this->updater->getMyShortDescriptions()[$localeCode];
            $row['description_' . $localeCode] = $this->updater->getMyDescriptions()[$localeCode];
        }
        $table[] = $row;

        $io->createTable()
            ->setHeaders(array_keys($table[0]))
            ->setRows($table)
            ->setVertical()
            ->render()
        ;

        $io->newLine();
        $io->success('Descriptions have been updated');

        return Command::SUCCESS;
    }
}