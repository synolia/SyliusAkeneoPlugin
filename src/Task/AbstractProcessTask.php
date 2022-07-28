<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task;

use Akeneo\Pim\ApiClient\Pagination\Page;
use Akeneo\Pim\ApiClient\Pagination\PageInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use BluePsyduck\SymfonyProcessManager\ProcessManager;
use BluePsyduck\SymfonyProcessManager\ProcessManagerInterface;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Psr\Log\LoggerInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Process\Process;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfigurationInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\ApiNotConfiguredException;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Throwable;

abstract class AbstractProcessTask implements AkeneoTaskInterface
{
    protected EntityManagerInterface $entityManager;

    protected ProcessManagerInterface $processManager;

    protected BatchTaskInterface $task;

    protected LoggerInterface $logger;

    private RepositoryInterface $apiConfigurationRepository;

    private int $updateCount = 0;

    private int $createCount = 0;

    private string $type;

    private string $projectDir;

    public function __construct(
        EntityManagerInterface $entityManager,
        ProcessManagerInterface $processManager,
        BatchTaskInterface $task,
        LoggerInterface $akeneoLogger,
        RepositoryInterface $apiConfigurationRepository,
        string $projectDir
    ) {
        $this->entityManager = $entityManager;
        $this->processManager = $processManager;
        $this->task = $task;
        $this->logger = $akeneoLogger;
        $this->apiConfigurationRepository = $apiConfigurationRepository;
        $this->projectDir = $projectDir;
    }

    protected function count(string $tableName): int
    {
        $query = $this->entityManager->getConnection()->prepare(sprintf(
            'SELECT count(id) FROM `%s`',
            $tableName
        ));
        $query->executeStatement();

        return (int) current($query->fetch());
    }

    protected function min(string $tableName): int
    {
        $query = $this->entityManager->getConnection()->prepare(sprintf(
            'SELECT id FROM `%s` ORDER BY id ASC LIMIT 1',
            $tableName
        ));
        $query->executeStatement();

        return (int) current($query->fetch());
    }

    protected function prepareSelectBatchIdsQuery(
        string $tableName,
        int $from,
        int $limit
    ): Statement {
        $query = $this->entityManager->getConnection()->prepare(sprintf(
            'SELECT id
             FROM `%s`
             WHERE id > :from
             ORDER BY id ASC
             LIMIT :limit',
            $tableName
        ));
        $query->bindValue('from', $from, ParameterType::INTEGER);
        $query->bindValue('limit', $limit, ParameterType::INTEGER);

        return $query;
    }

    protected function batch(
        PipelinePayloadInterface $payload,
        array $ids
    ): void {
        if ($payload->allowParallel()) {
            if (!$this->processManager instanceof ProcessManager) {
                throw new LogicException('ProcessManager');
            }
            $this->processManager->setNumberOfParallelProcesses($payload->getMaxRunningProcessQueueSize());

            $processArguments = [
                'php',
                'bin/console',
                $payload->getCommandName(),
                implode(',', $ids),
            ];

            if ('' !== $payload->getVerbosityArgument()) {
                $processArguments[] = $payload->getVerbosityArgument();
            }

            $process = new Process($processArguments, $this->projectDir);
            $process->setTimeout(null);
            $process->setIdleTimeout(null);
            $isTtySupported = Process::isTtySupported();
            $process->setTty($isTtySupported);
            $this->processManager->addProcess($process);

            return;
        }

        $batchPayload = $this->createBatchPayload($payload);
        $batchPayload->setIds($ids);
        $this->task->__invoke($batchPayload);
    }

    abstract protected function createBatchPayload(PipelinePayloadInterface $payload): PipelinePayloadInterface;

    protected function process(PipelinePayloadInterface $initialPayload): void
    {
        $this->logger->debug(self::class);
        $this->type = $initialPayload->getType();
        $this->logger->notice(Messages::createOrUpdate($this->type));

        /** @var ApiConfigurationInterface|null $apiConfiguration */
        $apiConfiguration = $this->apiConfigurationRepository->findOneBy([], ['id' => 'DESC']);

        if (!$apiConfiguration instanceof ApiConfigurationInterface) {
            throw new ApiNotConfiguredException();
        }

        try {
            $totalItemsCount = $this->count($initialPayload->getTmpTableName());

            if (0 === $totalItemsCount) {
                return;
            }

            $min = $this->min($initialPayload->getTmpTableName());
            $query = $this->prepareSelectBatchIdsQuery($initialPayload->getTmpTableName(), $min - 1, $initialPayload->getBatchSize());
            $query->executeStatement();

            while ($results = $query->fetchAll()) {
                $ids = [];
                foreach ($results as $result) {
                    $ids[] = $result['id'];
                }

                $this->batch($initialPayload, $ids);

                $query = $this->prepareSelectBatchIdsQuery($initialPayload->getTmpTableName(), (int) $result['id'], $initialPayload->getBatchSize());
                $query->executeStatement();
            }
            $this->processManager->waitForAllProcesses();
        } catch (Throwable $throwable) {
            $this->logger->warning($throwable->getMessage());

            throw $throwable;
        }

        $this->logger->notice(Messages::countCreateAndUpdate($this->type, $this->createCount, $this->updateCount));
    }

    /**
     * @param \Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface|PageInterface $handleType
     */
    protected function handle(PipelinePayloadInterface $payload, $handleType): void
    {
        $count = 0;
        $ids = [];

        if ($handleType instanceof PageInterface) {
            $this->handleByPage($payload, $handleType, $count, $ids);
        } else {
            $this->handleByCursor($payload, $handleType, $count, $ids);
        }

        if ($count > 0 && $payload->isBatchingAllowed() && $payload->getProcessAsSoonAsPossible()) {
            $this->logger->notice('Batching', ['from_id' => $ids[0], 'to_id' => $ids[\count($ids) - 1]]);
            $this->batch($payload, $ids);

            return;
        }

        if ($count > 0 && !$payload->isBatchingAllowed()) {
            $payload->setIds($ids);
            $this->task->__invoke($payload);

            return;
        }

        if ($count > 0 && !$payload->getProcessAsSoonAsPossible()) {
            $this->process($payload);
        }
    }

    private function handleByPage(
        PipelinePayloadInterface $payload,
        PageInterface $page,
        int &$count = 0,
        array &$ids = []
    ): void {
        while (
            ($page instanceof Page && $page->hasNextPage()) ||
            ($page instanceof Page && !$page->hasPreviousPage()) ||
            $page instanceof Page
        ) {
            foreach ($page->getItems() as $item) {
                ++$count;
                $sql = sprintf(
                    'INSERT INTO `%s` (`values`) VALUES (:values);',
                    $payload->getTmpTableName(),
                );
                $stmt = $this->entityManager->getConnection()->prepare($sql);
                $stmt->bindValue('values', json_encode($item));
                $stmt->execute();

                $ids[] = $this->entityManager->getConnection()->lastInsertId();

                if ($payload->isBatchingAllowed() &&
                    $payload->getProcessAsSoonAsPossible() &&
                    0 === $count % $payload->getBatchSize()) {
                    $this->logger->notice('Batching', ['from_id' => $ids[0], 'to_id' => $ids[\count($ids) - 1]]);
                    $this->batch($payload, $ids);
                    $ids = [];
                }
            }

            $page = $page->getNextPage();
        }
    }

    private function handleByCursor(
        PipelinePayloadInterface $payload,
        ResourceCursorInterface $resourceCursor,
        int &$count = 0,
        array &$ids = []
    ): void {
        foreach ($resourceCursor as $item) {
            ++$count;
            $sql = sprintf(
                'INSERT INTO `%s` (`values`) VALUES (:values);',
                $payload->getTmpTableName(),
            );
            $stmt = $this->entityManager->getConnection()->prepare($sql);
            $stmt->bindValue('values', json_encode($item));
            $stmt->execute();

            $ids[] = $this->entityManager->getConnection()->lastInsertId();

            if ($payload->isBatchingAllowed() &&
                $payload->getProcessAsSoonAsPossible() &&
                0 === $count % $payload->getBatchSize()) {
                $this->logger->notice('Batching', ['from_id' => $ids[0], 'to_id' => $ids[\count($ids) - 1]]);
                $this->batch($payload, $ids);
                $ids = [];
            }
        }
    }
}
