<?php

declare(strict_types=1);

namespace App\Command\Telegram;

use App\Exception\Telegram\TelegramNotFoundException;
use App\Repository\Telegram\TelegramBotRepository;
use App\Service\Telegram\Api\TelegramWebhookRemover;
use App\Service\Telegram\TelegramRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class TelegramBotWebhookRemoveCommand extends Command
{
    public function __construct(
        private readonly TelegramBotRepository $repository,
        private readonly TelegramRegistry $registry,
        private readonly TelegramWebhookRemover $remover,
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
            ->setDescription('Remove telegram bot webhook')
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

            if (!$bot->webhookSet()) {
                $io->warning('No webhook found for remove');

                $confirmed = $io->askQuestion(
                    new ConfirmationQuestion(
                        sprintf('Continue removing "%s" telegram bot webhook anyway?', $bot->getUsername()),
                        true
                    )
                );
            }

            $confirmed = $confirmed ?? $io->askQuestion(
                new ConfirmationQuestion(
                    sprintf('Are you sure you want to remove "%s" telegram bot webhook?', $bot->getUsername()),
                    true
                )
            );

            if (!$confirmed) {
                $io->info(
                    sprintf('"%s" telegram bot webhook removing has been cancelled', $bot->getUsername())
                );

                return Command::SUCCESS;
            }

            $telegram = $this->registry->getTelegram($bot->getUsername());

            $this->remover->removeTelegramWebhook($telegram);
            $bot->setIsWebhookSet(false);

            $this->entityManager->flush();
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->success('Telegram bot webhook has been removed');

        return Command::SUCCESS;
    }
}