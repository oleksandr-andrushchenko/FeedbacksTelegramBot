<?php

declare(strict_types=1);

namespace App\Tests\Service\Telegram;

use App\Entity\Address\AddressLocality;
use App\Entity\Telegram\TelegramBot;
use App\Entity\User\User;
use App\Enum\Telegram\TelegramGroup;
use App\Repository\Telegram\TelegramBotRepository;
use App\Service\Telegram\BetterMatchTelegramBotProvider;
use PHPUnit\Framework\TestCase;
use Generator;

class BetterMatchTelegramBotProviderTest extends TestCase
{
    /**
     * @param array $userAddressComponents
     * @param array $botAddressComponents
     * @param int $expectedPoints
     * @return void
     * @dataProvider calculateTelegramBotPointsDataProvider
     */
    public function testCalculateTelegramBotPoints(
        array $userAddressComponents,
        array $botAddressComponents,
        int $expectedPoints,
    ): void
    {
        $user = $this->makeUser(...$userAddressComponents);
        $bot = $this->makeBot(...$botAddressComponents);
        $provider = new BetterMatchTelegramBotProvider($this->createMock(TelegramBotRepository::class));

        $actualPoints = $provider->calculateTelegramBotPoints($user, $bot);
        $this->assertEquals($expectedPoints, $actualPoints);
    }

    public function calculateTelegramBotPointsDataProvider(): Generator
    {
        yield 'nothing & country, locale1' => [
            'user' => [],
            'bot' => ['au'],
            'expected' => 0,
        ];

        yield 'country1 & country2, locale1' => [
            'user' => ['us'],
            'bot' => ['au'],
            'expected' => 0,
        ];

        yield 'country1 & country1, region1, locale1' => [
            'user' => ['us'],
            'bot' => ['us', 'MT'],
            'expected' => 1 + 2,
        ];

        yield 'country1 & country1, locale1' => [
            'user' => ['us'],
            'bot' => ['us'],
            'expected' => 1 + 4,
        ];

        yield 'country1, region11, region21, locality1 & country1, locale1' => [
            'user' => ['us', 'MT', 'Missoula County', 'Missoula'],
            'bot' => ['us'],
            'expected' => 1 + 8,
        ];

        yield 'country1, region11, region21, locality1, locale1 & country1, locale1' => [
            'user' => ['us', 'MT', 'Missoula County', 'Missoula', 'en'],
            'bot' => ['us'],
            'expected' => 1 + 8 + 1,
        ];

        yield 'country1, region11, region21, locality1 & country1, region12, locale1' => [
            'user' => ['us', 'MT', 'Missoula County', 'Missoula'],
            'bot' => ['us', 'CA'],
            'expected' => 0,
        ];

        yield 'country1, region11, region21, locality1 & country1, region11, locale1' => [
            'user' => ['us', 'MT', 'Missoula County', 'Missoula'],
            'bot' => ['us', 'MT'],
            'expected' => 1 + 16 + 32,
        ];

        yield 'country1, region11, region21, locality1, locale1 & country1, region11, locale1' => [
            'user' => ['us', 'MT', 'Missoula County', 'Missoula', 'en'],
            'bot' => ['us', 'MT'],
            'expected' => 1 + 16 + 32 + 1,
        ];

        yield 'country1, region11, region21, locality1 & country1, region11, region22, locale1' => [
            'user' => ['us', 'MT', 'Missoula County', 'Missoula'],
            'bot' => ['us', 'MT', 'Gallatin County'],
            'expected' => 0,
        ];

        yield 'country1, region11, region21, locality1 & country1, region11, region21, locale1' => [
            'user' => ['us', 'MT', 'Missoula County', 'Missoula'],
            'bot' => ['us', 'MT', 'Missoula County'],
            'expected' => 1 + 16 + 64 + 128,
        ];

        yield 'country1, region11, region21, locality1, locale1 & country1, region11, region21, locale1' => [
            'user' => ['us', 'MT', 'Missoula County', 'Missoula', 'en'],
            'bot' => ['us', 'MT', 'Missoula County'],
            'expected' => 1 + 16 + 64 + 128 + 1,
        ];

        yield 'country1, region11, region21, locality1 & country1, region11, region21, locality2, locale1' => [
            'user' => ['us', 'MT', 'Missoula County', 'Missoula'],
            'bot' => ['us', 'MT', 'Missoula County', 'Seeley Lake'],
            'expected' => 0,
        ];

        yield 'country1, region11, region21, locality1 & country1, region11, region21, locality1, locale1' => [
            'user' => ['us', 'MT', 'Missoula County', 'Missoula'],
            'bot' => ['us', 'MT', 'Missoula County', 'Missoula'],
            'expected' => 1 + 16 + 64 + 256,
        ];

        yield 'country1, region11, region21, locality1, locale1 & country1, region11, region21, locality1, locale1' => [
            'user' => ['us', 'MT', 'Missoula County', 'Missoula', 'en'],
            'bot' => ['us', 'MT', 'Missoula County', 'Missoula'],
            'expected' => 1 + 16 + 64 + 256 + 1,
        ];
    }

    /**
     * @param array $userAddressComponents
     * @param array $botAddressComponentsStack
     * @param array $expectedBotIds
     * @return void
     * @dataProvider getBetterMatchTelegramBotsDataProvider
     */
    public function testGetBetterMatchTelegramBots(
        array $userAddressComponents,
        array $botAddressComponentsStack,
        array $expectedBotIds,
    ): void
    {
        $user = $this->makeUser(...$userAddressComponents);
        $bots = array_map(function (array $botAddressComponents) {
            $id = array_shift($botAddressComponents);

            return $this->makeBot(...$botAddressComponents, id: $id);
        }, $botAddressComponentsStack);
        $repository = $this->createMock(TelegramBotRepository::class);
        $repository
            ->expects($this->once())
            ->method('findByGroup')
            ->willReturn($bots)
        ;
        $provider = new BetterMatchTelegramBotProvider($repository);
        $actualBots = $provider->getBetterMatchTelegramBots($user, TelegramGroup::default);
        $actualBotIds = array_map(fn (TelegramBot $bot) => $bot->getId(), $actualBots);

        $this->assertEquals($expectedBotIds, $actualBotIds);
    }

    public function getBetterMatchTelegramBotsDataProvider(): Generator
    {
        $notMatchedBots = [
            [5, 'au'],
            [5, 'au', 'Victoria'],
            [5, 'au', 'Victoria', 'Mallee'],
            [5, 'au', 'Victoria', 'Mallee', 'Murrayville'],
        ];

        yield 'country1, region11, region21, locality1, locale1 & full match & single' => [
            'user' => ['us', 'MT', 'Missoula County', 'Missoula'],
            'bots' => [
                [1, 'us', 'MT', 'Missoula County', 'Missoula'],
                [2, 'us', 'MT', 'Missoula County'],
                [3, 'us', 'MT'],
                [4, 'us'],
                ...$notMatchedBots,
            ],
            'expected' => [
                1,
            ],
        ];

        yield 'country1, locale1 & full match & single' => [
            'user' => ['us'],
            'bots' => [
                [1, 'us', 'MT', 'Missoula County', 'Missoula'],
                [2, 'us', 'MT', 'Missoula County'],
                [3, 'us', 'MT'],
                [4, 'us'],
                ...$notMatchedBots,
            ],
            'expected' => [
                4,
            ],
        ];

        yield 'country1, locale1 & region2 guess match & single' => [
            'user' => ['us'],
            'bots' => [
                [1, 'us', 'MT', 'Missoula County', 'Missoula'],
                [2, 'us', 'MT', 'Missoula County'],
                [3, 'us', 'MT'],
                ...$notMatchedBots,
            ],
            'expected' => [
                3,
            ],
        ];

        yield 'country1, locale1 & region2 guess match & multiple' => [
            'user' => ['us'],
            'bots' => [
                [1, 'us', 'MT', 'Missoula County', 'Missoula'],
                [2, 'us', 'MT', 'Missoula County'],
                [3, 'us', 'MT'],
                [4, 'us', 'CA'],
                ...$notMatchedBots,
            ],
            'expected' => [
                3,
                4,
            ],
        ];

        yield 'country1, locale1 & no match' => [
            'user' => ['us'],
            'bots' => [
                [2, 'us', 'MT', 'Missoula County'],
                ...$notMatchedBots,
            ],
            'expected' => [],
        ];

        yield 'country1, region11, region21, locality1, locale1 & region2 match & single' => [
            'user' => ['us', 'MT', 'Missoula County', 'Missoula'],
            'bots' => [
                [2, 'us', 'MT', 'Missoula County'],
                [3, 'us', 'MT'],
                [4, 'us'],
                ...$notMatchedBots,
            ],
            'expected' => [
                2,
            ],
        ];

        yield 'country1, region11, region21, locality1, locale1 & region1 match & single' => [
            'user' => ['us', 'MT', 'Missoula County', 'Missoula'],
            'bots' => [
                [3, 'us', 'MT'],
                [4, 'us'],
                ...$notMatchedBots,
            ],
            'expected' => [
                3,
            ],
        ];

        yield 'country1, region11, region21, locality1, locale1 & country match & single' => [
            'user' => ['us', 'MT', 'Missoula County', 'Missoula'],
            'bots' => [
                [4, 'us'],
                ...$notMatchedBots,
            ],
            'expected' => [
                4,
            ],
        ];

        yield 'country1, region11, region21, locality1, locale12 & full match + other locale & single' => [
            'user' => ['us', 'MT', 'Missoula County', 'Missoula', 'es'],
            'bots' => [
                [1, 'us', 'MT', 'Missoula County', 'Missoula', 'de'],
                ...$notMatchedBots,
            ],
            'expected' => [
                1,
            ],
        ];

        yield 'country1, region11, region21, locality1, locale12 & full match + locale & single' => [
            'user' => ['us', 'MT', 'Missoula County', 'Missoula', 'es'],
            'bots' => [
                [1, 'us', 'MT', 'Missoula County', 'Missoula', 'de'],
                [2, 'us', 'MT', 'Missoula County', 'Missoula', 'es'],
                ...$notMatchedBots,
            ],
            'expected' => [
                2,
            ],
        ];
    }

    private function makeUser(
        string $countryCode = null,
        string $region1 = null,
        string $region2 = null,
        string $locality = null,
        string $localeCode = null,
    ): User
    {
        if ($region1 === null || $region2 === null || $locality === null) {
            $addressLocality = null;
        } else {
            $addressLocality = new AddressLocality(
                $countryCode,
                $region1,
                $region2,
                $locality,
            );
        }

        return $this->createConfiguredMock(User::class, [
            'getCountryCode' => $countryCode,
            'getLocaleCode' => $localeCode,
            'getAddressLocality' => $addressLocality,
        ]);
    }

    private function makeBot(
        string $countryCode = '',
        string $region1 = null,
        string $region2 = null,
        string $locality = null,
        string $localeCode = 'en',
        int $id = null
    ): TelegramBot
    {
        return $this->createConfiguredMock(TelegramBot::class, [
            'getId' => $id,
            'getCountryCode' => $countryCode,
            'getLocaleCode' => $localeCode,
            'getRegion1' => $region1,
            'getRegion2' => $region2,
            'getLocality' => $locality,
        ]);
    }
}