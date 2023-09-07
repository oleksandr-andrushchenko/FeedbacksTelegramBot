<?php

declare(strict_types=1);

namespace App\Command\Telegram;

use App\Exception\Telegram\TelegramNotFoundException;
use App\Repository\Telegram\TelegramBotRepository;
use App\Service\Telegram\Api\TelegramCommandsRemover;
use App\Service\Telegram\TelegramRegistry;
use Doctrine\ORM\EntityManagerInterface;
use App\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class TelegramBotCommandsRemoveCommand extends Command
{
    public function __construct(
        private readonly TelegramBotRepository $repository,
        private readonly TelegramRegistry $registry,
        private readonly TelegramCommandsRemover $remover,
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
            ->setDescription('Remove telegram bot commands')
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

            $telegram = $this->registry->getTelegram($bot->getUsername());

            $this->remover->removeTelegramCommands($telegram);
            $bot->setIsCommandsSet(false);

            $this->entityManager->flush();
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $row = [];
        $myCommands = $this->remover->getMyCommands();

        foreach ($myCommands as $myCommandsItem) {
            $value = sprintf('%s + %s', $myCommandsItem->getLocaleCode(), $myCommandsItem->getScope()->toJson());
            foreach ($myCommandsItem->getCommands() as $command) {
                if (!isset($row[$command->getName()])) {
                    $row[$command->getName()] = [];
                }

                $row[$command->getName()][] = $value;
            }
        }

        foreach ($row as $k => $v) {
            $row[$k] = implode('; ', $v);
        }

        $io->createTable()
            ->setHeaders(array_keys($row))
            ->setRows([$row])
            ->setVertical()
            ->render()
        ;

        $io->newLine();
        $io->success('Telegram bot commands have been removed');

        return Command::SUCCESS;
    }
}