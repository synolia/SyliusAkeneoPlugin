<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTaxonInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Taxonomy\Repository\TaxonRepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Repository\ProductTaxonRepository;

final class TaxonManager implements TaxonManagerInterface
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ProductTaxonRepository */
    private $productTaxonAkeneoRepository;

    /** @var TaxonRepositoryInterface */
    private $taxonRepository;

    /** @var FactoryInterface */
    private $productTaxonFactory;

    public function __construct(
        EntityManagerInterface $entityManager,
        ProductTaxonRepository $productTaxonAkeneoRepository,
        TaxonRepositoryInterface $taxonRepository,
        FactoryInterface $productTaxonFactory
    ) {
        $this->productTaxonAkeneoRepository = $productTaxonAkeneoRepository;
        $this->taxonRepository = $taxonRepository;
        $this->productTaxonFactory = $productTaxonFactory;
        $this->entityManager = $entityManager;
    }

    public function updateTaxon(array $resource, ProductInterface $product): array
    {
        $productTaxons = [];
        $checkProductTaxons = $this->productTaxonAkeneoRepository->findBy(['product' => $product]);
        foreach ($resource['categories'] as $category) {
            /** @var ProductTaxonInterface $productTaxon */
            $productTaxon = $this->productTaxonFactory->createNew();
            $productTaxon->setPosition(0);
            $productTaxon->setProduct($product);
            $taxon = $this->taxonRepository->findOneBy(['code' => $category]);
            if (!$taxon instanceof TaxonInterface) {
                continue;
            }

            $productTaxon->setTaxon($taxon);

            foreach ($this->entityManager->getUnitOfWork()->getScheduledEntityInsertions() as $entityInsertion) {
                if (!$entityInsertion instanceof ProductTaxonInterface) {
                    continue;
                }
                if ($entityInsertion->getProduct() === $product && $entityInsertion->getTaxon() === $taxon) {
                    continue 2;
                }
            }

            /** @var ProductTaxonInterface $checkProductTaxon */
            foreach ($checkProductTaxons as $checkProductTaxon) {
                if ($productTaxon->getTaxon() === $checkProductTaxon->getTaxon()
                    && $productTaxon->getProduct() === $checkProductTaxon->getProduct()
                ) {
                    $productTaxons[] = $checkProductTaxon->getId();

                    continue 2;
                }
            }

            $this->entityManager->persist($productTaxon);
        }

        return $productTaxons;
    }

    public function getProductTaxonIds(ProductInterface $product): array
    {
        $productTaxonIds = [];
        if ($product->getId() !== null) {
            $productTaxonIds = array_map(static function ($productTaxonIds) {
                return $productTaxonIds['id'];
            }, $this->productTaxonAkeneoRepository->getProductTaxonIds($product));
        }

        return $productTaxonIds;
    }

    public function setMainTaxon(array $resource, ProductInterface $product): void
    {
        if (isset($resource['categories'][0])) {
            $taxon = $this->taxonRepository->findOneBy(['code' => $resource['categories'][0]]);
            if ($taxon instanceof TaxonInterface) {
                $product->setMainTaxon($taxon);
            }
        }
    }

    public function removeUnusedProductTaxons(array $productTaxonIds, array $productTaxons): void
    {
        $diffs = array_diff($productTaxonIds, $productTaxons);
        if (count($diffs) > 0) {
            foreach ($diffs as $diff) {
                $this->productTaxonAkeneoRepository->removeProductTaxonById($diff);
            }
        }
    }
}
