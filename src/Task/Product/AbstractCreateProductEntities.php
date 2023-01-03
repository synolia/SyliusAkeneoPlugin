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
    protected RepositoryInterface $channelPricingRepository;

    protected FactoryInterface $channelPricingFactory;

    public function __construct(protected EntityManagerInterface $entityManager, protected RepositoryInterface $productVariantRepository, protected RepositoryInterface $productRepository, protected ChannelRepository $channelRepository, protected LocaleRepositoryInterface $localeRepository, protected RepositoryInterface $productConfigurationRepository, protected ProductVariantFactoryInterface $productVariantFactory, protected LoggerInterface $logger, protected ProductChannelEnablerProcessorInterface $productChannelEnabler)
    {
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
