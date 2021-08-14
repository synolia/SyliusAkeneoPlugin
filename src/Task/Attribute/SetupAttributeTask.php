<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Attribute;

use Doctrine\ORM\EntityManagerInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

class SetupAttributeTask implements AkeneoTaskInterface
{
    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $entityManager;

    /** @var \Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider */
    private $taskProvider;

    public function __construct(EntityManagerInterface $entityManager, AkeneoTaskProvider $taskProvider)
    {
        $this->entityManager = $entityManager;
        $this->taskProvider = $taskProvider;
    }

    /**
     * @param AttributePayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        if ($payload->isContinue()) {
            $schemaManager = $this->entityManager->getConnection()->getSchemaManager();
            $tableExist = $schemaManager->tablesExist([$payload::TEMP_AKENEO_TABLE_NAME]);

            if (true === $tableExist) {
                return $payload;
            }
        }

        $this->taskProvider->get(TearDownAttributeTask::class)->__invoke($payload);

        $query = \sprintf(
            'CREATE TABLE `%s` (
              `id` INT NOT NULL AUTO_INCREMENT,
              `values` JSON NULL,
              PRIMARY KEY (`id`));',
            AttributePayload::TEMP_AKENEO_TABLE_NAME
        );
        $this->entityManager->getConnection()->executeStatement($query);

        return $payload;
    }
}
