<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\Product;

use Doctrine\Common\Collections\Collection;
use Sylius\Component\Core\Model\ProductInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfigurationAkeneoImageAttribute;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Processor\AbstractImageProcessor;

final class ImagesProcessor extends AbstractImageProcessor implements ImagesProcessorInterface
{
    public function process(ProductInterface $product, array $resource): void
    {
        try {
            /** @var Collection|ProductConfigurationAkeneoImageAttribute[] $imageAttributes */
            $imageAttributes = $this->getProductConfiguration()->getAkeneoImageAttributes() ?? [];

            $this->cleanImages($product);
            $this->addImage($product, $resource['values'], $imageAttributes);
        } catch (\Throwable $throwable) {
            $this->akeneoLogger->warning($throwable->getMessage());
        }
    }

    public function support(ProductInterface $product, array $resource): bool
    {
        try {
            $imageAttributes = $this->getProductConfiguration()->getAkeneoImageAttributes();

            if (!$imageAttributes instanceof Collection || 0 === \count($imageAttributes)) {
                $this->akeneoLogger->debug(Messages::noConfigurationSet('at least one Akeneo image attribute', 'Import image'));

                return false;
            }

            return true;
        } catch (\Throwable $throwable) {
            $this->akeneoLogger->warning($throwable->getMessage());

            return false;
        }
    }
}
