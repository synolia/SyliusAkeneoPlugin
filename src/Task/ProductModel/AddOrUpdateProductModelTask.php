<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\ProductModel;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTranslationInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Product\Factory\ProductFactoryInterface;
use Sylius\Component\Product\Generator\SlugGeneratorInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfiguration;
use Synolia\SyliusAkeneoPlugin\Entity\ProductFiltersRules;
use Synolia\SyliusAkeneoPlugin\Entity\ProductGroup;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoProductFiltersConfigurationException;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Manager\ImageManager;
use Synolia\SyliusAkeneoPlugin\Manager\TaxonManagerInterface;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductResourcePayload;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributeDataProvider;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;
use Synolia\SyliusAkeneoPlugin\Repository\ProductFiltersRulesRepository;
use Synolia\SyliusAkeneoPlugin\Retriever\FamilyRetriever;
use Synolia\SyliusAkeneoPlugin\Service\SyliusAkeneoLocaleCodeProvider;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;
use Synolia\SyliusAkeneoPlugin\Task\Product\AddAttributesToProductTask;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
final class AddOrUpdateProductModelTask implements AkeneoTaskInterface
{
    private const ONE_VARIATION_AXIS = 1;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ProductRepositoryInterface */
    private $productRepository;

    /** @var ProductFactoryInterface */
    private $productFactory;

    /** @var \Synolia\SyliusAkeneoPlugin\Repository\ProductGroupRepository */
    private $productGroupRepository;

    /** @var \Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider */
    private $taskProvider;

    /** @var \Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload */
    private $payload;

    /** @var LoggerInterface */
    private $logger;

    /** @var int */
    private $updateCount = 0;

    /** @var int */
    private $createCount = 0;

    /** @var string */
    private $type;

    /** @var FamilyRetriever */
    private $familyRetriever;

    /** @var \Synolia\SyliusAkeneoPlugin\Service\SyliusAkeneoLocaleCodeProvider */
    private $syliusAkeneoLocaleCodeProvider;

    /** @var \Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributeDataProvider */
    private $akeneoAttributeDataProvider;

    /** @var \Synolia\SyliusAkeneoPlugin\Repository\ProductFiltersRulesRepository */
    private $productFiltersRulesRepository;

    /** @var string */
    private $scope;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $productTranslationRepository;

    /** @var \Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository */
    private $productConfigurationRepository;

    /** @var \Sylius\Component\Resource\Factory\FactoryInterface */
    private $productTranslationFactory;

    /** @var \Sylius\Component\Product\Generator\SlugGeneratorInterface */
    private $productSlugGenerator;

    /** @var TaxonManagerInterface */
    private $taxonManager;

    /** @var ImageManager */
    private $imageManager;

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Repository\ProductGroupRepository $productGroupRepository
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ProductFactoryInterface $productFactory,
        ProductRepositoryInterface $productRepository,
        EntityRepository $productGroupRepository,
        AkeneoTaskProvider $taskProvider,
        FamilyRetriever $familyRetriever,
        LoggerInterface $akeneoLogger,
        SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider,
        AkeneoAttributeDataProvider $akeneoAttributeDataProvider,
        ProductFiltersRulesRepository $productFiltersRulesRepository,
        RepositoryInterface $productTranslationRepository,
        EntityRepository $productConfigurationRepository,
        FactoryInterface $productTranslationFactory,
        SlugGeneratorInterface $productSlugGenerator,
        TaxonManagerInterface $taxonManager,
        ImageManager $imageManager
    ) {
        $this->entityManager = $entityManager;
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
        $this->productGroupRepository = $productGroupRepository;
        $this->familyRetriever = $familyRetriever;
        $this->taskProvider = $taskProvider;
        $this->logger = $akeneoLogger;
        $this->syliusAkeneoLocaleCodeProvider = $syliusAkeneoLocaleCodeProvider;
        $this->akeneoAttributeDataProvider = $akeneoAttributeDataProvider;
        $this->productFiltersRulesRepository = $productFiltersRulesRepository;
        $this->productTranslationRepository = $productTranslationRepository;
        $this->productConfigurationRepository = $productConfigurationRepository;
        $this->productTranslationFactory = $productTranslationFactory;
        $this->productSlugGenerator = $productSlugGenerator;
        $this->taxonManager = $taxonManager;
        $this->imageManager = $imageManager;
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

        /** @var \Synolia\SyliusAkeneoPlugin\Entity\ProductFiltersRules $filters */
        $filters = $this->productFiltersRulesRepository->findOneBy([]);
        if (!$filters instanceof ProductFiltersRules) {
            throw new NoProductFiltersConfigurationException('Product filters must be configured before importing product attributes.');
        }
        $this->scope = $filters->getChannel();

        $productsMapping = [];
        $products = $this->productRepository->findAll();
        /** @var ProductInterface $product */
        foreach ($products as $product) {
            $productsMapping[$product->getCode()] = $product;
        }

        $processedCount = 0;
        $totalItemsCount = $this->countTotalProducts();

        $query = $this->prepareSelectQuery(ProductModelPayload::SELECT_PAGINATION_SIZE, 0);
        $query->execute();

        while ($results = $query->fetchAll()) {
            $this->batchProcess($results, $productsMapping);

            $processedCount += \count($results);
            $this->logger->info(\sprintf('Processed %d products out of %d.', $processedCount, $totalItemsCount));
            $query = $this->prepareSelectQuery(ProductModelPayload::SELECT_PAGINATION_SIZE, $processedCount);
            $query->execute();
        }

        $this->logger->notice(Messages::countCreateAndUpdate($this->type, $this->createCount, $this->updateCount));

        return $payload;
    }

    private function countTotalProducts(): int
    {
        $query = $this->entityManager->getConnection()->prepare(\sprintf(
            'SELECT count(id) FROM `%s`',
            ProductModelPayload::TEMP_AKENEO_TABLE_NAME
        ));
        $query->execute();

        return (int) \current($query->fetch());
    }

    private function prepareSelectQuery(
        int $limit = ProductPayload::SELECT_PAGINATION_SIZE,
        int $offset = 0
    ): Statement {
        $query = $this->entityManager->getConnection()->prepare(\sprintf(
            'SELECT `values`
             FROM `%s`
             LIMIT :limit
             OFFSET :offset',
            ProductModelPayload::TEMP_AKENEO_TABLE_NAME
        ));
        $query->bindValue('limit', $limit, ParameterType::INTEGER);
        $query->bindValue('offset', $offset, ParameterType::INTEGER);

        return $query;
    }

    private function process(array $resource, array $productsMapping): void
    {
        if (isset($productsMapping[$resource['code']])) {
            $this->addOrUpdate($resource, $productsMapping[$resource['code']]);
            ++$this->updateCount;
            $this->logger->info(Messages::hasBeenUpdated($this->type, (string) $productsMapping[$resource['code']]));

            return;
        }

        /** @var ProductInterface $newProduct */
        $newProduct = $this->productFactory->createNew();

        $product = $this->addOrUpdate($resource, $newProduct);

        if ($product === null) {
            return;
        }

        ++$this->createCount;
        $this->logger->info(Messages::hasBeenCreated($this->type, (string) $product->getCode()));

        $this->entityManager->persist($product);
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function addOrUpdate(array $resource, ProductInterface $product): ?ProductInterface
    {
        $familyCode = null;
        if (!isset($resource['family'])) {
            try {
                $familyCode = $this->familyRetriever->getFamilyCodeByVariantCode($resource['family_variant']);
            } catch (\LogicException $exception) {
                $this->logger->warning($exception->getMessage());

                return null;
            }
        }

        $payloadProductGroup = $this->payload->getAkeneoPimClient()->getFamilyVariantApi()->get(
            $familyCode ? $familyCode : $resource['family'],
            $resource['family_variant']
        );

        $numberOfVariationAxis = isset($payloadProductGroup['variant_attribute_sets']) ? \count($payloadProductGroup['variant_attribute_sets']) : 0;

        if (null === $resource['parent'] && $numberOfVariationAxis > self::ONE_VARIATION_AXIS) {
            return null;
        }

        $this->updateProductRequirementsForActiveLocales(
            $product,
            $familyCode ? $familyCode : $resource['family'],
            $resource
        );

        $productResourcePayload = $this->updateAttributes(
            $resource,
            $product,
            $familyCode ? $familyCode : $resource['family'],
            $this->scope,
        );
        if ($productResourcePayload->getProduct() === null) {
            return null;
        }

        $this->addProductGroup($resource, $product);
        $productTaxonIds = $this->taxonManager->getProductTaxonIds($product);
        $productTaxons = $this->taxonManager->updateTaxon($resource, $product);
        $this->taxonManager->removeUnusedProductTaxons($productTaxonIds, $productTaxons);
        $this->imageManager->updateImages($resource, $product);
        $product->setCode($resource['code']);
        $this->taxonManager->setMainTaxon($resource, $product);

        return $product;
    }

    private function updateProductRequirementsForActiveLocales(
        ProductInterface $product,
        string $familyCode,
        array $resource
    ): void {
        $missingNameTranslationCount = 0;
        $familyResource = $this->payload->getAkeneoPimClient()->getFamilyApi()->get($familyCode);
        foreach ($this->syliusAkeneoLocaleCodeProvider->getUsedLocalesOnBothPlatforms() as $usedLocalesOnBothPlatform) {
            $productName = $this->akeneoAttributeDataProvider->getData(
                $familyResource['attribute_as_label'],
                $resource['values'][$familyResource['attribute_as_label']],
                $usedLocalesOnBothPlatform,
                $this->scope
            );

            if (null === $productName) {
                $productName = \sprintf('[%s]', $product->getCode());
                ++$missingNameTranslationCount;
            }

            $productTranslation = $this->setProductTranslation($product, $usedLocalesOnBothPlatform, $productName);

            /** @var ProductConfiguration $configuration */
            $configuration = $this->productConfigurationRepository->findOneBy([]);
            if ($product->getId() !== null &&
                $configuration !== null &&
                $productTranslation->getSlug() !== null &&
                $configuration->getRegenerateUrlRewrites() === false) {
                // no regenerate slug if config disable it

                continue;
            }

            if ($missingNameTranslationCount > 0) {
                //Multiple product has the same name
                $productTranslation->setSlug(\sprintf(
                    '%s-%s-%d',
                    $resource['code'],
                    $this->productSlugGenerator->generate($productName),
                    $missingNameTranslationCount
                ));

                continue;
            }

            //Multiple product has the same name
            $productTranslation->setSlug(\sprintf(
                '%s-%s',
                $resource['code'],
                $this->productSlugGenerator->generate($productName)
            ));
        }
    }

    private function addProductGroup(array $resource, ProductInterface $product): void
    {
        $productGroup = $this->productGroupRepository->findOneBy(['productParent' => $resource['parent']]);

        if ($productGroup instanceof ProductGroup && $this->productGroupRepository->isProductInProductGroup($product, $productGroup) === 0) {
            $productGroup->addProduct($product);
        }
    }

    private function updateAttributes(
        array $resource,
        ProductInterface $product,
        string $familyCode,
        string $scope
    ): ProductResourcePayload {
        $familyResource = $this->payload->getAkeneoPimClient()->getFamilyApi()->get($familyCode);

        $productResourcePayload = new ProductResourcePayload($this->payload->getAkeneoPimClient());
        $productResourcePayload
            ->setProduct($product)
            ->setResource($resource)
            ->setFamily($familyResource)
            ->setScope($scope)
        ;

        $addAttributesToProductTask = $this->taskProvider->get(AddAttributesToProductTask::class);
        $addAttributesToProductTask->__invoke($productResourcePayload);

        return $productResourcePayload;
    }

    private function setProductTranslation(ProductInterface $product, string $usedLocalesOnBothPlatform, ?string $productName): ProductTranslationInterface
    {
        $productTranslation = $this->productTranslationRepository->findOneBy([
            'translatable' => $product,
            'locale' => $usedLocalesOnBothPlatform,
        ]);

        if (!$productTranslation instanceof ProductTranslationInterface) {
            /** @var ProductTranslationInterface $productTranslation */
            $productTranslation = $this->productTranslationFactory->createNew();
            $productTranslation->setLocale($usedLocalesOnBothPlatform);
            $product->addTranslation($productTranslation);
        }

        $productTranslation->setName($productName);

        return $productTranslation;
    }

    private function batchProcess(array $results, array $productsMapping): void
    {
        foreach ($results as $result) {
            $resource = \json_decode($result['values'], true);

            try {
                $this->entityManager->beginTransaction();
                $this->process($resource, $productsMapping);

                $this->entityManager->flush();
                $this->entityManager->commit();

                \gc_collect_cycles();
            } catch (\Throwable $throwable) {
                $this->entityManager->rollback();
                $this->logger->warning($throwable->getMessage());
            }
        }
    }
}
