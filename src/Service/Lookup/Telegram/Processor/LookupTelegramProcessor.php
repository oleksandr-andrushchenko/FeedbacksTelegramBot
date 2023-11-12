<?php

declare(strict_types=1);

namespace App\Service\Lookup\Telegram\Processor;

use App\Entity\Feedback\FeedbackSearch;
use App\Entity\Lookup\LookupProcessorResult;
use App\Service\Telegram\Bot\TelegramBotAwareHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Throwable;

class LookupTelegramProcessor
{
    public function __construct(
        private readonly ServiceLocator $serviceLocator,
        private readonly LoggerInterface $logger,
    )
    {
    }

    /**
     * @param FeedbackSearch $feedbackSearch
     * @param TelegramBotAwareHelper $tg
     * @return LookupProcessorResult[]
     */
    public function lookupByFeedbackSearch(FeedbackSearch $feedbackSearch, TelegramBotAwareHelper $tg): iterable
    {
        foreach ($this->getLookupProcessors() as $processor) {
            try {
                $result = $processor->lookupByFeedbackSearch($feedbackSearch, $tg);

                if ($result === null) {
                    continue;
                }

                yield $result;
            } catch (Throwable $exception) {
                $this->logger->error($exception);
            }
        }
    }

    /**
     * @return LookupTelegramProcessorInterface[]
     */
    private function getLookupProcessors(): iterable
    {
        foreach ($this->serviceLocator->getProvidedServices() as $name => $service) {
            yield $this->serviceLocator->get($name);
        }
    }
}