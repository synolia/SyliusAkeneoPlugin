<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Category;

use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\Taxon;
use Sylius\Component\Taxonomy\Factory\TaxonFactoryInterface;
use Sylius\Component\Taxonomy\Model\TaxonInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoCategoryResourcesException;
use Synolia\SyliusAkeneoPlugin\Model\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Repository\ProductRepository;
use Synolia\SyliusAkeneoPlugin\Repository\TaxonRepository;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class CreateUpdateDeleteEntityTask implements AkeneoTaskInterface
{
    /** @var \Sylius\Component\Taxonomy\Factory\TaxonFactoryInterface */
    private $taxonFactory;

    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $entityManager;

    /** @var \Synolia\SyliusAkeneoPlugin\Repository\TaxonRepository */
    private $taxonRepository;

    /** @var \Synolia\SyliusAkeneoPlugin\Repository\ProductRepository */
    private $productRepository;

    public function __construct(
        TaxonFactoryInterface $taxonFactory,
        EntityManagerInterface $entityManager,
        TaxonRepository $taxonRepository,
        ProductRepository $productRepository
    ) {
        $this->taxonFactory = $taxonFactory;
        $this->entityManager = $entityManager;
        $this->taxonRepository = $taxonRepository;
        $this->productRepository = $productRepository;
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

                /** @var \Sylius\Component\Core\Model\TaxonInterface $taxon */
                $taxon = $this->getOrCreateEntity($resource['code']);
                $taxons[$resource['code']] = $taxon;

                if (null !== $resource['parent']) {
                    $parent = $taxons[$resource['parent']];

                    if (!$parent instanceof Taxon) {
                        continue;
                    }

                    $taxon->setParent($parent);
                }

                foreach ($resource['labels'] as $locale => $label) {
                    $taxon->setCurrentLocale($locale);
                    $taxon->setFallbackLocale($locale);
                    $taxon->setName($resource['labels'][$locale]);
                    $taxon->setName($label);
                    $taxon->setSlug($resource['code']);
                }
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

    private function getOrCreateEntity(string $code): \Sylius\Component\Core\Model\TaxonInterface
    {
        /** @var \Sylius\Component\Core\Model\TaxonInterface $taxon */
        $taxon = $this->entityManager->getRepository(Taxon::class)->findOneBy(['code' => $code]);

        if (!$taxon instanceof \Sylius\Component\Core\Model\TaxonInterface) {
            /** @var TaxonInterface $taxon */
            $taxon = $this->taxonFactory->createNew();
            $taxon->setCode($code);
            $this->entityManager->persist($taxon);
        }

        return $taxon;
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
            $taxon = $this->entityManager->getPartialReference(Taxon::class, $taxonId);
            if (!$taxon instanceof TaxonInterface) {
                continue;
            }
            $this->entityManager->remove($taxon);
        }
    }
}
