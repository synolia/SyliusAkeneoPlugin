<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\Resource\ProductVariant;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Product\Factory\ProductFactoryInterface;
use Sylius\Component\Product\Factory\ProductVariantFactoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Synolia\SyliusAkeneoPlugin\Event\Product\AfterProcessingProductEvent;
use Synolia\SyliusAkeneoPlugin\Event\Product\BeforeProcessingProductEvent;
use Synolia\SyliusAkeneoPlugin\Event\ProductVariant\AfterProcessingProductVariantEvent;
use Synolia\SyliusAkeneoPlugin\Event\ProductVariant\BeforeProcessingProductVariantEvent;
use Synolia\SyliusAkeneoPlugin\Processor\Product\ProductProcessorChainInterface;
use Synolia\SyliusAkeneoPlugin\Processor\ProductVariant\ProductVariantProcessorChainInterface;
use Synolia\SyliusAkeneoPlugin\Processor\Resource\AkeneoResourceProcessorInterface;
use Throwable;

class SimpleProductVariantResourceProcessor implements AkeneoResourceProcessorInterface, ProductVariantAkeneoResourceProcessorInterface
{
    public function __construct(
        private RepositoryInterface $productRepository,
        private RepositoryInterface $productVariantRepository,
        private FactoryInterface $productFactory,
        private ProductVariantFactoryInterface $productVariantFactory,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $akeneoLogger,
        private EventDispatcherInterface $dispatcher,
        private ProductProcessorChainInterface $productProcessorChain,
        private ProductVariantProcessorChainInterface $productVariantProcessorChain,
        protected RepositoryInterface $channelPricingRepository,
        protected FactoryInterface $channelPricingFactory,
    ) {
    }

    private function getOrCreateSimpleVariant(ProductInterface $product): ProductVariantInterface
    {
        /** @var ProductVariantInterface $productVariant */
        $productVariant = $this->productVariantRepository->findOneBy(['code' => $product->getCode()]);

        if (!$productVariant instanceof ProductVariantInterface) {
            $productVariant = $this->productVariantFactory->createForProduct($product);
            $productVariant->setCode($product->getCode());

            $this->entityManager->persist($productVariant);
        }

        return $productVariant;
    }

    private function getOrCreateEntity(array $resource): ProductInterface
    {
        /** @var ProductInterface $product */
        $product = $this->productRepository->findOneBy(['code' => $resource['identifier']]);

        if (!$product instanceof ProductInterface) {
            if (!$this->productFactory instanceof ProductFactoryInterface) {
                throw new LogicException('Wrong Factory');
            }

            if (null === $resource['parent']) {
                /** @var ProductInterface $product */
                $product = $this->productFactory->createNew();
            }

            $product->setCode($resource['identifier']);
            $this->entityManager->persist($product);

            return $product;
        }

        return $product;
    }

    public function support(array $resource): bool
    {
        return null === $resource['parent'];
    }

    public function process(array $resource): void
    {
        try {
            $this->dispatcher->dispatch(new BeforeProcessingProductEvent($resource));

            $product = $this->getOrCreateEntity($resource);
            $this->productProcessorChain->chain($product, $resource);

            $this->dispatcher->dispatch(new AfterProcessingProductEvent($resource, $product));

            $this->dispatcher->dispatch(new BeforeProcessingProductVariantEvent($resource, $product));

            $productVariant = $this->getOrCreateSimpleVariant($product);
            $this->productVariantProcessorChain->chain($productVariant, $resource);

            $this->dispatcher->dispatch(new AfterProcessingProductVariantEvent($resource, $productVariant));
        } catch (Throwable $throwable) {
            $this->akeneoLogger->warning($throwable->getMessage(), ['exception' => $throwable]);
        }
    }
}
