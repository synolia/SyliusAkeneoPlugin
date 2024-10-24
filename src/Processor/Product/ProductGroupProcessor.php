<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\Product;

use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ProductGroup;
use Synolia\SyliusAkeneoPlugin\Repository\ProductGroupRepository;

final class ProductGroupProcessor implements ProductGroupProcessorInterface
{
    public function __construct(
        private ProductGroupRepository $productGroupRepository,
        private LoggerInterface $akeneoLogger,
    ) {
    }

    public static function getDefaultPriority(): int
    {
        return 400;
    }

    public function process(ProductInterface $product, array $resource): void
    {
        $productGroup = $this->productGroupRepository->findOneBy(['model' => $resource['parent']]);

        if ($productGroup instanceof ProductGroup && 0 === $this->productGroupRepository->isProductInProductGroup($product, $productGroup)) {
            $productGroup->addProduct($product);

            $this->akeneoLogger->info('Added product to group', [
                'product_code' => $product->getCode(),
                'product_group_id' => $productGroup->getId(),
                'family' => $productGroup->getFamily(),
            ]);
        }
    }

    public function support(ProductInterface $product, array $resource): bool
    {
        return \array_key_exists('parent', $resource);
    }
}
