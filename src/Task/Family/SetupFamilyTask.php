<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Family;

use Doctrine\ORM\EntityManagerInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Family\FamilyPayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

class SetupFamilyTask implements AkeneoTaskInterface
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

    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        if ($payload->isContinue()) {
            $schemaManager = $this->entityManager->getConnection()->getSchemaManager();
            $tableExist = $schemaManager->tablesExist([FamilyPayload::TEMP_AKENEO_TABLE_NAME]);

            if (true === $tableExist) {
                return $payload;
            }
        }

        $this->taskProvider->get(TearDownFamilyTask::class)->__invoke($payload);

        $query = \sprintf(
            'CREATE TABLE `%s` (
              `id` INT NOT NULL AUTO_INCREMENT,
              `values` JSON NULL,
              PRIMARY KEY (`id`));',
            FamilyPayload::TEMP_AKENEO_TABLE_NAME
        );
        $this->entityManager->getConnection()->executeStatement($query);

        return $payload;
    }
}
