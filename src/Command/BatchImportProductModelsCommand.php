<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactoryInterface;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;
use Synolia\SyliusAkeneoPlugin\Task\ProductModel\BatchProductModelTask;
use Webmozart\Assert\Assert;

#[AsCommand(
    name: 'akeneo:batch:product-models',
    description: 'Import batch product model ids from Akeneo PIM.',
)]
final class BatchImportProductModelsCommand extends AbstractBatchCommand
{
    public function __construct(
        private LoggerInterface $akeneoLogger,
        private ClientFactoryInterface $clientFactory,
        private BatchProductModelTask $batchProductModelTask,
    ) {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        Assert::string($input->getArgument('ids'));
        $ids = explode(',', $input->getArgument('ids'));

        $this->akeneoLogger->debug('Processing batch', ['from_id' => $ids[0], 'to_id' => $ids[\count($ids) - 1]]);
        $this->akeneoLogger->debug($this->getName() ?? '', ['batched_ids' => $ids]);

        $productModelPayload = new ProductModelPayload($this->clientFactory->createFromApiCredentials());
        $productModelPayload->setIds($ids);

        $this->batchProductModelTask->__invoke($productModelPayload);

        return Command::SUCCESS;
    }
}
