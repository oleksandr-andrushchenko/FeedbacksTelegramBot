<?php

declare(strict_types=1);

namespace App\Command\Telegram;

use App\Exception\Telegram\TelegramNotFoundException;
use App\Repository\Telegram\TelegramBotRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class TelegramBotToggleCheckUpdatesCommand extends Command
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
            ->setDescription('Toggle telegram bot check-updates option')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function invoke(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $username = $input->getOption('username');
            $bot = $this->repository->findOneByUsername($username);
            if ($bot === null) {
                throw new TelegramNotFoundException($username);
            }

            $bot->setIsCheckUpdates(!$bot->checkUpdates());
            $this->entityManager->flush();
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->success(
            sprintf(
                '"%s" option of "%s" Telegram bot has been turned %s',
                'Check-updates',
                $bot->getUsername(),
                $bot->checkUpdates() ? 'on' : 'off'
            )
        );

        return Command::SUCCESS;
    }
}