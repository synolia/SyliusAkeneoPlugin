<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\Resource\ProductVariant;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Synolia\SyliusAkeneoPlugin\Processor\Resource\AkeneoResourceProcessorInterface;
use Synolia\SyliusAkeneoPlugin\Processor\Resource\Exception\MaxResourceProcessorRetryException;

class ProductVariantResourceProcessor implements AkeneoResourceProcessorInterface
{
    /**
     * @param ProductVariantAkeneoResourceProcessorInterface[] $productVariantAkeneoResourceProcessors
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $akeneoLogger,
        private ManagerRegistry $managerRegistry,
        #[Autowire('%env(int:SYNOLIA_AKENEO_MAX_RETRY_COUNT)%')]
        private int $maxRetryCount,
        #[Autowire('%env(int:SYNOLIA_AKENEO_RETRY_WAIT_TIME)%')]
        private int $retryWaitTime,
        private int $retryCount = 0,
        #[TaggedIterator(ProductVariantAkeneoResourceProcessorInterface::class)]
        private iterable $productVariantAkeneoResourceProcessors = [],
    ) {
    }

    /**
     * @throws MaxResourceProcessorRetryException
     */
    public function process(array $resource): void
    {
        if ($this->retryCount === $this->maxRetryCount) {
            $this->retryCount = 0;

            throw new MaxResourceProcessorRetryException();
        }

        try {
            $this->akeneoLogger->notice('Processing product variant', [
                'code' => $resource['code'] ?? $resource['identifier'] ?? 'unknown',
            ]);

            foreach ($this->productVariantAkeneoResourceProcessors as $productVariantAkeneoResourceProcessor) {
                if ($productVariantAkeneoResourceProcessor->support($resource)) {
                    $productVariantAkeneoResourceProcessor->process($resource);
                }
            }

            $this->entityManager->flush();
        } catch (ORMInvalidArgumentException $ormInvalidArgumentException) {
            ++$this->retryCount;
            usleep($this->retryWaitTime);

            $this->akeneoLogger->error('Retrying import', [
                'product' => $resource,
                'retry_count' => $this->retryCount,
                'error' => $ormInvalidArgumentException->getMessage(),
            ]);

            $this->entityManager = $this->getNewEntityManager();
            $this->process($resource);
        } catch (\Throwable $throwable) {
            ++$this->retryCount;
            usleep($this->retryWaitTime);

            $this->akeneoLogger->error('Retrying import', [
                'message' => $throwable->getMessage(),
                'trace' => $throwable->getTraceAsString(),
            ]);

            $this->entityManager = $this->getNewEntityManager();
            $this->process($resource);
        }
    }

    private function getNewEntityManager(): EntityManagerInterface
    {
        $objectManager = $this->managerRegistry->resetManager();

        if (!$objectManager instanceof EntityManagerInterface) {
            throw new \LogicException('Wrong ObjectManager');
        }

        return $objectManager;
    }
}
