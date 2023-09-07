<?php

declare(strict_types=1);

namespace App\Command\Telegram;

use App\Exception\Telegram\TelegramNotFoundException;
use App\Repository\Telegram\TelegramBotRepository;
use App\Service\Telegram\Api\TelegramMessageSender;
use App\Service\Telegram\TelegramRegistry;
use App\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class TelegramBotMessageSendCommand extends Command
{
    public function __construct(
        private readonly TelegramBotRepository $repository,
        private readonly TelegramRegistry $registry,
        private readonly TelegramMessageSender $sender,
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
            ->addOption('chat', mode: InputOption::VALUE_REQUIRED, description: 'Target telegram chat id')
            ->addOption('text', mode: InputOption::VALUE_REQUIRED, description: 'Message to send')
            ->setDescription('Send message to chat from Telegram bot')
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

            $chatId = (int) $input->getOption('chat');
            $text = $input->getOption('text');

            $this->sender->sendTelegramMessage($telegram, $chatId, $text);
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->success('Message has been successfully sent');

        return Command::SUCCESS;
    }
}