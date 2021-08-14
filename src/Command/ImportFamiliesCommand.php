<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Command;

use League\Pipeline\Pipeline;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactory;
use Synolia\SyliusAkeneoPlugin\Factory\FamilyPipelineFactory;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\Family\FamilyPayload;

final class ImportFamiliesCommand extends AbstractImportCommand
{
    use LockableTrait;

    /** @var string */
    protected static $defaultDescription = 'Import Families from Akeneo PIM.';

    /** @var string */
    protected static $defaultName = 'akeneo:import:families';

    /** @var FamilyPipelineFactory */
    private $familyPipelineFactory;

    /** @var ClientFactory */
    private $clientFactory;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        FamilyPipelineFactory $familyPipelineFactory,
        ClientFactory $clientFactory,
        LoggerInterface $akeneoLogger,
        string $name = null
    ) {
        parent::__construct($name);
        $this->familyPipelineFactory = $familyPipelineFactory;
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
        /** @var Pipeline $familyPipeline */
        $familyPipeline = $this->familyPipelineFactory->create();

        $payload = new FamilyPayload($this->clientFactory->createFromApiCredentials(), $context);
        $familyPipeline->process($payload);

        $this->logger->notice(Messages::endOfCommand(self::$defaultName));
        $this->release();

        return 0;
    }
}
