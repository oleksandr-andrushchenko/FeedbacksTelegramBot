<?php

declare(strict_types=1);

namespace App\Command\Telegram;

use App\Enum\Telegram\TelegramGroup;
use App\Exception\Telegram\TelegramGroupNotFoundException;
use App\Repository\Telegram\TelegramBotRepository;
use App\Service\Telegram\TelegramBotInfoProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class TelegramBotListInfoCommand extends Command
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
            ->addArgument('group', InputArgument::REQUIRED, 'Telegram bot group')
            ->setDescription('List telegram bots info')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $groupName = $input->getArgument('group');
            $group = TelegramGroup::fromName($groupName);

            if ($group === null) {
                throw new TelegramGroupNotFoundException($groupName);
            }

            $bots = $this->repository->findByGroup($group);

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
                ->setVertical()
                ->render()
            ;

            $io->newLine();
            $io->success('Telegram bots info have been listed');
        }

        return Command::SUCCESS;
    }
}