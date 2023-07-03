<?php

declare(strict_types=1);

namespace App\Command\Telegram;

use App\Service\Telegram\TelegramCommandsRemover;
use App\Service\Telegram\TelegramRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class TelegramCommandsDeleteCommand extends Command
{
    public function __construct(
        private readonly TelegramRegistry $telegramRegistry,
        private readonly TelegramCommandsRemover $telegramCommandsRemover,
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
            $telegram = $this->telegramRegistry->getTelegram($input->getArgument('name'));

            $this->telegramCommandsRemover->removeTelegramCommands($telegram);
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $table = [];
        $myCommands = $this->telegramCommandsRemover->getMyCommands();

        foreach ($myCommands as $myCommandsItem) {
            $value = sprintf('%s + %s', $myCommandsItem->getLanguageCode(), $myCommandsItem->getScope()->toJson());
            foreach ($myCommandsItem->getCommands() as $command) {
                if (!isset($table[$command->getName()])) {
                    $table[$command->getName()] = [];
                }

                $table[$command->getName()][] = $value;
            }
        }

        foreach ($table as $k => $v) {
            $table[$k] = implode('; ', $v);
        }

        $io->createTable()
            ->setHeaders(array_keys($table))
            ->setRows([
                $table,
            ])
            ->setVertical()
            ->render()
        ;

        $io->newLine();
        $io->success('Commands have been deleted');

        return Command::SUCCESS;
    }
}