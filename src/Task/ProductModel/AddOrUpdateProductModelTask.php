<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\ProductModel;

use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTaxonInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Product\Factory\ProductFactoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Taxonomy\Repository\TaxonRepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ProductGroup;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoProductModelResourcesException;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductMediaPayload;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductResourcePayload;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;
use Synolia\SyliusAkeneoPlugin\Repository\ProductTaxonRepository;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;
use Synolia\SyliusAkeneoPlugin\Task\Product\AddAttributesToProductTask;
use Synolia\SyliusAkeneoPlugin\Task\Product\InsertProductImagesTask;

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

    /** @var FactoryInterface */
    private $productTaxonFactory;

    /** @var TaxonRepositoryInterface */
    private $taxonRepository;

    /** @var ProductTaxonRepository */
    private $productTaxonRepository;

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

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Repository\ProductGroupRepository $productGroupRepository
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ProductFactoryInterface $productFactory,
        ProductRepositoryInterface $productRepository,
        ProductTaxonRepository $productTaxonAkeneoRepository,
        TaxonRepositoryInterface $taxonRepository,
        EntityRepository $productGroupRepository,
        FactoryInterface $productTaxonFactory,
        AkeneoTaskProvider $taskProvider,
        LoggerInterface $akeneoLogger
    ) {
        $this->entityManager = $entityManager;
        $this->productFactory = $productFactory;
        $this->productTaxonFactory = $productTaxonFactory;
        $this->productRepository = $productRepository;
        $this->productTaxonRepository = $productTaxonAkeneoRepository;
        $this->productGroupRepository = $productGroupRepository;
        $this->taxonRepository = $taxonRepository;
        $this->taskProvider = $taskProvider;
        $this->logger = $akeneoLogger;
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

        try {
            $this->entityManager->beginTransaction();
            foreach ($payload->getResources() as $resource) {
                $this->process($resource, $productsMapping);
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

    private function process(array $resource, array $productsMapping): void
    {
        if (!isset($resource['values']['name']) && $resource['parent'] === null) {
            return;
        }

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

    private function addOrUpdate(array $resource, ProductInterface $product): ?ProductInterface
    {
        $payloadProductGroup = $this->payload->getAkeneoPimClient()->getFamilyVariantApi()->get($resource['family'], $resource['family_variant']);
        $numberOfVariationAxis = isset($payloadProductGroup['variant_attribute_sets']) ? \count($payloadProductGroup['variant_attribute_sets']) : 0;

        if (null === $resource['parent'] && $numberOfVariationAxis > self::ONE_VARIATION_AXIS) {
            return null;
        }

        $productGroup = $this->productGroupRepository->findOneBy(['productParent' => $resource['parent']]);

        if ($productGroup instanceof ProductGroup && $this->productGroupRepository->isProductInProductGroup($product, $productGroup) === 0) {
            $productGroup->addProduct($product);
        }

        $productTaxonIds = [];
        if ($product->getId() !== null) {
            $productTaxonIds = array_map(function ($productTaxonIds) {
                return $productTaxonIds['id'];
            }, $this->productTaxonRepository->getProductTaxonIds($product));
        }

        $productTaxons = $this->updateTaxon($resource, $product);
        $this->removeUnusedProductTaxons($productTaxonIds, $productTaxons);
        $this->updateAttributes($resource, $product);
        $this->updateImages($resource, $product);

        $product->setCode($resource['code']);

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

    private function updateAttributes(array $resource, ProductInterface $product): void
    {
        $productResourcePayload = new ProductResourcePayload($this->payload->getAkeneoPimClient());
        $productResourcePayload
            ->setProduct($product)
            ->setResource($resource)
        ;
        $addAttributesToProductTask = $this->taskProvider->get(AddAttributesToProductTask::class);
        $addAttributesToProductTask->__invoke($productResourcePayload);
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
