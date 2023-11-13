<?php

declare(strict_types=1);

namespace App\Service\Util\Array;

class ArrayStringNormalizerAndFilterer
{
    public function normalizeAndFilterEmptyStrings(array $input): array
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
}
