<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Product;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Product\Factory\ProductVariantFactoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfiguration;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoAttributeResourcesException;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoProductConfigurationException;
use Synolia\SyliusAkeneoPlugin\Repository\ChannelRepository;

class AbstractCreateProductEntities
{
    private const PRICE_CENTS = 100;

    /** @var \Doctrine\ORM\EntityManagerInterface */
    protected $entityManager;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    protected $productVariantRepository;

    /** @var \Sylius\Component\Product\Factory\ProductVariantFactoryInterface */
    protected $productVariantFactory;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    protected $productRepository;

    /** @var \Synolia\SyliusAkeneoPlugin\Repository\ChannelRepository */
    protected $channelRepository;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    protected $channelPricingRepository;

    /** @var \Sylius\Component\Resource\Factory\FactoryInterface */
    protected $channelPricingFactory;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    protected $localeRepository;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $productConfigurationRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        RepositoryInterface $productVariantRepository,
        RepositoryInterface $productRepository,
        ChannelRepository $channelRepository,
        RepositoryInterface $channelPricingRepository,
        RepositoryInterface $localeRepository,
        RepositoryInterface $productConfigurationRepository,
        ProductVariantFactoryInterface $productVariantFactory,
        FactoryInterface $channelPricingFactory
    ) {
        $this->entityManager = $entityManager;
        $this->productVariantRepository = $productVariantRepository;
        $this->productVariantFactory = $productVariantFactory;
        $this->productRepository = $productRepository;
        $this->channelRepository = $channelRepository;
        $this->channelPricingRepository = $channelPricingRepository;
        $this->productConfigurationRepository = $productConfigurationRepository;
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

    protected function setProductPrices(
        ProductVariantInterface $productVariant,
        array $attributes = []
    ): void {
        try {
            $pricingAttribute = $this->getPriceAttributeData($attributes);

            foreach ($pricingAttribute as $price) {
                /** @var \Sylius\Component\Core\Model\ChannelInterface $channel */
                foreach ($this->channelRepository->findByCurrencyCode($price['currency']) as $channel) {
                    $this->addPriceToChannel((float) $price['amount'], $channel, $productVariant);
                }
            }
        } catch (\Throwable $throwable) {
            return;
        }
    }

    private function addPriceToChannel(
        float $amount,
        ChannelInterface $channel,
        ProductVariantInterface $productVariant
    ): void {
        /** @var \Sylius\Component\Core\Model\ChannelPricingInterface $channelPricing */
        $channelPricing = $this->channelPricingRepository->findOneBy([
            'channelCode' => $channel->getCode(),
            'productVariant' => $productVariant,
        ]);

        if (!$channelPricing instanceof ChannelPricingInterface) {
            /** @var \Sylius\Component\Core\Model\ChannelPricingInterface $channelPricing */
            $channelPricing = $this->channelPricingFactory->createNew();
        }

        $channelPricing->setOriginalPrice((int) ($amount * self::PRICE_CENTS));
        $channelPricing->setPrice((int) ($amount * self::PRICE_CENTS));
        $channelPricing->setProductVariant($productVariant);
        $channelPricing->setChannelCode($channel->getCode());

        $productVariant->addChannelPricing($channelPricing);
    }

    private function getPriceAttributeData(array $attributes): array
    {
        /** @var \Synolia\SyliusAkeneoPlugin\Entity\ProductConfiguration|null $productConfiguration */
        $productConfiguration = $this->productConfigurationRepository->findOneBy([]);

        if (!$productConfiguration instanceof ProductConfiguration) {
            throw new NoProductConfigurationException('Product Configuration is not configured in BO.');
        }

        if (null === $productConfiguration->getAkeneoPriceAttribute()) {
            throw new NoProductConfigurationException('Product Configuration is not configured in BO.');
        }

        foreach ($attributes as $attributeCode => $attributeValue) {
            if ($attributeCode !== $productConfiguration->getAkeneoPriceAttribute()) {
                continue;
            }

            if (\count($attributeValue) === 0) {
                throw new \LogicException('Price attribute is empty.');
            }

            return \current($attributeValue)['data'];
        }

        throw new NoAttributeResourcesException('Price attribute not found.');
    }
}
