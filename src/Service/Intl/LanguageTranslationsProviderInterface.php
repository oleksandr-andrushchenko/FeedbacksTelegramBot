<?php

declare(strict_types=1);

namespace App\Service\Intl;

use App\Entity\Intl\Country;

interface LanguageTranslationsProviderInterface
{
    public function getLanguageTranslations(): ?array;
}