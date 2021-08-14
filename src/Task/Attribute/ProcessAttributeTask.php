<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Attribute;

use BluePsyduck\SymfonyProcessManager\ProcessManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Provider\ConfigurationProvider;
use Synolia\SyliusAkeneoPlugin\Task\AbstractBatchTask;

final class ProcessAttributeTask extends AbstractBatchTask
{
    /** @var \Synolia\SyliusAkeneoPlugin\Provider\ConfigurationProvider */
    private $configurationProvider;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $akeneoLogger,
        RepositoryInterface $apiConfigurationRepository,
        ProcessManagerInterface $processManager,
        BatchAttributesTask $task,
        ConfigurationProvider $configurationProvider,
        string $projectDir
    ) {
        parent::__construct($entityManager, $processManager, $task, $akeneoLogger, $apiConfigurationRepository, $projectDir);

        $this->configurationProvider = $configurationProvider;
    }

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $this->logger->debug(self::class);

        if ($payload->isContinue()) {
            $this->process($payload);

            return $payload;
        }

        $page = $payload->getAkeneoPimClient()->getAttributeApi()->listPerPage(
            $this->configurationProvider->getConfiguration()->getPaginationSize(),
            true,
        );

        $this->handle($payload, $page);

        return $payload;
    }

    protected function createBatchPayload(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $commandContext = ($payload->hasCommandContext()) ? $payload->getCommandContext() : null;

        return new AttributePayload($payload->getAkeneoPimClient(), $commandContext);
    }
}
