<?php

declare(strict_types=1);

namespace App\Service\Telegram\Channel;

use App\Entity\ImportResult;
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
        private readonly TelegramChannelRemover $remover,
        private readonly CountryProvider $countryProvider,
        private readonly LocaleProvider $localeProvider,
        private readonly string $stage,
    )
    {
    }

    public function importTelegramChannels(string $filename, callable $logger = null): ImportResult
    {
        if (!file_exists($filename)) {
            throw new InvalidArgumentException(sprintf('"%s" file is not exists', $filename));
        }

        $handle = fopen($filename, 'r');

        if ($handle === false) {
            throw new RuntimeException(sprintf('Unable to open "%s" file', $filename));
        }

        $result = new ImportResult();

        try {
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
                'delete',
            ];
            $addressComponents = [
                'country',
                'administrative_area_level_1',
                'administrative_area_level_2',
                'administrative_area_level_3',
            ];

            $logger = $logger ?? fn (string $message) => null;

            $columns = fgetcsv($handle);
            $count = count($columns);

            $index = 1;

            while (($row = fgetcsv($handle)) !== false) {
                $index++;

                if (!isset($row[0]) || [null] === $row) {
                    continue;
                }

                $rowCount = count($row);

                if ($count !== $rowCount) {
                    throw new LogicException(sprintf('Row #%d has wrong number of columns. Should have %d columns, got %d', $index, $count, $rowCount));
                }

                $data = array_combine($columns, $row);

                foreach ($mandatoryColumns as $mandatoryColumn) {
                    if (!array_key_exists($mandatoryColumn, $data)) {
                        throw new LogicException(sprintf('Row #%d has not "%s" column', $index, $mandatoryColumn));
                    }
                }

                if ($data['stage'] !== $this->stage) {
                    continue;
                }

                if ($data['skip'] === '1') {
                    continue;
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

                if ($data['delete'] === '1') {
                    if ($channel === null) {
                        $message .= 'unchanged (nothing to delete)';
                        $result->incUnchangedCount();
                    } else {
                        if ($this->remover->telegramChannelRemoved($channel)) {
                            $message .= 'unchanged (deleted already)';
                            $result->incUnchangedCount();
                        } else {
                            $this->remover->removeTelegramChannel($channel);
                            $message .= 'deleted';
                            $result->incDeletedCount();
                        }
                    }
                } elseif ($channel === null) {
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
            }
        } finally {
            fclose($handle);
        }

        return $result;
    }
}