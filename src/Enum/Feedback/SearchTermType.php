<?php

declare(strict_types=1);

namespace App\Enum\Feedback;

enum SearchTermType: int
{
    case unknown = 0;

    case instagram_username = 21;
    case facebook_username = 29;
    case telegram_username = 22;
    case twitter_username = 23;
    case tiktok_username = 24;
    case youtube_username = 25;
    case onlyfans_username = 26;
    case reddit_username = 27;
    case messenger_username = 20;

    case messenger_profile_url = 10;
    case url = 30;

    case email = 40;

    case phone_number = 50;

    case person_name = 60;
    case organization_name = 70;
    case place_name = 80;

    public static function sort(array $items): array
    {
        $sortedAll = self::cases();

        $sorted = [];

        foreach ($sortedAll as $item) {
            if (in_array($item, $items, true)) {
                $sorted[] = $item;
            }
        }

        return $sorted;
    }
}
