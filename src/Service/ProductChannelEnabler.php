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

final class ProductChannelEnabler
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
            $enabledChannels = $this->getEnabledChannelsAttributeData($product, $resource);

            $this->entityManager->beginTransaction();

            //Disable the product for all channels
            $product->getChannels()->clear();

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

                $product->addChannel($channel);
                $this->logger->info(\sprintf(
                    'Enabled channel "%s" for product "%s"',
                    $channel->getCode(),
                    $product->getCode()
                ));
            }
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Throwable $throwable) {
            if ($this->entityManager->getConnection()->isTransactionActive()) {
                $this->entityManager->rollback();
            }

            throw $throwable;
        }
    }

    public function getEnabledChannelsAttributeData(ProductInterface $product, array $resource): array
    {
        /** @var \Synolia\SyliusAkeneoPlugin\Entity\ProductConfiguration|null $productConfiguration */
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

            return \current($attributeValue)['data'];
        }

        throw new NoAttributeResourcesException(\sprintf('Enabled channels attribute not found for product "%s".', $product->getCode()));
    }
}
