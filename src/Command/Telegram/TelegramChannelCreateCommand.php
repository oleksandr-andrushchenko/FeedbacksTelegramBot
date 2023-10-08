<?php

declare(strict_types=1);

namespace App\Command\Telegram;

use App\Enum\Telegram\TelegramGroup;
use App\Exception\Intl\CountryNotFoundException;
use App\Exception\Intl\LocaleNotFoundException;
use App\Exception\Telegram\TelegramGroupNotFoundException;
use App\Object\Telegram\TelegramChannelTransfer;
use App\Service\Intl\CountryProvider;
use App\Service\Intl\LocaleProvider;
use App\Service\Telegram\TelegramChannelCreator;
use App\Service\Telegram\TelegramChannelInfoProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TelegramChannelCreateCommand extends Command
{
    public function __construct(
        private readonly TelegramChannelCreator $creator,
        private readonly EntityManagerInterface $entityManager,
        private readonly TelegramChannelInfoProvider $infoProvider,
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
            ->addArgument('country', InputArgument::REQUIRED, 'Country code')
            ->addArgument('locale', InputArgument::REQUIRED, 'Locale code')
            ->addOption('region1', mode: InputOption::VALUE_REQUIRED, description: 'Google Region 1 short name')
            ->addOption('region2', mode: InputOption::VALUE_REQUIRED, description: 'Google Region 2 (3) short name')
            ->addOption('locality', mode: InputOption::VALUE_REQUIRED, description: 'Google Locality short name')
            ->addOption('primary', mode: InputOption::VALUE_NEGATABLE, description: 'Whether to make a channel primary or not, primary channels are unique across group, country, locale and address', default: true)
            ->setDescription('Create telegram channel (inner)')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $channelTransfer = new TelegramChannelTransfer($input->getArgument('username'));

        $groupName = $input->getArgument('group');
        $group = TelegramGroup::fromName($groupName);

        if ($group === null) {
            throw new TelegramGroupNotFoundException($groupName);
        }

        $channelTransfer->setGroup($group);
        $channelTransfer->setName($input->getArgument('name'));

        $countryCode = $input->getArgument('country');
        $country = $this->countryProvider->getCountry($countryCode);

        if ($country === null) {
            throw new CountryNotFoundException($countryCode);
        }

        $channelTransfer->setCountry($country);

        $localeCode = $input->getArgument('locale');
        $locale = $this->localeProvider->getLocale($localeCode);

        if ($locale === null) {
            throw new LocaleNotFoundException($localeCode);
        }

        $channelTransfer->setLocale($locale);
        $channelTransfer->setRegion1($input->getOption('region1'));
        $channelTransfer->setRegion2($input->getOption('region2'));
        $channelTransfer->setLocality($input->getOption('locality'));
        $channelTransfer->setPrimary($input->getOption('primary'));

        $channel = $this->creator->createTelegramChannel($channelTransfer);

        $this->entityManager->flush();

        $row = $this->infoProvider->getTelegramChannelInfo($channel);

        $io->createTable()
            ->setHeaders(array_keys($row))
            ->setRows([$row])
            ->setVertical()
            ->render()
        ;

        $io->newLine();
        $io->success(sprintf('"%s" Telegram channel has been created', $channel->getUsername()));

        return Command::SUCCESS;
    }
}