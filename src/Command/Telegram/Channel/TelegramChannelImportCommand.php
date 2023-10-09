<?php

declare(strict_types=1);

namespace App\Command\Telegram\Channel;

use App\Service\Doctrine\DryRunner;
use App\Service\Telegram\Channel\TelegramChannelImporter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class TelegramChannelImportCommand extends Command
{
    public function __construct(
        private readonly string $dataDir,
        private readonly TelegramChannelImporter $importer,
        private readonly DryRunner $dryRunner,
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
            ->addArgument('file', InputArgument::REQUIRED, 'Base Filename name to import')
            ->addOption('dry-run', mode: InputOption::VALUE_NONE, description: 'Dry run')
            ->setDescription('Import telegram channels')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $filename = $this->dataDir . '/' . $input->getArgument('file');
        $dryRun = $input->getOption('dry-run');

        if (!$dryRun) {
            $confirmed = $io->askQuestion(
                new ConfirmationQuestion(sprintf('Are you sure you want to import "%s" Telegram channels file?', $filename), true)
            );

            if (!$confirmed) {
                $io->warning(
                    sprintf('"%s" Telegram channels file import has been cancelled', $filename)
                );

                return Command::SUCCESS;
            }
        }

        $countCreated = 0;
        $countUpdated = 0;

        $logger = fn (string $message) => $io->note($message);
        $func = fn () => $this->importer->importTelegramChannels($filename, $logger, $countCreated, $countUpdated);

        if ($dryRun) {
            $this->dryRunner->dryRun($func);
        } else {
            $this->entityManager->wrapInTransaction($func);
        }

        $io->newLine();
        $io->success(
            sprintf(
                'Telegram bots have been imported, created: %d, updated: %d',
                $countCreated,
                $countUpdated
            )
        );

        return Command::SUCCESS;
    }
}