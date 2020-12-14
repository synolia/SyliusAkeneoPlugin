<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Product;

use LogicException;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfiguration;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductVariantMediaPayload;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;
use Throwable;

final class InsertProductVariantImagesTask extends AbstractInsertProductImageTask implements AkeneoTaskInterface
{
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        try {
            if (!$payload instanceof ProductVariantMediaPayload) {
                throw new LogicException('Wrong payload provided.');
            }

            /** @var ProductVariantInterface|null $productVariant */
            $productVariant = $payload->getProductVariant();

            if (!$productVariant instanceof ProductVariantInterface) {
                return $payload;
            }

            $this->cleanImages($productVariant);

            $configuration = $this->productConfigurationRepository->findOneBy([]);

            if (!$configuration instanceof ProductConfiguration) {
                return $payload;
            }

            $this->configuration = $configuration;

            $imageAttributes = $this->configuration->getAkeneoImageAttributes();

            if ($imageAttributes === null) {
                return $payload;
            }

            $this->addImage($payload, $productVariant, $imageAttributes);
        } catch (Throwable $throwable) {
            $this->logger->warning($throwable->getMessage());
        }

        return $payload;
    }
}
