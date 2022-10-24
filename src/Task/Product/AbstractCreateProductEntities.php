<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Product;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Product\Factory\ProductVariantFactoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Processor\Product\ProductChannelEnablerProcessorInterface;
use Synolia\SyliusAkeneoPlugin\Repository\ChannelRepository;
use Synolia\SyliusAkeneoPlugin\Repository\LocaleRepositoryInterface;

abstract class AbstractCreateProductEntities
{
    protected EntityManagerInterface $entityManager;

    protected RepositoryInterface $productVariantRepository;

    protected ProductVariantFactoryInterface $productVariantFactory;

    protected RepositoryInterface $productRepository;

    protected ChannelRepository $channelRepository;

    protected RepositoryInterface $channelPricingRepository;

    protected FactoryInterface $channelPricingFactory;

    protected LocaleRepositoryInterface $localeRepository;

    protected LoggerInterface $logger;

    protected RepositoryInterface $productConfigurationRepository;

    protected ProductChannelEnablerProcessorInterface $productChannelEnabler;

    public function __construct(
        EntityManagerInterface $entityManager,
        RepositoryInterface $productVariantRepository,
        RepositoryInterface $productRepository,
        ChannelRepository $channelRepository,
        LocaleRepositoryInterface $localeRepository,
        RepositoryInterface $productConfigurationRepository,
        ProductVariantFactoryInterface $productVariantFactory,
        LoggerInterface $akeneoLogger,
        ProductChannelEnablerProcessorInterface $productChannelEnabler
    ) {
        $this->entityManager = $entityManager;
        $this->productVariantRepository = $productVariantRepository;
        $this->productVariantFactory = $productVariantFactory;
        $this->productRepository = $productRepository;
        $this->channelRepository = $channelRepository;
        $this->productConfigurationRepository = $productConfigurationRepository;
        $this->localeRepository = $localeRepository;
        $this->logger = $akeneoLogger;
        $this->productChannelEnabler = $productChannelEnabler;
    }

    protected function getOrCreateSimpleVariant(ProductInterface $product): ProductVariantInterface
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
}
