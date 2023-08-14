<?php

declare(strict_types=1);

namespace App\Command\Telegram;

use App\Enum\Telegram\TelegramGroup;
use App\Exception\Telegram\TelegramGroupNotFoundException;
use App\Exception\Telegram\TelegramNotFoundException;
use App\Object\Telegram\TelegramBotTransfer;
use App\Repository\Telegram\TelegramBotRepository;
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
        private readonly TelegramBotRepository $repository,
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
            ->addArgument('group', InputArgument::REQUIRED, 'Telegram Group name')
            ->addArgument('username', InputArgument::REQUIRED, 'Telegram bot username')
            ->addArgument('token', InputArgument::REQUIRED, 'Telegram bot Token')
            ->addArgument('country', InputArgument::REQUIRED, 'Telegram bot Country code')
            ->addArgument('locale', InputArgument::REQUIRED, 'Telegram bot Locale code')
            ->addArgument('primary-username', InputArgument::OPTIONAL, 'Telegram Primary bot username')
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
            $groupName = $input->getArgument('group');
            $group = TelegramGroup::fromName($groupName);
            if ($group === null) {
                throw new TelegramGroupNotFoundException($groupName);
            }

            $primaryUsername = $input->getArgument('primary-username');
            if ($primaryUsername === null) {
                $primaryBot = null;
            } else {
                $primaryBot = $this->repository->findOneByUsername($primaryUsername);

                if ($primaryBot === null) {
                    throw new TelegramNotFoundException($primaryUsername);
                }
            }

            $botTransfer = new TelegramBotTransfer(
                $input->getArgument('username'),
                $input->getArgument('token'),
                $input->getArgument('country'),
                $input->getArgument('locale'),
                $group,
                $primaryBot,
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