<?php

declare(strict_types=1);

namespace App\Tests\Traits\Search;

use App\Enum\Search\SearchProviderName;
use App\Service\Search\Provider\SearchProviderInterface;

trait SearchProviderTrait
{
    public function getSearchProvider(SearchProviderName $providerName): SearchProviderInterface
    {
        return static::getContainer()->get('app.search_provider_' . $providerName->name);
    }
}