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
        return '◻️ ' . implode("\n▫️ ", $this->normalizeAndFilterEmptyStrings($list));
    }

    protected function wrapResultRecord(?string $title, array $items, callable $record, bool $full): string
    {
        $message = [];

        if ($title !== null) {
            $message[] = $this->wrapTitle($title);
        }

        $count = count($items);

        if ($full) {
            $maxResults = $count;
        } else {
            $maxResults = intval($count * .1);
            $maxResults = max($maxResults, 1);
        }

        foreach (array_slice($items, 0, $maxResults) as $item) {
            $message[] = $this->wrapList($record($item));
        }

        if ($maxResults !== $count) {
            $parameters = [
                'shown_count' => $maxResults,
                'total_count' => $count,
                'subscribe_command' => '/subscribe',
            ];
            $message[] = sprintf('<i>[ %s ]</i>', $this->translator->trans('hidden_list', $parameters, 'lookups.tg'));
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
