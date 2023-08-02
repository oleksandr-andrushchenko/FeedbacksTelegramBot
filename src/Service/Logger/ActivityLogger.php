<?php

declare(strict_types=1);

namespace App\Service\Logger;

use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ActivityLogger
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly NormalizerInterface $normalizer,
    )
    {
    }

    public function logActivity(object $object): void
    {
        $message = sprintf('"%s" has been %s(?)', get_class($object), $object->getUpdatedAt() === null ? 'created' : 'updated');
        $context = $this->normalizer->normalize($object, 'activity');

        $this->logger->info($message, $context);
    }
}