<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactoryInterface;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\Association\AssociationPayload;
use Synolia\SyliusAkeneoPlugin\Task\Association\AssociateProductsTask;

#[AsCommand(
    name: 'akeneo:import:associations',
    description: 'Import Product Associations from Akeneo PIM.',
)]
final class ImportAssociationsCommand extends Command
{
    use LockableTrait;

    public function __construct(
        private LoggerInterface $akeneoLogger,
        private ClientFactoryInterface $clientFactory,
        private AssociateProductsTask $associateProductsTask,
    ) {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ) {
        if (!$this->lock()) {
            $output->writeln(Messages::commandAlreadyRunning());

            return 0;
        }

        $this->akeneoLogger->notice($this->getName() ?? '');

        $payload = new AssociationPayload($this->clientFactory->createFromApiCredentials());
        $this->associateProductsTask->__invoke($payload);

        $this->akeneoLogger->notice(Messages::endOfCommand($this->getName() ?? ''));
        $this->release();

        return Command::SUCCESS;
    }
}
