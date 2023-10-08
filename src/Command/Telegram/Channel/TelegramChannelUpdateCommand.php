<?php

declare(strict_types=1);

namespace App\Command\Telegram\Channel;

use App\Enum\Telegram\TelegramBotGroupName;
use App\Exception\Intl\CountryNotFoundException;
use App\Exception\Intl\LocaleNotFoundException;
use App\Exception\Telegram\Bot\TelegramBotGroupNotFoundException;
use App\Exception\Telegram\Bot\TelegramBotNotFoundException;
use App\Repository\Telegram\Channel\TelegramChannelRepository;
use App\Service\Intl\CountryProvider;
use App\Service\Intl\LocaleProvider;
use App\Service\Telegram\Channel\TelegramChannelInfoProvider;
use App\Service\Telegram\Channel\TelegramChannelUpdater;
use App\Transfer\Telegram\TelegramChannelTransfer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TelegramChannelUpdateCommand extends Command
{
    public function __construct(
        private readonly TelegramChannelRepository $repository,
        private readonly TelegramChannelUpdater $updater,
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
            ->addArgument('username', InputArgument::REQUIRED, 'Telegram Username')
            ->addOption('group', mode: InputOption::VALUE_REQUIRED, description: 'Telegram Group (inner name)')
            ->addOption('name', mode: InputOption::VALUE_REQUIRED, description: 'Telegram Name')
            ->addOption('country', mode: InputOption::VALUE_REQUIRED, description: 'Country code')
            ->addOption('locale', mode: InputOption::VALUE_REQUIRED, description: 'Locale code')
            ->addOption('region1', mode: InputOption::VALUE_REQUIRED, description: 'Google Region 1 short name')
            ->addOption('region2', mode: InputOption::VALUE_REQUIRED, description: 'Google Region 2 (3) short name')
            ->addOption('locality', mode: InputOption::VALUE_REQUIRED, description: 'Google Locality short name')
            ->addOption('primary', mode: InputOption::VALUE_NEGATABLE, description: 'Whether to make a channel primary or not, primary channels are unique across group, country, locale and address', default: true)
            ->setDescription('Update telegram channel (inner)')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $username = $input->getArgument('username');
        $channel = $this->repository->findOneByUsername($username);

        if ($channel === null) {
            throw new TelegramBotNotFoundException($username);
        }

        $channelTransfer = new TelegramChannelTransfer($username);

        $groupName = $input->getOption('group');

        if ($groupName !== null) {
            $group = TelegramBotGroupName::fromName($groupName);

            if ($group === null) {
                throw new TelegramBotGroupNotFoundException($groupName);
            }

            $channelTransfer->setGroup($group);
        }

        $name = $input->getOption('name');

        if ($name !== null) {
            $channelTransfer->setName($name);
        }

        $countryCode = $input->getOption('country');

        if ($countryCode !== null) {
            $country = $this->countryProvider->getCountry($countryCode);

            if ($country === null) {
                throw new CountryNotFoundException($countryCode);
            }

            $channelTransfer->setCountry($country);
        }

        $localeCode = $input->getOption('locale');

        if ($localeCode !== null) {
            $locale = $this->localeProvider->getLocale($localeCode);

            if ($locale === null) {
                throw new LocaleNotFoundException($localeCode);
            }

            $channelTransfer->setLocale($locale);
        }

        $region1 = $input->getOption('region1');

        if ($region1 !== null) {
            $channelTransfer->setRegion1($region1);
        }

        $region2 = $input->getOption('region2');

        if ($region2 !== null) {
            $channelTransfer->setRegion2($region2);
        }

        $locality = $input->getOption('locality');

        if ($locality !== null) {
            $channelTransfer->setLocality($locality);
        }

        if ($input->hasOption('primary')) {
            $channelTransfer->setPrimary($input->getOption('primary'));
        }

        $this->updater->updateTelegramChannel($channel, $channelTransfer);

        $this->entityManager->flush();

        $row = $this->infoProvider->getTelegramChannelInfo($channel);

        $io->createTable()
            ->setHeaders(array_keys($row))
            ->setRows([$row])
            ->setVertical()
            ->render()
        ;

        $io->newLine();
        $io->success(sprintf('"%s" Telegram channel has been updated', $channel->getUsername()));

        return Command::SUCCESS;
    }
}