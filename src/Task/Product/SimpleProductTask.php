<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Product;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Product\Factory\ProductFactory;
use Sylius\Component\Product\Factory\ProductVariantFactoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Synolia\SyliusAkeneoPlugin\Event\Product\AfterProcessingProductEvent;
use Synolia\SyliusAkeneoPlugin\Event\Product\BeforeProcessingProductEvent;
use Synolia\SyliusAkeneoPlugin\Event\ProductVariant\AfterProcessingProductVariantEvent;
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
    private FactoryInterface $productFactory;

    private EventDispatcherInterface $dispatcher;

    private ProductProcessorChainInterface $productProcessorChain;

    private ProductVariantProcessorChainInterface $productVariantProcessorChain;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        RepositoryInterface $productRepository,
        ChannelRepository $channelRepository,
        RepositoryInterface $productVariantRepository,
        LocaleRepositoryInterface $localeRepository,
        RepositoryInterface $productConfigurationRepository,
        FactoryInterface $productFactory,
        ProductVariantFactoryInterface $productVariantFactory,
        EntityManagerInterface $entityManager,
        LoggerInterface $akeneoLogger,
        EventDispatcherInterface $dispatcher,
        ProductChannelEnablerProcessorInterface $productChannelEnabler,
        ProductProcessorChainInterface $productProcessorChain,
        ProductVariantProcessorChainInterface $productVariantProcessorChain
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
            $productChannelEnabler
        );

        $this->productFactory = $productFactory;
        $this->dispatcher = $dispatcher;
        $this->productProcessorChain = $productProcessorChain;
        $this->productVariantProcessorChain = $productVariantProcessorChain;
    }

    /**
     * @param ProductPayload $payload
     * @inheritDoc
     */
    public function __invoke(PipelinePayloadInterface $payload, array $resource): void
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

            $this->entityManager->flush();
        } catch (Throwable $throwable) {
            $this->logger->warning($throwable->getMessage());
        }
    }

    private function getOrCreateEntity(array $resource): ProductInterface
    {
        /** @var ProductInterface $product */
        $product = $this->productRepository->findOneBy(['code' => $resource['identifier']]);

        if (!$product instanceof ProductInterface) {
            if (!$this->productFactory instanceof ProductFactory) {
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
