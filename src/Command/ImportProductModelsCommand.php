<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Command;

use Allocine\Twigcs\Console\ContainerAwareCommand;
use League\Pipeline\Pipeline;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactory;
use Synolia\SyliusAkeneoPlugin\Factory\ProductModelPipelineFactory;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;

final class ImportProductModelsCommand extends ContainerAwareCommand
{
    use LockableTrait;

    private const DESCRIPTION = 'Import Product Models from Akeneo PIM.';

    protected static $defaultName = 'akeneo:import:product-models';

    /** @var ProductModelPipelineFactory */
    private $productModelPipelineFactory;

    /** @var ClientFactory */
    private $clientFactory;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        ProductModelPipelineFactory $productModelPipelineFactory,
        ClientFactory $clientFactory,
        LoggerInterface $logger,
        string $name = null
    ) {
        parent::__construct($name);
        $this->productModelPipelineFactory = $productModelPipelineFactory;
        $this->clientFactory = $clientFactory;
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        $this->setDescription(self::DESCRIPTION);
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

        $this->logger->notice(self::DESCRIPTION);
        /** @var Pipeline $productModelPipeline */
        $productModelPipeline = $this->productModelPipelineFactory->create();

        /** @var ProductModelPayload $productModelPayload */
        $productModelPayload = new ProductModelPayload($this->clientFactory->createFromApiCredentials());
        $productModelPipeline->process($productModelPayload);

        $this->logger->notice(Messages::endOfCommand(self::DESCRIPTION));
        $this->release();

        return 0;
    }
}
