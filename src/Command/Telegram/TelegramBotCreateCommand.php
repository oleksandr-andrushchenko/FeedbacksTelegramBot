<?php

declare(strict_types=1);

namespace App\Command\Telegram;

use App\Enum\Telegram\TelegramGroup;
use App\Object\Telegram\TelegramBotTransfer;
use App\Service\Telegram\TelegramBotCreator;
use App\Service\Telegram\TelegramBotInfoProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class TelegramBotCreateCommand extends Command
{
    public function __construct(
        private readonly TelegramBotCreator $creator,
        private readonly EntityManagerInterface $entityManager,
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
            ->addArgument('username', InputArgument::REQUIRED, 'Username')
            ->addArgument('token', InputArgument::REQUIRED, 'Token')
            ->addArgument('country', InputArgument::REQUIRED, 'Country code')
            ->addArgument('locale', InputArgument::REQUIRED, 'Locale code')
            ->addArgument('group', InputArgument::REQUIRED, 'Group name')
            ->addArgument('primary-bot-username', InputArgument::OPTIONAL, 'Primary username')
            ->setDescription('Create telegram bot')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $botTransfer = new TelegramBotTransfer(
                $input->getArgument('username'),
                $input->getArgument('token'),
                $input->getArgument('country'),
                $input->getArgument('locale'),
                TelegramGroup::fromName($input->getArgument('group')),
                $input->getArgument('primary-bot-username'),
            );

            $bot = $this->creator->createTelegramBot($botTransfer);
            $this->entityManager->flush();
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $row = $this->infoProvider->getTelegramBotInfo($bot);

        $io->createTable()
            ->setHeaders(array_keys($row))
            ->setRows([$row])
            ->setVertical()
            ->render()
        ;

        $io->newLine();
        $io->success('Telegram bot has been created');

        return Command::SUCCESS;
    }
}