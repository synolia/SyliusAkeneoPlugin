<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactoryInterface;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;
use Synolia\SyliusAkeneoPlugin\Task\ProductModel\BatchProductModelTask;

final class BatchImportProductModelsCommand extends AbstractBatchCommand
{
    protected static $defaultDescription = 'Import batch product model ids from Akeneo PIM.';

    /** @var string */
    protected static $defaultName = 'akeneo:batch:product-models';

    /** @var \Synolia\SyliusAkeneoPlugin\Task\ProductModel\BatchProductModelTask */
    private $attributesTask;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /** @var ClientFactoryInterface */
    private $clientFactory;

    public function __construct(
        ClientFactoryInterface $clientFactory,
        BatchProductModelTask $batchProductModelTask,
        LoggerInterface $akeneoLogger,
        string $name = null
    ) {
        parent::__construct($name);
        $this->attributesTask = $batchProductModelTask;
        $this->logger = $akeneoLogger;
        $this->clientFactory = $clientFactory;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $ids = explode(',', $input->getArgument('ids'));

        $this->logger->notice('Processing batch', ['from_id' => $ids[0], 'to_id' => $ids[\count($ids) - 1]]);
        $this->logger->debug(self::$defaultName, ['batched_ids' => $ids]);

        $productModelPayload = new ProductModelPayload($this->clientFactory->createFromApiCredentials());
        $productModelPayload->setIds($ids);

        $this->attributesTask->__invoke($productModelPayload);

        return 0;
    }
}
