<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Task\Batch\Payload\CommandContext;

use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;

class PayloadBatchTaskProvider
{
    /**
     * @param PayloadBatchTaskProviderInterface[] $providers
     */
    public function __construct(
        #[TaggedIterator(PayloadBatchTaskProviderInterface::class)]
        private iterable $providers = [],
    ) {
    }

    public function createBatchPayload(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->support($payload)) {
                return $provider->createCommandContextBatchPayload($payload);
            }
        }

        throw new \InvalidArgumentException('Could not find command context batch payload provider for payload ' . $payload::class);
    }
}
