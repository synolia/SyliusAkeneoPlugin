<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\Product;

use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Taxonomy\Repository\TaxonRepositoryInterface;

final class MainTaxonProcessor implements MainTaxonProcessorInterface
{
    public static function getDefaultPriority(): int
    {
        return 700;
    }

    public function __construct(private TaxonRepositoryInterface $taxonRepository)
    {
    }

    public function process(ProductInterface $product, array $resource): void
    {
        if (isset($resource['categories'][0])) {
            $taxon = $this->taxonRepository->findOneBy(['code' => $resource['categories'][0]]);
            if ($taxon instanceof TaxonInterface) {
                $product->setMainTaxon($taxon);
            }
        }
    }

    public function support(ProductInterface $product, array $resource): bool
    {
        return \array_key_exists('categories', $resource);
    }
}
