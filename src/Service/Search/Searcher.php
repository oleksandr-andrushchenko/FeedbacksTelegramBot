<?php

declare(strict_types=1);

namespace App\Service\Search;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Enum\Search\SearchProviderName;
use App\Service\Search\Provider\SearchProviderInterface;
use App\Service\Search\Viewer\SearchViewerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Throwable;

class Searcher
{
    public function __construct(
        private readonly ServiceLocator $providers,
        private readonly ServiceLocator $viewers,
        private readonly LoggerInterface $logger,
    )
    {
    }

    /**
     * @param FeedbackSearchTerm $searchTerm
     * @param callable $render
     * @param array $context
     * @param SearchProviderName[]|null $providers
     * @return void
     */
    public function search(FeedbackSearchTerm $searchTerm, callable $render, array $context = [], array $providers = null): void
    {
        foreach ($this->getProviders($providers) as $provider) {
            try {
                if (!$provider->supports($searchTerm, $context)) {
                    continue;
                }

                $viewer = $this->getViewer($provider);

                $render($viewer->getOnSearchTitle($searchTerm));

                $records = [];

                try {
                    $records = array_merge($records, $provider->search($searchTerm, $context));
                } catch (Throwable $exception) {
                    $this->logger->error($exception);

                    $render($viewer->getErrorResultTitle($searchTerm, $context));
                }

                $records = array_filter($records);
                $count = count($records);

                if ($count === 0) {
                    $render($viewer->getEmptyResultTitle($searchTerm, $context));
                    continue;
                }

                foreach ($records as $index => $record) {
                    $render($viewer->getResultRecord($record, $searchTerm, $context + ['index' => $index]));
                }
            } catch (Throwable $exception) {
                $this->logger->error($exception);
            }
        }
    }

    /**
     * @param SearchProviderName[]|null $filter
     * @return SearchProviderInterface[]
     */
    private function getProviders(array $filter = null): iterable
    {
        $providers = empty($filter)
            ? array_keys($this->providers->getProvidedServices())
            : array_map(static fn (SearchProviderName $provider): string => $provider->name, $filter);

        foreach ($providers as $provider) {
            yield $this->providers->get($provider);
        }
    }

    private function getViewer(SearchProviderInterface $provider): SearchViewerInterface
    {
        return $this->viewers->get($provider->getName()->name);
    }
}