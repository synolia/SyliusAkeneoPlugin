<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Product;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Product\Factory\ProductVariantFactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ProductGroup;
use Synolia\SyliusAkeneoPlugin\Event\ProductVariant\AfterProcessingProductVariantEvent;
use Synolia\SyliusAkeneoPlugin\Event\ProductVariant\BeforeProcessingProductVariantEvent;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload;
use Synolia\SyliusAkeneoPlugin\Processor\Product\ProductChannelEnablerProcessorInterface;
use Synolia\SyliusAkeneoPlugin\Processor\ProductVariant\ProductVariantProcessorChainInterface;
use Synolia\SyliusAkeneoPlugin\Repository\ChannelRepository;
use Synolia\SyliusAkeneoPlugin\Repository\LocaleRepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Repository\ProductGroupRepository;
use Throwable;

final class ConfigurableProductsTask extends AbstractCreateProductEntities
{
    private ProductGroupRepository $productGroupRepository;

    private EventDispatcherInterface $dispatcher;

    private ProductVariantProcessorChainInterface $productVariantProcessorChain;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        RepositoryInterface $productVariantRepository,
        RepositoryInterface $productRepository,
        ChannelRepository $channelRepository,
        LocaleRepositoryInterface $localeRepository,
        RepositoryInterface $productConfigurationRepository,
        ProductGroupRepository $productGroupRepository,
        ProductVariantFactoryInterface $productVariantFactory,
        LoggerInterface $akeneoLogger,
        EventDispatcherInterface $dispatcher,
        ProductChannelEnablerProcessorInterface $productChannelEnabler,
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

        $this->productGroupRepository = $productGroupRepository;
        $this->dispatcher = $dispatcher;
        $this->productVariantProcessorChain = $productVariantProcessorChain;
    }

    /**
     * @param ProductPayload $payload
     * @inheritDoc
     */
    public function __invoke(PipelinePayloadInterface $payload, array $resource): void
    {
        try {
            /** @var ProductInterface $productModel */
            $productModel = $this->productRepository->findOneBy(['code' => $resource['parent']]);

            //Skip product variant import if it does not have a parent model on Sylius
            if (!$productModel instanceof ProductInterface || !\is_string($productModel->getCode())) {
                $this->logger->warning(sprintf(
                    'Skipped product "%s" because model "%s" does not exist.',
                    $resource['identifier'],
                    $resource['parent'],
                ));

                return;
            }

            $this->dispatcher->dispatch(new BeforeProcessingProductVariantEvent($resource, $productModel));

            $productGroup = $this->productGroupRepository->findOneBy(['productParent' => $productModel->getCode()]);

            if (!$productGroup instanceof ProductGroup) {
                $this->logger->warning(sprintf(
                    'Skipped product "%s" because model "%s" does not exist as group.',
                    $resource['identifier'],
                    $resource['parent'],
                ));

                return;
            }

            $variationAxes = $productGroup->getVariationAxes();

            if (0 === \count($variationAxes)) {
                $this->logger->warning(sprintf(
                    'Skipped product "%s" because group has no variation axis.',
                    $resource['identifier'],
                ));

                return;
            }

            $productVariant = $this->getOrCreateEntity($resource['identifier'], $productModel);
            $this->productVariantProcessorChain->chain($productVariant, $resource);

            $this->dispatcher->dispatch(new AfterProcessingProductVariantEvent($resource, $productVariant));
            $this->entityManager->flush();
        } catch (Throwable $throwable) {
            $this->logger->warning($throwable->getMessage());
        }
    }

    private function getOrCreateEntity(string $variantCode, ProductInterface $productModel): ProductVariantInterface
    {
        $productVariant = $this->productVariantRepository->findOneBy(['code' => $variantCode]);

        if (!$productVariant instanceof ProductVariantInterface) {
            /** @var ProductVariantInterface $productVariant */
            $productVariant = $this->productVariantFactory->createForProduct($productModel);
            $productVariant->setCode($variantCode);

            $this->entityManager->persist($productVariant);

            return $productVariant;
        }

        return $productVariant;
    }
}
