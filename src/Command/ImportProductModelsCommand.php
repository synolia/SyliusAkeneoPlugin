<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Command;

use Allocine\Twigcs\Console\ContainerAwareCommand;
use League\Pipeline\Pipeline;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactory;
use Synolia\SyliusAkeneoPlugin\Factory\ProductModelPipelineFactory;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;

final class ImportProductModelsCommand extends ContainerAwareCommand
{
    use LockableTrait;

    protected static $defaultName = 'akeneo:import:product-models';

    /** @var ProductModelPipelineFactory */
    private $productModelPipelineFactory;

    /** @var ClientFactory */
    private $clientFactory;

    public function __construct(
        ProductModelPipelineFactory $productModelPipelineFactory,
        ClientFactory $clientFactory,
        string $name = null
    ) {
        parent::__construct($name);
        $this->productModelPipelineFactory = $productModelPipelineFactory;
        $this->clientFactory = $clientFactory;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        if (!$this->lock()) {
            $output->writeln('The command is already running in another process.');

            return 0;
        }

        /** @var Pipeline $productModelPipeline */
        $productModelPipeline = $this->productModelPipelineFactory->create();

        /** @var ProductModelPayload $productModelPayload */
        $productModelPayload = new ProductModelPayload($this->clientFactory->createFromApiCredentials());
        $productModelPipeline->process($productModelPayload);

        $this->release();

        return 0;
    }
}
