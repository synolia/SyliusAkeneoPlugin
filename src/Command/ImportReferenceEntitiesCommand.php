<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactory;
use Synolia\SyliusAkeneoPlugin\Factory\ReferenceEntityPipelineFactory;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\ReferenceEntity\ReferenceEntityOptionsPayload;

final class ImportReferenceEntitiesCommand extends Command
{
    use LockableTrait;

    private const DESCRIPTION = 'Import Reference Entities from Akeneo PIM.';

    /** @var string */
    protected static $defaultName = 'akeneo:import:reference-entities';

    /** @var \Synolia\SyliusAkeneoPlugin\Factory\ReferenceEntityPipelineFactory */
    private $referenceEntityPipelineFactory;

    /** @var \Synolia\SyliusAkeneoPlugin\Client\ClientFactory */
    private $clientFactory;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        ReferenceEntityPipelineFactory $referenceEntityPipelineFactory,
        ClientFactory $clientFactory,
        LoggerInterface $akeneoLogger,
        string $name = null
    ) {
        parent::__construct($name);
        $this->referenceEntityPipelineFactory = $referenceEntityPipelineFactory;
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
        /** @var \League\Pipeline\Pipeline $referenceEntityPipeline */
        $referenceEntityPipeline = $this->referenceEntityPipelineFactory->create();

        /** @var \Synolia\SyliusAkeneoPlugin\Payload\ReferenceEntity\ReferenceEntityOptionsPayload $referenceEntityPayload */
        $referenceEntityPayload = new ReferenceEntityOptionsPayload($this->clientFactory->createFromApiCredentials());
        $payload = $referenceEntityPipeline->process($referenceEntityPayload);

        $this->logger->notice(Messages::endOfCommand(self::$defaultName));
        $this->release();

        return 0;
    }
}
