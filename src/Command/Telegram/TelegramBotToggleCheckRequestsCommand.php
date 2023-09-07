<?php

declare(strict_types=1);

namespace App\Command\Telegram;

use App\Exception\Telegram\TelegramNotFoundException;
use App\Repository\Telegram\TelegramBotRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class TelegramBotToggleCheckRequestsCommand extends Command
{
    public function __construct(
        private readonly TelegramBotRepository $repository,
        private readonly EntityManagerInterface $entityManager,
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
            ->addOption('username', mode: InputOption::VALUE_REQUIRED, description: 'Telegram bot username')
            ->setDescription('Toggle telegram bot check-requests option')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $username = $input->getOption('username');
            $bot = $this->repository->findOneByUsername($username);
            if ($bot === null) {
                throw new TelegramNotFoundException($username);
            }

            $bot->setIsCheckRequests(!$bot->checkRequests());
            $this->entityManager->flush();
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->success(
            sprintf(
                '"%s" option of "%s" Telegram bot has been turned %s',
                'Check-requests',
                $bot->getUsername(),
                $bot->checkRequests() ? 'on' : 'off'
            )
        );

        return Command::SUCCESS;
    }
}