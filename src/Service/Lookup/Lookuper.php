<?php

declare(strict_types=1);

namespace App\Service\Lookup;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Enum\Lookup\LookupProcessorName;
use App\Service\Lookup\Processor\LookupProcessorInterface;
use App\Service\Lookup\Viewer\LookupViewerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Throwable;

class Lookuper
{
    public function __construct(
        private readonly ServiceLocator $processorServiceLocator,
        private readonly ServiceLocator $viewerServiceLocator,
        private readonly LoggerInterface $logger,
    )
    {
    }

    /**
     * @param FeedbackSearchTerm $searchTerm
     * @param callable $render
     * @param array $context
     * @param LookupProcessorName[]|null $processors
     * @return void
     */
    public function lookup(FeedbackSearchTerm $searchTerm, callable $render, array $context = [], array $processors = null): void
    {
        foreach ($this->getProcessors($processors) as $processor) {
            try {
                if (!$processor->supports($searchTerm, $context)) {
                    continue;
                }

                $viewer = $this->getViewer($processor);

                $render('🔍 ' . $viewer->getOnSearchTitle($searchTerm));

                $records = [];

                foreach ($processor->getSearchers($searchTerm, $context) as $searcher) {
                    try {
                        $records = array_merge($records, $searcher($searchTerm, $context));
                    } catch (Throwable $exception) {
                        $this->logger->error($exception);
                    }
                }

                $records = array_filter($records);
                $count = count($records);

                if ($count === 0) {
                    $render($viewer->getEmptyResultTitle($searchTerm, $context));
                    continue;
                }

//                $render($viewer->getResultTitle($searchTerm, $count, $context));

                foreach ($records as $index => $record) {
                    $render($viewer->getResultRecord($record, $context + ['index' => $index]));
                }
            } catch (Throwable $exception) {
                $this->logger->error($exception);
            }
        }
    }

    /**
     * @param LookupProcessorName[]|null $filter
     * @return LookupProcessorInterface[]
     */
    private function getProcessors(array $filter = null): iterable
    {
        $processors = empty($filter)
            ? array_keys($this->processorServiceLocator->getProvidedServices())
            : array_map(static fn (LookupProcessorName $processor): string => $processor->value, $filter);

        foreach ($processors as $processor) {
            yield $this->processorServiceLocator->get($processor);
        }
    }

    private function getViewer(LookupProcessorInterface $processor): LookupViewerInterface
    {
        return $this->viewerServiceLocator->get($processor->getName()->value);
    }
}