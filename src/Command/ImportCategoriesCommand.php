<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactoryInterface;
use Synolia\SyliusAkeneoPlugin\Factory\CategoryPipelineFactory;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\Category\CategoryPayload;

final class ImportCategoriesCommand extends Command
{
    use LockableTrait;

    private const DESCRIPTION = 'Import Categories from Akeneo PIM.';

    /** @var string */
    protected static $defaultName = 'akeneo:import:categories';

    public function __construct(
        private CategoryPipelineFactory $categoryPipelineFactory,
        private ClientFactoryInterface $clientFactory,
        private LoggerInterface $logger,
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

        $this->logger->notice(self::$defaultName);
        /** @var \League\Pipeline\Pipeline $categoryPipeline */
        $categoryPipeline = $this->categoryPipelineFactory->create();

        /** @var \Synolia\SyliusAkeneoPlugin\Payload\Category\CategoryPayload $categoryPayload */
        $categoryPayload = new CategoryPayload($this->clientFactory->createFromApiCredentials());
        $categoryPipeline->process($categoryPayload);

        $this->logger->notice(Messages::endOfCommand(self::$defaultName));
        $this->release();

        return 0;
    }
}
