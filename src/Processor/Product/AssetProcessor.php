<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\Product;

use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Synolia\SyliusAkeneoPlugin\Checker\EditionCheckerInterface;
use Synolia\SyliusAkeneoPlugin\Repository\AssetRepository;

final class AssetProcessor implements AssetProcessorInterface
{
    public static function getDefaultPriority(): int
    {
        return 850;
    }

    public function __construct(
        private EditionCheckerInterface $editionChecker,
        private AssetRepository $assetRepository,
        private LoggerInterface $akeneoLogger,
    ) {
    }

    public function process(ProductInterface $product, array $resource): void
    {
        /*
         * I need to clean the product-assets association to clean removed product asset from akeneo but
         * I couldn't clean assets using $product->getAssets()->clear() before re-importing them as the unit of work still
         * thinks that the assets are to be deleted and I didn't want to use the entity manager flush as we don't want
         * to import an empty product.
         */
        $this->assetRepository->cleanAssetsForProduct($product);
        $this->akeneoLogger->info('Dissociated assets for product ' . $product->getCode());
    }

    public function support(ProductInterface $product, array $resource): bool
    {
        $isEnterprise = $this->editionChecker->isEnterprise() || $this->editionChecker->isSerenityEdition();

        if (!$isEnterprise) {
            return false;
        }

        if (!\method_exists($product, 'getAssets')) {
            return false;
        }

        return true;
    }
}
