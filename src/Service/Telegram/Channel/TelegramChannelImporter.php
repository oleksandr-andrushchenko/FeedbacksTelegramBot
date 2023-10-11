<?php

declare(strict_types=1);

namespace App\Service\Telegram\Channel;

use App\Entity\ImportResult;
use App\Entity\Location;
use App\Enum\Telegram\TelegramBotGroupName;
use App\Repository\Telegram\Channel\TelegramChannelRepository;
use App\Service\Address\AddressProvider;
use App\Service\CsvFileWalker;
use App\Service\Intl\CountryProvider;
use App\Service\Intl\LocaleProvider;
use App\Transfer\Telegram\TelegramChannelTransfer;
use Doctrine\ORM\EntityManagerInterface;

class TelegramChannelImporter
{
    public function __construct(
        private readonly AddressProvider $addressProvider,
        private readonly TelegramChannelRepository $repository,
        private readonly TelegramChannelCreator $creator,
        private readonly TelegramChannelUpdater $updater,
        private readonly TelegramChannelRemover $remover,
        private readonly CountryProvider $countryProvider,
        private readonly LocaleProvider $localeProvider,
        private readonly CsvFileWalker $walker,
        private readonly EntityManagerInterface $entityManager,
        private readonly string $stage,
    )
    {
    }

    public function importTelegramChannels(string $filename, callable $logger = null): ImportResult
    {
        $result = new ImportResult();

        $channels = $this->repository->findAll();
        $usernames = $this->getUsernames($filename);
        foreach ($channels as $channel) {
            if (!in_array($channel->getUsername(), $usernames, true) && !$this->remover->telegramChannelRemoved($channel)) {
                $this->remover->removeTelegramChannel($channel);
                $message = $channel->getUsername();
                $message .= ': [OK] deleted';
                $result->incDeletedCount();
                $logger($message);
            }
        }

        $this->entityManager->flush();

        $logger = $logger ?? fn (string $message) => null;

        $this->walk($filename, function ($data) use ($result, $logger): void {
            $addressComponents = [
                'country',
                'administrative_area_level_1',
                'administrative_area_level_2',
                'administrative_area_level_3',
            ];
            $administrativeAreaLevel1 = null;
            $administrativeAreaLevel2 = null;
            $administrativeAreaLevel3 = null;

            if (!empty($data['latitude']) && !empty($data['longitude'])) {
                if (empty($data['highest_address_component'])) {
                    return;
                }

                $location = new Location($data['latitude'], $data['longitude']);
                $address = $this->addressProvider->getAddress($location);

                if ($address === null) {
                    return;
                }

                $highestAddressComponent = $data['highest_address_component'];

                $addressComponentPosition = array_search($highestAddressComponent, $addressComponents);
                $administrativeAreaLevel1 = $addressComponentPosition >= array_search('administrative_area_level_1', $addressComponents)
                    ? $address?->getAdministrativeAreaLevel1()
                    : null;
                $administrativeAreaLevel2 = $addressComponentPosition >= array_search('administrative_area_level_2', $addressComponents)
                    ? $address?->getAdministrativeAreaLevel2()
                    : null;
                $administrativeAreaLevel3 = $addressComponentPosition >= array_search('administrative_area_level_3', $addressComponents)
                    ? $address?->getAdministrativeAreaLevel3()
                    : null;
            }

            $transfer = (new TelegramChannelTransfer($data['username']))
                ->setGroup(TelegramBotGroupName::fromName($data['group']))
                ->setName($data['name'])
                ->setCountry($this->countryProvider->getCountry($data['country']))
                ->setLocale($this->localeProvider->getLocale($data['locale']))
                ->setAdministrativeAreaLevel1($administrativeAreaLevel1)
                ->setAdministrativeAreaLevel2($administrativeAreaLevel2)
                ->setAdministrativeAreaLevel3($administrativeAreaLevel3)
                ->setPrimary($data['primary'] === '1')
            ;

            $channel = $this->repository->findAnyOneByUsername($transfer->getUsername());

            $message = $transfer->getUsername();
            $message .= ': [OK] ';

            if ($channel === null) {
                $this->creator->createTelegramChannel($transfer);
                $message .= 'created';
                $result->incCreatedCount();
            } else {
                $this->updater->updateTelegramChannel($channel, $transfer);
                $message .= 'updated';
                $result->incUpdatedCount();

                if ($this->remover->telegramChannelRemoved($channel)) {
                    $this->remover->undoTelegramChannelRemove($channel);
                    $message .= '; [OK] restored';
                    $result->incRestoredCount();
                }
            }

            $logger($message);
        });

        return $result;
    }

    private function getUsernames(string $filename): array
    {
        $usernames = [];

        $this->walk($filename, static function (array $data) use (&$usernames): void {
            $usernames[] = $data['username'];
        });

        return $usernames;
    }

    private function walk(string $filename, callable $func): void
    {
        $mandatoryColumns = [
            'skip',
            'group',
            'username',
            'name',
            'stage',
            'country',
            'locale',
            'primary',
            'latitude',
            'longitude',
            'highest_address_component',
        ];

        $this->walker->walk($filename, function (array $data) use ($func): void {
            if ($data['stage'] !== $this->stage) {
                return;
            }

            if ($data['skip'] === '1') {
                return;
            }

            $func($data);
        }, mandatoryColumns: $mandatoryColumns);
    }
}