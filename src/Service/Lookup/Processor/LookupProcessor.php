<?php

declare(strict_types=1);

namespace App\Service\Lookup\Processor;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Enum\Lookup\LookupProcessorName;
use App\Service\Lookup\Viewer\LookupViewerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Throwable;

class LookupProcessor
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
    public function processLookup(
        FeedbackSearchTerm $searchTerm,
        callable $render,
        array $context = [],
        array $processors = null
    ): void
    {
        foreach ($this->getProcessors($processors) as $processor) {
            try {
                if (!$processor->supports($searchTerm, $context)) {
                    continue;
                }

                $viewer = $this->getViewer($processor);

                $render($viewer->getOnSearchTitle($searchTerm));

                $records = $processor->search($searchTerm);
                $count = count($records);

                if ($count === 0) {
                    $render($viewer->getEmptyResultTitle($searchTerm, $context));
                    continue;
                }

                $render($viewer->getResultTitle($searchTerm, $count, $context));

                foreach ($records as $record) {
                    $render($viewer->getResultRecord($record, $context));
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
        return $this->viewerServiceLocator->get($processor->getName()->name);
    }
}