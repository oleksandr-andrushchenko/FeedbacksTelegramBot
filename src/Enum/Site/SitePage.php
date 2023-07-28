<?php

declare(strict_types=1);

namespace App\Enum\Site;

enum SitePage: string
{
    case INDEX = 'index';
    case PRIVACY_POLICY = 'privacy_policy';
    case TERMS_OF_USE = 'terms_of_use';
    case CONTACTS = 'contacts';

    public function view(string $locale = null): string
    {
        if ($locale === null) {
            return 'site.' . $this->value . '.html.twig';
        }

        return 'site.' . $this->value . '.' . $locale . '.html.twig';
    }
}