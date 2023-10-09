<?php

declare(strict_types=1);

namespace App\Service\Telegram\Channel;

use App\Entity\Location;
use App\Enum\Telegram\TelegramBotGroupName;
use App\Repository\Telegram\Channel\TelegramChannelRepository;
use App\Service\Address\AddressProvider;
use App\Service\Intl\CountryProvider;
use App\Service\Intl\LocaleProvider;
use App\Transfer\Telegram\TelegramChannelTransfer;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

class TelegramChannelImporter
{
    public function __construct(
        private readonly AddressProvider $addressProvider,
        private readonly TelegramChannelRepository $repository,
        private readonly TelegramChannelCreator $creator,
        private readonly TelegramChannelUpdater $updater,
        private readonly CountryProvider $countryProvider,
        private readonly LocaleProvider $localeProvider,
    )
    {
    }

    public function importTelegramChannels(
        string $filename,
        callable $logger = null,
        int &$countCreated = 0,
        int &$countUpdated = 0,
    ): void
    {
        if (!file_exists($filename)) {
            throw new InvalidArgumentException(sprintf('"%s" file is not exists', $filename));
        }

        $handle = fopen($filename, 'r');

        if ($handle === false) {
            throw new RuntimeException(sprintf('Unable to open "%s" file', $filename));
        }

        $logger = $logger ?? fn (string $message) => null;

        $addressComponents = ['country', 'administrative_area_level_1', 'administrative_area_level_2', 'administrative_area_level_3'];

        try {
            $columns = fgetcsv($handle);
            $count = count($columns);

            $index = 2;

            while (($row = fgetcsv($handle)) !== false) {
                $rowCount = count($row);

                if ($count !== $rowCount) {
                    throw new LogicException(sprintf('Row #%d has wrong number of columns. Should have %d columns, got %d', $index, $count, $rowCount));
                }

                $data = array_combine($columns, $row);

                if (isset($data['skip']) && $data['skip'] === '1') {
                    continue;
                }

                if (!isset($data['username'])) {
                    throw new LogicException(sprintf('Row #%d has not "username" column', $index));
                }

                $administrativeAreaLevel1 = null;
                $administrativeAreaLevel2 = null;
                $administrativeAreaLevel3 = null;

                if (!empty($data['latitude']) && !empty($data['longitude'])) {
                    if (empty($data['highest_address_component'])) {
                        continue;
                    }

                    $location = new Location($data['latitude'], $data['longitude']);
                    $address = $this->addressProvider->getAddress($location);

                    if ($address === null) {
                        continue;
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
                    ->setGroup(empty($data['group']) ? null : TelegramBotGroupName::fromName($data['group']))
                    ->setName(empty($data['name']) ? null : $data['name'])
                    ->setCountry(empty($data['country']) ? null : $this->countryProvider->getCountry($data['country']))
                    ->setLocale(empty($data['locale']) ? null : $this->localeProvider->getLocale($data['locale']))
                    ->setAdministrativeAreaLevel1($administrativeAreaLevel1)
                    ->setAdministrativeAreaLevel2($administrativeAreaLevel2)
                    ->setAdministrativeAreaLevel3($administrativeAreaLevel3)
                    ->setPrimary(isset($data['primary']) && $data['primary'] === '1')
                ;

                $channel = $this->repository->findOneByUsername($transfer->getUsername());

                if ($channel === null) {
                    $channel = $this->creator->createTelegramChannel($transfer);
                    $countCreated++;
                } else {
                    $this->updater->updateTelegramChannel($channel, $transfer);
                    $countUpdated++;
                }

                $message = $channel->getUsername();
                $message .= ': [OK] ';
                $message .= $channel->getId() === null ? 'created' : 'updated';

                $logger($message);

                $index++;
            }
        } finally {
            fclose($handle);
        }
    }
}