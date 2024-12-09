<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Task\Batch;

use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Task\BatchTaskInterface;

class BatchTaskProvider
{
    /**
     * @param BatchTaskProviderInterface[] $providers
     */
    public function __construct(
        #[TaggedIterator(BatchTaskProviderInterface::class)]
        private iterable $providers = [],
    ) {
    }

    public function getTask(PipelinePayloadInterface $payload): BatchTaskInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->support($payload)) {
                return $provider->getTask();
            }
        }

        throw new \LogicException('No batch task found');
    }
}
