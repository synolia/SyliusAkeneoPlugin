<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Product;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Product\Factory\ProductVariantFactoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

class AbstractCreateProductEntities
{
    /** @var \Doctrine\ORM\EntityManagerInterface */
    protected $entityManager;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    protected $productVariantRepository;

    /** @var \Sylius\Component\Product\Factory\ProductVariantFactoryInterface */
    protected $productVariantFactory;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    protected $productRepository;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    protected $channelRepository;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    protected $channelPricingRepository;

    /** @var \Sylius\Component\Resource\Factory\FactoryInterface */
    protected $channelPricingFactory;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    protected $localeRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        RepositoryInterface $productVariantRepository,
        RepositoryInterface $productRepository,
        RepositoryInterface $channelRepository,
        RepositoryInterface $channelPricingRepository,
        RepositoryInterface $localeRepository,
        ProductVariantFactoryInterface $productVariantFactory,
        FactoryInterface $channelPricingFactory
    ) {
        $this->entityManager = $entityManager;
        $this->productVariantRepository = $productVariantRepository;
        $this->productVariantFactory = $productVariantFactory;
        $this->productRepository = $productRepository;
        $this->channelRepository = $channelRepository;
        $this->channelPricingRepository = $channelPricingRepository;
        $this->channelPricingFactory = $channelPricingFactory;
        $this->localeRepository = $localeRepository;
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

    protected function setProductPrices(ProductVariantInterface $productVariant): void
    {
        /** @var \Sylius\Component\Core\Model\ChannelInterface $channel */
        foreach ($this->channelRepository->findAll() as $channel) {
            /** @var \Sylius\Component\Core\Model\ChannelPricingInterface $channelPricing */
            $channelPricing = $this->channelPricingRepository->findOneBy([
                'channelCode' => $channel->getCode(),
                'productVariant' => $productVariant,
            ]);

            if (!$channelPricing instanceof ChannelPricingInterface) {
                /** @var \Sylius\Component\Core\Model\ChannelPricingInterface $channelPricing */
                $channelPricing = $this->channelPricingFactory->createNew();
            }

            $channelPricing->setOriginalPrice(0);
            $channelPricing->setPrice(0);
            $channelPricing->setProductVariant($productVariant);
            $channelPricing->setChannelCode($channel->getCode());

            $productVariant->addChannelPricing($channelPricing);
        }
    }
}
