<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Command;

use Allocine\Twigcs\Console\ContainerAwareCommand;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactory;
use Synolia\SyliusAkeneoPlugin\Factory\CategoryPipelineFactory;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\Category\CategoryPayload;

final class ImportCategoriesCommand extends ContainerAwareCommand
{
    use LockableTrait;

    private const DESCRIPTION = 'Import Categories from Akeneo PIM.';

    protected static $defaultName = 'akeneo:import:categories';

    /** @var LoggerInterface */
    private $logger;

    /** @var \Synolia\SyliusAkeneoPlugin\Factory\CategoryPipelineFactory */
    private $categoryPipelineFactory;

    /** @var \Synolia\SyliusAkeneoPlugin\Client\ClientFactory */
    private $clientFactory;

    public function __construct(
        CategoryPipelineFactory $categoryPipelineFactory,
        ClientFactory $clientFactory,
        LoggerInterface $logger,
        string $name = null
    ) {
        parent::__construct($name);
        $this->categoryPipelineFactory = $categoryPipelineFactory;
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

        $this->logger->notice(self::DESCRIPTION);
        /** @var \League\Pipeline\Pipeline $categoryPipeline */
        $categoryPipeline = $this->categoryPipelineFactory->create();

        /** @var \Synolia\SyliusAkeneoPlugin\Payload\Category\CategoryPayload $categoryPayload */
        $categoryPayload = new CategoryPayload($this->clientFactory->createFromApiCredentials());
        $categoryPipeline->process($categoryPayload);

        $this->logger->notice(Messages::endOfCommand(self::DESCRIPTION));
        $this->release();

        return 0;
    }
}
