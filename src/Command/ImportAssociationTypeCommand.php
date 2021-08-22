<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\Command\CommandLockedException;
use Synolia\SyliusAkeneoPlugin\Factory\AssociationTypePipelineFactory;
use Synolia\SyliusAkeneoPlugin\Factory\PayloadFactoryInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Association\AssociationTypePayload;

final class ImportAssociationTypeCommand extends AbstractImportCommand
{
    use LockableTrait;

    protected static $defaultDescription = 'Import Associations type from Akeneo PIM.';

    /** @var string */
    protected static $defaultName = 'akeneo:import:association-type';

    public function __construct(
        AssociationTypePipelineFactory $pipelineFactory,
        LoggerInterface $akeneoLogger,
        PayloadFactoryInterface $payloadFactory,
        string $name = null
    ) {
        parent::__construct($akeneoLogger, $payloadFactory, $pipelineFactory, $name);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->preExecute();

            $payload = $this->payloadFactory->createFromCommand(AssociationTypePayload::class, $input, $output);
            $this->pipeline->process($payload);

            $this->postExecute();
        } catch (CommandLockedException $commandLockedException) {
            $this->logger->warning($commandLockedException->getMessage());

            return 1;
        }

        return 0;
    }
}
