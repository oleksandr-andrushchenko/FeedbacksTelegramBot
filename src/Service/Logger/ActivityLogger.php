<?php

declare(strict_types=1);

namespace App\Service\Logger;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Stringable;

class ActivityLogger extends AbstractLogger implements LoggerInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly NormalizerInterface $normalizer,
    )
    {
    }

    public function log($level, string|Stringable $message, array $context = []): void
    {
        if (!is_object($message)) {
            return;
        }

        $updated = method_exists($message, 'getUpdatedAt') && $message->getUpdatedAt() !== null;
        $class = get_class($message);

        $classPrefix = 'App\Entity';
        if (str_starts_with($class, $classPrefix)) {
            $class = substr($class, strlen($classPrefix) + 1);
        }

        $envelop = sprintf('"%s" has been %s(?)', $class, $updated ? 'updated' : 'created');
        $context = $this->normalizer->normalize($message, 'activity');

        $this->logger->log($level, $envelop, $context);
    }
}