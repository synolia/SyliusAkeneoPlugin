<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task;

use Doctrine\ORM\EntityManagerInterface;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;

final class SetupTask implements AkeneoTaskInterface
{
    private EntityManagerInterface $entityManager;

    private TearDownTask $tearDownTask;

    public function __construct(
        EntityManagerInterface $entityManager,
        TearDownTask $tearDownTask
    ) {
        $this->entityManager = $entityManager;
        $this->tearDownTask = $tearDownTask;
    }

    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        if ($payload->isContinue()) {
            $schemaManager = $this->entityManager->getConnection()->getSchemaManager();
            $tableExist = $schemaManager->tablesExist([$payload->getTmpTableName()]);

            if (true === $tableExist) {
                return $payload;
            }
        }

        $this->tearDownTask->__invoke($payload);

        $query = sprintf(
            'CREATE TABLE `%s` (
              `id` INT NOT NULL AUTO_INCREMENT,
              `values` JSON NULL,
              PRIMARY KEY (`id`));',
            $payload->getTmpTableName()
        );
        $this->entityManager->getConnection()->executeStatement($query);

        return $payload;
    }
}
