<?php

declare(strict_types=1);

namespace App\Command\Telegram;

use App\Enum\Telegram\TelegramGroup;
use App\Exception\Intl\CountryNotFoundException;
use App\Exception\Intl\LocaleNotFoundException;
use App\Exception\Telegram\TelegramGroupNotFoundException;
use App\Object\Telegram\TelegramBotTransfer;
use App\Service\Intl\CountryProvider;
use App\Service\Intl\LocaleProvider;
use App\Service\Telegram\TelegramBotCreator;
use App\Service\Telegram\TelegramBotInfoProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class TelegramBotCreateCommand extends Command
{
    public function __construct(
        private readonly TelegramBotCreator $creator,
        private readonly EntityManagerInterface $entityManager,
        private readonly TelegramBotInfoProvider $infoProvider,
        private readonly CountryProvider $countryProvider,
        private readonly LocaleProvider $localeProvider,
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
            ->addArgument('group', InputArgument::REQUIRED, 'Telegram Group name')
            ->addArgument('username', InputArgument::REQUIRED, 'Telegram bot username')
            ->addArgument('name', InputArgument::REQUIRED, 'Telegram bot name')
            ->addArgument('token', InputArgument::REQUIRED, 'Telegram bot Token')
            ->addArgument('country', InputArgument::REQUIRED, 'Telegram bot Country code')
            ->addOption('locale', mode: InputOption::VALUE_REQUIRED, description: 'Telegram bot Locale code')
            ->addOption('channel-username', mode: InputOption::VALUE_REQUIRED, description: 'Telegram channel username where to send activity')
            ->addOption('group-username', mode: InputOption::VALUE_REQUIRED, description: 'Telegram group username which should be linked to telegram channel')
            ->addOption('check-updates', mode: InputOption::VALUE_NEGATABLE, description: 'Whether to check telegram updates', default: true)
            ->addOption('check-requests', mode: InputOption::VALUE_NEGATABLE, description: 'Whether to check telegram requests', default: true)
            ->addOption('accept-payments', mode: InputOption::VALUE_NEGATABLE, description: 'Whether to allow the bot accept payments', default: false)
            ->addOption('admin-id', mode: InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, description: 'Telegram user admin id (-s)')
            ->addOption('admin-only', mode: InputOption::VALUE_NEGATABLE, description: 'Whether to process admin requests only', default: true)
            ->setDescription('Create telegram bot')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $botTransfer = new TelegramBotTransfer($input->getArgument('username'));

            $groupName = $input->getArgument('group');
            $group = TelegramGroup::fromName($groupName);

            if ($group === null) {
                throw new TelegramGroupNotFoundException($groupName);
            }

            $botTransfer->setGroup($group);

            $botTransfer->setName($input->getArgument('name'));

            $channelUsername = $input->getOption('channel-username');

            if ($channelUsername !== null) {
                $botTransfer->setChannelUsername($channelUsername);
            }

            $groupUsername = $input->getOption('group-username');

            if ($groupUsername !== null) {
                $botTransfer->setGroupUsername($groupUsername);
            }

            $botTransfer->setToken($input->getArgument('token'));

            $countryCode = $input->getArgument('country');
            $country = $this->countryProvider->getCountry($countryCode);

            if ($country === null) {
                throw new CountryNotFoundException($countryCode);
            }

            $botTransfer->setCountry($country);

            $localeCode = $input->getOption('locale');

            if ($localeCode !== null) {
                $locale = $this->localeProvider->getLocale($localeCode);

                if ($locale === null) {
                    throw new LocaleNotFoundException($localeCode);
                }

                $botTransfer->setLocale($locale);
            }

            $botTransfer->setCheckUpdates($input->getOption('check-updates'));
            $botTransfer->setCheckRequests($input->getOption('check-requests'));
            $botTransfer->setAcceptPayments($input->getOption('accept-payments'));
            $botTransfer->setAdminOnly($input->getOption('admin-only'));

            $botTransfer->setAdminIds($input->getOption('admin-id'));

            $bot = $this->creator->createTelegramBot($botTransfer);

            $this->entityManager->flush();
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $row = $this->infoProvider->getTelegramBotInfo($bot);

        $io->createTable()
            ->setHeaders(array_keys($row))
            ->setRows([$row])
            ->setVertical()
            ->render()
        ;

        $io->newLine();
        $io->success(sprintf('"%s" Telegram bot has been created', $bot->getUsername()));

        return Command::SUCCESS;
    }
}