<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\ProductModel;

use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
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
    ) {
        parent::__construct($entityManager);
    }

    /**
     * @param ProductModelPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $this->logger->debug(self::class);
        $this->type = $payload->getType();
        $this->logger->notice(Messages::createOrUpdate($this->type));

        $query = $this->getSelectStatement($payload);
        /** @var Result $queryResult */
        $queryResult = $query->executeQuery();

        while ($results = $queryResult->fetchAll()) {
            foreach ($results as $result) {
                $resource = json_decode($result['values'], true, 512, \JSON_THROW_ON_ERROR);

                try {
                    $this->dispatcher->dispatch(new BeforeProcessingProductEvent($resource));

                    $this->entityManager->beginTransaction();

                    if ($this->isProductProcessableChecker->check($resource)) {
                        $product = $this->process($resource);
                        $this->dispatcher->dispatch(new AfterProcessingProductEvent($resource, $product));
                    }

                    $this->entityManager->flush();
                    $this->entityManager->commit();
                    $this->entityManager->clear();

                    unset($resource, $product);
                    $this->removeEntry($payload, (int) $result['id']);
                } catch (\Throwable $throwable) {
                    $this->entityManager->rollback();
                    $this->logger->warning($throwable->getMessage());
                    $this->removeEntry($payload, (int) $result['id']);
                }
            }
        }

        return $payload;
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
}
