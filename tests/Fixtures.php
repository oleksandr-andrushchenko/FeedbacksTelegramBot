<?php

declare(strict_types=1);

namespace App\Tests;

use App\Enum\Feedback\SearchTermType;
use App\Enum\Messenger\Messenger;
use App\Transfer\Messenger\MessengerUserTransfer;
use Closure;

class Fixtures
{
    public const BOT_USERNAME_1 = 'any_bot';
    public const INSTAGRAM_USER_ID_1 = 1;
    public const INSTAGRAM_USERNAME_1 = '1dmy.tro2811';
    public const INSTAGRAM_USER_ID_2 = 2;
    public const INSTAGRAM_USERNAME_2 = 'wild_sss';
    public const INSTAGRAM_USER_ID_3 = 3;
    public const INSTAGRAM_USERNAME_3 = 'instasd';
    public const TELEGRAM_USER_ID_1 = 409525390;
    public const TELEGRAM_USERNAME_1 = 'Gatu_za1';
    public const TELEGRAM_USER_ID_2 = 2;
    public const TELEGRAM_USERNAME_2 = 'tg2';
    public const TELEGRAM_USER_ID_3 = 3;
    public const TELEGRAM_USERNAME_3 = 'tg3';
    public const TELEGRAM_CHAT_ID_1 = 409525390;
    public const TIKTOK_USER_ID_1 = 2;
    public const TIKTOK_USERNAME_1 = '4dm.yt_ro2811';
    public const TWITTER_USER_ID_1 = 3;
    public const TWITTER_USERNAME_1 = '6dm_ytr.o2811';
    public const YOUTUBE_USER_ID_1 = 4;
    public const YOUTUBE_USERNAME_1 = '6dm_ytr.o2811';
    public const VKONTAKTE_USER_ID_1 = 4;
    public const VKONTAKTE_USERNAME_1 = '6dm_ytr.o2811';
    public const UNKNOWN_USER_ID_1 = 5;
    public const UNKNOWN_USERNAME_1 = 'unknown';

    public const PERSONS = [
        'en' => 'John Smith',
        'uk' => 'Олександр Андрущенко',
        'ru' => 'Дмитрий Сергеевич',
    ];

    public const PLACES = [
        'usa' => '2901 N Federal Hwy, Boca Raton, FL 33431',
        'uk' => 'Бориса Гмирїі 9в, 55',
        'ru' => 'пер. Луначарского-Быкова 134/1, 1 подьезд, кв. 14',
    ];

    public const ORGANIZATIONS = [
        'en_1' => 'United Nations Children’s Fund (UNICEF)',
        'en_2' => 'United Nations Education Scientific & Cultural Organization (UNESCO)',
        'usa_1' => 'Apple Inc. (AAPL)',
        'usa_2' => 'Saudi Aramco (2222.SR)',
        'ukr_1' => 'Теле-канал «Україна»',
        'ukr_2' => 'McDonald`s',
        'ru_1' => '«Сургутнефтегаз», ПАО',
        'ru_2' => 'En+ Group',
        'ru_3' => '«Аэрофлот - Российские авиалинии»',
        'ru_4' => '«Бэст Прайс» (сеть магазинов Fix Price)',
    ];

    public const NON_MESSENGER_SEARCH_TYPES = [
        'url' => [
            SearchTermType::url,
            'https://example.com',
        ],
        'email' => [
            SearchTermType::email,
            'example@gmail.com',
        ],
        'phone_number' => [
            SearchTermType::phone_number,
            '+1 (561) 314-5672',
            '15613145672',
        ],
        'person_name' => [
            SearchTermType::person_name,
            'Adam',
        ],
        'organization_name' => [
            SearchTermType::organization_name,
            'Apple Inc.',
        ],
        'place_name' => [
            SearchTermType::place_name,
            '2901 N Federal Hwy',
        ],
        'car_number' => [
            SearchTermType::car_number,
            'нu34123ЧW',
        ],
    ];


    public static function getInstagramMessengerUserTransferFixture(int $number = 1): MessengerUserTransfer
    {
        return new MessengerUserTransfer(
            Messenger::instagram,
            (string) constant(sprintf('App\Tests\Fixtures::INSTAGRAM_USER_ID_%d', $number)),
            constant(sprintf('App\Tests\Fixtures::INSTAGRAM_USERNAME_%d', $number)),
            'Instagram Name',
            'us',
            'en',
            'USD'
        );
    }

    public static function getFacebookMessengerUserTransferFixture(int $number = 1): MessengerUserTransfer
    {
        return new MessengerUserTransfer(
            Messenger::facebook,
            (string) $number,
            sprintf('fb_user_%d', $number),
            sprintf('Facebook %d Name', $number),
            'us',
            'en',
            'USD'
        );
    }

    public static function getRedditMessengerUserTransferFixture(int $number = 1): MessengerUserTransfer
    {
        return new MessengerUserTransfer(
            Messenger::reddit,
            (string) $number,
            sprintf('reddit_user_%d', $number),
            sprintf('Reddit %d Name', $number),
            'us',
            'en',
            'USD'
        );
    }

    public static function getOnlyfansMessengerUserTransferFixture(int $number = 1): MessengerUserTransfer
    {
        return new MessengerUserTransfer(
            Messenger::onlyfans,
            (string) $number,
            sprintf('onlyfans_user_%d', $number),
            sprintf('Onlyfans %d Name', $number),
            'us',
            'en',
            'USD'
        );
    }

    public static function getTelegramMessengerUserTransferFixture(int $number = 1): MessengerUserTransfer
    {
        return new MessengerUserTransfer(
            Messenger::telegram,
            (string) constant(sprintf('App\Tests\Fixtures::TELEGRAM_USER_ID_%d', $number)),
            constant(sprintf('App\Tests\Fixtures::TELEGRAM_USERNAME_%d', $number)),
            'Telegram Name',
            'us',
            'en',
            'USD'
        );
    }

    public static function getTiktokMessengerUserTransferFixture(): MessengerUserTransfer
    {
        return new MessengerUserTransfer(
            Messenger::tiktok,
            (string) Fixtures::TIKTOK_USER_ID_1,
            Fixtures::TIKTOK_USERNAME_1,
            'Tiktok Name',
            'us',
            'en',
            'USD'
        );
    }

    public static function getTwitterMessengerUserTransferFixture(): MessengerUserTransfer
    {
        return new MessengerUserTransfer(
            Messenger::twitter,
            (string) Fixtures::TWITTER_USER_ID_1,
            Fixtures::TWITTER_USERNAME_1,
            'Twitter Name',
            'us',
            'en',
            'USD'
        );
    }

    public static function getYoutubeMessengerUserTransferFixture(): MessengerUserTransfer
    {
        return new MessengerUserTransfer(
            Messenger::youtube,
            (string) Fixtures::YOUTUBE_USER_ID_1,
            Fixtures::YOUTUBE_USERNAME_1,
            'Youtube Name',
            'us',
            'en',
            'USD'
        );
    }

    public static function getVkontakteMessengerUserTransferFixture(): MessengerUserTransfer
    {
        return new MessengerUserTransfer(
            Messenger::vkontakte,
            (string) Fixtures::VKONTAKTE_USER_ID_1,
            Fixtures::VKONTAKTE_USERNAME_1,
            'Vkontakte Name',
            'us',
            'en',
            'USD'
        );
    }

    public static function getUnknownMessengerUserTransferFixture(): MessengerUserTransfer
    {
        return new MessengerUserTransfer(
            Messenger::unknown,
            (string) Fixtures::UNKNOWN_USER_ID_1,
            Fixtures::UNKNOWN_USERNAME_1,
            'Unknown Name',
            'us',
            'en',
            'USD'
        );
    }

    public static function getNetworkMessengerUserUsernames(int $number = 1): array
    {
        return [
            'instagram' => [
                $messengerUser = static::getInstagramMessengerUserTransferFixture($number),
                SearchTermType::instagram_username,
                static::getInstagramMessengerUserProviderMocks($messengerUser),
            ],
        ];
    }

    public static function getInstagramMessengerUserProviderMocks($messengerUser): ?Closure
    {
        return null;
    }

    public static function getNonNetworkMessengerUserUsernames(int $number = 1): array
    {
        return [
            'telegram' => [
                static::getTelegramMessengerUserTransferFixture($number),
                SearchTermType::telegram_username,
                null,
            ],
            'facebook' => [
                static::getFacebookMessengerUserTransferFixture($number),
                SearchTermType::facebook_username,
                null,
            ],
            'reddit' => [
                static::getRedditMessengerUserTransferFixture($number),
                SearchTermType::reddit_username,
                null,
            ],
            'onlyfans' => [
                static::getOnlyfansMessengerUserTransferFixture($number),
                SearchTermType::onlyfans_username,
                null,
            ],
            'tiktok' => [
                static::getTiktokMessengerUserTransferFixture(),
                SearchTermType::tiktok_username,
                null,
            ],
            'twitter' => [
                static::getTwitterMessengerUserTransferFixture(),
                SearchTermType::twitter_username,
                null,
            ],
            'youtube' => [
                static::getYoutubeMessengerUserTransferFixture(),
                SearchTermType::youtube_username,
            ],
            'vkontakte' => [
                static::getVkontakteMessengerUserTransferFixture(),
                SearchTermType::vkontakte_username,
            ],
        ];
    }

    public static function getMessengerUserUsernames(int $number = 1): array
    {
        return array_merge(
            static::getNetworkMessengerUserUsernames($number),
            static::getNonNetworkMessengerUserUsernames($number),
        );
    }

    public static function getNetworkMessengerUserProfileUrls(int $number = 1): array
    {
        return [
            'instagram' => [
                $messengerUser = static::getInstagramMessengerUserTransferFixture($number),
                SearchTermType::instagram_username,
                static::getInstagramMessengerUserProviderMocks($messengerUser),
            ],
        ];
    }

    public static function getNonNetworkMessengerUserProfileUrls(int $number = 1): array
    {
        return [
            'telegram' => [
                static::getTelegramMessengerUserTransferFixture($number),
                SearchTermType::telegram_username,
            ],
            'facebook' => [
                static::getFacebookMessengerUserTransferFixture($number),
                SearchTermType::facebook_username,
            ],
            'reddit' => [
                static::getRedditMessengerUserTransferFixture($number),
                SearchTermType::reddit_username,
            ],
            'onlyfans' => [
                static::getOnlyfansMessengerUserTransferFixture($number),
                SearchTermType::onlyfans_username,
            ],
            'tiktok' => [
                static::getTiktokMessengerUserTransferFixture(),
                SearchTermType::tiktok_username,
            ],
            'twitter' => [
                static::getTwitterMessengerUserTransferFixture(),
                SearchTermType::twitter_username,
            ],
            'youtube' => [
                static::getYoutubeMessengerUserTransferFixture(),
                SearchTermType::youtube_username,
            ],
            'vkontakte' => [
                static::getVkontakteMessengerUserTransferFixture(),
                SearchTermType::vkontakte_username,
            ],
        ];
    }

    public static function getMessengerUserProfileUrls(int $number = 1): array
    {
        return array_merge(
            static::getNetworkMessengerUserProfileUrls($number),
            static::getNonNetworkMessengerUserProfileUrls($number),
        );
    }
}