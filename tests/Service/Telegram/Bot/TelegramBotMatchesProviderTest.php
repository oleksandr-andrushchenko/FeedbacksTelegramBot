<?php

declare(strict_types=1);

namespace App\Tests\Service\Telegram\Bot;

use App\Entity\Address\Address;
use App\Entity\Telegram\TelegramBot;
use App\Entity\User\User;
use App\Enum\Telegram\TelegramBotGroupName;
use App\Repository\Telegram\Bot\TelegramBotRepository;
use App\Service\Telegram\Bot\TelegramBotMatchesProvider;
use Generator;
use PHPUnit\Framework\TestCase;

class TelegramBotMatchesProviderTest extends TestCase
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
        $provider = new TelegramBotMatchesProvider($this->createMock(TelegramBotRepository::class));

        $actualPoints = $provider->calculateTelegramBotPoints($bot, $user);
        $this->assertEquals($expectedPoints, $actualPoints);
    }

    public function calculateTelegramBotPointsDataProvider(): Generator
    {
        yield 'null+null & c+l' => [
            'user' => [],
            'bot' => ['au', 'en'],
            'expected' => 0,
        ];

        yield 'c+null & cc+l' => [
            'user' => ['us'],
            'bot' => ['au', 'en'],
            'expected' => 0,
        ];

        yield 'c+null & c+l' => [
            'user' => ['us'],
            'bot' => ['us', 'es'],
            'expected' => 1,
        ];

        yield 'c+l & c+ll' => [
            'user' => ['us', 'en'],
            'bot' => ['us', 'es'],
            'expected' => 1,
        ];

        yield 'c+l & c+l' => [
            'user' => ['us', 'en'],
            'bot' => ['us', 'en'],
            'expected' => 2,
        ];
    }

    /**
     * @param array $userAddressComponents
     * @param array $botAddressComponentsStack
     * @param array $expectedBotIds
     * @return void
     * @dataProvider getTelegramBotMatchesDataProvider
     */
    public function testGetTelegramBotMatches(
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
            ->method('findPrimaryByGroup')
            ->willReturn($bots)
        ;
        $provider = new TelegramBotMatchesProvider($repository);
        $actualBots = $provider->getTelegramBotMatches($user, TelegramBotGroupName::default);
        $actualBotIds = array_map(fn (TelegramBot $bot) => $bot->getId(), $actualBots);

        $this->assertEquals($expectedBotIds, $actualBotIds);
    }

    public function getTelegramBotMatchesDataProvider(): Generator
    {
        $notMatchedBots = [
            [5, 'au'],
        ];

        yield 'null+null & c+l' => [
            'user' => [],
            'bots' => [
                [1, 'us', 'en'],
                ...$notMatchedBots,
            ],
            'expected' => [],
        ];

        yield 'c+null & c+l' => [
            'user' => ['us'],
            'bots' => [
                [1, 'us', 'en'],
                ...$notMatchedBots,
            ],
            'expected' => [
                1,
            ],
        ];

        yield 'c+l & c+l' => [
            'user' => ['us', 'en'],
            'bots' => [
                [1, 'us', 'en'],
                ...$notMatchedBots,
            ],
            'expected' => [
                1,
            ],
        ];

        yield 'c+l & empty' => [
            'user' => ['us', 'en'],
            'bots' => [
                ...$notMatchedBots,
            ],
            'expected' => [],
        ];

        yield 'c+l+a1+a2 & c+l' => [
            'user' => ['us', 'en', 'MT', 'Missoula County'],
            'bots' => [
                [1, 'us', 'en'],
                ...$notMatchedBots,
            ],
            'expected' => [
                1,
            ],
        ];

        yield 'country1, locale1 & country match & single' => [
            'user' => ['us'],
            'bots' => [
                [4, 'us'],
                ...$notMatchedBots,
            ],
            'expected' => [
                4,
            ],
        ];

        yield 'c+l & c+ll' => [
            'user' => ['us', 'es'],
            'bots' => [
                [1, 'us', 'de'],
                ...$notMatchedBots,
            ],
            'expected' => [
                1,
            ],
        ];

        yield 'c+l & c+ll, c+l' => [
            'user' => ['us', 'es'],
            'bots' => [
                [1, 'us', 'de'],
                [2, 'us', 'es'],
                ...$notMatchedBots,
            ],
            'expected' => [
                2,
            ],
        ];
    }

    private function makeUser(
        string $countryCode = null,
        string $localeCode = null,
        string $administrativeAreaLevel1 = null,
        string $administrativeAreaLevel2 = null,
        string $administrativeAreaLevel3 = null,
    ): User
    {
        if ($countryCode === null || $administrativeAreaLevel1 === null) {
            $address = null;
        } else {
            $address = new Address(
                $countryCode,
                $administrativeAreaLevel1,
                $administrativeAreaLevel2,
                $administrativeAreaLevel3,
            );
        }

        return $this->createConfiguredMock(User::class, [
            'getCountryCode' => $countryCode,
            'getLocaleCode' => $localeCode,
            'getAddress' => $address,
        ]);
    }

    private function makeBot(
        string $countryCode = '',
        string $localeCode = '',
        int $id = null
    ): TelegramBot
    {
        return $this->createConfiguredMock(TelegramBot::class, [
            'getId' => $id,
            'getCountryCode' => $countryCode,
            'getLocaleCode' => $localeCode,
        ]);
    }
}