<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\Command\CommandLockedException;
use Synolia\SyliusAkeneoPlugin\Factory\FamilyPipelineFactory;
use Synolia\SyliusAkeneoPlugin\Factory\PayloadFactoryInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Family\FamilyPayload;

final class ImportFamiliesCommand extends AbstractImportCommand
{
    use LockableTrait;

    /** @var string */
    protected static $defaultDescription = 'Import Families from Akeneo PIM.';

    /** @var string */
    protected static $defaultName = 'akeneo:import:families';

    public function __construct(
        FamilyPipelineFactory $pipelineFactory,
        LoggerInterface $akeneoLogger,
        PayloadFactoryInterface $payloadFactory
    ) {
        parent::__construct($akeneoLogger, $payloadFactory, $pipelineFactory, self::$defaultName);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->preExecute();

            $payload = $this->payloadFactory->createFromCommand(FamilyPayload::class, $input, $output);
            $this->pipeline->process($payload);

            $this->postExecute();
        } catch (CommandLockedException $commandLockedException) {
            $this->logger->warning($commandLockedException->getMessage());

            return 1;
        }

        return 0;
    }
}
