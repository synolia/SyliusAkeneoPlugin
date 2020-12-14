<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Command;

use League\Pipeline\Pipeline;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactory;
use Synolia\SyliusAkeneoPlugin\Factory\AttributeOptionPipelineFactory;
use Synolia\SyliusAkeneoPlugin\Factory\AttributePipelineFactory;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;

final class ImportAttributesCommand extends Command
{
    use LockableTrait;

    private const DESCRIPTION = 'Import Attributes and Options from Akeneo PIM.';

    /** @var string */
    protected static $defaultName = 'akeneo:import:attributes';

    private AttributePipelineFactory $attributePipelineFactory;

    private AttributeOptionPipelineFactory $attributeOptionPipelineFactory;

    private ClientFactory $clientFactory;

    private LoggerInterface $logger;

    public function __construct(
        AttributePipelineFactory $attributePipelineFactory,
        AttributeOptionPipelineFactory $attributeOptionPipelineFactory,
        ClientFactory $clientFactory,
        LoggerInterface $akeneoLogger,
        string $name = null
    ) {
        parent::__construct($name);
        $this->attributePipelineFactory = $attributePipelineFactory;
        $this->attributeOptionPipelineFactory = $attributeOptionPipelineFactory;
        $this->clientFactory = $clientFactory;
        $this->logger = $akeneoLogger;
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

        $this->logger->notice(self::$defaultName);
        /** @var Pipeline $attributePipeline */
        $attributePipeline = $this->attributePipelineFactory->create();

        /** @var AttributePayload $attributePayload */
        $attributePayload = new AttributePayload($this->clientFactory->createFromApiCredentials());
        $payload = $attributePipeline->process($attributePayload);

        /** @var Pipeline $optionPipeline */
        $optionPipeline = $this->attributeOptionPipelineFactory->create();
        $optionPipeline->process($payload);

        $this->logger->notice(Messages::endOfCommand(self::$defaultName));
        $this->release();

        return 0;
    }
}
