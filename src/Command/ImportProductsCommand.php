<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactory;
use Synolia\SyliusAkeneoPlugin\Factory\ProductPipelineFactory;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload;

final class ImportProductsCommand extends Command
{
    protected static $defaultName = 'akeneo:import:products';

    /** @var \Synolia\SyliusAkeneoPlugin\Client\ClientFactory */
    private $clientFactory;

    /** @var \Synolia\SyliusAkeneoPlugin\Factory\ProductPipelineFactory */
    private $productPipelineFactory;

    public function __construct(
        ProductPipelineFactory $productPipelineFactory,
        ClientFactory $clientFactory,
        string $name = null
    ) {
        parent::__construct($name);
        $this->productPipelineFactory = $productPipelineFactory;
        $this->clientFactory = $clientFactory;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        /** @var \League\Pipeline\Pipeline $productPipeline */
        $productPipeline = $this->productPipelineFactory->create();

        /** @var \Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload $productPayload */
        $productPayload = new ProductPayload($this->clientFactory->createFromApiCredentials());
        $productPipeline->process($productPayload);

        return 0;
    }
}
