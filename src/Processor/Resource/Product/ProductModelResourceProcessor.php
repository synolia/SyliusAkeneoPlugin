<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\Resource\Product;

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
use Synolia\SyliusAkeneoPlugin\Processor\Product\ProductProcessorChainInterface;
use Synolia\SyliusAkeneoPlugin\Processor\ProductGroup\ProductGroupProcessor;
use Synolia\SyliusAkeneoPlugin\Processor\Resource\AkeneoResourceProcessorInterface;
use Synolia\SyliusAkeneoPlugin\Processor\Resource\Exception\MaxResourceProcessorRetryException;

class ProductModelResourceProcessor implements AkeneoResourceProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProductFactoryInterface $productFactory,
        private ProductRepositoryInterface $productRepository,
        private LoggerInterface $akeneoLogger,
        private EventDispatcherInterface $dispatcher,
        private ProductProcessorChainInterface $productProcessorChain,
        private IsProductProcessableCheckerInterface $isProductProcessableChecker,
        private ProductGroupProcessor $productGroupProcessor,
        private ManagerRegistry $managerRegistry,
        private int $maxRetryCount,
        private int $retryWaitTime,
        private int $retryCount = 0,
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
            $this->akeneoLogger->notice('Processing product', [
                'code' => $resource['code'] ?? $resource['identifier'] ?? 'unknown',
            ]);

            $this->handleProductGroup($resource);
            $this->dispatcher->dispatch(new BeforeProcessingProductEvent($resource));

            if (!$this->isProductProcessableChecker->check($resource)) {
                return;
            }

            $product = $this->getOrCreateEntity($resource);
            $this->productProcessorChain->chain($product, $resource);

            // TODO: check if id is null
            $this->akeneoLogger->info(Messages::hasBeenCreated($product::class, (string) $product->getCode()));
            $this->akeneoLogger->info(Messages::hasBeenUpdated($product::class, (string) $resource['code']));

            $this->dispatcher->dispatch(new AfterProcessingProductEvent($resource, $product));
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

    private function handleProductGroup(array $resource): void
    {
        try {
            $this->productGroupProcessor->process($resource);
            $this->entityManager->flush();
        } catch (ORMInvalidArgumentException $ormInvalidArgumentException) {
            if (!$this->entityManager->isOpen()) {
                $this->akeneoLogger->warning('Recreating entity manager', ['exception' => $ormInvalidArgumentException]);
                $this->entityManager = $this->getNewEntityManager();
            }

            ++$this->retryCount;

            throw $ormInvalidArgumentException;
        }
    }

    private function getOrCreateEntity(array &$resource): ProductInterface
    {
        $product = $this->productRepository->findOneByCode($resource['code']);

        if (!$product instanceof ProductInterface) {
            /** @var ProductInterface $product */
            $product = $this->productFactory->createNew();
            $product->setCode($resource['code']);

            $this->entityManager->persist($product);

            return $product;
        }

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
