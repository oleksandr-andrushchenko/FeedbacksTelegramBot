<?php

declare(strict_types=1);

namespace App\Command\Telegram\Bot;

use App\Exception\Telegram\Bot\TelegramBotNotFoundException;
use App\Repository\Telegram\Bot\TelegramBotRepository;
use App\Service\Telegram\Bot\Api\TelegramBotMessageSender;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TelegramBotMessageSendCommand extends Command
{
    public function __construct(
        private readonly TelegramBotRepository $repository,
        private readonly TelegramBotMessageSender $sender,
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
            ->addArgument('chat', InputArgument::REQUIRED, 'Target telegram chat id')
            ->addArgument('text', InputArgument::REQUIRED, 'Message to send')
            ->setDescription('Send message to chat from Telegram bot')
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

        $chatId = $input->getArgument('chat');
        $chatId = is_numeric($chatId) ? $chatId : ('@' . $chatId);
        $text = $input->getArgument('text');

        $this->sender->sendTelegramMessage($bot, $chatId, $text);

        $io->success('Message has been successfully sent');

        return Command::SUCCESS;
    }
}