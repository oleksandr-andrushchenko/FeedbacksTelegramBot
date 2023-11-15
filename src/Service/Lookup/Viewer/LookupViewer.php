<?php

declare(strict_types=1);

namespace App\Service\Lookup\Viewer;

use Symfony\Contracts\Translation\TranslatorInterface;

abstract class LookupViewer
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly string $transDomain,
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
        return 'ℹ️ ' . implode("\n▫️ ", $this->normalizeAndFilterEmptyStrings($list));
    }

    protected function wrapResultRecord(?string $title, array $items, callable $record, bool $full): string
    {
        $message = [];

        if ($title !== null) {
            $message[] = $this->wrapTitle($title);
        }

//        if ($full) {
            $maxResults = count($items);
//        } else {
//            $maxResults = min(1);
//        }

        foreach (array_slice($items, 0, $maxResults) as $item) {
            $message[] = $this->wrapList($record($item));
        }

        if ($maxResults !== count($items)) {
            $message[] = '';
        }

        return implode("\n\n", $message);
    }

    protected function normalizeAndFilterEmptyStrings(array $input): array
    {
        $output = [];

        foreach ($input as $item) {
            if (empty($item)) {
                continue;
            }

            $item = trim($item);
            $item = preg_replace('/\s+/', ' ', $item);

            $noTagsItem = strip_tags($item);
            $noTagsItem = trim($noTagsItem);

            if (!empty($item) && !empty($noTagsItem)) {
                $output[] = $item;
            }
        }

        return $output;
    }

    protected function trans($id, array $parameters = []): string
    {
        return $this->translator->trans($id, $parameters, 'lookups.tg.' . $this->transDomain);
    }
}
