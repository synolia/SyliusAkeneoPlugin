<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task;

use Doctrine\DBAL\Exception\ConnectionLost;
use Doctrine\ORM\EntityManagerInterface;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;

final class TearDownTask implements AkeneoTaskInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        try {
            $this->delete($payload);
        } catch (ConnectionLost) {
            $this->delete($payload);
        }

        return $payload;
    }

    private function delete(PipelinePayloadInterface $payload): void
    {
        $exists = $this->entityManager->getConnection()->getSchemaManager()->tablesExist([$payload->getTmpTableName()]);

        if ($exists) {
            $this->entityManager->getConnection()->getSchemaManager()->dropTable($payload->getTmpTableName());
        }
    }
}
