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
            ->addArgument('group', InputArgument::REQUIRED, 'Telegram Group (inner name)')
            ->addArgument('username', InputArgument::REQUIRED, 'Telegram Username')
            ->addArgument('name', InputArgument::REQUIRED, 'Telegram Name')
            ->addArgument('token', InputArgument::REQUIRED, 'Telegram Token')
            ->addArgument('country', InputArgument::REQUIRED, 'Country code')
            ->addArgument('locale', InputArgument::REQUIRED, 'Locale code')
            ->addOption('check-updates', mode: InputOption::VALUE_NEGATABLE, description: 'Whether to check telegram updates', default: true)
            ->addOption('check-requests', mode: InputOption::VALUE_NEGATABLE, description: 'Whether to check telegram requests', default: true)
            ->addOption('accept-payments', mode: InputOption::VALUE_NEGATABLE, description: 'Whether to allow the bot accept payments', default: false)
            ->addOption('admin-id', mode: InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, description: 'Telegram user admin id (-s)')
            ->addOption('admin-only', mode: InputOption::VALUE_NEGATABLE, description: 'Whether to process admin requests only', default: true)
            ->addOption('primary', mode: InputOption::VALUE_NEGATABLE, description: 'Whether to make a bot primary or not, primary bots are unique across group, country and locale', default: true)
            ->setDescription('Create telegram bot (inner)')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $botTransfer = new TelegramBotTransfer($input->getArgument('username'));

        $groupName = $input->getArgument('group');
        $group = TelegramGroup::fromName($groupName);

        if ($group === null) {
            throw new TelegramGroupNotFoundException($groupName);
        }

        $botTransfer->setGroup($group);
        $botTransfer->setName($input->getArgument('name'));
        $botTransfer->setToken($input->getArgument('token'));

        $countryCode = $input->getArgument('country');
        $country = $this->countryProvider->getCountry($countryCode);

        if ($country === null) {
            throw new CountryNotFoundException($countryCode);
        }

        $botTransfer->setCountry($country);

        $localeCode = $input->getArgument('locale');
        $locale = $this->localeProvider->getLocale($localeCode);

        if ($locale === null) {
            throw new LocaleNotFoundException($localeCode);
        }

        $botTransfer->setLocale($locale);
        $botTransfer->setCheckUpdates($input->getOption('check-updates'));
        $botTransfer->setCheckRequests($input->getOption('check-requests'));
        $botTransfer->setAcceptPayments($input->getOption('accept-payments'));
        $botTransfer->setAdminOnly($input->getOption('admin-only'));
        $botTransfer->setAdminIds($input->getOption('admin-id'));
        $botTransfer->setPrimary($input->getOption('primary'));

        $bot = $this->creator->createTelegramBot($botTransfer);

        $this->entityManager->flush();

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