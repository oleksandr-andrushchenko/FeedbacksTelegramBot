<?php

declare(strict_types=1);

namespace App\Service\Lookup\Viewer;

use App\Service\Util\Array\ArrayStringNormalizerAndFilterer;

class LookupViewerHelper
{
    public function __construct(
        private readonly ArrayStringNormalizerAndFilterer $arrayStringNormalizerAndFilterer,
    )
    {
    }

    public function wrapTitle(string $title): string
    {
        return sprintf('<b>%s</b>', $title);
    }

    public function wrapList(array $list): string
    {
        // 🔴🟡🟢⚪️🚨‼️
        // ⬜️⬛️◻️◼️◽️◾️▫️▪️
        return 'ℹ️ ' . implode("\n▫️ ", $this->arrayStringNormalizerAndFilterer->normalizeAndFilterEmptyStrings($list));
    }

    public function wrapResultRecord(?string $title, array $items, callable $record): string
    {
        $message = [];

        if ($title !== null) {
            $message[] = $this->wrapTitle($title);
        }

        foreach ($items as $item) {
            $message[] = $this->wrapList($record($item));
        }

        return implode("\n\n", $message);
    }
}
