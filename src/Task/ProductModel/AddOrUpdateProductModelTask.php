<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\ProductModel;

use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
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
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfiguration;
use Synolia\SyliusAkeneoPlugin\Entity\ProductGroup;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoProductModelResourcesException;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductMediaPayload;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;
use Synolia\SyliusAkeneoPlugin\Provider\ExcludedAttributesProvider;
use Synolia\SyliusAkeneoPlugin\Repository\ProductTaxonRepository;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;
use Synolia\SyliusAkeneoPlugin\Task\Product\InsertProductImagesTask;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
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

    /** @var \Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider */
    private $taskProvider;

    /** @var \Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload */
    private $payload;

    /** @var EntityRepository */
    private $productConfigurationRepository;

    /** @var LoggerInterface */
    private $logger;

    /** @var int */
    private $updateCount = 0;

    /** @var int */
    private $createCount = 0;

    /** @var int */
    private $groupAlreadyExistCount = 0;

    /** @var int */
    private $groupCreateCount = 0;

    /** @var string */
    private $type;

    /** @var \Synolia\SyliusAkeneoPlugin\Provider\ExcludedAttributesProvider */
    private $excludedAttributesProvider;

    public function __construct(
        EntityManagerInterface $entityManager,
        ProductFactoryInterface $productFactory,
        ProductRepositoryInterface $productRepository,
        ProductTaxonRepository $productTaxonAkeneoRepository,
        TaxonRepositoryInterface $taxonRepository,
        EntityRepository $productAttributeRepository,
        EntityRepository $productConfigurationRepository,
        EntityRepository $productTranslationRepository,
        EntityRepository $productGroupRepository,
        FactoryInterface $productAttributeValueFactory,
        FactoryInterface $productTaxonFactory,
        SlugGeneratorInterface $slugGenerator,
        AkeneoTaskProvider $taskProvider,
        LoggerInterface $akeneoLogger,
        ExcludedAttributesProvider $excludedAttributesProvider
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
        $this->taskProvider = $taskProvider;
        $this->logger = $akeneoLogger;
        $this->excludedAttributesProvider = $excludedAttributesProvider;
        $this->productConfigurationRepository = $productConfigurationRepository;
    }

    /**
     * @param ProductModelPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $this->logger->debug(self::class);
        $this->type = $payload->getType();
        $this->logger->notice(Messages::createOrUpdate($this->type));

        $this->payload = $payload;
        if (!$payload->getResources() instanceof ResourceCursorInterface) {
            throw new NoProductModelResourcesException('No resource found.');
        }

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

        try {
            $this->entityManager->beginTransaction();

            $this->createProductGroup($payload->getResources());

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Throwable $throwable) {
            $this->entityManager->rollback();
            $this->logger->warning($throwable->getMessage());

            throw $throwable;
        }

        $this->logger->notice(Messages::countCreateAndExist('ProductGroup', $this->groupCreateCount, $this->groupAlreadyExistCount));

        try {
            $this->entityManager->beginTransaction();
            foreach ($payload->getResources() as $resource) {
                $this->process($resource, $productsMapping, $attributesMapping);
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Throwable $throwable) {
            $this->entityManager->rollback();
            $this->logger->warning($throwable->getMessage());

            throw $throwable;
        }

        $this->logger->notice(Messages::countCreateAndUpdate($this->type, $this->createCount, $this->updateCount));

        return $payload;
    }

    private function process(array $resource, array $productsMapping, array $attributesMapping): void
    {
        if (!isset($resource['values']['name']) && $resource['parent'] === null) {
            return;
        }

        if (isset($productsMapping[$resource['code']])) {
            $this->addOrUpdate($resource, $productsMapping[$resource['code']], $attributesMapping);
            ++$this->updateCount;
            $this->logger->info(Messages::hasBeenUpdated($this->type, (string) $productsMapping[$resource['code']]));

            return;
        }

        /** @var ProductInterface $newProduct */
        $newProduct = $this->productFactory->createNew();

        $product = $this->addOrUpdate($resource, $newProduct, $attributesMapping);

        if ($product === null) {
            return;
        }

        ++$this->createCount;
        $this->logger->info(Messages::hasBeenCreated($this->type, (string) $product->getCode()));

        $this->entityManager->persist($product);
    }

    private function createProductGroup(ResourceCursorInterface $resources): void
    {
        foreach ($resources as $resource) {
            if ($resource['parent'] !== null) {
                continue;
            }
            if ($resource['code'] !== null && $this->productGroupRepository->findOneBy(['productParent' => $resource['code']]) !== null) {
                ++$this->groupAlreadyExistCount;
                $this->logger->info(Messages::hasBeenAlreadyExist('ProductGroup', (string) $resource['code']));

                continue;
            }
            $productGroup = new ProductGroup();
            $productGroup->setProductParent($resource['code']);
            $this->entityManager->persist($productGroup);

            ++$this->groupCreateCount;
            $this->logger->info(Messages::hasBeenCreated('ProductGroup', (string) $resource['code']));
        }
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
        $this->updateImages($resource, $product);

        $product->setCode($resource['code']);

        $this->updateSlug($product, $resource);

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
            //Do not import attributes that must not be used as attribute in Sylius
            if (\in_array($attribute, $this->excludedAttributesProvider->getExcludedAttributes(), true)) {
                continue;
            }

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
        /** @var ProductConfiguration $configuration */
        $configuration = $this->productConfigurationRepository->findOneBy([]);
        if ($product->getId() !== null && $configuration !== null && $configuration->getRegenerateUrlRewrites() === false) {
            return;
        }

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

    private function updateImages(array $resource, ProductInterface $product): void
    {
        $productMediaPayload = new ProductMediaPayload($this->payload->getAkeneoPimClient());
        $productMediaPayload
            ->setProduct($product)
            ->setAttributes($resource['values'])
        ;
        $imageTask = $this->taskProvider->get(InsertProductImagesTask::class);
        $imageTask->__invoke($productMediaPayload);
    }
}
