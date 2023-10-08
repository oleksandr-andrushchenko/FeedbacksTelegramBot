<?php

declare(strict_types=1);

namespace App\Command\Telegram;

use App\Exception\Telegram\TelegramNotFoundException;
use App\Repository\Telegram\TelegramBotRepository;
use App\Service\Telegram\Api\TelegramCommandsRemover;
use App\Service\Telegram\TelegramRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

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
            ->addArgument('username', InputArgument::REQUIRED, 'Telegram Username')
            ->setDescription('Remove telegram bot commands')
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
            throw new TelegramNotFoundException($username);
        }

        $telegram = $this->registry->getTelegram($bot);

        $this->remover->removeTelegramCommands($telegram);
        $bot->setCommandsSet(false);

        $this->entityManager->flush();

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
        $io->success(sprintf('"%s" Telegram bot\'s commands have been removed', $bot->getUsername()));

        return Command::SUCCESS;
    }
}