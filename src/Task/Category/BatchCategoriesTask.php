<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Category;

use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\Category\CategoryPayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Processor\Resource\Category\CategoryResourceProcessor;
use Synolia\SyliusAkeneoPlugin\Processor\Resource\Exception\MaxResourceProcessorRetryException;
use Synolia\SyliusAkeneoPlugin\Task\AbstractBatchTask;

final class BatchCategoriesTask extends AbstractBatchTask
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        private LoggerInterface $akeneoLogger,
        private CategoryResourceProcessor $resourceProcessor,
    ) {
        parent::__construct($entityManager);
    }

    /**
     * @param CategoryPayload $payload
     *
     * @throws \Throwable
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $this->akeneoLogger->debug(self::class);
        $type = $payload->getType();
        $this->akeneoLogger->notice(Messages::createOrUpdate($type));

        $query = $this->getSelectStatement($payload);
        /** @var Result $queryResult */
        $queryResult = $query->executeQuery();

        while ($results = $queryResult->fetchAllAssociative()) {
            /** @var array{id: int, values: string} $result */
            foreach ($results as $result) {
                /** @var array $resource */
                $resource = json_decode($result['values'], true);

                try {
                    $this->resourceProcessor->process($resource);
                    $this->removeEntry($payload, (int) $result['id']);
                } catch (MaxResourceProcessorRetryException) {
                    // Skip the failing line
                    continue;
                }
            }
        }

        return $payload;
    }
}
