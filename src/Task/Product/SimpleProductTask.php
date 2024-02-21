<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Product;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Product\Factory\ProductFactoryInterface;
use Sylius\Component\Product\Factory\ProductVariantFactoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Synolia\SyliusAkeneoPlugin\Event\Product\AfterProcessingProductEvent;
use Synolia\SyliusAkeneoPlugin\Event\Product\AfterProductRetrievedEvent;
use Synolia\SyliusAkeneoPlugin\Event\Product\BeforeProcessingProductEvent;
use Synolia\SyliusAkeneoPlugin\Event\ProductVariant\AfterProcessingProductVariantEvent;
use Synolia\SyliusAkeneoPlugin\Event\ProductVariant\AfterProductVariantRetrievedEvent;
use Synolia\SyliusAkeneoPlugin\Event\ProductVariant\BeforeProcessingProductVariantEvent;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload;
use Synolia\SyliusAkeneoPlugin\Processor\Product\ProductChannelEnablerProcessorInterface;
use Synolia\SyliusAkeneoPlugin\Processor\Product\ProductProcessorChainInterface;
use Synolia\SyliusAkeneoPlugin\Processor\ProductVariant\ProductVariantProcessorChainInterface;
use Synolia\SyliusAkeneoPlugin\Repository\ChannelRepository;
use Synolia\SyliusAkeneoPlugin\Repository\LocaleRepositoryInterface;
use Throwable;

final class SimpleProductTask extends AbstractCreateProductEntities
{
    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        RepositoryInterface $productRepository,
        ChannelRepository $channelRepository,
        RepositoryInterface $productVariantRepository,
        LocaleRepositoryInterface $localeRepository,
        RepositoryInterface $productConfigurationRepository,
        private FactoryInterface $productFactory,
        ProductVariantFactoryInterface $productVariantFactory,
        EntityManagerInterface $entityManager,
        LoggerInterface $akeneoLogger,
        private EventDispatcherInterface $dispatcher,
        ProductChannelEnablerProcessorInterface $productChannelEnabler,
        private ProductProcessorChainInterface $productProcessorChain,
        private ProductVariantProcessorChainInterface $productVariantProcessorChain,
    ) {
        parent::__construct(
            $entityManager,
            $productVariantRepository,
            $productRepository,
            $channelRepository,
            $localeRepository,
            $productConfigurationRepository,
            $productVariantFactory,
            $akeneoLogger,
            $productChannelEnabler,
        );
    }

    /**
     * @param ProductPayload $payload
     *
     * @inheritDoc
     */
    public function __invoke(PipelinePayloadInterface $payload, array $resource): void
    {
        try {
            $this->dispatcher->dispatch(new BeforeProcessingProductEvent($resource));
            $product = $this->getOrCreateEntity($resource);
            $this->dispatcher->dispatch(new AfterProductRetrievedEvent($resource, $product));
            $event = new AfterProcessingProductEvent($resource, clone $product, $product);
            $this->productProcessorChain->chain($product, $resource);
            $this->dispatcher->dispatch($event);

            $this->dispatcher->dispatch(new BeforeProcessingProductVariantEvent($resource, $product));
            $productVariant = $this->getOrCreateSimpleVariant($product);
            $this->dispatcher->dispatch(new AfterProductVariantRetrievedEvent($resource, $productVariant));
            $event = new AfterProcessingProductVariantEvent($resource, clone $productVariant, $productVariant);
            $this->productVariantProcessorChain->chain($productVariant, $resource);
            $this->dispatcher->dispatch($event);

            $this->entityManager->flush();
        } catch (Throwable $throwable) {
            $this->logger->warning($throwable->getMessage(), ['exception' => $throwable]);
        }
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
}
