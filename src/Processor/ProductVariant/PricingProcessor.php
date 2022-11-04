<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductVariant;

use LogicException;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfiguration;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoAttributeResourcesException;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoProductConfigurationException;
use Synolia\SyliusAkeneoPlugin\Repository\ChannelRepository;
use Throwable;

class PricingProcessor implements PricingProcessorInterface
{
    private const PRICE_CENTS = 100;

    private ?ProductConfiguration $productConfiguration = null;

    private RepositoryInterface $productConfigurationRepository;

    private RepositoryInterface $channelPricingRepository;

    private ChannelRepository $channelRepository;

    private FactoryInterface $channelPricingFactory;

    private LoggerInterface $logger;

    public function __construct(
        RepositoryInterface $productConfigurationRepository,
        RepositoryInterface $channelPricingRepository,
        ChannelRepository $channelRepository,
        FactoryInterface $channelPricingFactory,
        LoggerInterface $logger
    ) {
        $this->productConfigurationRepository = $productConfigurationRepository;
        $this->channelPricingRepository = $channelPricingRepository;
        $this->channelRepository = $channelRepository;
        $this->channelPricingFactory = $channelPricingFactory;
        $this->logger = $logger;
    }

    public function process(ProductVariantInterface $productVariant, array $resource): void
    {
        try {
            $pricingAttribute = $this->getPriceAttributeData($resource['values']);

            foreach ($pricingAttribute as $price) {
                /** @var ChannelInterface $channel */
                foreach ($this->channelRepository->findByCurrencyCode($price['currency']) as $channel) {
                    $this->addPriceToChannel((float) $price['amount'], $channel, $productVariant);
                }
            }
        } catch (Throwable $throwable) {
            $this->logger->warning($throwable->getMessage());

            return;
        }
    }

    public function support(ProductVariantInterface $productVariant, array $resource): bool
    {
        try {
            $this->getProductConfiguration();

            return is_array($this->getPriceAttributeData($resource['values']));
        } catch (NoAttributeResourcesException|NoProductConfigurationException $exception) {
            return false;
        }
    }

    /**
     * @throws NoProductConfigurationException
     */
    private function getProductConfiguration(): ProductConfiguration
    {
        if ($this->productConfiguration instanceof ProductConfiguration) {
            return $this->productConfiguration;
        }

        $productConfiguration = $this->productConfigurationRepository->findOneBy([], ['id' => 'DESC']);

        if (!$productConfiguration instanceof ProductConfiguration) {
            throw new NoProductConfigurationException('Product Configuration is not configured in BO.');
        }

        return $this->productConfiguration = $productConfiguration;
    }

    private function addPriceToChannel(
        float $amount,
        ChannelInterface $channel,
        ProductVariantInterface $productVariant
    ): void {
        /** @var ChannelPricingInterface $channelPricing */
        $channelPricing = $this->channelPricingRepository->findOneBy([
            'channelCode' => $channel->getCode(),
            'productVariant' => $productVariant,
        ]);

        if (!$channelPricing instanceof ChannelPricingInterface) {
            /** @var ChannelPricingInterface $channelPricing */
            $channelPricing = $this->channelPricingFactory->createNew();
        }

        $channelPricing->setOriginalPrice(((int) round($amount * self::PRICE_CENTS)));
        $channelPricing->setPrice(((int) round($amount * self::PRICE_CENTS)));
        $channelPricing->setProductVariant($productVariant);
        $channelPricing->setChannelCode($channel->getCode());

        $productVariant->addChannelPricing($channelPricing);
    }

    /**
     * @throws NoAttributeResourcesException
     * @throws NoProductConfigurationException
     */
    private function getPriceAttributeData(array $attributes): array
    {
        $productConfiguration = $this->getProductConfiguration();

        if (null === $productConfiguration->getAkeneoPriceAttribute()) {
            throw new NoProductConfigurationException('Product Configuration is not configured in BO.');
        }

        foreach ($attributes as $attributeCode => $attributeValue) {
            if ($attributeCode !== $productConfiguration->getAkeneoPriceAttribute()) {
                continue;
            }

            if (0 === \count($attributeValue)) {
                throw new LogicException('Price attribute is empty.');
            }

            return current($attributeValue)['data'];
        }

        throw new NoAttributeResourcesException('Price attribute not found.');
    }
}
