<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\Product;

use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfiguration;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoAttributeResourcesException;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoProductConfigurationException;
use Synolia\SyliusAkeneoPlugin\Repository\ChannelRepository;
use Synolia\SyliusAkeneoPlugin\Repository\ProductConfigurationRepository;

final class ProductChannelEnablerProcessor implements ProductChannelEnablerProcessorInterface
{
    private ChannelRepository $channelRepository;

    private LoggerInterface $logger;

    private ProductConfigurationRepository $productConfigurationRepository;

    public function __construct(
        ChannelRepository $channelRepository,
        ProductConfigurationRepository $productConfigurationRepository,
        LoggerInterface $akeneoLogger
    ) {
        $this->channelRepository = $channelRepository;
        $this->productConfigurationRepository = $productConfigurationRepository;
        $this->logger = $akeneoLogger;
    }

    public static function getDefaultPriority(): int
    {
        return 600;
    }

    public function process(ProductInterface $product, array $resource): void
    {
        try {
            $enabledChannels = $this->getEnabledChannelsAttributeData($product, $resource);

            //Disable the product for all channels
            $product->getChannels()->clear();

            foreach ($enabledChannels as $enabledChannel) {
                $channel = $this->channelRepository->findOneBy(['code' => $enabledChannel]);
                if (!$channel instanceof ChannelInterface) {
                    $this->logger->info(
                        sprintf(
                            'Channel "%s" could not be activated for product "%s" because the channel was not found in the database.',
                            $enabledChannel,
                            $product->getCode()
                        )
                    );

                    continue;
                }

                $product->addChannel($channel);
                $this->logger->info('Enabled channel for product', [
                    'channel_code' => $channel->getCode(),
                    'product_code' => $product->getCode(),
                ]);
            }
        } catch (NoAttributeResourcesException|NoProductConfigurationException $exception) {
            $this->logger->info($exception->getMessage());
        }
    }

    private function getEnabledChannelsAttributeData(ProductInterface $product, array $resource): array
    {
        $productConfiguration = $this->productConfigurationRepository->findOneBy([]);

        if (!$productConfiguration instanceof ProductConfiguration) {
            throw new NoProductConfigurationException('Product Configuration is not configured in BO.');
        }

        if (null === $productConfiguration->getAkeneoEnabledChannelsAttribute()) {
            throw new NoProductConfigurationException('Product configuration -> Enabled channels is not configured in BO.');
        }

        foreach ($resource['values'] as $attributeCode => $attributeValue) {
            if ($attributeCode !== $productConfiguration->getAkeneoEnabledChannelsAttribute()) {
                continue;
            }

            if (0 === \count($attributeValue)) {
                throw new \LogicException('Enabled channels attribute is empty.');
            }

            return current($attributeValue)['data'];
        }

        throw new NoAttributeResourcesException(sprintf('Enabled channels attribute not found for product "%s".', $product->getCode()));
    }

    public function support(ProductInterface $product, array $resource): bool
    {
        try {
            $this->getEnabledChannelsAttributeData($product, $resource);

            return true;
        } catch (NoAttributeResourcesException|NoProductConfigurationException $exception) {
            $this->logger->info($exception->getMessage());

            return false;
        }
    }
}
