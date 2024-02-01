<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\ProductModel;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Product\Factory\ProductFactoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Synolia\SyliusAkeneoPlugin\Checker\Product\IsProductProcessableCheckerInterface;
use Synolia\SyliusAkeneoPlugin\Event\Product\AfterProcessingProductEvent;
use Synolia\SyliusAkeneoPlugin\Event\Product\BeforeProcessingProductEvent;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;
use Synolia\SyliusAkeneoPlugin\Processor\Product\ProductProcessorChainInterface;
use Synolia\SyliusAkeneoPlugin\Processor\ProductGroup\ProductGroupProcessor;
use Synolia\SyliusAkeneoPlugin\Task\AbstractBatchTask;

final class BatchProductModelTask extends AbstractBatchTask
{
    private string $type;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        private ProductFactoryInterface $productFactory,
        private ProductRepositoryInterface $productRepository,
        private LoggerInterface $logger,
        private EventDispatcherInterface $dispatcher,
        private ProductProcessorChainInterface $productProcessorChain,
        private IsProductProcessableCheckerInterface $isProductProcessableChecker,
        private ProductGroupProcessor $productGroupProcessor,
        private ManagerRegistry $managerRegistry,
        private int $maxRetryCount,
        private int $retryWaitTime,
        private int $retryCount = 0,
    ) {
        parent::__construct($entityManager);
    }

    /**
     * @param ProductModelPayload $payload
     *
     * @throws Exception
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        if ($this->retryCount === $this->maxRetryCount) {
            return $payload;
        }

        $this->logger->debug(self::class);
        $this->type = $payload->getType();

        $query = $this->getSelectStatement($payload);
        $queryResult = $query->executeQuery();

        while ($results = $queryResult->fetchAllAssociative()) {
            foreach ($results as $result) {
                $isSuccess = false;

                /** @var array $resource */
                $resource = json_decode($result['values'], true);

                do {
                    try {
                        $this->logger->notice('Processing product', [
                            'code' => $resource['code'] ?? $resource['identifier'] ?? 'unknown',
                        ]);

                        $this->handleProductModel($resource);
                        $isSuccess = true;
                    } catch (ORMInvalidArgumentException $ormInvalidArgumentException) {
                        ++$this->retryCount;
                        usleep($this->retryWaitTime);

                        $this->logger->error('Retrying import', [
                            'product' => $result,
                            'retry_count' => $this->retryCount,
                            'error' => $ormInvalidArgumentException->getMessage(),
                        ]);

                        $this->entityManager = $this->getNewEntityManager();
                    } catch (\Throwable $throwable) {
                        ++$this->retryCount;
                        usleep($this->retryWaitTime);

                        $this->logger->error('Error importing product', [
                            'message' => $throwable->getMessage(),
                            'trace' => $throwable->getTraceAsString(),
                        ]);

                        $this->entityManager = $this->getNewEntityManager();
                    }
                } while (false === $isSuccess && $this->retryCount < $this->maxRetryCount);

                unset($resource);
                $this->removeEntry($payload, (int) $result['id']);
                $this->retryCount = 0;
            }
        }

        return $payload;
    }

    private function handleProductModel(array $resource): void
    {
        $this->handleProductGroup($resource);
        $this->dispatcher->dispatch(new BeforeProcessingProductEvent($resource));

        if (!$this->isProductProcessableChecker->check($resource)) {
            return;
        }

        $product = $this->process($resource);
        $this->dispatcher->dispatch(new AfterProcessingProductEvent($resource, $product));
        $this->entityManager->flush();
    }

    private function handleProductGroup(array $resource): void
    {
        try {
            $this->productGroupProcessor->process($resource);
            $this->entityManager->flush();
        } catch (ORMInvalidArgumentException $ormInvalidArgumentException) {
            if (!$this->entityManager->isOpen()) {
                $this->logger->warning('Recreating entity manager', ['exception' => $ormInvalidArgumentException]);
                $this->entityManager = $this->getNewEntityManager();
            }

            ++$this->retryCount;

            throw $ormInvalidArgumentException;
        }
    }

    private function process(array &$resource): ProductInterface
    {
        $product = $this->productRepository->findOneByCode($resource['code']);

        if (!$product instanceof ProductInterface) {
            /** @var ProductInterface $product */
            $product = $this->productFactory->createNew();
            $product->setCode($resource['code']);

            $this->entityManager->persist($product);
            $this->productProcessorChain->chain($product, $resource);

            $this->logger->info(Messages::hasBeenCreated($this->type, (string) $product->getCode()));

            return $product;
        }

        $this->productProcessorChain->chain($product, $resource);
        $this->logger->info(Messages::hasBeenUpdated($this->type, (string) $resource['code']));

        return $product;
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
