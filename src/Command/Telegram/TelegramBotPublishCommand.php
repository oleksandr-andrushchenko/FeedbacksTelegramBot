<?php

declare(strict_types=1);

namespace App\Command\Telegram;

use App\Exception\Telegram\TelegramNotFoundException;
use App\Repository\Telegram\TelegramBotRepository;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class TelegramBotPublishCommand extends Command
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
            ->addArgument('name', InputArgument::REQUIRED, 'Telegram bot username')
            ->setDescription('Publish telegram bot')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $username = $input->getArgument('name');
            $bot = $this->repository->findOneByUsername($username);

            if ($bot === null) {
                throw new TelegramNotFoundException($username);
            }

            if (!$bot->adminOnly()) {
                throw new LogicException(sprintf('"%s" telegram bot is non-admin only already', $bot->getUsername()));
            }

            $bot->setIsAdminOnly(false);
            $this->entityManager->flush();
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->success(sprintf('"%s" Telegram bot has been published', $bot->getUsername()));

        return Command::SUCCESS;
    }
}