<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Category;

use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\Taxon;
use Sylius\Component\Taxonomy\Model\TaxonInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoCategoryResourcesException;
use Synolia\SyliusAkeneoPlugin\Model\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Repository\ProductRepository;
use Synolia\SyliusAkeneoPlugin\Repository\TaxonRepository;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class DeleteEntityTask implements AkeneoTaskInterface
{
    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $entityManager;

    /** @var \Synolia\SyliusAkeneoPlugin\Repository\TaxonRepository */
    private $taxonRepository;

    /** @var \Synolia\SyliusAkeneoPlugin\Repository\ProductRepository */
    private $productRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        ProductRepository $productAkeneoRepository,
        TaxonRepository $taxonAkeneoRepository
    ) {
        $this->entityManager = $entityManager;
        $this->productRepository = $productAkeneoRepository;
        $this->taxonRepository = $taxonAkeneoRepository;
    }

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Payload\Category\CategoryPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        if (!$payload->getResources() instanceof ResourceCursorInterface) {
            throw new NoCategoryResourcesException('No resource found.');
        }

        /** To be used for categories removal */
        $codes = [];

        try {
            $this->entityManager->beginTransaction();

            foreach ($payload->getResources() as $resource) {
                $codes[] = $resource['code'];
            }

            $this->removeUnusedCategories($codes);

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Throwable $throwable) {
            $this->entityManager->rollback();

            throw $throwable;
        }

        return $payload;
    }

    private function removeUnusedCategories(array $codes): void
    {
        /** @var array $taxonIdsArray */
        $taxonIdsArray = $this->taxonRepository->getMissingCategoriesIds($codes);

        /** @var array $taxonIds */
        $taxonIds = \array_map(function (array $data) {
            return $data['id'];
        }, $taxonIdsArray);

        //unset main taxon from products
        $products = $this->productRepository->findProductsUsingCategories($taxonIds);

        /** @var Product $product */
        foreach ($products as $product) {
            $product->setMainTaxon(null);
        }

        foreach ($taxonIdsArray as $taxonId) {
            /** @var TaxonInterface $taxon */
            $taxon = $this->entityManager->getReference(Taxon::class, $taxonId);
            if (!$taxon instanceof TaxonInterface) {
                continue;
            }
            $this->entityManager->remove($taxon);
        }
    }
}
