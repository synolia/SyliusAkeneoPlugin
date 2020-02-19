<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Command;

use Allocine\Twigcs\Console\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactory;
use Synolia\SyliusAkeneoPlugin\Factory\CategoryPipelineFactory;
use Synolia\SyliusAkeneoPlugin\Payload\Category\CategoryPayload;

final class ImportCategoriesCommand extends ContainerAwareCommand
{
    protected static $defaultName = 'akeneo:import:categories';

    /** @var \Synolia\SyliusAkeneoPlugin\Factory\CategoryPipelineFactory */
    private $categoryPipelineFactory;

    /** @var \Synolia\SyliusAkeneoPlugin\Client\ClientFactory */
    private $clientFactory;

    public function __construct(
        CategoryPipelineFactory $categoryPipelineFactory,
        ClientFactory $clientFactory,
        string $name = null
    ) {
        parent::__construct($name);
        $this->categoryPipelineFactory = $categoryPipelineFactory;
        $this->clientFactory = $clientFactory;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        /** @var \League\Pipeline\Pipeline $categoryPipeline */
        $categoryPipeline = $this->categoryPipelineFactory->create();

        /** @var \Synolia\SyliusAkeneoPlugin\Payload\Category\CategoryPayload $categoryPayload */
        $categoryPayload = new CategoryPayload($this->clientFactory->createFromApiCredentials());
        $categoryPipeline->process($categoryPayload);

        return 0;
    }
}
