<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\ProductModel;

use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Attribute\Model\AttributeValueInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTaxonInterface;
use Sylius\Component\Core\Model\ProductTranslationInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Product\Factory\ProductFactoryInterface;
use Sylius\Component\Product\Generator\SlugGeneratorInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Taxonomy\Repository\TaxonRepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ProductGroup;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoProductModelResourcesException;
use Synolia\SyliusAkeneoPlugin\Model\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;
use Synolia\SyliusAkeneoPlugin\Repository\ProductTaxonRepository;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class AddOrUpdateProductModelTask implements AkeneoTaskInterface
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ProductRepositoryInterface */
    private $productRepository;

    /** @var ProductFactoryInterface */
    private $productFactory;

    /** @var FactoryInterface */
    private $productAttributeValueFactory;

    /** @var FactoryInterface */
    private $productTaxonFactory;

    /** @var TaxonRepositoryInterface */
    private $taxonRepository;

    /** @var EntityRepository */
    private $productAttributeRepository;

    /** @var SlugGeneratorInterface */
    private $slugGenerator;

    /** @var EntityRepository */
    private $productTranslationRepository;

    /** @var ProductTaxonRepository */
    private $productTaxonRepository;

    /** @var EntityRepository */
    private $productGroupRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        ProductFactoryInterface $productFactory,
        ProductRepositoryInterface $productRepository,
        ProductTaxonRepository $productTaxonAkeneoRepository,
        TaxonRepositoryInterface $taxonRepository,
        EntityRepository $productAttributeRepository,
        EntityRepository $productTranslationRepository,
        EntityRepository $productGroupRepository,
        FactoryInterface $productAttributeValueFactory,
        FactoryInterface $productTaxonFactory,
        SlugGeneratorInterface $slugGenerator
    ) {
        $this->entityManager = $entityManager;
        $this->productFactory = $productFactory;
        $this->productAttributeValueFactory = $productAttributeValueFactory;
        $this->productTaxonFactory = $productTaxonFactory;
        $this->productRepository = $productRepository;
        $this->productTaxonRepository = $productTaxonAkeneoRepository;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->productTranslationRepository = $productTranslationRepository;
        $this->productGroupRepository = $productGroupRepository;
        $this->taxonRepository = $taxonRepository;
        $this->slugGenerator = $slugGenerator;
    }

    /**
     * @param ProductModelPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        if (!$payload->getResources() instanceof ResourceCursorInterface) {
            throw new NoProductModelResourcesException('No resource found.');
        }

        try {
            $productsMapping = [];
            $products = $this->productRepository->findAll();
            /** @var ProductInterface $product */
            foreach ($products as $product) {
                $productsMapping[$product->getCode()] = $product;
            }

            $attributesMapping = [];
            $attributes = $this->productAttributeRepository->findAll();
            /** @var AttributeInterface $attribute */
            foreach ($attributes as $attribute) {
                $attributesMapping[$attribute->getName()] = $attribute;
            }

            $this->createProductGroup($payload->getResources());
            $this->entityManager->beginTransaction();
            foreach ($payload->getResources() as $resource) {
                $this->process($resource, $productsMapping, $attributesMapping);
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Throwable $throwable) {
            $this->entityManager->rollback();

            throw $throwable;
        }

        return $payload;
    }

    private function process(array $resource, array $productsMapping, array $attributesMapping): void
    {
        if (!isset($resource['values']['name']) && $resource['parent'] === null) {
            return;
        }

        if (isset($productsMapping[$resource['code']])) {
            $this->addOrUpdate($resource, $productsMapping[$resource['code']], $attributesMapping);

            return;
        }
        /** @var ProductInterface $newProduct */
        $newProduct = $this->productFactory->createNew();

        $product = $this->addOrUpdate($resource, $newProduct, $attributesMapping);

        if ($product === null) {
            return;
        }

        $this->entityManager->persist($product);
    }

    private function createProductGroup(ResourceCursorInterface $resources): void
    {
        $this->entityManager->beginTransaction();
        foreach ($resources as $resource) {
            if ($resource['parent'] !== null) {
                continue;
            }
            if ($resource['code'] !== null && $this->productGroupRepository->findOneBy(['productParent' => $resource['code']]) !== null) {
                continue;
            }
            $productGroup = new ProductGroup();
            $productGroup->setProductParent($resource['code']);
            $this->entityManager->persist($productGroup);
        }
        $this->entityManager->flush();
        $this->entityManager->commit();
    }

    private function addOrUpdate(array $resource, ProductInterface $product, array $attributesMapping): ?ProductInterface
    {
        $productGroup = $this->productGroupRepository->findOneBy(['productParent' => $resource['parent']]);
        if (!$productGroup instanceof ProductGroup) {
            return null;
        }

        $productGroup->addProduct($product);

        $productTaxonIds = [];
        if ($product->getId() !== null) {
            $productTaxonIds = array_map(function ($productTaxonIds) {
                return $productTaxonIds['id'];
            }, $this->productTaxonRepository->getProductTaxonIds($product));
        }

        $productTaxons = $this->updateTaxon($resource, $product);
        $this->removeUnusedProductTaxons($productTaxonIds, $productTaxons);
        $this->updateAttributes($resource, $product, $attributesMapping);

        $product->setCode($resource['code']);

        if ($product->getSlug() === null) {
            $this->updateSlug($product, $resource);
        }

        if (isset($resource['categories'][0])) {
            $taxon = $this->taxonRepository->findOneBy(['code' => $resource['categories'][0]]);
            if ($taxon instanceof TaxonInterface) {
                $product->setMainTaxon($taxon);
            }
        }

        return $product;
    }

    private function updateTaxon(array $resource, ProductInterface $product): array
    {
        $productTaxons = [];
        $checkProductTaxons = $this->productTaxonRepository->findBy(['product' => $product]);
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

    private function updateAttributes(array $resource, ProductInterface $product, array $attributesMapping): void
    {
        foreach ($product->getAttributes() as $attribute) {
            $product->removeAttribute($attribute);
        }

        foreach ($resource['values'] as $attribute => $value) {
            $setter = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $attribute)));
            if (in_array($setter, get_class_methods($product))) {
                $product->$setter($value[0]['data']);

                continue;
            }
            if (!isset($attributesMapping[$attribute])) {
                continue;
            }
            /** @var AttributeValueInterface $attributeValue */
            $attributeValue = $this->productAttributeValueFactory->createNew();
            $attributeValue->setAttribute($attributesMapping[$attribute]);
            $attributeValue->setValue($value[0]['data']);
            $product->addAttribute($attributeValue);
        }
    }

    private function updateSlug(ProductInterface $product, array $resource): void
    {
        $productTranslation = $this->productTranslationRepository->findOneBy(['name' => $resource['values']['name'][0]['data']]);

        if ($productTranslation !== null) {
            $product->setSlug($this->slugGenerator->generate(
                $resource['values']['variation_name'][0]['data'] ?? $resource['values']['erp_name'][0]['data']
            ));

            return;
        }

        foreach ($this->entityManager->getUnitOfWork()->getScheduledEntityInsertions() as $entityInsertion) {
            if (!$entityInsertion instanceof ProductTranslationInterface) {
                continue;
            }
            if ($entityInsertion->getSlug() === $this->slugGenerator->generate($resource['values']['name'][0]['data'])) {
                $product->setSlug($this->slugGenerator->generate(
                    $resource['values']['variation_name'][0]['data'] ?? $resource['values']['erp_name'][0]['data']
                ));

                return;
            }
        }

        $product->setSlug($this->slugGenerator->generate($resource['values']['name'][0]['data']));
    }

    private function removeUnusedProductTaxons(array $productTaxonIds, array $productTaxons): void
    {
        if (!empty($diffs = array_diff($productTaxonIds, $productTaxons))) {
            foreach ($diffs as $diff) {
                $this->productTaxonRepository->removeProductTaxonById($diff);
            }
        }
    }
}
