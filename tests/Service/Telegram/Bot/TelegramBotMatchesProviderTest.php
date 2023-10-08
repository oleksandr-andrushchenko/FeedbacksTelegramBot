<?php

declare(strict_types=1);

namespace App\Tests\Service\Telegram\Bot;

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

        yield 'country1 & country1, locale1' => [
            'user' => ['us'],
            'bot' => ['us'],
            'expected' => 1,
        ];

        yield 'country1, locale1 & country1, locale1' => [
            'user' => ['us', 'en'],
            'bot' => ['us'],
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

        yield 'country1, locale1 & full match & single' => [
            'user' => ['us'],
            'bots' => [
                [1, 'us'],
                ...$notMatchedBots,
            ],
            'expected' => [
                1,
            ],
        ];

        yield 'country1, locale1 & region2 guess match & single' => [
            'user' => ['us'],
            'bots' => [
                [3, 'us'],
                ...$notMatchedBots,
            ],
            'expected' => [
                3,
            ],
        ];

        yield 'country1, locale1 & region2 guess match & multiple' => [
            'user' => ['us'],
            'bots' => [
                [3, 'us'],
                [4, 'us'],
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
                ...$notMatchedBots,
            ],
            'expected' => [],
        ];

        yield 'country1, locale1 & region2 match & single' => [
            'user' => ['us'],
            'bots' => [
                [2, 'us'],
                ...$notMatchedBots,
            ],
            'expected' => [
                2,
            ],
        ];

        yield 'country1, locale1 & region1 match & single' => [
            'user' => ['us', 'MT', 'Missoula County', 'Missoula'],
            'bots' => [
                [4, 'us'],
                ...$notMatchedBots,
            ],
            'expected' => [
                4,
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

        yield 'country1, locale12 & full match + other locale & single' => [
            'user' => ['us', 'es'],
            'bots' => [
                [1, 'us', 'de'],
                ...$notMatchedBots,
            ],
            'expected' => [
                1,
            ],
        ];

        yield 'country1, locale12 & full match + locale & single' => [
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
    ): User
    {
        return $this->createConfiguredMock(User::class, [
            'getCountryCode' => $countryCode,
            'getLocaleCode' => $localeCode,
        ]);
    }

    private function makeBot(
        string $countryCode = '',
        string $localeCode = 'en',
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