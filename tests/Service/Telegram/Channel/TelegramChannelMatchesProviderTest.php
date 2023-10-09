<?php

declare(strict_types=1);

namespace App\Tests\Service\Telegram\Channel;

use App\Entity\Address\Address;
use App\Entity\Telegram\TelegramBot;
use App\Entity\Telegram\TelegramChannel;
use App\Entity\User\User;
use App\Enum\Telegram\TelegramBotGroupName;
use App\Repository\Telegram\Channel\TelegramChannelRepository;
use App\Service\Telegram\Channel\TelegramChannelMatchesProvider;
use Generator;
use PHPUnit\Framework\TestCase;

class TelegramChannelMatchesProviderTest extends TestCase
{
    /**
     * @param array $userAddressComponents
     * @param array $botAddressComponents
     * @param array $channelAddressComponents
     * @param int $expectedPoints
     * @return void
     * @dataProvider calculateTelegramChannelPointsDataProvider
     */
    public function testCalculateTelegramChannelPoints(
        array $userAddressComponents,
        array $botAddressComponents,
        array $channelAddressComponents,
        int $expectedPoints,
    ): void
    {
        $user = $this->makeUser(...$userAddressComponents);
        $bot = $this->makeBot(...$botAddressComponents);
        $channel = $this->makeChannel(...$channelAddressComponents);
        $provider = new TelegramChannelMatchesProvider($this->createMock(TelegramChannelRepository::class));

        $actualPoints = $provider->calculateTelegramChannelPoints($bot, $user, $channel);
        $this->assertEquals($expectedPoints, $actualPoints);
    }

    public function calculateTelegramChannelPointsDataProvider(): Generator
    {
        yield 'c & cc & ccc' => [
            'user' => ['us'],
            'bot' => ['au'],
            'channel' => ['gb'],
            'expected' => 0,
        ];

        yield 'null & c & c' => [
            'user' => [],
            'bot' => ['us'],
            'channel' => ['us'],
            'expected' => 1,
        ];

        yield 'c & c & c' => [
            'user' => ['us'],
            'bot' => ['us'],
            'channel' => ['us'],
            'expected' => 1,
        ];

        yield 'c & cc & cc' => [
            'user' => ['us'],
            'bot' => ['ua'],
            'channel' => ['ua'],
            'expected' => 1,
        ];

        yield 'c+a1 & cc & cc' => [
            'user' => ['ua', 'Lviv Oblast'],
            'bot' => ['au'],
            'channel' => ['au'],
            'expected' => 1,
        ];

        yield 'c+a1 & c & c' => [
            'user' => ['ua', 'Lviv Oblast'],
            'bot' => ['ua'],
            'channel' => ['ua'],
            'expected' => 1,
        ];

        yield 'c+a1 & c & c+a1' => [
            'user' => ['ua', 'Lviv Oblast'],
            'bot' => ['ua'],
            'channel' => ['ua', 'Lviv Oblast'],
            'expected' => 1 + 2 + 4 + 8,
        ];

        yield 'c+a1+a2 & c & c+a1' => [
            'user' => ['ua', 'Lviv Oblast', 'Zhovkivs\'kyi district'],
            'bot' => ['ua'],
            'channel' => ['ua', 'Lviv Oblast'],
            'expected' => 1 + 2 + 4 + 8,
        ];

        yield 'c & c & c+a1' => [
            'user' => ['ua'],
            'bot' => ['ua'],
            'channel' => ['ua', 'Lviv Oblast'],
            'expected' => 0,
        ];
    }

    /**
     * @param array $userAddressComponents
     * @param array $botAddressComponents
     * @param array $channelAddressComponentsStack
     * @param array $expectedChannelIds
     * @return void
     * @dataProvider getTelegramChannelMatchesDataProvider
     */
    public function testGetTelegramChannelMatches(
        array $userAddressComponents,
        array $botAddressComponents,
        array $channelAddressComponentsStack,
        array $expectedChannelIds,
    ): void
    {
        $user = $this->makeUser(...$userAddressComponents);
        $bot = $this->makeBot(...$botAddressComponents);
        $channels = array_map(function (array $channelAddressComponents) {
            $id = array_shift($channelAddressComponents);

            return $this->makeChannel(...$channelAddressComponents, id: $id);
        }, $channelAddressComponentsStack);
        $repository = $this->createMock(TelegramChannelRepository::class);
        $repository
            ->expects($this->once())
            ->method('findPrimaryByGroup')
            ->willReturn($channels)
        ;

        $provider = new TelegramChannelMatchesProvider($repository);
        $actualChannels = $provider->getTelegramChannelMatches($user, $bot);
        $actualChannelIds = array_map(fn (TelegramChannel $channel) => $channel->getId(), $actualChannels);

        $this->assertEquals($expectedChannelIds, $actualChannelIds);
    }

    public function getTelegramChannelMatchesDataProvider(): Generator
    {
        $notMatchedChannels = [
            [5, 'au'],
        ];

        yield 'c & cc & ccc' => [
            'user' => ['ua'],
            'bot' => ['us'],
            'channels' => [
                [1, 'ru'],
                ...$notMatchedChannels,
            ],
            'expected' => [],
        ];

        yield 'c & c & cc' => [
            'user' => ['ua'],
            'bot' => ['ua'],
            'channels' => [
                [1, 'us'],
                ...$notMatchedChannels,
            ],
            'expected' => [],
        ];

        yield 'c & c & c' => [
            'user' => ['ua'],
            'bot' => ['ua'],
            'channels' => [
                [1, 'ua'],
                ...$notMatchedChannels,
            ],
            'expected' => [
                1,
            ],
        ];

        yield 'c & c & c, c+a1' => [
            'user' => ['ua'],
            'bot' => ['ua'],
            'channels' => [
                [1, 'ua'],
                [2, 'ua', 'Lviv Oblast'],
                ...$notMatchedChannels,
            ],
            'expected' => [
                1,
            ],
        ];

        yield 'c & c & c, c+a1, c+a1+a2' => [
            'user' => ['ua'],
            'bot' => ['ua'],
            'channels' => [
                [1, 'ua'],
                [2, 'ua', 'Lviv Oblast'],
                [2, 'ua', 'Lviv Oblast', 'Zhovkivs\'kyi district'],
                ...$notMatchedChannels,
            ],
            'expected' => [
                1,
            ],
        ];

        yield 'c+a1 & c & c, c+a1, c+a1+a2' => [
            'user' => ['ua', 'Lviv Oblast'],
            'bot' => ['ua'],
            'channels' => [
                [1, 'ua'],
                [2, 'ua', 'Lviv Oblast'],
                [3, 'ua', 'Lviv Oblast', 'Zhovkivs\'kyi district'],
                ...$notMatchedChannels,
            ],
            'expected' => [
                1,
                2,
            ],
        ];

        yield 'c+a1+a2 & c & c, c+a1, c+a1+a2' => [
            'user' => ['ua', 'Lviv Oblast', 'Zhovkivs\'kyi district'],
            'bot' => ['ua'],
            'channels' => [
                [1, 'ua'],
                [2, 'ua', 'Lviv Oblast'],
                [3, 'ua', 'Lviv Oblast', 'Zhovkivs\'kyi district'],
                ...$notMatchedChannels,
            ],
            'expected' => [
                1,
                2,
                3,
            ],
        ];

        yield 'c+a1+a2 & c & c, c+a1, c+a1+a2a2' => [
            'user' => ['ua', 'Lviv Oblast', 'Kam\'yanka-Buz\'kyi district'],
            'bot' => ['ua'],
            'channels' => [
                [1, 'ua'],
                [2, 'ua', 'Lviv Oblast'],
                [3, 'ua', 'Lviv Oblast', 'Zhovkivs\'kyi district'],
                ...$notMatchedChannels,
            ],
            'expected' => [
                1,
                2,
            ],
        ];

        yield 'c+a1 & c & c, c+a1a1, c+a1a1+a2' => [
            'user' => ['ua', 'Chernihiv Oblast'],
            'bot' => ['ua'],
            'channels' => [
                [1, 'ua'],
                [2, 'ua', 'Lviv Oblast'],
                [3, 'ua', 'Lviv Oblast', 'Zhovkivs\'kyi district'],
                ...$notMatchedChannels,
            ],
            'expected' => [
                1,
            ],
        ];

        yield 'c & cc & cc, cc+a1a1, cc+a1a1+a2' => [
            'user' => ['us'],
            'bot' => ['ua'],
            'channels' => [
                [1, 'ua'],
                [2, 'ua', 'Lviv Oblast'],
                [3, 'ua', 'Lviv Oblast', 'Zhovkivs\'kyi district'],
                ...$notMatchedChannels,
            ],
            'expected' => [
                1,
            ],
        ];

        yield 'c+a1 & cc & cc, cc+a1a1, cc+a1a1+a2' => [
            'user' => ['us', 'MT'],
            'bot' => ['ua'],
            'channels' => [
                [1, 'ua'],
                [2, 'ua', 'Lviv Oblast'],
                [3, 'ua', 'Lviv Oblast', 'Zhovkivs\'kyi district'],
                ...$notMatchedChannels,
            ],
            'expected' => [
                1,
            ],
        ];
    }

    private function makeUser(
        string $countryCode = null,
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
            'getAddress' => $address,
        ]);
    }

    private function makeBot(
        string $countryCode = '',
        int $id = null
    ): TelegramBot
    {
        return $this->createConfiguredMock(TelegramBot::class, [
            'getId' => $id,
            'getGroup' => TelegramBotGroupName::default,
            'getCountryCode' => $countryCode,
        ]);
    }

    private function makeChannel(
        string $countryCode = '',
        string $administrativeAreaLevel1 = null,
        string $administrativeAreaLevel2 = null,
        string $administrativeAreaLevel3 = null,
        int $id = null
    ): TelegramChannel
    {
        return $this->createConfiguredMock(TelegramChannel::class, [
            'getId' => $id,
            'getCountryCode' => $countryCode,
            'getAdministrativeAreaLevel1' => $administrativeAreaLevel1,
            'getAdministrativeAreaLevel2' => $administrativeAreaLevel2,
            'getAdministrativeAreaLevel3' => $administrativeAreaLevel3,
        ]);
    }
}