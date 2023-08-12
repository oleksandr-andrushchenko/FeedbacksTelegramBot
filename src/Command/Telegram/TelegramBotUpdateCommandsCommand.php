<?php

declare(strict_types=1);

namespace App\Command\Telegram;

use App\Service\Telegram\Api\TelegramCommandsUpdater;
use App\Service\Telegram\TelegramRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class TelegramBotUpdateCommandsCommand extends Command
{
    public function __construct(
        private readonly TelegramRegistry $registry,
        private readonly TelegramCommandsUpdater $updater,
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
            ->setDescription('Update telegram bot commands')
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

            $this->updater->updateTelegramCommands($telegram);
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $row = [];
        $myCommands = $this->updater->getMyCommands();

        foreach ($myCommands as $myCommandsItem) {
            $value = sprintf('%s + %s', $myCommandsItem->getLocaleCode(), $myCommandsItem->getScope()->toJson());
            foreach ($myCommandsItem->getCommands() as $command) {
                if (!isset($row[$command->getName()])) {
                    $row[$command->getName()] = [];
                }

                $row[$command->getName()][] = $value;
            }
        }

        foreach ($row as $k => $v) {
            $row[$k] = implode('; ', $v);
        }

        $io->createTable()
            ->setHeaders(array_keys($row))
            ->setRows([$row])
            ->setVertical()
            ->render()
        ;

        $io->newLine();
        $io->success('Commands have been updated');

        return Command::SUCCESS;
    }
}