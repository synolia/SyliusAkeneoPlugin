<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Command;

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

    /** @var \Synolia\SyliusAkeneoPlugin\Factory\AttributePipelineFactory */
    private $attributePipelineFactory;

    /** @var \Synolia\SyliusAkeneoPlugin\Factory\AttributeOptionPipelineFactory */
    private $attributeOptionPipelineFactory;

    /** @var \Synolia\SyliusAkeneoPlugin\Client\ClientFactory */
    private $clientFactory;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        AttributePipelineFactory $attributePipelineFactory,
        AttributeOptionPipelineFactory $attributeOptionPipelineFactory,
        ClientFactory $clientFactory,
        LoggerInterface $logger,
        string $name = null
    ) {
        parent::__construct($name);
        $this->attributePipelineFactory = $attributePipelineFactory;
        $this->attributeOptionPipelineFactory = $attributeOptionPipelineFactory;
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

        $this->logger->notice(self::$defaultName);
        /** @var \League\Pipeline\Pipeline $attributePipeline */
        $attributePipeline = $this->attributePipelineFactory->create();

        /** @var \Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload $attributePayload */
        $attributePayload = new AttributePayload($this->clientFactory->createFromApiCredentials());
        $payload = $attributePipeline->process($attributePayload);

        /** @var \League\Pipeline\Pipeline $optionPipeline */
        $optionPipeline = $this->attributeOptionPipelineFactory->create();
        $optionPipeline->process($payload);

        $this->logger->notice(Messages::endOfCommand(self::$defaultName));
        $this->release();

        return 0;
    }
}
