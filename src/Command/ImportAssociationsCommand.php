<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactoryInterface;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\Association\AssociationPayload;
use Synolia\SyliusAkeneoPlugin\Task\Association\AssociateProductsTask;

final class ImportAssociationsCommand extends Command
{
    use LockableTrait;

    private const DESCRIPTION = 'Import Product Associations from Akeneo PIM.';

    /** @var string */
    protected static $defaultName = 'akeneo:import:associations';

    public function __construct(
        private ClientFactoryInterface $clientFactory,
        private AssociateProductsTask $associateProductsTask,
        private LoggerInterface $akeneoLogger,
    ) {
        parent::__construct(self::$defaultName);
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
        OutputInterface $output,
    ) {
        if (!$this->lock()) {
            $output->writeln(Messages::commandAlreadyRunning());

            return 0;
        }

        $this->akeneoLogger->notice(self::$defaultName);

        $payload = new AssociationPayload($this->clientFactory->createFromApiCredentials());
        $this->associateProductsTask->__invoke($payload);

        $this->akeneoLogger->notice(Messages::endOfCommand(self::$defaultName));
        $this->release();

        return 0;
    }
}
