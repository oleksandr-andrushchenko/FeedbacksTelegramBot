<?php

declare(strict_types=1);

namespace App\Command\Telegram;

use App\Exception\Telegram\TelegramNotFoundException;
use App\Repository\Telegram\TelegramBotRepository;
use App\Service\Telegram\Api\TelegramTextsUpdater;
use App\Service\Telegram\TelegramBotTextsInfoProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class TelegramBotTextsUpdateCommand extends Command
{
    public function __construct(
        private readonly TelegramBotRepository $repository,
        private readonly TelegramTextsUpdater $updater,
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
            ->addOption('username', mode: InputOption::VALUE_REQUIRED, description: 'Telegram bot username')
            ->setDescription('Update telegram bot name, short and long descriptions')
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

            $this->updater->updateTelegramDescriptions($bot);
            $bot->setIsTextsSet(true);

            $this->entityManager->flush();
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

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