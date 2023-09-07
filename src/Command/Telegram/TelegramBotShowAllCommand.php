<?php

declare(strict_types=1);

namespace App\Command\Telegram;

use App\Repository\Telegram\TelegramBotRepository;
use App\Service\Telegram\TelegramBotInfoProvider;
use App\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class TelegramBotShowAllCommand extends Command
{
    public function __construct(
        private readonly TelegramBotRepository $repository,
        private readonly TelegramBotInfoProvider $infoProvider,
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
            ->setDescription('Show all telegram bots info')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function invoke(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $bots = $this->repository->findAll();

            $table = [];
            foreach ($bots as $index => $bot) {
                $table[] = array_merge(
                    [
                        'index' => $index + 1,
                    ],
                    $this->infoProvider->getTelegramBotInfo($bot)
                );
            }
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        if (count($table) === 0) {
            $io->success('No telegram bots have been found');
        } else {
            $io->createTable()
                ->setHeaders(array_keys($table[0]))
                ->setRows($table)
                ->render()
            ;

            $io->newLine();
            $io->success('Telegram bots info have been shown');
        }

        return Command::SUCCESS;
    }
}