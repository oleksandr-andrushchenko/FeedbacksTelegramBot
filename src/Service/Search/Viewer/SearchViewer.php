<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer;

use App\Entity\Search\Viewer\Modifier;
use App\Service\Util\String\SecretsAdder;
use DateTimeInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class SearchViewer
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly SecretsAdder $secretsAdder,
        private readonly string $transDomain,
    )
    {
    }

    protected function wrapResultRecord(string $title, array $items, callable $record, bool $full): string
    {
        // 🔴🟡🟢⚪️🚨‼️
        // ⬜️⬛️◻️◼️◽️◾️▫️▪️
        // 💥🔥✨⚡️💫🥳🤩

        $messages = [];

        $messages[] = sprintf('<b>%s</b>', $title);

        $count = count($items);

        if ($full) {
            $maxResults = $count;
        } else {
            $maxResults = intval($count * .1);
            $maxResults = max($maxResults, 1);
        }

        $added = 0;

        foreach ($items as $item) {
            $messages[] = '◻️ ' . implode("\n▫️ ", $this->normalizeAndFilterEmptyStrings($record($item)));
            $added++;

            if ($added === $maxResults) {
                break;
            }
        }

        if (!$full) {
            $message = '';

            if ($maxResults !== $count) {
                $message .= sprintf('<i>%s</i>', $this->transSubscriptionSkippedRecords($maxResults, $count));
            }

            $message .= sprintf('<i>%s</i>', $this->transSubscriptionSkippedData());
            $message .= "\n";
            $message .= sprintf('<i>%s</i>', $this->transSubscriptionBenefits());

            $messages[] = $message;
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

    protected function modifier(): Modifier
    {
        return new Modifier();
    }

    protected function boldModifier(): callable
    {
        return static fn (?string $text): ?string => empty($text) ? null : sprintf('<b>%s</b>', $text);
    }

    protected function italicModifier(): callable
    {
        return static fn (?string $text): ?string => empty($text) ? null : sprintf('<i>%s</i>', $text);
    }

    protected function underlineModifier(): callable
    {
        return static fn (?string $text): ?string => empty($text) ? null : sprintf('<u>%s</u>', $text);
    }

    protected function linkModifier(?string $href): callable
    {
        return static fn (?string $text): ?string => empty($text) ? null : (empty($href) ? $text : sprintf('<a href="%s">%s</a>', $href, $text));
    }

    protected function secretsModifier(): callable
    {
        return static fn (?string $text): ?string => empty($text) ? null : $this->secretsAdder->addSecrets($text);
    }

    protected function hiddenModifier(string $id): callable
    {
        return static fn (?string $text): ?string => empty($text) ? null : $this->transHidden($id);
    }

    protected function greenWhiteModifier(string $id): callable
    {
        return fn (?bool $active): ?string => $active === null ? null : sprintf('%s %s', $active ? '🟢' : '⚪️', $this->trans(($active ? '' : 'not_') . $id));
    }

    protected function redWhiteModifier(string $id = null): callable
    {
        return fn (?bool $active): ?string => $active === null ? null : (($active ? '🔴' : '⚪️') . ($id === null ? '' : (' ' . $this->trans(($active ? '' : 'not_') . $id))));
    }

    protected function slashesModifier(): callable
    {
        return static fn (?string $text): ?string => empty($text) ? null : addslashes($text);
    }

    protected function conditionalModifier($condition): callable
    {
        return static fn (?string $text): ?string => $condition ? $text : null;
    }

    protected function bracketsModifier(string $id): callable
    {
        return fn (?string $text): ?string => empty($text) ? null : sprintf('%s [ %s ]', $text, $this->trans($id));
    }

    protected function appendModifier(string $append): callable
    {
        return static fn (?string $text): ?string => empty($text) ? null : sprintf('%s %s', $text, $append);
    }

    protected function datetimeModifier(string $format): callable
    {
        return static fn (?DateTimeInterface $dateTime): ?string => $dateTime?->format($format);
    }

    protected function transSubscriptionSkippedRecords(int $maxResults, int $count): string
    {
        $parameters = [
            'shown_count' => $maxResults,
            'total_count' => $count,
        ];

        return $this->translator->trans('subscription_skipped_records', $parameters, 'search.tg');
    }

    protected function transSubscriptionSkippedData(): string
    {
        $parameters = [];

        return $this->translator->trans('subscription_skipped_data', $parameters, 'search.tg');
    }

    protected function transSubscriptionBenefits(): string
    {
        $parameters = [
            'all_records' => sprintf('<b>%s</b>', $this->translator->trans('subscription_all_records', domain: 'search.tg')),
            'all_data' => sprintf('<b>%s</b>', $this->translator->trans('subscription_all_data', domain: 'search.tg')),
            'subscribe_command' => sprintf('<b>%s</b>', '/subscribe'),
        ];

        return $this->translator->trans('subscription_benefits', $parameters, 'search.tg');
    }

    protected function transHidden(string $id): string
    {
        $parameters = [
            'entity' => $this->trans($id),
        ];

        return $this->translator->trans('hidden', $parameters, 'search.tg');
    }

    protected function trans($id, array $parameters = []): string
    {
        return $this->translator->trans($id, $parameters, 'search.tg.' . $this->transDomain);
    }
}
