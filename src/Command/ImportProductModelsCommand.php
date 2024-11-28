<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\Command\CommandLockedException;
use Synolia\SyliusAkeneoPlugin\Factory\PayloadFactoryInterface;
use Synolia\SyliusAkeneoPlugin\Factory\ProductModelPipelineFactory;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;

#[AsCommand(
    name: 'akeneo:import:product-models',
    description: 'Import Product Models from Akeneo.',
)]
final class ImportProductModelsCommand extends AbstractImportCommand
{
    use LockableTrait;

    public function __construct(
        protected LoggerInterface $akeneoLogger,
        protected PayloadFactoryInterface $payloadFactory,
        private ProductModelPipelineFactory $pipelineFactory,
    ) {
        parent::__construct($akeneoLogger, $payloadFactory, $pipelineFactory);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->preExecute();

            $payload = $this->payloadFactory->createFromCommand(ProductModelPayload::class, $input, $output);
            $this->pipeline->process($payload);

            $this->postExecute();
        } catch (CommandLockedException $commandLockedException) {
            $this->akeneoLogger->info($commandLockedException->getMessage());

            return Command::SUCCESS;
        }

        return Command::SUCCESS;
    }
}
