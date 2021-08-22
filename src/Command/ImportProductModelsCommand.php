<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Command;

use League\Pipeline\Pipeline;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactoryInterface;
use Synolia\SyliusAkeneoPlugin\Factory\ProductModelPipelineFactory;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;

final class ImportProductModelsCommand extends AbstractImportCommand
{
    use LockableTrait;

    protected static $defaultDescription = 'Import Product Models from Akeneo PIM.';

    /** @var string */
    protected static $defaultName = 'akeneo:import:product-models';

    /** @var ProductModelPipelineFactory */
    private $productModelPipelineFactory;

    /** @var ClientFactoryInterface */
    private $clientFactory;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        ProductModelPipelineFactory $productModelPipelineFactory,
        ClientFactoryInterface $clientFactory,
        LoggerInterface $akeneoLogger,
        string $name = null
    ) {
        parent::__construct($name);
        $this->productModelPipelineFactory = $productModelPipelineFactory;
        $this->clientFactory = $clientFactory;
        $this->logger = $akeneoLogger;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        if (!$this->lock()) {
            $output->writeln(Messages::commandAlreadyRunning());

            return 0;
        }

        $context = parent::createContext($input, $output);

        $this->logger->notice(self::$defaultName);
        /** @var Pipeline $productModelPipeline */
        $productModelPipeline = $this->productModelPipelineFactory->create();

        $productModelPayload = new ProductModelPayload($this->clientFactory->createFromApiCredentials(), $context);
        $productModelPipeline->process($productModelPayload);

        $this->logger->notice(Messages::endOfCommand(self::$defaultName));
        $this->release();

        return 0;
    }
}
