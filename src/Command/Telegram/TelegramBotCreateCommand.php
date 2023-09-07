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
use App\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
            ->addOption('group', mode: InputOption::VALUE_REQUIRED, description: 'Telegram Group name')
            ->addOption('username', mode: InputOption::VALUE_REQUIRED, description: 'Telegram bot username')
            ->addOption('token', mode: InputOption::VALUE_REQUIRED, description: 'Telegram bot Token')
            ->addOption('country', mode: InputOption::VALUE_REQUIRED, description: 'Telegram bot Country code')
            ->addOption('primary-username', mode: InputOption::VALUE_OPTIONAL, description: 'Telegram Primary bot username')
            ->setDescription('Create telegram bot')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function invoke(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $groupName = $input->getOption('group');
            $group = TelegramGroup::fromName($groupName);
            if ($group === null) {
                throw new TelegramGroupNotFoundException($groupName);
            }

            $primaryUsername = $input->getOption('primary-username');
            if ($primaryUsername === null) {
                $primaryBot = null;
            } else {
                $primaryBot = $this->repository->findOneByUsername($primaryUsername);

                if ($primaryBot === null) {
                    throw new TelegramNotFoundException($primaryUsername);
                }
            }

            $botTransfer = new TelegramBotTransfer(
                $input->getOption('username'),
                $input->getOption('token'),
                $input->getOption('country'),
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