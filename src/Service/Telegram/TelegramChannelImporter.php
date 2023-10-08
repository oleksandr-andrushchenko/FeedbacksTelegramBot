<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Location;
use App\Enum\Telegram\TelegramGroup;
use App\Object\Telegram\TelegramChannelTransfer;
use App\Repository\Telegram\TelegramChannelRepository;
use App\Service\Address\AddressProvider;
use App\Service\Intl\CountryProvider;
use App\Service\Intl\LocaleProvider;
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
        $this->validateTelegramChannels($filename);

        $logger = $logger ?? fn (string $message) => null;

        $addressComponents = ['country', 'region1', 'region2', 'locality'];

        $handle = fopen($filename, 'r');
        $columns = fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($columns, $row);

            if (isset($data['skip']) && $data['skip'] === '1') {
                continue;
            }

            $location = new Location($data['latitude'], $data['longitude']);
            $address = $this->addressProvider->getAddress($location);

            $highestAddressComponent = empty($data['highest_address_component']) ? null : $data['highest_address_component'];
            if ($highestAddressComponent === null) {
                $region1 = null;
                $region2 = null;
                $locality = null;
            } else {
                $addressComponentPosition = array_search($highestAddressComponent, $addressComponents);
                $region1 = $addressComponentPosition >= array_search('region1', $addressComponents)
                    ? $address?->getRegion1()
                    : null;
                $region2 = $addressComponentPosition >= array_search('region2', $addressComponents)
                    ? $address?->getRegion2()
                    : null;
                $locality = $addressComponentPosition >= array_search('locality', $addressComponents)
                    ? $address?->getLocality()
                    : null;
            }

            $transfer = (new TelegramChannelTransfer($data['username']))
                ->setGroup(empty($data['group']) ? null : TelegramGroup::fromName($data['group']))
                ->setName(empty($data['name']) ? null : $data['name'])
                ->setCountry(empty($data['country']) ? null : $this->countryProvider->getCountry($data['country']))
                ->setLocale(empty($data['locale']) ? null : $this->localeProvider->getLocale($data['locale']))
                ->setRegion1($region1)
                ->setRegion2($region2)
                ->setLocality($locality)
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
        }

        fclose($handle);
    }

    public function validateTelegramChannels(string $filename): void
    {
        if (!file_exists($filename)) {
            throw new InvalidArgumentException(sprintf('"%s" file is not exists', $filename));
        }

        $handle = fopen($filename, 'r');

        if ($handle === false) {
            throw new RuntimeException(sprintf('Unable to open "%s" file', $filename));
        }

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

                if (!isset($data['username'])) {
                    throw new LogicException(sprintf('Row #%d has not "username" column', $index));
                }

                $index++;
            }
        } finally {
            fclose($handle);
        }
    }
}