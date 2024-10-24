<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\Command\CommandLockedException;
use Synolia\SyliusAkeneoPlugin\Factory\PayloadFactoryInterface;
use Synolia\SyliusAkeneoPlugin\Factory\ProductModelPipelineFactory;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;

final class ImportProductModelsCommand extends AbstractImportCommand
{
    use LockableTrait;

    protected static $defaultDescription = 'Import Product Models from Akeneo PIM.';

    /** @var string */
    protected static $defaultName = 'akeneo:import:product-models';

    public function __construct(
        ProductModelPipelineFactory $pipelineFactory,
        LoggerInterface $akeneoLogger,
        PayloadFactoryInterface $payloadFactory,
    ) {
        parent::__construct($akeneoLogger, $payloadFactory, $pipelineFactory, self::$defaultName);
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
            $this->akeneoLogger->warning($commandLockedException->getMessage());

            return 1;
        }

        return 0;
    }
}
