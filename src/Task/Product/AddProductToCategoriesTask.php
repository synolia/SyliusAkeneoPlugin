<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Product;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\ProductTaxonInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductCategoriesPayload;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class AddProductToCategoriesTask implements AkeneoTaskInterface
{
    private EntityManagerInterface $entityManager;

    private RepositoryInterface $taxonRepository;

    private RepositoryInterface $productTaxonRepository;

    private FactoryInterface $productTaxonFactory;

    public function __construct(
        EntityManagerInterface $entityManager,
        RepositoryInterface $taxonRepository,
        RepositoryInterface $productTaxonRepository,
        FactoryInterface $productTaxonFactory
    ) {
        $this->entityManager = $entityManager;
        $this->taxonRepository = $taxonRepository;
        $this->productTaxonRepository = $productTaxonRepository;
        $this->productTaxonFactory = $productTaxonFactory;
    }

    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        if (!$payload instanceof ProductCategoriesPayload) {
            return $payload;
        }

        foreach ($payload->getCategories() as $category) {
            $taxon = $this->taxonRepository->findOneBy(['code' => $category]);
            if (!$taxon instanceof TaxonInterface) {
                continue;
            }
            /** @var ProductTaxonInterface $productTaxon */
            $productTaxon = $this->productTaxonRepository->findOneBy(['product' => $payload->getProduct(), 'taxon' => $taxon]);

            if (!$productTaxon instanceof ProductTaxonInterface) {
                /** @var ProductTaxonInterface $productTaxon */
                $productTaxon = $this->productTaxonFactory->createNew();
                $productTaxon->setProduct($payload->getProduct());
                $productTaxon->setTaxon($taxon);
                $this->entityManager->persist($productTaxon);
            }

            $payload->getProduct()->addProductTaxon($productTaxon);
        }

        return $payload;
    }
}
