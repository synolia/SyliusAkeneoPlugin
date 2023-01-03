<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task;

use Doctrine\DBAL\Exception\ConnectionLost;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityManagerInterface;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;

abstract class AbstractBatchTask implements AkeneoTaskInterface, BatchTaskInterface
{
    public function __construct(protected EntityManagerInterface $entityManager)
    {
    }

    protected function getSelectStatement(PipelinePayloadInterface $payload): Statement
    {
        return $this->entityManager->getConnection()->prepare(sprintf(
            'SELECT id, `values`
             FROM `%s`
             WHERE id IN (%s)
             ORDER BY id ASC',
            $payload->getTmpTableName(),
            implode(',', $payload->getIds()),
        ));
    }

    protected function removeEntry(PipelinePayloadInterface $payload, int $id): void
    {
        $query = $this->entityManager->getConnection()->prepare(sprintf(
            'DELETE FROM `%s` WHERE id = :id',
            $payload->getTmpTableName(),
        ));
        $query->bindValue('id', $id, ParameterType::INTEGER);

        try {
            $query->execute();
            /** @phpstan-ignore-next-line ConnectionLost is throw but phpdoc is not good*/
        } catch (ConnectionLost) {
            $query->execute();
        }
    }
}
