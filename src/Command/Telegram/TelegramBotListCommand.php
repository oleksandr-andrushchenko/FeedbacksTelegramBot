<?php

declare(strict_types=1);

namespace App\Command\Telegram;

use App\Enum\Telegram\TelegramGroup;
use App\Repository\Telegram\TelegramBotRepository;
use App\Service\Telegram\Api\TelegramWebhookInfoProvider;
use App\Service\Telegram\TelegramRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class TelegramBotListCommand extends Command
{
    public function __construct(
        private readonly TelegramBotRepository $repository,
        private readonly TelegramRegistry $registry,
        private readonly TelegramWebhookInfoProvider $provider,
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
            ->addArgument('name', InputArgument::REQUIRED, 'Telegram bot group')
            ->setDescription('List telegram bots')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $bots = $this->repository->findByGroup(TelegramGroup::fromName($input->getArgument('name')));

            $table = [];
            foreach ($bots as $index => $bot) {
                $telegram = $this->registry->getTelegram($bot->getUsername());
                $webhookInfo = $this->provider->getTelegramWebhookInfo($telegram);

                $table[] = [
                    '#' => $index + 1,
                    'username' => $bot->getUsername(),
                    'webhook' => $webhookInfo === '' ? 'Inactive' : 'Active',
                    'country' => $bot->getCountryCode(),
                    'locale' => $bot->getLocaleCode(),
                    'primary' => $bot->getPrimaryBot() === null ? 'Yes' : sprintf('No (%s)', $bot->getPrimaryBot()->getUsername()),
                    'check updates' => $bot->checkUpdates() ? 'Yes' : 'No',
                    'check requests' => $bot->checkRequests() ? 'Yes' : 'No',
                    'accept payments' => $bot->acceptPayments() ? 'Yes' : 'No',
                    'admin only' => $bot->adminOnly() ? 'Yes' : 'No',
                ];
            }
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        if (count($table) === 0) {
            $io->success('No telegram bots have been found');
        } else {
            $io->createTable()
                ->setHeaders(array_map(fn (string $column) => ucfirst($column), array_keys($table[0])))
                ->setRows($table)
                ->render()
            ;

            $io->newLine();
            $io->success('Telegram bots have been listed');
        }

        return Command::SUCCESS;
    }
}