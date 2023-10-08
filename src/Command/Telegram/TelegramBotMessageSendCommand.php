<?php

declare(strict_types=1);

namespace App\Command\Telegram;

use App\Exception\Telegram\TelegramNotFoundException;
use App\Repository\Telegram\TelegramBotRepository;
use App\Service\Telegram\Api\TelegramMessageSender;
use App\Service\Telegram\TelegramRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

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
            throw new TelegramNotFoundException($username);
        }

        $telegram = $this->registry->getTelegram($bot);

        $chatId = $input->getArgument('chat');
        $chatId = is_numeric($chatId) ? $chatId : ('@' . $chatId);
        $text = $input->getArgument('text');

        $this->sender->sendTelegramMessage($telegram, $chatId, $text);

        $io->success('Message has been successfully sent');

        return Command::SUCCESS;
    }
}