<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\Command\CommandLockedException;
use Synolia\SyliusAkeneoPlugin\Factory\AttributePipelineFactory;
use Synolia\SyliusAkeneoPlugin\Factory\PayloadFactoryInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;

#[AsCommand(
    name: 'akeneo:import:attributes',
    description: 'Import Attributes and Options from Akeneo PIM.',
)]
final class ImportAttributesCommand extends AbstractImportCommand
{
    public function __construct(
        protected LoggerInterface $akeneoLogger,
        protected PayloadFactoryInterface $payloadFactory,
        private AttributePipelineFactory $pipelineFactory,
    ) {
        parent::__construct($akeneoLogger, $payloadFactory, $pipelineFactory);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->preExecute();

            $payload = $this->payloadFactory->createFromCommand(AttributePayload::class, $input, $output);
            $this->pipeline->process($payload);

            $this->postExecute();
        } catch (CommandLockedException $commandLockedException) {
            $this->akeneoLogger->info($commandLockedException->getMessage());

            return Command::SUCCESS;
        }

        return Command::SUCCESS;
    }
}
