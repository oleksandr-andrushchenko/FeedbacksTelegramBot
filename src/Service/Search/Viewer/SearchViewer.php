<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer;

use Symfony\Contracts\Translation\TranslatorInterface;

abstract class SearchViewer
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
        // 💥🔥✨⚡️💫🥳🤩
        return '◻️ ' . implode("\n▫️ ", $this->normalizeAndFilterEmptyStrings($list));
    }

    protected function wrapResultRecord(string $title, array $items, callable $record, bool $full): string
    {
        $messages = [];

        if ($title !== null) {
            $messages[] = $this->wrapTitle($title);
        }

        $count = count($items);

        if ($full) {
            $maxResults = $count;
        } else {
            $maxResults = intval($count * .1);
            $maxResults = max($maxResults, 1);
        }

        $added = 0;

        foreach ($items as $item) {
            $message = $this->wrapList($record($item));

            if (empty($message)) {
                continue;
            }

            $messageNotTags = trim(strip_tags($message));

            if (empty($messageNotTags)) {
                continue;
            }

            $messages[] = $message;
            $added++;

            if ($added === $maxResults) {
                break;
            }
        }

        if ($maxResults !== $count) {
            $parameters = [
                'shown_count' => $maxResults,
                'total_count' => $count,
                'subscribe_command' => '/subscribe',
            ];
            $messages[] = sprintf('<i>[ %s ]</i>', $this->translator->trans('hidden_list', $parameters, 'search.tg'));
        }

        return implode("\n\n", $messages);
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
        return $this->translator->trans($id, $parameters, 'search.tg.' . $this->transDomain);
    }
}
