<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactory;
use Synolia\SyliusAkeneoPlugin\Factory\ProductPipelineFactory;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload;

final class ImportProductsCommand extends Command
{
    use LockableTrait;

    private const DESCRIPTION = 'Import Products from Akeneo PIM.';

    /** @var string */
    protected static $defaultName = 'akeneo:import:products';

    /** @var \Synolia\SyliusAkeneoPlugin\Client\ClientFactory */
    private $clientFactory;

    /** @var \Synolia\SyliusAkeneoPlugin\Factory\ProductPipelineFactory */
    private $productPipelineFactory;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        ProductPipelineFactory $productPipelineFactory,
        ClientFactory $clientFactory,
        LoggerInterface $akeneoLogger,
        ?string $name = null
    ) {
        parent::__construct($name);
        $this->productPipelineFactory = $productPipelineFactory;
        $this->clientFactory = $clientFactory;
        $this->logger = $akeneoLogger;
    }

    protected function configure(): void
    {
        $this->setDescription(self::DESCRIPTION);
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        if (!$this->lock()) {
            $output->writeln(Messages::commandAlreadyRunning());

            return 0;
        }

        $this->logger->notice(self::$defaultName);
        /** @var \League\Pipeline\Pipeline $productPipeline */
        $productPipeline = $this->productPipelineFactory->create();

        /** @var \Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload $productPayload */
        $productPayload = new ProductPayload($this->clientFactory->createFromApiCredentials());
        $productPipeline->process($productPayload);

        $this->logger->notice(Messages::endOfCommand(self::$defaultName));
        $this->release();

        return 0;
    }
}
