<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfiguration;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoAttributeResourcesException;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoProductConfigurationException;
use Synolia\SyliusAkeneoPlugin\Repository\ChannelRepository;
use Synolia\SyliusAkeneoPlugin\Repository\ProductConfigurationRepository;

final class ProductChannelEnabler implements ProductChannelEnablerInterface
{
    /** @var \Synolia\SyliusAkeneoPlugin\Repository\ChannelRepository */
    private $channelRepository;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /** @var \Synolia\SyliusAkeneoPlugin\Repository\ProductConfigurationRepository */
    private $productConfigurationRepository;

    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $entityManager;

    public function __construct(
        ChannelRepository $channelRepository,
        ProductConfigurationRepository $productConfigurationRepository,
        LoggerInterface $akeneoLogger,
        EntityManagerInterface $entityManager
    ) {
        $this->channelRepository = $channelRepository;
        $this->productConfigurationRepository = $productConfigurationRepository;
        $this->logger = $akeneoLogger;
        $this->entityManager = $entityManager;
    }

    public function enableChannelForProduct(ProductInterface $product, array $resource): void
    {
        try {
            $productConfiguration = $this->getProductConfiguration();

            if (!$productConfiguration->getEnableImportedProducts()) {
                return;
            }

            $this->entityManager->beginTransaction();

            //Disable the product for all channels
            $product->getChannels()->clear();

            $this->handleByAkeneoEnabledChannelsAttribute($productConfiguration, $product, $resource);
            $this->handleBySyliusConfiguration($productConfiguration, $product);

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Throwable $throwable) {
            if ($this->entityManager->getConnection()->isTransactionActive()) {
                $this->entityManager->rollback();
            }

            throw $throwable;
        }
    }

    private function addProductToChannel(ProductInterface $product, ChannelInterface $channel): void
    {
        $product->addChannel($channel);
        $this->logger->info(\sprintf(
            'Enabled channel "%s" for product "%s"',
            $channel->getCode(),
            $product->getCode()
        ));
    }

    private function getProductConfiguration(): ProductConfiguration
    {
        /** @var \Synolia\SyliusAkeneoPlugin\Entity\ProductConfiguration|null $productConfiguration */
        $productConfiguration = $this->productConfigurationRepository->findOneBy([]);

        if (!$productConfiguration instanceof ProductConfiguration) {
            throw new NoProductConfigurationException('Product Configuration is not configured in BO.');
        }

        return $productConfiguration;
    }

    private function getEnabledChannelsAttributeData(
        ProductConfiguration $productConfiguration,
        ProductInterface $product,
        array $resource
    ): array {
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

            return \current($attributeValue)['data'];
        }

        throw new NoAttributeResourcesException(\sprintf('Enabled channels attribute not found for product "%s".', $product->getCode()));
    }

    private function handleByAkeneoEnabledChannelsAttribute(
        ProductConfiguration $productConfiguration,
        ProductInterface $product,
        array $resource
    ): void {
        $channels = $productConfiguration->getChannelsToEnable();
        if ($channels->count() > 0) {
            //Channel configuration section as higher priority.
            return;
        }

        $enabledChannels = $this->getEnabledChannelsAttributeData($productConfiguration, $product, $resource);

        foreach ($enabledChannels as $enabledChannel) {
            $channel = $this->channelRepository->findOneBy(['code' => $enabledChannel]);
            if (!$channel instanceof ChannelInterface) {
                $this->logger->warning(\sprintf(
                    'Channel "%s" could not be activated for product "%s" because the channel was not found in the database.',
                    $enabledChannel,
                    $product->getCode()
                ));

                continue;
            }

            $this->addProductToChannel($product, $channel);
        }
    }

    private function handleBySyliusConfiguration(ProductConfiguration $productConfiguration, ProductInterface $product): void
    {
        $channels = $productConfiguration->getChannelsToEnable();

        if (0 < $channels->count()) {
            return;
        }

        $this->addProductToChannels($product, $channels);
    }

    private function addProductToChannels(ProductInterface $product, iterable $channels): void
    {
        foreach ($channels as $channel) {
            $this->addProductToChannel($product, $channel);
        }
    }
}
