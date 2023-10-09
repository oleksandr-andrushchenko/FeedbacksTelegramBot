<?php

declare(strict_types=1);

namespace App\Service\Telegram\Channel;

use App\Entity\Telegram\TelegramChannel;

class TelegramChannelInfoProvider
{
    public function getTelegramChannelInfo(TelegramChannel $channel): array
    {
        return [
            'group' => $channel->getGroup()->name,
            'name' => $channel->getName(),
            'username' => $channel->getUsername(),
            'country' => $channel->getCountryCode(),
            'locale' => $channel->getLocaleCode(),
            'administrative_area_level_1' => $channel->getAdministrativeAreaLevel1() ?? 'N/A',
            'administrative_area_level_2' => $channel->getAdministrativeAreaLevel2() ?? 'N/A',
            'administrative_area_level_3' => $channel->getAdministrativeAreaLevel3() ?? 'N/A',
            'primary' => $channel->primary() ? 'Yes' : 'No',
            'created_at' => $channel->getCreatedAt()->format('Y-m-d H:i'),
            'updated_at' => $channel->getUpdatedAt() === null ? 'N/A' : $channel->getUpdatedAt()->format('Y-m-d H:i'),
            'deleted_at' => $channel->getDeletedAt() === null ? 'N/A' : $channel->getDeletedAt()->format('Y-m-d H:i'),
        ];
    }
}