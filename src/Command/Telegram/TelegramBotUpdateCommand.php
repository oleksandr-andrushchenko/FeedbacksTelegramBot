<?php

declare(strict_types=1);

namespace App\Command\Telegram;

use App\Enum\Telegram\TelegramGroup;
use App\Exception\Intl\CountryNotFoundException;
use App\Exception\Intl\LocaleNotFoundException;
use App\Exception\Telegram\TelegramGroupNotFoundException;
use App\Exception\Telegram\TelegramNotFoundException;
use App\Object\Telegram\TelegramBotTransfer;
use App\Repository\Telegram\TelegramBotRepository;
use App\Service\Intl\CountryProvider;
use App\Service\Intl\LocaleProvider;
use App\Service\Telegram\TelegramBotInfoProvider;
use App\Service\Telegram\TelegramBotUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TelegramBotUpdateCommand extends Command
{
    public function __construct(
        private readonly TelegramBotRepository $repository,
        private readonly TelegramBotUpdater $updater,
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
            ->addArgument('username', InputArgument::REQUIRED, 'Telegram Username')
            ->addOption('group', mode: InputOption::VALUE_REQUIRED, description: 'Telegram Group (inner name)')
            ->addOption('name', mode: InputOption::VALUE_REQUIRED, description: 'Telegram Name')
            ->addOption('token', mode: InputOption::VALUE_REQUIRED, description: 'Telegram Token')
            ->addOption('country', mode: InputOption::VALUE_REQUIRED, description: 'Country code')
            ->addOption('locale', mode: InputOption::VALUE_REQUIRED, description: 'Locale code')
            ->addOption('check-updates', mode: InputOption::VALUE_NEGATABLE, description: 'Whether to check telegram updates', default: true)
            ->addOption('check-requests', mode: InputOption::VALUE_NEGATABLE, description: 'Whether to check telegram requests', default: true)
            ->addOption('accept-payments', mode: InputOption::VALUE_NEGATABLE, description: 'Whether to allow the bot accept payments', default: false)
            ->addOption('admin-id', mode: InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, description: 'Telegram user admin id (-s)')
            ->addOption('admin-only', mode: InputOption::VALUE_NEGATABLE, description: 'Whether to process admin requests only', default: true)
            ->addOption('primary', mode: InputOption::VALUE_NEGATABLE, description: 'Whether to make a bot primary or not, primary bots are unique across group, country and locale', default: true)
            ->setDescription('Update telegram bot (inner)')
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

        $botTransfer = new TelegramBotTransfer($username);

        $groupName = $input->getOption('group');

        if ($groupName !== null) {
            $group = TelegramGroup::fromName($groupName);

            if ($group === null) {
                throw new TelegramGroupNotFoundException($groupName);
            }

            $botTransfer->setGroup($group);
        }

        $name = $input->getOption('name');

        if ($name !== null) {
            $botTransfer->setName($name);
        }

        $token = $input->getOption('token');

        if ($token !== null) {
            $botTransfer->setToken($token);
        }

        $countryCode = $input->getOption('country');

        if ($countryCode !== null) {
            $country = $this->countryProvider->getCountry($countryCode);

            if ($country === null) {
                throw new CountryNotFoundException($countryCode);
            }

            $botTransfer->setCountry($country);
        }

        $localeCode = $input->getOption('locale');

        if ($localeCode !== null) {
            $locale = $this->localeProvider->getLocale($localeCode);

            if ($locale === null) {
                throw new LocaleNotFoundException($localeCode);
            }

            $botTransfer->setLocale($locale);
        }
        if ($input->hasOption('check-updates')) {
            $botTransfer->setCheckUpdates($input->getOption('check-updates'));
        }
        if ($input->hasOption('check-requests')) {
            $botTransfer->setCheckRequests($input->getOption('check-requests'));
        }
        if ($input->hasOption('accept-payments')) {
            $botTransfer->setAcceptPayments($input->getOption('accept-payments'));
        }
        if ($input->hasOption('admin-only')) {
            $botTransfer->setAdminOnly($input->getOption('admin-only'));
        }

        $adminIds = $input->getOption('admin-id');

        if ($adminIds !== null) {
            $botTransfer->setAdminIds($adminIds);
        }

        if ($input->hasOption('primary')) {
            $botTransfer->setPrimary($input->getOption('primary'));
        }

        $this->updater->updateTelegramBot($bot, $botTransfer);

        $this->entityManager->flush();

        $row = $this->infoProvider->getTelegramBotInfo($bot);

        $io->createTable()
            ->setHeaders(array_keys($row))
            ->setRows([$row])
            ->setVertical()
            ->render()
        ;

        $io->newLine();
        $io->success(sprintf('"%s" Telegram bot has been updated', $bot->getUsername()));

        return Command::SUCCESS;
    }
}