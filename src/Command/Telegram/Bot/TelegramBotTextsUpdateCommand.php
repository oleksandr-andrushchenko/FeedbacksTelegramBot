<?php

declare(strict_types=1);

namespace App\Command\Telegram\Bot;

use App\Exception\Telegram\Bot\TelegramBotNotFoundException;
use App\Repository\Telegram\Bot\TelegramBotRepository;
use App\Service\Telegram\Bot\Api\TelegramBotTextsUpdater;
use App\Service\Telegram\Bot\TelegramBotTextsInfoProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TelegramBotTextsUpdateCommand extends Command
{
    public function __construct(
        private readonly TelegramBotRepository $repository,
        private readonly TelegramBotTextsUpdater $updater,
        private readonly EntityManagerInterface $entityManager,
        private readonly TelegramBotTextsInfoProvider $infoProvider,
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
            ->addArgument('username', InputArgument::REQUIRED, 'Telegram Username')
            ->setDescription('Update telegram bot name, short and long descriptions')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $username = $input->getArgument('username');
        $bot = $this->repository->findOneByUsername($username);

        if ($bot === null) {
            throw new TelegramBotNotFoundException($username);
        }

        $this->updater->updateTelegramDescriptions($bot);
        $this->entityManager->flush();

        $row = $this->infoProvider->getTelegramBotTextsInfo($bot);

        $io->createTable()
            ->setHeaders(array_keys($row))
            ->setRows([$row])
            ->setVertical()
            ->render()
        ;

        $io->newLine();
        $io->success('Telegram bot texts have been updated');

        return Command::SUCCESS;
    }
}