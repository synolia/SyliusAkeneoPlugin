<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductVariant;

use Sylius\Component\Core\Model\ProductVariantInterface;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Processor\AbstractImageProcessor;

final class ImagesProcessor extends AbstractImageProcessor implements ImagesProcessorInterface
{
    public function process(ProductVariantInterface $productVariant, array $resource): void
    {
        try {
            $imageAttributes = $this->getProductConfiguration()->getAkeneoImageAttributes() ?? [];

            $this->cleanImages($productVariant);
            $this->addImage($productVariant, $resource['values'], $imageAttributes);
        } catch (\Throwable $throwable) {
            $this->logger->warning($throwable->getMessage());
        }
    }

    public function support(ProductVariantInterface $product, array $resource): bool
    {
        $imageAttributes = $this->getProductConfiguration()->getAkeneoImageAttributes();

        if (null === $imageAttributes || 0 === \count($imageAttributes)) {
            $this->logger->warning(Messages::noConfigurationSet('at least one Akeneo image attribute', 'Import image'));

            return false;
        }

        return true;
    }
}
