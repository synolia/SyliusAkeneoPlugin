<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Factory;

use const FILTER_VALIDATE_BOOLEAN;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactoryInterface;
use Synolia\SyliusAkeneoPlugin\Command\Context\CommandContext;
use Synolia\SyliusAkeneoPlugin\Command\Context\CommandContextInterface;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;

final class PayloadFactory implements PayloadFactoryInterface
{
    public function __construct(private ClientFactoryInterface $clientFactory)
    {
    }

    public function create(string $className): PipelinePayloadInterface
    {
        /** @phpstan-ignore-next-line */
        return new $className($this->clientFactory->createFromApiCredentials());
    }

    public function createFromCommand(
        string $className,
        InputInterface $input,
        OutputInterface $output,
    ): PipelinePayloadInterface {
        $context = $this->createContext($input, $output);

        /** @phpstan-ignore-next-line */
        return new $className($this->clientFactory->createFromApiCredentials(), $context);
    }

    private function createContext(
        InputInterface $input,
        OutputInterface $output,
    ): CommandContextInterface {
        $context = new CommandContext($input, $output);

        $isBatchingAllowed = !($input->getOption('disable-batch') ?? true);
        $isParallelAllowed = $input->getOption('parallel') ?? false;
        $batchAfterFetch = $input->getOption('batch-after-fetch') ?? false;

        $context
            ->setIsContinue($input->getOption('continue') ?? false)
            ->setAllowParallel($isParallelAllowed)
            ->setBatchingAllowed($isBatchingAllowed)
            ->setProcessAsSoonAsPossible(filter_var($batchAfterFetch, FILTER_VALIDATE_BOOLEAN))
            ->setBatchSize((int) $input->getOption('batch-size'))
            ->setMaxRunningProcessQueueSize((int) $input->getOption('max-concurrency'))
            ->setFilters((array) ($input->getOption('filter') ?: []))
            ->setHandler($input->getOption('handler') ?? $context->getHandler())
        ;

        if (!$isBatchingAllowed) {
            $context->disableBatching();
        }

        return $context;
    }
}
