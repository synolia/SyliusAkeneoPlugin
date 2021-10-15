<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Command;

use League\Pipeline\Pipeline;
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

    private LoggerInterface $logger;

    private CategoryPipelineFactory $categoryPipelineFactory;

    private ClientFactoryInterface $clientFactory;

    public function __construct(
        CategoryPipelineFactory $categoryPipelineFactory,
        ClientFactoryInterface $clientFactory,
        LoggerInterface $akeneoLogger,
        string $name = null
    ) {
        parent::__construct($name);
        $this->categoryPipelineFactory = $categoryPipelineFactory;
        $this->clientFactory = $clientFactory;
        $this->logger = $akeneoLogger;
    }

    protected function configure(): void
    {
        $this->setDescription(self::DESCRIPTION);
    }

    /**
     * @inheritdoc
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        if (!$this->lock()) {
            $output->writeln(Messages::commandAlreadyRunning());

            return 0;
        }

        $this->logger->notice(self::$defaultName);
        /** @var Pipeline $categoryPipeline */
        $categoryPipeline = $this->categoryPipelineFactory->create();

        /** @var CategoryPayload $categoryPayload */
        $categoryPayload = new CategoryPayload($this->clientFactory->createFromApiCredentials());
        $categoryPipeline->process($categoryPayload);

        $this->logger->notice(Messages::endOfCommand(self::$defaultName));
        $this->release();

        return 0;
    }
}
