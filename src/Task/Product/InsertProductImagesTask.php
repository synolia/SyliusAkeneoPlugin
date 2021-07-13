<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Product;

use Sylius\Component\Core\Model\ProductInterface;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductMediaPayload;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class InsertProductImagesTask extends AbstractInsertProductImageTask implements AkeneoTaskInterface
{
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        try {
            if (!$payload instanceof ProductMediaPayload) {
                throw new \LogicException('Wrong payload provided.');
            }

            /** @var \Sylius\Component\Core\Model\ProductInterface $product */
            $product = $payload->getProduct();

            if (!$product instanceof ProductInterface) {
                return $payload;
            }

            $this->cleanImages($product);

            $imageAttributes = $payload->getProductConfiguration()->getAkeneoImageAttributes();
            if (null === $imageAttributes) {
                $this->logger->warning(Messages::noConfigurationSet('at least one Akeneo image attribute', 'Import image'));

                return $payload;
            }

            $this->addImage($payload, $product, $imageAttributes);
        } catch (\Throwable $throwable) {
            $this->logger->warning($throwable->getMessage());
        }

        return $payload;
    }
}
