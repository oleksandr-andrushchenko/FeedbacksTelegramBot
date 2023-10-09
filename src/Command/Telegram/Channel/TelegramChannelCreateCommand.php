<?php

declare(strict_types=1);

namespace App\Command\Telegram\Channel;

use App\Enum\Telegram\TelegramBotGroupName;
use App\Exception\Intl\CountryNotFoundException;
use App\Exception\Intl\LocaleNotFoundException;
use App\Exception\Telegram\Bot\TelegramBotGroupNotFoundException;
use App\Transfer\Telegram\TelegramChannelTransfer;
use App\Service\Intl\CountryProvider;
use App\Service\Intl\LocaleProvider;
use App\Service\Telegram\Channel\TelegramChannelCreator;
use App\Service\Telegram\Channel\TelegramChannelInfoProvider;
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
            ->addOption('administrative-area-level-1', mode: InputOption::VALUE_REQUIRED, description: 'Google Administrative area level 1 short name')
            ->addOption('administrative-area-level-2', mode: InputOption::VALUE_REQUIRED, description: 'Google Administrative area level 2 short name')
            ->addOption('administrative-area-level-3', mode: InputOption::VALUE_REQUIRED, description: 'Google Administrative area level 3 short name')
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
        $group = TelegramBotGroupName::fromName($groupName);

        if ($group === null) {
            throw new TelegramBotGroupNotFoundException($groupName);
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
        $channelTransfer->setAdministrativeAreaLevel1($input->getOption('administrative-area-level-1'));
        $channelTransfer->setAdministrativeAreaLevel2($input->getOption('administrative-area-level-2'));
        $channelTransfer->setAdministrativeAreaLevel3($input->getOption('administrative-area-level-3'));
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